<?php
/**
 * Массовые действия и дублирование блюда.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Групповые операции со списком блюд.
 */
class Merler_Bulk {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_filter( 'bulk_actions-edit-merler_dish', array( __CLASS__, 'actions' ) );
		add_filter( 'handle_bulk_actions-edit-merler_dish', array( __CLASS__, 'handle' ), 10, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'admin_post_merler_duplicate', array( __CLASS__, 'duplicate' ) );
	}

	/**
	 * Пункты выпадающего списка «Действия».
	 *
	 * @param array $actions Действия.
	 * @return array
	 */
	public static function actions( $actions ) {
		$actions['merler_in_stock']     = __( 'Поставить «В наличии»', 'merler-menu' );
		$actions['merler_soon']         = __( 'Поставить «Скоро»', 'merler-menu' );
		$actions['merler_out_of_stock'] = __( 'Поставить на стоп', 'merler-menu' );
		$actions['merler_hidden']       = __( 'Скрыть с сайта', 'merler-menu' );
		return $actions;
	}

	/**
	 * Обработка массового действия.
	 *
	 * @param string $redirect Адрес возврата.
	 * @param string $action   Действие.
	 * @param array  $post_ids Записи.
	 * @return string
	 */
	public static function handle( $redirect, $action, $post_ids ) {
		$map = array(
			'merler_in_stock'     => 'in_stock',
			'merler_soon'         => 'soon',
			'merler_out_of_stock' => 'out_of_stock',
			'merler_hidden'       => 'hidden',
		);

		if ( ! isset( $map[ $action ] ) ) {
			return $redirect;
		}

		$done = 0;
		foreach ( (array) $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			if ( ! current_user_can( 'edit_merler_dish', $post_id ) ) {
				continue;
			}
			update_post_meta( $post_id, '_merler_status', $map[ $action ] );
			++$done;
		}

		Merler_Cache::flush();

		return add_query_arg( 'merler_status_done', $done, $redirect );
	}

	/**
	 * Сообщение после массового действия.
	 */
	public static function notice() {
		if ( empty( $_GET['merler_status_done'] ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'edit-merler_dish' !== $screen->id ) {
			return;
		}

		$done = (int) $_GET['merler_status_done'];
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %d: number of dishes */
					esc_html( _n( 'Статус изменён у %d блюда.', 'Статус изменён у %d блюд.', $done, 'merler-menu' ) ),
					$done
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Дублирование блюда.
	 */
	public static function duplicate() {
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;

		check_admin_referer( 'merler_duplicate_' . $post_id );

		if ( ! $post_id || ! current_user_can( 'edit_merler_dishes' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'merler-menu' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || 'merler_dish' !== $post->post_type ) {
			wp_die( esc_html__( 'Блюдо не найдено.', 'merler-menu' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'   => 'merler_dish',
				/* translators: %s: dish title */
				'post_title'  => sprintf( __( '%s (копия)', 'merler-menu' ), $post->post_title ),
				'post_status' => 'draft',
				'menu_order'  => (int) $post->menu_order + 1,
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html__( 'Не удалось создать копию.', 'merler-menu' ) );
		}

		foreach ( array( '_merler_price', '_merler_weight', '_merler_description', '_merler_badges', '_merler_status' ) as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( '' !== $value ) {
				update_post_meta( $new_id, $key, $value );
			}
		}

		$thumb = get_post_thumbnail_id( $post_id );
		if ( $thumb ) {
			set_post_thumbnail( $new_id, $thumb );
		}

		$terms = wp_get_object_terms( $post_id, Merler_Taxonomy::TAX, array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $terms ) && $terms ) {
			wp_set_object_terms( $new_id, $terms, Merler_Taxonomy::TAX, false );
		}

		Merler_Cache::flush();

		wp_safe_redirect( admin_url( 'post.php?post=' . (int) $new_id . '&action=edit' ) );
		exit;
	}
}
