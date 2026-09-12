<?php
/**
 * Таксономия «Разделы меню» и поля терминов.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Разделы меню.
 */
class Merler_Taxonomy {

	const TAX = 'merler_section';

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );

		add_action( self::TAX . '_add_form_fields', array( __CLASS__, 'add_fields' ) );
		add_action( self::TAX . '_edit_form_fields', array( __CLASS__, 'edit_fields' ) );
		add_action( 'created_' . self::TAX, array( __CLASS__, 'save_fields' ) );
		add_action( 'edited_' . self::TAX, array( __CLASS__, 'save_fields' ) );

		add_filter( 'manage_edit-' . self::TAX . '_columns', array( __CLASS__, 'columns' ) );
		add_filter( 'manage_' . self::TAX . '_custom_column', array( __CLASS__, 'column_content' ), 10, 3 );
	}

	/**
	 * Регистрация таксономии.
	 */
	public static function register() {
		register_taxonomy(
			self::TAX,
			array( 'merler_dish' ),
			array(
				'labels'            => array(
					'name'              => __( 'Разделы меню', 'merler-menu' ),
					'singular_name'     => __( 'Раздел меню', 'merler-menu' ),
					'menu_name'         => __( 'Разделы меню', 'merler-menu' ),
					'all_items'         => __( 'Все разделы', 'merler-menu' ),
					'edit_item'         => __( 'Редактировать раздел', 'merler-menu' ),
					'add_new_item'      => __( 'Добавить раздел', 'merler-menu' ),
					'new_item_name'     => __( 'Название раздела', 'merler-menu' ),
					'parent_item'       => __( 'Родительский раздел', 'merler-menu' ),
					'parent_item_colon' => __( 'Родительский раздел:', 'merler-menu' ),
					'search_items'      => __( 'Искать разделы', 'merler-menu' ),
					'not_found'         => __( 'Разделов пока нет', 'merler-menu' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_rest'      => false,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'rewrite'           => false,
				'query_var'         => false,
				'capabilities'      => array(
					'manage_terms' => 'manage_merler_sections',
					'edit_terms'   => 'manage_merler_sections',
					'delete_terms' => 'manage_merler_sections',
					'assign_terms' => 'edit_merler_dishes',
				),
			)
		);
	}

	/**
	 * Поля при создании раздела.
	 */
	public static function add_fields() {
		?>
		<div class="form-field">
			<label for="merler_note"><?php esc_html_e( 'Короткая подпись', 'merler-menu' ); ?></label>
			<input type="text" name="merler_note" id="merler_note" value="">
			<p><?php esc_html_e( 'Выводится серым под заголовком раздела. Например: «к хинкалу».', 'merler-menu' ); ?></p>
		</div>
		<div class="form-field">
			<label for="merler_order"><?php esc_html_e( 'Порядок', 'merler-menu' ); ?></label>
			<input type="number" name="merler_order" id="merler_order" value="0" step="10">
			<p><?php esc_html_e( 'Чем меньше число, тем выше раздел в меню.', 'merler-menu' ); ?></p>
		</div>
		<div class="form-field">
			<label><input type="checkbox" name="merler_visible" value="1" checked> <?php esc_html_e( 'Показывать раздел на сайте', 'merler-menu' ); ?></label>
		</div>
		<?php
	}

	/**
	 * Поля при редактировании раздела.
	 *
	 * @param WP_Term $term Термин.
	 */
	public static function edit_fields( $term ) {
		$note    = get_term_meta( $term->term_id, 'merler_note', true );
		$order   = get_term_meta( $term->term_id, 'merler_order', true );
		$visible = get_term_meta( $term->term_id, 'merler_visible', true );
		$visible = ( '' === $visible ) ? '1' : $visible;
		?>
		<tr class="form-field">
			<th scope="row"><label for="merler_note"><?php esc_html_e( 'Короткая подпись', 'merler-menu' ); ?></label></th>
			<td>
				<input type="text" name="merler_note" id="merler_note" value="<?php echo esc_attr( $note ); ?>">
				<p class="description"><?php esc_html_e( 'Выводится серым под заголовком раздела. Например: «к хинкалу».', 'merler-menu' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="merler_order"><?php esc_html_e( 'Порядок', 'merler-menu' ); ?></label></th>
			<td>
				<input type="number" name="merler_order" id="merler_order" value="<?php echo esc_attr( $order ); ?>" step="10">
				<p class="description"><?php esc_html_e( 'Чем меньше число, тем выше раздел в меню. Порядок можно менять и перетаскиванием на странице «Порядок разделов».', 'merler-menu' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Видимость', 'merler-menu' ); ?></th>
			<td>
				<label><input type="checkbox" name="merler_visible" value="1" <?php checked( '1', $visible ); ?>> <?php esc_html_e( 'Показывать раздел на сайте', 'merler-menu' ); ?></label>
			</td>
		</tr>
		<?php
	}

	/**
	 * Сохранение полей раздела.
	 *
	 * @param int $term_id ID термина.
	 */
	public static function save_fields( $term_id ) {
		if ( ! current_user_can( 'manage_merler_sections' ) ) {
			return;
		}

		// Проверка nonce формы таксономии WordPress.
		$nonce_ok = isset( $_POST['_wpnonce_add-tag'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce_add-tag'] ) ), 'add-tag' );
		if ( ! $nonce_ok ) {
			$nonce_ok = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'update-tag_' . $term_id );
		}
		if ( ! $nonce_ok ) {
			return;
		}

		if ( isset( $_POST['merler_note'] ) ) {
			update_term_meta( $term_id, 'merler_note', sanitize_text_field( wp_unslash( $_POST['merler_note'] ) ) );
		}
		if ( isset( $_POST['merler_order'] ) ) {
			update_term_meta( $term_id, 'merler_order', (int) $_POST['merler_order'] );
		}
		update_term_meta( $term_id, 'merler_visible', isset( $_POST['merler_visible'] ) ? '1' : '0' );

		Merler_Cache::flush();
	}

	/**
	 * Колонки списка разделов.
	 *
	 * @param array $columns Колонки.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'name' === $key ) {
				$new['merler_order']   = __( 'Порядок', 'merler-menu' );
				$new['merler_visible'] = __( 'Показывать', 'merler-menu' );
			}
		}
		return $new;
	}

	/**
	 * Содержимое колонок.
	 *
	 * @param string $content Содержимое.
	 * @param string $column  Колонка.
	 * @param int    $term_id ID термина.
	 * @return string
	 */
	public static function column_content( $content, $column, $term_id ) {
		if ( 'merler_order' === $column ) {
			return esc_html( (string) (int) get_term_meta( $term_id, 'merler_order', true ) );
		}
		if ( 'merler_visible' === $column ) {
			$visible = get_term_meta( $term_id, 'merler_visible', true );
			return ( '0' === $visible ) ? esc_html__( 'скрыт', 'merler-menu' ) : esc_html__( 'да', 'merler-menu' );
		}
		return $content;
	}

	/**
	 * Разделы верхнего уровня с подразделами, отсортированные для вывода.
	 *
	 * @param bool $only_visible Только видимые.
	 * @return array Массив WP_Term верхнего уровня.
	 */
	public static function get_tree( $only_visible = true ) {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAX,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$by_parent = array();
		foreach ( $terms as $term ) {
			if ( $only_visible && '0' === get_term_meta( $term->term_id, 'merler_visible', true ) ) {
				continue;
			}
			$term->merler_order = (int) get_term_meta( $term->term_id, 'merler_order', true );
			$term->merler_note  = (string) get_term_meta( $term->term_id, 'merler_note', true );
			$term->children     = array();

			$by_parent[ $term->parent ][] = $term;
		}

		$sort = static function ( $a, $b ) {
			if ( $a->merler_order === $b->merler_order ) {
				return strcmp( $a->name, $b->name );
			}
			return $a->merler_order <=> $b->merler_order;
		};

		$roots = isset( $by_parent[0] ) ? $by_parent[0] : array();
		usort( $roots, $sort );

		foreach ( $roots as $root ) {
			if ( isset( $by_parent[ $root->term_id ] ) ) {
				$children = $by_parent[ $root->term_id ];
				usort( $children, $sort );
				$root->children = $children;
			}
		}

		return $roots;
	}
}
