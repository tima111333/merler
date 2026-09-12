<?php
/**
 * Форма редактирования блюда.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Метабоксы блюда.
 */
class Merler_Metabox {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
		add_action( 'save_post_merler_dish', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * Регистрация метабоксов.
	 */
	public static function register() {
		add_meta_box(
			'merler_dish_fields',
			__( 'Блюдо', 'merler-menu' ),
			array( __CLASS__, 'render' ),
			'merler_dish',
			'normal',
			'high'
		);

		remove_meta_box( 'slugdiv', 'merler_dish', 'normal' );
	}

	/**
	 * Форма.
	 *
	 * @param WP_Post $post Запись.
	 */
	public static function render( $post ) {
		wp_nonce_field( 'merler_save_dish', 'merler_dish_nonce' );

		$weight      = (string) get_post_meta( $post->ID, '_merler_weight', true );
		$price       = (string) get_post_meta( $post->ID, '_merler_price', true );
		$description = (string) get_post_meta( $post->ID, '_merler_description', true );
		$badges      = get_post_meta( $post->ID, '_merler_badges', true );
		$badges      = is_array( $badges ) ? $badges : array();
		$status      = merler_dish_status( $post->ID );
		?>
		<div class="merler-fields">
			<p class="merler-field">
				<label for="merler_price"><strong><?php esc_html_e( 'Цена, ₽', 'merler-menu' ); ?></strong></label><br>
				<input type="number" id="merler_price" name="merler_price" value="<?php echo esc_attr( $price ); ?>" min="0" step="10" class="merler-price-input">
				<span class="description"><?php esc_html_e( 'Только число: 790', 'merler-menu' ); ?></span>
			</p>

			<p class="merler-field">
				<label for="merler_weight"><strong><?php esc_html_e( 'Вес или выход', 'merler-menu' ); ?></strong></label><br>
				<input type="text" id="merler_weight" name="merler_weight" value="<?php echo esc_attr( $weight ); ?>" class="regular-text" placeholder="330 г">
				<span class="description"><?php esc_html_e( 'Например: 330 г, 8 шт, 0,5 л. Пусто — на сайте ничего не выводится.', 'merler-menu' ); ?></span>
			</p>

			<p class="merler-field">
				<label for="merler_description"><strong><?php esc_html_e( 'Состав', 'merler-menu' ); ?></strong></label><br>
				<textarea id="merler_description" name="merler_description" rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
				<span class="description"><?php esc_html_e( 'Перечислите ингредиенты через запятую — как в печатном меню.', 'merler-menu' ); ?></span>
			</p>

			<div class="merler-field">
				<strong><?php esc_html_e( 'Статус', 'merler-menu' ); ?></strong>
				<ul class="merler-radio">
					<?php foreach ( merler_statuses() as $key => $label ) : ?>
						<li>
							<label>
								<input type="radio" name="merler_status" value="<?php echo esc_attr( $key ); ?>" <?php checked( $status, $key ); ?>>
								<?php echo esc_html( $label ); ?>
								<span class="description"><?php echo esc_html( self::status_hint( $key ) ); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="merler-field">
				<strong><?php esc_html_e( 'Бейджи', 'merler-menu' ); ?></strong>
				<ul class="merler-checks">
					<?php foreach ( merler_badges() as $key => $label ) : ?>
						<li>
							<label>
								<input type="checkbox" name="merler_badges[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $badges, true ) ); ?>>
								<?php echo esc_html( $label ); ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<p class="merler-field">
				<label for="merler_menu_order"><strong><?php esc_html_e( 'Порядок в разделе', 'merler-menu' ); ?></strong></label><br>
				<input type="number" id="merler_menu_order" name="merler_menu_order" value="<?php echo esc_attr( (string) (int) $post->menu_order ); ?>" step="10" class="small-text">
				<span class="description"><?php esc_html_e( 'Меньше число — выше в разделе. Порядок удобнее менять мышкой на странице «Порядок блюд».', 'merler-menu' ); ?></span>
			</p>
		</div>
		<?php
	}

	/**
	 * Пояснение к статусу.
	 *
	 * @param string $key Статус.
	 * @return string
	 */
	private static function status_hint( $key ) {
		$hints = array(
			'in_stock'     => __( '— обычная карточка', 'merler-menu' ),
			'soon'         => __( '— приглушённая карточка с пометкой «Скоро»', 'merler-menu' ),
			'out_of_stock' => __( '— полупрозрачная карточка, заказать нельзя', 'merler-menu' ),
			'hidden'       => __( '— блюдо не выводится на сайте', 'merler-menu' ),
		);
		return isset( $hints[ $key ] ) ? $hints[ $key ] : '';
	}

	/**
	 * Сохранение.
	 *
	 * @param int     $post_id ID записи.
	 * @param WP_Post $post    Запись.
	 */
	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['merler_dish_nonce'] ) ) {
			return; // Быстрое редактирование обрабатывается отдельно.
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['merler_dish_nonce'] ) ), 'merler_save_dish' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_merler_dish', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_merler_price', Merler_Meta::sanitize_price( isset( $_POST['merler_price'] ) ? wp_unslash( $_POST['merler_price'] ) : 0 ) );
		update_post_meta( $post_id, '_merler_weight', Merler_Meta::sanitize_weight( isset( $_POST['merler_weight'] ) ? wp_unslash( $_POST['merler_weight'] ) : '' ) );
		update_post_meta( $post_id, '_merler_description', Merler_Meta::sanitize_description( isset( $_POST['merler_description'] ) ? wp_unslash( $_POST['merler_description'] ) : '' ) );
		update_post_meta( $post_id, '_merler_status', Merler_Meta::sanitize_status( isset( $_POST['merler_status'] ) ? wp_unslash( $_POST['merler_status'] ) : 'in_stock' ) );

		$badges = isset( $_POST['merler_badges'] ) ? wp_unslash( $_POST['merler_badges'] ) : array();
		update_post_meta( $post_id, '_merler_badges', Merler_Meta::sanitize_badges( $badges ) );

		if ( isset( $_POST['merler_menu_order'] ) ) {
			$order = (int) $_POST['merler_menu_order'];
			if ( $order !== (int) $post->menu_order ) {
				remove_action( 'save_post_merler_dish', array( __CLASS__, 'save' ), 10 );
				wp_update_post(
					array(
						'ID'         => $post_id,
						'menu_order' => $order,
					)
				);
				add_action( 'save_post_merler_dish', array( __CLASS__, 'save' ), 10, 2 );
			}
		}
	}
}
