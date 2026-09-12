<?php
/**
 * Страница «QR-код»: генерация и табличка A6.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * QR-код меню.
 */
class Merler_QR {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Скрипты страницы QR.
	 *
	 * @param string $hook Текущая страница админки.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( $hook, 'merler-qr' ) ) {
			return;
		}

		wp_enqueue_script( 'merler-qrcode', MERLER_URL . 'assets/qrcode.min.js', array(), '1.4.4', true );
		wp_enqueue_script( 'merler-qr', MERLER_URL . 'admin/js/qr.js', array( 'merler-qrcode' ), MERLER_VERSION, true );

		$logo_id = (int) merler_option( 'logo_id' );

		wp_localize_script(
			'merler-qr',
			'merlerQR',
			array(
				'url'      => merler_menu_url(),
				'urlHuman' => self::human_url(),
				'logo'     => $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : MERLER_URL . 'assets/logo-mark.png',
				'phone'    => merler_option( 'phone' ),
				'wifiName' => merler_option( 'wifi_name' ),
				'wifiPass' => merler_option( 'wifi_pass' ),
				'wifiShow' => (int) merler_option( 'wifi_show' ),
				'hotel'    => get_bloginfo( 'name' ),
				'accent'   => merler_option( 'color_accent', '#7A4514' ),
				'bg'       => merler_option( 'color_bg', '#FFF9EB' ),
				'i18n'     => array(
					'scan'  => __( 'Отсканируйте, чтобы открыть меню', 'merler-menu' ),
					'wifi'  => __( 'Wi-Fi', 'merler-menu' ),
					'saved' => __( 'Файл скачан', 'merler-menu' ),
				),
			)
		);
	}

	/**
	 * Адрес меню как его прочитает человек: без протокола и завершающей косой черты.
	 *
	 * Подписывается под кодом на табличке: гость видит, куда ведёт код, и подменённая
	 * наклейка сразу заметна.
	 *
	 * @return string
	 */
	private static function human_url() {
		$url = merler_option( 'menu_url', '' );

		if ( '' === $url ) {
			$url = home_url( '/' );
		}

		$url = preg_replace( '~^https?://~i', '', $url );
		$url = preg_replace( '~^www\.~i', '', $url );

		return rtrim( $url, '/' );
	}

	/**
	 * Страница QR-кода.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_merler_settings' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'merler-menu' ) );
		}

		$url = merler_menu_url();
		?>
		<div class="wrap merler-qr-page">
			<h1><?php esc_html_e( 'QR-код меню', 'merler-menu' ); ?></h1>

			<p class="description">
				<?php esc_html_e( 'QR ведёт на постоянный адрес меню. Само меню вы меняете в админке — перепечатывать код после изменения блюд и цен не нужно.', 'merler-menu' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="merler-qr-url"><?php esc_html_e( 'Адрес в коде', 'merler-menu' ); ?></label></th>
					<td>
						<input type="text" id="merler-qr-url" class="large-text code" value="<?php echo esc_attr( $url ); ?>">
						<p class="description"><?php esc_html_e( 'Кириллический домен автоматически переведён в punycode — так его понимает любая камера. Изменить адрес можно в настройках меню.', 'merler-menu' ); ?></p>
					</td>
				</tr>
			</table>

			<div class="merler-qr-grid">
				<div class="merler-qr-box">
					<h2><?php esc_html_e( 'Без логотипа', 'merler-menu' ); ?></h2>
					<div id="merler-qr-plain" class="merler-qr-canvas"></div>
					<p>
						<button type="button" class="button" data-qr-download="plain" data-format="svg"><?php esc_html_e( 'Скачать SVG', 'merler-menu' ); ?></button>
						<button type="button" class="button" data-qr-download="plain" data-format="png"><?php esc_html_e( 'Скачать PNG 2000px', 'merler-menu' ); ?></button>
					</p>
					<p class="description"><?php esc_html_e( 'Самый надёжный вариант: читается даже при плохой печати.', 'merler-menu' ); ?></p>
				</div>

				<div class="merler-qr-box">
					<h2><?php esc_html_e( 'С символом «М» в центре', 'merler-menu' ); ?></h2>
					<div id="merler-qr-logo" class="merler-qr-canvas"></div>
					<p>
						<button type="button" class="button" data-qr-download="logo" data-format="svg"><?php esc_html_e( 'Скачать SVG', 'merler-menu' ); ?></button>
						<button type="button" class="button" data-qr-download="logo" data-format="png"><?php esc_html_e( 'Скачать PNG 2000px', 'merler-menu' ); ?></button>
					</p>
					<p class="description"><?php esc_html_e( 'Логотип занимает не больше 20% площади, уровень коррекции ошибок H.', 'merler-menu' ); ?></p>
				</div>
			</div>

			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Обязательно проверьте перед печатью.', 'merler-menu' ); ?></strong>
				<?php esc_html_e( 'Отсканируйте оба варианта минимум с двух разных телефонов (iPhone и Android) с расстояния 20–30 см. Если вариант с логотипом читается хуже — печатайте вариант без логотипа.', 'merler-menu' ); ?></p>
			</div>

			<hr>

			<h2><?php esc_html_e( 'Настольная табличка A6', 'merler-menu' ); ?></h2>
			<p><?php esc_html_e( 'Готовый макет 105×148 мм в фирменном стиле: логотип, надпись, QR-код, телефон и Wi-Fi. Скачайте SVG для типографии или распечатайте прямо из браузера.', 'merler-menu' ); ?></p>

			<div id="merler-tent" class="merler-tent-preview"></div>

			<p>
				<button type="button" class="button button-primary" id="merler-tent-svg"><?php esc_html_e( 'Скачать SVG для типографии', 'merler-menu' ); ?></button>
				<button type="button" class="button" id="merler-tent-print"><?php esc_html_e( 'Распечатать / сохранить в PDF', 'merler-menu' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'В окне печати выберите формат A6 (или A4 с масштабом 100%) и поля «нет». PDF получается через «Сохранить как PDF» в том же окне.', 'merler-menu' ); ?></p>
		</div>
		<?php
	}
}
