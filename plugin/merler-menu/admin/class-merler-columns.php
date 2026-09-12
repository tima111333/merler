<?php
/**
 * Список блюд: колонки, фильтр, быстрое редактирование.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Экран списка блюд.
 */
class Merler_Columns {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_filter( 'manage_merler_dish_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_merler_dish_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'manage_edit-merler_dish_sortable_columns', array( __CLASS__, 'sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_query' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'filters' ) );

		add_action( 'quick_edit_custom_box', array( __CLASS__, 'quick_edit_fields' ), 10, 2 );
		add_action( 'save_post_merler_dish', array( __CLASS__, 'save_quick_edit' ), 10, 2 );

		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
	}

	/**
	 * Колонки.
	 *
	 * @param array $columns Колонки.
	 * @return array
	 */
	public static function columns( $columns ) {
		return array(
			'cb'            => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'merler_thumb'  => __( 'Фото', 'merler-menu' ),
			'title'         => __( 'Название', 'merler-menu' ),
			'merler_section' => __( 'Раздел', 'merler-menu' ),
			'merler_weight' => __( 'Вес', 'merler-menu' ),
			'merler_price'  => __( 'Цена', 'merler-menu' ),
			'merler_status' => __( 'Статус', 'merler-menu' ),
			'merler_order'  => __( 'Порядок', 'merler-menu' ),
		);
	}

	/**
	 * Содержимое колонок.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id ID записи.
	 */
	public static function column( $column, $post_id ) {
		switch ( $column ) {
			case 'merler_thumb':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, array( 60, 60 ), array( 'class' => 'merler-thumb' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo '<span class="merler-thumb merler-thumb-empty" aria-hidden="true"></span>';
				}
				break;

			case 'merler_section':
				$terms = get_the_terms( $post_id, Merler_Taxonomy::TAX );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$names = array();
					foreach ( $terms as $term ) {
						$names[] = $term->name;
					}
					echo esc_html( implode( ', ', $names ) );
				} else {
					echo '<span class="merler-warn">' . esc_html__( 'без раздела', 'merler-menu' ) . '</span>';
				}
				break;

			case 'merler_weight':
				echo esc_html( (string) get_post_meta( $post_id, '_merler_weight', true ) );
				break;

			case 'merler_price':
				$price = (int) get_post_meta( $post_id, '_merler_price', true );
				echo $price ? esc_html( merler_price( $price ) ) : '—';
				break;

			case 'merler_status':
				$status   = merler_dish_status( $post_id );
				$statuses = merler_statuses();
				echo '<span class="merler-status merler-status-' . esc_attr( $status ) . '">' . esc_html( $statuses[ $status ] ) . '</span>';
				break;

			case 'merler_order':
				$post = get_post( $post_id );
				echo esc_html( (string) (int) $post->menu_order );
				break;
		}

		// Данные для быстрого редактирования.
		if ( 'merler_order' === $column ) {
			printf(
				'<div class="hidden merler-inline-data" id="merler-inline-%1$d" data-price="%2$s" data-weight="%3$s" data-status="%4$s"></div>',
				(int) $post_id,
				esc_attr( (string) get_post_meta( $post_id, '_merler_price', true ) ),
				esc_attr( (string) get_post_meta( $post_id, '_merler_weight', true ) ),
				esc_attr( merler_dish_status( $post_id ) )
			);
		}
	}

	/**
	 * Сортируемые колонки.
	 *
	 * @param array $columns Колонки.
	 * @return array
	 */
	public static function sortable( $columns ) {
		$columns['merler_price'] = 'merler_price';
		$columns['merler_order'] = 'menu_order';
		$columns['title']        = 'title';
		return $columns;
	}

	/**
	 * Сортировка по цене.
	 *
	 * @param WP_Query $query Запрос.
	 */
	public static function sort_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'merler_dish' !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( 'merler_price' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_merler_price' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query->set( 'orderby', 'meta_value_num' );
		}

		if ( ! $query->get( 'orderby' ) ) {
			$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		}

		$status = isset( $_GET['merler_status_filter'] ) ? sanitize_key( wp_unslash( $_GET['merler_status_filter'] ) ) : '';
		if ( $status && array_key_exists( $status, merler_statuses() ) ) {
			$meta_query = (array) $query->get( 'meta_query' );
			$meta_query[] = array(
				'key'   => '_merler_status',
				'value' => $status,
			);
			$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}
	}

	/**
	 * Фильтры над списком.
	 *
	 * @param string $post_type Тип записи.
	 */
	public static function filters( $post_type ) {
		if ( 'merler_dish' !== $post_type ) {
			return;
		}

		$current = isset( $_GET[ Merler_Taxonomy::TAX ] ) ? sanitize_text_field( wp_unslash( $_GET[ Merler_Taxonomy::TAX ] ) ) : '';

		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'Все разделы', 'merler-menu' ),
				'taxonomy'        => Merler_Taxonomy::TAX,
				'name'            => Merler_Taxonomy::TAX,
				'value_field'     => 'slug',
				'selected'        => $current,
				'hierarchical'    => true,
				'hide_empty'      => false,
				'show_count'      => true,
				'orderby'         => 'name',
			)
		);

		$status = isset( $_GET['merler_status_filter'] ) ? sanitize_key( wp_unslash( $_GET['merler_status_filter'] ) ) : '';
		echo '<select name="merler_status_filter">';
		echo '<option value="">' . esc_html__( 'Любой статус', 'merler-menu' ) . '</option>';
		foreach ( merler_statuses() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Поля быстрого редактирования.
	 *
	 * @param string $column    Колонка.
	 * @param string $post_type Тип записи.
	 */
	public static function quick_edit_fields( $column, $post_type ) {
		if ( 'merler_dish' !== $post_type || 'merler_price' !== $column ) {
			return;
		}

		wp_nonce_field( 'merler_quick_edit', 'merler_quick_edit_nonce' );
		?>
		<fieldset class="inline-edit-col-right merler-quick-edit">
			<div class="inline-edit-col">
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Цена, ₽', 'merler-menu' ); ?></span>
					<input type="number" name="merler_price" min="0" step="10" value="">
				</label>
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Вес', 'merler-menu' ); ?></span>
					<input type="text" name="merler_weight" value="">
				</label>
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Статус', 'merler-menu' ); ?></span>
					<select name="merler_status">
						<?php foreach ( merler_statuses() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Сохранение быстрого редактирования.
	 *
	 * @param int     $post_id ID записи.
	 * @param WP_Post $post    Запись.
	 */
	public static function save_quick_edit( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['merler_quick_edit_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['merler_quick_edit_nonce'] ) ), 'merler_quick_edit' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_merler_dish', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['merler_price'] ) ) {
			update_post_meta( $post_id, '_merler_price', Merler_Meta::sanitize_price( wp_unslash( $_POST['merler_price'] ) ) );
		}
		if ( isset( $_POST['merler_weight'] ) ) {
			update_post_meta( $post_id, '_merler_weight', Merler_Meta::sanitize_weight( wp_unslash( $_POST['merler_weight'] ) ) );
		}
		if ( isset( $_POST['merler_status'] ) ) {
			update_post_meta( $post_id, '_merler_status', Merler_Meta::sanitize_status( wp_unslash( $_POST['merler_status'] ) ) );
		}
	}

	/**
	 * Ссылка «Дублировать» в списке.
	 *
	 * @param array   $actions Действия.
	 * @param WP_Post $post    Запись.
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( 'merler_dish' !== $post->post_type || ! current_user_can( 'edit_merler_dishes' ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=merler_duplicate&post=' . (int) $post->ID ),
			'merler_duplicate_' . (int) $post->ID
		);

		$actions['merler_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Дублировать', 'merler-menu' ) . '</a>';

		return $actions;
	}
}
