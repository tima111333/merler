<?php
/**
 * Заголовок, описание и favicon. JSON-LD и Open Graph выводит плагин.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Заголовок страницы меню.
 *
 * @param array $parts Части заголовка.
 * @return array
 */
function merler_theme_title_parts( $parts ) {
	if ( is_front_page() ) {
		$parts['title'] = get_bloginfo( 'name' );
		$parts['site']  = merler_theme_option( 'hero_title', __( 'Меню ресторана', 'merler-theme' ) );
	}
	return $parts;
}
add_filter( 'document_title_parts', 'merler_theme_title_parts' );

/**
 * Favicon из логотипа, если он загружен и в настройках WordPress иконка не задана.
 */
function merler_theme_favicon() {
	if ( has_site_icon() ) {
		return;
	}

	$logo_id = (int) merler_theme_option( 'logo_id' );
	$src     = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : get_template_directory_uri() . '/assets/img/logo-mark.png';

	if ( ! $src ) {
		return;
	}

	echo '<link rel="icon" href="' . esc_url( $src ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $src ) . '">' . "\n";
	echo '<meta name="theme-color" content="' . esc_attr( merler_theme_option( 'color_bg', '#FFF9EB' ) ) . '">' . "\n";
}
add_action( 'wp_head', 'merler_theme_favicon', 4 );

/**
 * Предзагрузка основного шрифта — первый экран рисуется быстрее.
 */
function merler_theme_preload_fonts() {
	$dir = get_template_directory_uri() . '/assets/fonts/woff2/';
	$files = array( 'montserrat-alternates-600-cyrillic.woff2', 'inter-400-cyrillic.woff2' );

	foreach ( $files as $file ) {
		if ( file_exists( get_template_directory() . '/assets/fonts/woff2/' . $file ) ) {
			echo '<link rel="preload" as="font" type="font/woff2" href="' . esc_url( $dir . $file ) . '" crossorigin>' . "\n";
		}
	}
}
add_action( 'wp_head', 'merler_theme_preload_fonts', 3 );
