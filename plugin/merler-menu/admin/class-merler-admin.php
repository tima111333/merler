<?php
/**
 * Меню админки и подключение ассетов.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Админка плагина.
 */
class Merler_Admin {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'admin_notices', array( __CLASS__, 'setup_notice' ) );
	}

	/**
	 * Пункты меню.
	 */
	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=merler_dish',
			__( 'Порядок блюд', 'merler-menu' ),
			__( 'Порядок блюд', 'merler-menu' ),
			'edit_merler_dishes',
			'merler-order',
			array( 'Merler_Order', 'render_page' )
		);

		add_submenu_page(
			'edit.php?post_type=merler_dish',
			__( 'Настройки меню', 'merler-menu' ),
			__( 'Настройки меню', 'merler-menu' ),
			'manage_merler_settings',
			'merler-settings',
			array( 'Merler_Settings', 'render_page' )
		);

		add_submenu_page(
			'edit.php?post_type=merler_dish',
			__( 'Импорт и экспорт', 'merler-menu' ),
			__( 'Импорт и экспорт', 'merler-menu' ),
			'manage_merler_settings',
			'merler-tools',
			array( 'Merler_Importer', 'render_page' )
		);

		add_submenu_page(
			'edit.php?post_type=merler_dish',
			__( 'QR-код', 'merler-menu' ),
			__( 'QR-код', 'merler-menu' ),
			'manage_merler_settings',
			'merler-qr',
			array( 'Merler_QR', 'render_page' )
		);

		add_submenu_page(
			'edit.php?post_type=merler_dish',
			__( 'Как редактировать меню', 'merler-menu' ),
			__( 'Инструкция', 'merler-menu' ),
			'edit_merler_dishes',
			'merler-help',
			array( 'Merler_Dashboard', 'render_help_page' )
		);
	}

	/**
	 * Стили и скрипты админки.
	 *
	 * @param string $hook Текущая страница.
	 */
	public static function assets( $hook ) {
		$screen = get_current_screen();
		$is_ours = false;

		if ( $screen && in_array( $screen->post_type, array( 'merler_dish', 'merler_review' ), true ) ) {
			$is_ours = true;
		}
		if ( $screen && Merler_Taxonomy::TAX === $screen->taxonomy ) {
			$is_ours = true;
		}
		if ( false !== strpos( $hook, 'merler-' ) ) {
			$is_ours = true;
		}

		if ( ! $is_ours ) {
			return;
		}

		wp_enqueue_style( 'merler-admin', MERLER_URL . 'admin/css/admin.css', array(), MERLER_VERSION );

		// Выбор картинок в настройках.
		if ( false !== strpos( $hook, 'merler-settings' ) ) {
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'merler-settings', MERLER_URL . 'admin/js/settings.js', array( 'jquery', 'wp-color-picker' ), MERLER_VERSION, true );
		}

		// Перетаскивание порядка.
		if ( false !== strpos( $hook, 'merler-order' ) ) {
			wp_enqueue_script( 'merler-sortable', MERLER_URL . 'admin/js/sortable.min.js', array(), '1.15.6', true );
			wp_enqueue_script( 'merler-order', MERLER_URL . 'admin/js/order.js', array( 'merler-sortable' ), MERLER_VERSION, true );
			wp_localize_script(
				'merler-order',
				'merlerOrder',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'merler_order' ),
					'saved'   => __( 'Порядок сохранён', 'merler-menu' ),
					'error'   => __( 'Не удалось сохранить порядок', 'merler-menu' ),
				)
			);
		}

		// Быстрое редактирование в списке блюд.
		if ( $screen && 'edit-merler_dish' === $screen->id ) {
			wp_enqueue_script( 'merler-quick-edit', MERLER_URL . 'admin/js/quick-edit.js', array( 'jquery', 'inline-edit-post' ), MERLER_VERSION, true );
		}
	}

	/**
	 * Класс на body — для стилей админки.
	 *
	 * @param string $classes Классы.
	 * @return string
	 */
	public static function body_class( $classes ) {
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->post_type, array( 'merler_dish', 'merler_review' ), true ) ) {
			$classes .= ' merler-admin';
		}
		return $classes;
	}

	/**
	 * Подсказка, если меню ещё пустое.
	 */
	public static function setup_notice() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-merler_dish' !== $screen->id ) {
			return;
		}

		$count = wp_count_posts( 'merler_dish' );
		if ( isset( $count->publish ) && (int) $count->publish > 0 ) {
			return;
		}

		$url = admin_url( 'edit.php?post_type=merler_dish&page=merler-tools' );
		?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'Меню пустое.', 'merler-menu' ); ?>
				<a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Загрузите печатное меню одной кнопкой', 'merler-menu' ); ?></a>
				<?php esc_html_e( 'или добавьте блюда вручную.', 'merler-menu' ); ?>
			</p>
		</div>
		<?php
	}
}
