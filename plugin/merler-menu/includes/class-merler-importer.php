<?php
/**
 * Импорт и экспорт меню в JSON.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Инструменты меню.
 */
class Merler_Importer {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'admin_post_merler_import', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_post_merler_import_bundled', array( __CLASS__, 'handle_import_bundled' ) );
		add_action( 'admin_post_merler_export', array( __CLASS__, 'handle_export' ) );
	}

	/**
	 * Страница «Импорт и экспорт».
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_merler_settings' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'merler-menu' ) );
		}

		$notice = isset( $_GET['merler_notice'] ) ? sanitize_key( wp_unslash( $_GET['merler_notice'] ) ) : '';
		$counts = array(
			'created' => isset( $_GET['created'] ) ? (int) $_GET['created'] : 0,
			'updated' => isset( $_GET['updated'] ) ? (int) $_GET['updated'] : 0,
			'terms'   => isset( $_GET['terms'] ) ? (int) $_GET['terms'] : 0,
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Импорт и экспорт меню', 'merler-menu' ); ?></h1>

			<?php if ( 'imported' === $notice ) : ?>
				<div class="notice notice-success"><p>
					<?php
					printf(
						/* translators: 1: created, 2: updated, 3: sections */
						esc_html__( 'Готово. Добавлено блюд: %1$d, обновлено: %2$d, разделов: %3$d.', 'merler-menu' ),
						(int) $counts['created'],
						(int) $counts['updated'],
						(int) $counts['terms']
					);
					?>
				</p></div>
			<?php elseif ( 'error' === $notice ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Файл не прочитался. Нужен JSON, выгруженный этим же плагином.', 'merler-menu' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Резервная копия', 'merler-menu' ); ?></h2>
			<p><?php esc_html_e( 'Выгрузите меню в файл перед большими правками — так его всегда можно вернуть.', 'merler-menu' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="merler_export">
				<?php wp_nonce_field( 'merler_export' ); ?>
				<?php submit_button( __( 'Скачать меню в JSON', 'merler-menu' ), 'secondary', 'submit', false ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Загрузить меню из файла', 'merler-menu' ); ?></h2>
			<p><?php esc_html_e( 'Блюда сопоставляются по адресу (slug): существующие обновляются, новые добавляются. Повторная загрузка того же файла дублей не создаёт.', 'merler-menu' ); ?></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="merler_import">
				<?php wp_nonce_field( 'merler_import' ); ?>
				<p><input type="file" name="merler_file" accept="application/json,.json" required></p>
				<p><label><input type="checkbox" name="merler_overwrite_photos" value="1"> <?php esc_html_e( 'Сбрасывать фото у обновляемых блюд', 'merler-menu' ); ?></label></p>
				<?php submit_button( __( 'Загрузить', 'merler-menu' ), 'primary', 'submit', false ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Восстановить печатное меню', 'merler-menu' ); ?></h2>
			<p><?php esc_html_e( 'Загрузит меню из файла, который идёт в комплекте с плагином: 115 блюд из печатной версии. Ваши цены и тексты, совпадающие по адресу блюда, будут перезаписаны.', 'merler-menu' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Перезаписать меню данными из печатной версии?', 'merler-menu' ) ); ?>');">
				<input type="hidden" name="action" value="merler_import_bundled">
				<?php wp_nonce_field( 'merler_import_bundled' ); ?>
				<?php submit_button( __( 'Загрузить печатное меню', 'merler-menu' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Обработка загрузки файла.
	 */
	public static function handle_import() {
		check_admin_referer( 'merler_import' );

		if ( ! current_user_can( 'manage_merler_settings' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'merler-menu' ) );
		}

		$result = false;

		if ( isset( $_FILES['merler_file'] ) && isset( $_FILES['merler_file']['tmp_name'] ) ) {
			$tmp = sanitize_text_field( wp_unslash( $_FILES['merler_file']['tmp_name'] ) );
			if ( $tmp && is_uploaded_file( $tmp ) ) {
				$reset_photos = ! empty( $_POST['merler_overwrite_photos'] );
				$result       = self::import_file( $tmp, $reset_photos );
			}
		}

		self::redirect_back( $result );
	}

	/**
	 * Импорт файла из комплекта плагина.
	 */
	public static function handle_import_bundled() {
		check_admin_referer( 'merler_import_bundled' );

		if ( ! current_user_can( 'manage_merler_settings' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'merler-menu' ) );
		}

		$result = self::import_file( MERLER_DIR . 'data/menu-data.json' );

		self::redirect_back( $result );
	}

	/**
	 * Возврат на страницу инструментов с сообщением.
	 *
	 * @param array|false $result Результат импорта.
	 */
	private static function redirect_back( $result ) {
		$url = admin_url( 'edit.php?post_type=merler_dish&page=merler-tools' );

		if ( is_array( $result ) ) {
			$url = add_query_arg(
				array(
					'merler_notice' => 'imported',
					'created'       => (int) $result['created'],
					'updated'       => (int) $result['updated'],
					'terms'         => (int) $result['terms'],
				),
				$url
			);
		} else {
			$url = add_query_arg( 'merler_notice', 'error', $url );
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Импорт меню из файла.
	 *
	 * @param string $path         Путь к JSON.
	 * @param bool   $reset_photos Сбрасывать ли фото.
	 * @return array|false
	 */
	public static function import_file( $path, $reset_photos = false ) {
		if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
			return false;
		}

		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw ) {
			return false;
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || empty( $data['sections'] ) || ! is_array( $data['sections'] ) ) {
			return false;
		}

		$stats = array(
			'created' => 0,
			'updated' => 0,
			'terms'   => 0,
		);

		foreach ( $data['sections'] as $section ) {
			$term_id = self::upsert_term( $section, 0, $stats );
			if ( ! $term_id ) {
				continue;
			}

			if ( ! empty( $section['dishes'] ) && is_array( $section['dishes'] ) ) {
				foreach ( $section['dishes'] as $dish ) {
					self::upsert_dish( $dish, $term_id, $stats, $reset_photos );
				}
			}

			if ( ! empty( $section['subsections'] ) && is_array( $section['subsections'] ) ) {
				foreach ( $section['subsections'] as $sub ) {
					$sub_id = self::upsert_term( $sub, $term_id, $stats );
					if ( ! $sub_id || empty( $sub['dishes'] ) || ! is_array( $sub['dishes'] ) ) {
						continue;
					}
					foreach ( $sub['dishes'] as $dish ) {
						self::upsert_dish( $dish, $sub_id, $stats, $reset_photos );
					}
				}
			}
		}

		Merler_Cache::flush();

		return $stats;
	}

	/**
	 * Создать или обновить раздел.
	 *
	 * @param array $section Данные раздела.
	 * @param int   $parent  Родитель.
	 * @param array $stats   Счётчики.
	 * @return int|false
	 */
	private static function upsert_term( $section, $parent, &$stats ) {
		if ( empty( $section['name'] ) ) {
			return false;
		}

		$name = sanitize_text_field( $section['name'] );
		$slug = ! empty( $section['slug'] ) ? sanitize_title( $section['slug'] ) : sanitize_title( $name );

		$term = get_term_by( 'slug', $slug, Merler_Taxonomy::TAX );

		if ( $term ) {
			$term_id = (int) $term->term_id;
			wp_update_term(
				$term_id,
				Merler_Taxonomy::TAX,
				array(
					'name'   => $name,
					'parent' => (int) $parent,
				)
			);
		} else {
			$created = wp_insert_term(
				$name,
				Merler_Taxonomy::TAX,
				array(
					'slug'   => $slug,
					'parent' => (int) $parent,
				)
			);
			if ( is_wp_error( $created ) ) {
				return false;
			}
			$term_id = (int) $created['term_id'];
		}

		update_term_meta( $term_id, 'merler_order', isset( $section['order'] ) ? (int) $section['order'] : 0 );
		update_term_meta( $term_id, 'merler_note', isset( $section['description'] ) ? sanitize_text_field( $section['description'] ) : '' );

		$visible = get_term_meta( $term_id, 'merler_visible', true );
		if ( '' === $visible ) {
			update_term_meta( $term_id, 'merler_visible', '1' );
		}

		++$stats['terms'];

		return $term_id;
	}

	/**
	 * Создать или обновить блюдо.
	 *
	 * @param array $dish         Данные блюда.
	 * @param int   $term_id      Раздел.
	 * @param array $stats        Счётчики.
	 * @param bool  $reset_photos Сбрасывать фото.
	 */
	private static function upsert_dish( $dish, $term_id, &$stats, $reset_photos = false ) {
		if ( empty( $dish['title'] ) ) {
			return;
		}

		$title = sanitize_text_field( $dish['title'] );
		$slug  = ! empty( $dish['slug'] ) ? sanitize_title( $dish['slug'] ) : sanitize_title( $title );

		$existing = get_posts(
			array(
				'post_type'              => 'merler_dish',
				'post_status'            => 'any',
				'name'                   => $slug,
				'numberposts'            => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$postarr = array(
			'post_type'   => 'merler_dish',
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_status' => 'publish',
			'menu_order'  => isset( $dish['order'] ) ? (int) $dish['order'] : 0,
		);

		if ( ! empty( $existing ) ) {
			$post_id            = (int) $existing[0];
			$postarr['ID']      = $post_id;
			wp_update_post( $postarr );
			++$stats['updated'];

			if ( $reset_photos ) {
				delete_post_thumbnail( $post_id );
			}
		} else {
			$post_id = wp_insert_post( $postarr, true );
			if ( is_wp_error( $post_id ) ) {
				return;
			}
			++$stats['created'];
		}

		update_post_meta( $post_id, '_merler_weight', Merler_Meta::sanitize_weight( isset( $dish['weight'] ) ? $dish['weight'] : '' ) );
		update_post_meta( $post_id, '_merler_price', Merler_Meta::sanitize_price( isset( $dish['price'] ) ? $dish['price'] : 0 ) );
		update_post_meta( $post_id, '_merler_description', Merler_Meta::sanitize_description( isset( $dish['description'] ) ? $dish['description'] : '' ) );
		update_post_meta( $post_id, '_merler_badges', Merler_Meta::sanitize_badges( isset( $dish['badges'] ) ? $dish['badges'] : array() ) );
		update_post_meta( $post_id, '_merler_status', Merler_Meta::sanitize_status( isset( $dish['status'] ) ? $dish['status'] : 'in_stock' ) );

		wp_set_object_terms( $post_id, array( (int) $term_id ), Merler_Taxonomy::TAX, false );
	}

	/**
	 * Отдать файл с меню.
	 */
	public static function handle_export() {
		check_admin_referer( 'merler_export' );

		if ( ! current_user_can( 'manage_merler_settings' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'merler-menu' ) );
		}

		$json     = self::export_json();
		$filename = 'merler-menu-' . gmdate( 'Y-m-d' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Length: ' . strlen( $json ) );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Меню в виде JSON-строки.
	 *
	 * @return string
	 */
	public static function export_json() {
		$roots = Merler_Taxonomy::get_tree( false );
		$out   = array(
			'version'    => 1,
			'restaurant' => get_bloginfo( 'name' ),
			'exported'   => gmdate( 'c' ),
			'currency'   => merler_option( 'currency', '₽' ),
			'sections'   => array(),
		);

		foreach ( $roots as $root ) {
			$section = array(
				'name'        => $root->name,
				'slug'        => $root->slug,
				'order'       => (int) $root->merler_order,
				'description' => (string) $root->merler_note,
				'dishes'      => self::term_dishes( $root->term_id ),
				'subsections' => array(),
			);

			foreach ( $root->children as $child ) {
				$section['subsections'][] = array(
					'name'        => $child->name,
					'slug'        => $child->slug,
					'order'       => (int) $child->merler_order,
					'description' => (string) $child->merler_note,
					'dishes'      => self::term_dishes( $child->term_id ),
				);
			}

			$out['sections'][] = $section;
		}

		return wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Блюда раздела для экспорта.
	 *
	 * @param int $term_id ID раздела.
	 * @return array
	 */
	private static function term_dishes( $term_id ) {
		$posts = get_posts(
			array(
				'post_type'      => 'merler_dish',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy'         => Merler_Taxonomy::TAX,
						'field'            => 'term_id',
						'terms'            => (int) $term_id,
						'include_children' => false,
					),
				),
			)
		);

		$out = array();
		foreach ( $posts as $post ) {
			$dish  = merler_get_dish( $post );
			$out[] = array(
				'slug'        => $dish['slug'],
				'title'       => $dish['title'],
				'weight'      => $dish['weight'],
				'price'       => $dish['price'],
				'description' => $dish['description'],
				'badges'      => $dish['badges'],
				'status'      => $dish['status'],
				'order'       => $dish['order'],
			);
		}

		return $out;
	}
}
