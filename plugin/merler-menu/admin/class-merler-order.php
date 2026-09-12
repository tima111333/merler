<?php
/**
 * Страница «Порядок блюд»: перетаскивание блюд и разделов.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Порядок вывода.
 */
class Merler_Order {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'wp_ajax_merler_save_order', array( __CLASS__, 'save_order' ) );
	}

	/**
	 * Страница.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'edit_merler_dishes' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'merler-menu' ) );
		}

		$roots = Merler_Taxonomy::get_tree( false );
		?>
		<div class="wrap merler-order-page">
			<h1><?php esc_html_e( 'Порядок блюд и разделов', 'merler-menu' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Перетащите мышкой разделы или блюда внутри раздела — порядок сохранится сам. Так же они встанут на сайте.', 'merler-menu' ); ?></p>

			<div id="merler-order-status" class="merler-order-status" role="status" aria-live="polite"></div>

			<?php if ( empty( $roots ) ) : ?>
				<p><?php esc_html_e( 'Разделов пока нет.', 'merler-menu' ); ?></p>
			<?php else : ?>
				<ul class="merler-sections" data-sortable="sections">
					<?php foreach ( $roots as $root ) : ?>
						<li class="merler-section" data-term="<?php echo esc_attr( (string) $root->term_id ); ?>">
							<div class="merler-section-head">
								<span class="merler-handle" aria-hidden="true">⠿</span>
								<strong><?php echo esc_html( $root->name ); ?></strong>
								<?php if ( '0' === get_term_meta( $root->term_id, 'merler_visible', true ) ) : ?>
									<em class="merler-hidden-mark"><?php esc_html_e( 'скрыт', 'merler-menu' ); ?></em>
								<?php endif; ?>
							</div>

							<?php self::dish_list( $root->term_id ); ?>

							<?php if ( ! empty( $root->children ) ) : ?>
								<ul class="merler-subsections" data-sortable="sections">
									<?php foreach ( $root->children as $child ) : ?>
										<li class="merler-section merler-subsection" data-term="<?php echo esc_attr( (string) $child->term_id ); ?>">
											<div class="merler-section-head">
												<span class="merler-handle" aria-hidden="true">⠿</span>
												<span><?php echo esc_html( $child->name ); ?></span>
											</div>
											<?php self::dish_list( $child->term_id ); ?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Список блюд раздела.
	 *
	 * @param int $term_id ID раздела.
	 */
	private static function dish_list( $term_id ) {
		$dishes = get_posts(
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

		if ( empty( $dishes ) ) {
			echo '<p class="merler-empty">' . esc_html__( 'В этом разделе пока нет блюд.', 'merler-menu' ) . '</p>';
			return;
		}
		?>
		<ul class="merler-dishes" data-sortable="dishes">
			<?php foreach ( $dishes as $dish ) : ?>
				<li class="merler-dish" data-post="<?php echo esc_attr( (string) $dish->ID ); ?>">
					<span class="merler-handle" aria-hidden="true">⠿</span>
					<a href="<?php echo esc_url( get_edit_post_link( $dish->ID ) ); ?>"><?php echo esc_html( get_the_title( $dish ) ); ?></a>
					<span class="merler-dish-price"><?php echo esc_html( merler_price( get_post_meta( $dish->ID, '_merler_price', true ) ) ); ?></span>
					<?php
					$status = merler_dish_status( $dish->ID );
					if ( 'in_stock' !== $status ) :
						$labels = merler_statuses();
						?>
						<em class="merler-status merler-status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $labels[ $status ] ); ?></em>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Сохранение порядка (AJAX).
	 */
	public static function save_order() {
		check_ajax_referer( 'merler_order', 'nonce' );

		if ( ! current_user_can( 'edit_merler_dishes' ) ) {
			wp_send_json_error( array( 'message' => __( 'Недостаточно прав.', 'merler-menu' ) ), 403 );
		}

		$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$ids  = isset( $_POST['ids'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['ids'] ) ) : array();

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Пустой список.', 'merler-menu' ) ), 400 );
		}

		$order = 10;

		if ( 'dishes' === $type ) {
			foreach ( $ids as $post_id ) {
				if ( ! current_user_can( 'edit_merler_dish', $post_id ) ) {
					continue;
				}
				wp_update_post(
					array(
						'ID'         => $post_id,
						'menu_order' => $order,
					)
				);
				$order += 10;
			}
		} elseif ( 'sections' === $type ) {
			if ( ! current_user_can( 'manage_merler_sections' ) ) {
				wp_send_json_error( array( 'message' => __( 'Недостаточно прав.', 'merler-menu' ) ), 403 );
			}
			foreach ( $ids as $term_id ) {
				update_term_meta( $term_id, 'merler_order', $order );
				$order += 10;
			}
		} else {
			wp_send_json_error( array( 'message' => __( 'Неизвестный тип.', 'merler-menu' ) ), 400 );
		}

		Merler_Cache::flush();

		wp_send_json_success( array( 'message' => __( 'Порядок сохранён', 'merler-menu' ) ) );
	}
}
