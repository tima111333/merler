<?php
/**
 * Тема «Мерлер»: только внешний вид. Данные — в плагине merler-menu.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

define( 'MERLER_THEME_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/seo.php';

/**
 * Подключение стилей и скриптов.
 */
function merler_theme_assets() {
	$dir = get_template_directory_uri();

	wp_enqueue_style( 'merler-fonts', $dir . '/assets/fonts/fonts.css', array(), MERLER_THEME_VERSION );
	wp_enqueue_style( 'merler-menu-style', $dir . '/assets/css/menu.css', array( 'merler-fonts' ), MERLER_THEME_VERSION );
	wp_add_inline_style( 'merler-menu-style', merler_theme_css_vars() );

	wp_enqueue_script( 'merler-menu-script', $dir . '/assets/js/menu.js', array(), MERLER_THEME_VERSION, true );

	wp_localize_script(
		'merler-menu-script',
		'merlerData',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'merler_review' ),
			'reviewUrl'  => merler_theme_option( 'review_url' ),
			'privacyUrl' => merler_theme_option( 'privacy_url' ),
			'i18n'       => array(
				'searchEmpty' => __( 'Ничего не нашлось. Попробуйте другое слово.', 'merler-theme' ),
				'sending'     => __( 'Отправляем…', 'merler-theme' ),
				'thanks'      => __( 'Спасибо! Мы прочитаем каждое слово.', 'merler-theme' ),
				'error'       => __( 'Не получилось отправить. Попробуйте ещё раз.', 'merler-theme' ),
				'needStars'   => __( 'Поставьте оценку.', 'merler-theme' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'merler_theme_assets' );

/**
 * Настройка меню из плагина с запасным значением, если плагин отключён.
 *
 * @param string $key     Ключ.
 * @param mixed  $default Значение по умолчанию.
 * @return mixed
 */
function merler_theme_option( $key, $default = '' ) {
	if ( function_exists( 'merler_option' ) ) {
		return merler_option( $key, $default );
	}
	return $default;
}

/**
 * CSS-переменные из настроек плагина.
 *
 * @return string
 */
function merler_theme_css_vars() {
	$map = array(
		'--bg'     => merler_theme_option( 'color_bg', '#FFF9EB' ),
		'--card'   => merler_theme_option( 'color_card', '#FFFFFF' ),
		'--accent' => merler_theme_option( 'color_accent', '#7A4514' ),
		'--text'   => merler_theme_option( 'color_text', '#14120E' ),
		'--muted'  => merler_theme_option( 'color_muted', '#605A54' ),
		'--line'   => merler_theme_option( 'color_line', '#EFE2C8' ),
	);

	$css = ':root{';
	foreach ( $map as $name => $value ) {
		$css .= $name . ':' . sanitize_hex_color( $value ) . ';';
	}
	$css .= '}';

	return $css;
}

/**
 * Класс на body: режим без фото.
 *
 * @param array $classes Классы.
 * @return array
 */
function merler_theme_body_class( $classes ) {
	if ( merler_theme_option( 'no_photo_mode' ) ) {
		$classes[] = 'is-no-photo';
	}
	return $classes;
}
add_filter( 'body_class', 'merler_theme_body_class' );

/**
 * Меню ресторана: структура из плагина.
 *
 * @return array
 */
function merler_theme_menu() {
	if ( ! class_exists( 'Merler_Query' ) ) {
		return array();
	}
	return Merler_Query::get_menu();
}

/**
 * Картинка из настроек.
 *
 * @param string $key   Ключ настройки с ID вложения.
 * @param string $size  Размер.
 * @param array  $attrs Атрибуты тега.
 * @return string
 */
function merler_theme_image( $key, $size = 'large', $attrs = array() ) {
	$id = (int) merler_theme_option( $key );
	if ( $id ) {
		return wp_get_attachment_image( $id, $size, false, $attrs );
	}

	// Пока клиент не загрузил свои файлы — показываем те, что идут с темой.
	$fallbacks = array(
		'logo_id'     => array( 'logo.webp', 354, 240 ),
		'building_id' => array( 'building.webp', 707, 670 ),
	);

	if ( ! isset( $fallbacks[ $key ] ) ) {
		return '';
	}

	list( $file, $width, $height ) = $fallbacks[ $key ];

	$class = isset( $attrs['class'] ) ? $attrs['class'] : '';
	$alt   = isset( $attrs['alt'] ) ? $attrs['alt'] : '';

	return sprintf(
		'<img src="%1$s" width="%2$d" height="%3$d" class="%4$s" alt="%5$s" decoding="async">',
		esc_url( get_template_directory_uri() . '/assets/img/' . $file ),
		(int) $width,
		(int) $height,
		esc_attr( $class ),
		esc_attr( $alt )
	);
}

/**
 * Заглушка вместо фото блюда.
 *
 * @return string
 */
function merler_theme_placeholder() {
	$src = get_template_directory_uri() . '/assets/img/logo-mark.png';
	return '<span class="ph" aria-hidden="true"><img src="' . esc_url( $src ) . '" width="140" height="104" alt="" loading="lazy"></span>';
}
