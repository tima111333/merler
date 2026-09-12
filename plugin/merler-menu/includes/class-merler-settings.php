<?php
/**
 * Страница «Настройки меню».
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Настройки сайта-меню.
 */
class Merler_Settings {

	const OPTION = 'merler_settings';
	const GROUP  = 'merler_settings_group';

	/**
	 * Кэш настроек в рамках запроса.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter( 'option_page_capability_' . self::GROUP, array( __CLASS__, 'capability' ) );
	}

	/**
	 * Значения по умолчанию.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'logo_id'       => 0,
			'building_id'   => 0,
			'hero_title'    => __( 'Меню ресторана', 'merler-menu' ),
			'greeting'      => __( 'Европейская, арабская и дагестанская кухня. Всё готовится после заказа — спасибо за терпение.', 'merler-menu' ),
			'about_title'   => __( 'О ресторане', 'merler-menu' ),
			'about_text'    => __( 'Ресторан бутик-отеля «Мерлер». Завтраки для гостей отеля, обеды и ужины — для всех.', 'merler-menu' ),
			'phone'         => '+7 988 641-32-34',
			'whatsapp'      => '+7 988 641-32-34',
			'instagram'     => 'merler.hotel',
			'address'       => '',
			'map_url'       => '',
			'hours'         => __( 'Кухня работает с 8:00 до 23:00', 'merler-menu' ),
			'wifi_show'     => 0,
			'wifi_name'     => '',
			'wifi_pass'     => '',
			'review_url'    => '',
			'review_email'  => get_option( 'admin_email' ),
			'privacy_url'   => '',
			'menu_url'      => '',
			'currency'      => '₽',
			'no_photo_mode' => 0,
			'enable_search' => 1,
			'enable_rating' => 1,
			'color_bg'      => '#FFF9EB',
			'color_card'    => '#FFFFFF',
			'color_accent'  => '#7A4514',
			'color_text'    => '#14120E',
			'color_muted'   => '#605A54',
			'color_line'    => '#EFE2C8',
		);
	}

	/**
	 * Все настройки.
	 *
	 * @return array
	 */
	public static function get_all() {
		if ( is_array( self::$cache ) ) {
			return self::$cache;
		}

		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		self::$cache = array_merge( self::defaults(), $saved );

		return self::$cache;
	}

	/**
	 * Право на сохранение настроек.
	 *
	 * @return string
	 */
	public static function capability() {
		return 'manage_merler_settings';
	}

	/**
	 * Регистрация настроек.
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Санитизация всех полей.
	 *
	 * @param mixed $input Ввод.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$out      = self::get_all();
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();

		$text_fields = array( 'hero_title', 'about_title', 'phone', 'whatsapp', 'instagram', 'address', 'hours', 'wifi_name', 'wifi_pass', 'currency' );
		foreach ( $text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$out[ $field ] = sanitize_text_field( wp_unslash( $input[ $field ] ) );
			}
		}

		foreach ( array( 'greeting', 'about_text' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$out[ $field ] = wp_strip_all_tags( wp_unslash( $input[ $field ] ) );
			}
		}

		foreach ( array( 'map_url', 'review_url', 'privacy_url', 'menu_url' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$out[ $field ] = esc_url_raw( wp_unslash( $input[ $field ] ) );
			}
		}

		if ( isset( $input['review_email'] ) ) {
			$email                = sanitize_email( wp_unslash( $input['review_email'] ) );
			$out['review_email']  = is_email( $email ) ? $email : $defaults['review_email'];
		}

		foreach ( array( 'logo_id', 'building_id' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$out[ $field ] = max( 0, (int) $input[ $field ] );
			}
		}

		foreach ( array( 'wifi_show', 'no_photo_mode', 'enable_search', 'enable_rating' ) as $field ) {
			$out[ $field ] = empty( $input[ $field ] ) ? 0 : 1;
		}

		foreach ( array( 'color_bg', 'color_card', 'color_accent', 'color_text', 'color_muted', 'color_line' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$color         = sanitize_hex_color( wp_unslash( $input[ $field ] ) );
				$out[ $field ] = $color ? $color : $defaults[ $field ];
			}
		}

		// Кнопка «Сбросить к фирменным цветам».
		if ( ! empty( $input['reset_colors'] ) ) {
			foreach ( array( 'color_bg', 'color_card', 'color_accent', 'color_text', 'color_muted', 'color_line' ) as $field ) {
				$out[ $field ] = $defaults[ $field ];
			}
		}

		self::$cache = null;

		return $out;
	}

	/**
	 * Вывод страницы настроек.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_merler_settings' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'merler-menu' ) );
		}

		$s = self::get_all();
		?>
		<div class="wrap merler-settings">
			<h1><?php esc_html_e( 'Настройки меню', 'merler-menu' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Всё, что видит гость на сайте, кроме самих блюд. После сохранения изменения появляются сразу.', 'merler-menu' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>

				<h2 class="title"><?php esc_html_e( 'Картинки', 'merler-menu' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Логотип', 'merler-menu' ); ?></th>
						<td><?php self::image_field( 'logo_id', (int) $s['logo_id'], __( 'Прозрачный PNG или SVG. Выводится в шапке.', 'merler-menu' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Иллюстрация здания', 'merler-menu' ); ?></th>
						<td><?php self::image_field( 'building_id', (int) $s['building_id'], __( 'Акварельный рисунок отеля. Выводится на первом экране и в блоке «О ресторане».', 'merler-menu' ) ); ?></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Тексты', 'merler-menu' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::text_row( 'hero_title', __( 'Подпись под логотипом', 'merler-menu' ), $s['hero_title'] );
					self::textarea_row( 'greeting', __( 'Приветствие', 'merler-menu' ), $s['greeting'], __( 'Одно-два предложения. Показывается на первом экране.', 'merler-menu' ) );
					self::text_row( 'about_title', __( 'Заголовок блока «О нас»', 'merler-menu' ), $s['about_title'] );
					self::textarea_row( 'about_text', __( 'Текст блока «О нас»', 'merler-menu' ), $s['about_text'] );
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Контакты', 'merler-menu' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::text_row( 'phone', __( 'Телефон', 'merler-menu' ), $s['phone'], __( 'В любом виде: +7 988 641-32-34.', 'merler-menu' ) );
					self::text_row( 'whatsapp', __( 'WhatsApp', 'merler-menu' ), $s['whatsapp'], __( 'Номер для ссылки wa.me. Пусто — кнопка не выводится.', 'merler-menu' ) );
					self::text_row( 'instagram', __( 'Instagram', 'merler-menu' ), $s['instagram'], __( 'Только имя аккаунта, без «@» и ссылки.', 'merler-menu' ) );
					self::text_row( 'address', __( 'Адрес', 'merler-menu' ), $s['address'] );
					self::text_row( 'map_url', __( 'Ссылка на карты', 'merler-menu' ), $s['map_url'], __( 'Яндекс Карты или 2ГИС — ссылка на карточку заведения.', 'merler-menu' ) );
					self::text_row( 'hours', __( 'Часы работы кухни', 'merler-menu' ), $s['hours'] );
					?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Wi-Fi', 'merler-menu' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[wifi_show]" value="1" <?php checked( 1, (int) $s['wifi_show'] ); ?>> <?php esc_html_e( 'Показывать Wi-Fi на сайте', 'merler-menu' ); ?></label>
							<p>
								<input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[wifi_name]" value="<?php echo esc_attr( $s['wifi_name'] ); ?>" placeholder="<?php esc_attr_e( 'Имя сети', 'merler-menu' ); ?>" class="regular-text">
								<input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[wifi_pass]" value="<?php echo esc_attr( $s['wifi_pass'] ); ?>" placeholder="<?php esc_attr_e( 'Пароль', 'merler-menu' ); ?>" class="regular-text">
							</p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Отзывы', 'merler-menu' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::text_row( 'review_url', __( 'Ссылка «Оставить отзыв на картах»', 'merler-menu' ), $s['review_url'] );
					self::text_row( 'review_email', __( 'E-mail для отзывов', 'merler-menu' ), $s['review_email'], __( 'Каждый отзыв придёт письмом и сохранится в разделе «Отзывы».', 'merler-menu' ) );
					self::text_row( 'privacy_url', __( 'Ссылка на политику обработки данных', 'merler-menu' ), $s['privacy_url'], __( 'Нужна, если гость может оставить телефон.', 'merler-menu' ) );
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Как выглядит меню', 'merler-menu' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Режим без фото', 'merler-menu' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[no_photo_mode]" value="1" <?php checked( 1, (int) $s['no_photo_mode'] ); ?>> <?php esc_html_e( 'Компактный список вместо карточек с фото', 'merler-menu' ); ?></label>
							<p class="description"><?php esc_html_e( 'Удобно, пока фотографий блюд нет: меню выглядит как печатное.', 'merler-menu' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Поиск', 'merler-menu' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enable_search]" value="1" <?php checked( 1, (int) $s['enable_search'] ); ?>> <?php esc_html_e( 'Показывать поиск по блюдам', 'merler-menu' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Кнопка «Оценить»', 'merler-menu' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enable_rating]" value="1" <?php checked( 1, (int) $s['enable_rating'] ); ?>> <?php esc_html_e( 'Показывать кнопку оценки и формы отзыва', 'merler-menu' ); ?></label></td>
					</tr>
					<?php
					self::text_row( 'currency', __( 'Символ валюты', 'merler-menu' ), $s['currency'] );
					self::text_row( 'menu_url', __( 'Постоянный адрес меню', 'merler-menu' ), $s['menu_url'], __( 'Используется в QR-коде. Пусто — берётся адрес сайта.', 'merler-menu' ) );
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Цвета', 'merler-menu' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::color_row( 'color_bg', __( 'Фон страницы', 'merler-menu' ), $s['color_bg'] );
					self::color_row( 'color_card', __( 'Фон карточек', 'merler-menu' ), $s['color_card'] );
					self::color_row( 'color_accent', __( 'Акцент (заголовки, кнопки)', 'merler-menu' ), $s['color_accent'] );
					self::color_row( 'color_text', __( 'Основной текст', 'merler-menu' ), $s['color_text'] );
					self::color_row( 'color_muted', __( 'Второстепенный текст', 'merler-menu' ), $s['color_muted'] );
					self::color_row( 'color_line', __( 'Линии и рамки', 'merler-menu' ), $s['color_line'] );
					?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Сброс', 'merler-menu' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[reset_colors]" value="1"> <?php esc_html_e( 'Сбросить к фирменным цветам при сохранении', 'merler-menu' ); ?></label>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Сохранить настройки', 'merler-menu' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Строка с текстовым полем.
	 *
	 * @param string $key   Ключ.
	 * @param string $label Подпись.
	 * @param string $value Значение.
	 * @param string $hint  Пояснение.
	 */
	private static function text_row( $key, $label, $value, $hint = '' ) {
		$id = 'merler_' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="text" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<?php if ( $hint ) : ?>
					<p class="description"><?php echo esc_html( $hint ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Строка с многострочным полем.
	 *
	 * @param string $key   Ключ.
	 * @param string $label Подпись.
	 * @param string $value Значение.
	 * @param string $hint  Пояснение.
	 */
	private static function textarea_row( $key, $label, $value, $hint = '' ) {
		$id = 'merler_' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<textarea class="large-text" rows="3" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php if ( $hint ) : ?>
					<p class="description"><?php echo esc_html( $hint ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Строка с выбором цвета.
	 *
	 * @param string $key   Ключ.
	 * @param string $label Подпись.
	 * @param string $value Значение.
	 */
	private static function color_row( $key, $label, $value ) {
		$id = 'merler_' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="text" class="merler-color" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( self::defaults()[ $key ] ); ?>">
			</td>
		</tr>
		<?php
	}

	/**
	 * Поле выбора картинки из медиатеки.
	 *
	 * @param string $key      Ключ.
	 * @param int    $image_id ID картинки.
	 * @param string $hint     Пояснение.
	 */
	private static function image_field( $key, $image_id, $hint = '' ) {
		$src = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<div class="merler-image-field" data-field="<?php echo esc_attr( $key ); ?>">
			<div class="merler-image-preview">
				<?php if ( $src ) : ?>
					<img src="<?php echo esc_url( $src ); ?>" alt="">
				<?php endif; ?>
			</div>
			<input type="hidden" name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $image_id ); ?>" class="merler-image-id">
			<button type="button" class="button merler-image-select"><?php esc_html_e( 'Выбрать картинку', 'merler-menu' ); ?></button>
			<button type="button" class="button-link merler-image-remove"<?php echo $image_id ? '' : ' style="display:none"'; ?>><?php esc_html_e( 'Убрать', 'merler-menu' ); ?></button>
			<?php if ( $hint ) : ?>
				<p class="description"><?php echo esc_html( $hint ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
