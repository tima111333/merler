<?php
/**
 * Поддержка возможностей темы.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Настройка темы.
 */
function merler_theme_setup() {
	load_theme_textdomain( 'merler-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support( 'responsive-embeds' );

	// Размеры фото блюд регистрирует плагин; здесь — на случай, если он выключен.
	if ( ! function_exists( 'merler_option' ) ) {
		add_image_size( 'merler-card', 600, 450, true );
		add_image_size( 'merler-card-2x', 1200, 900, true );
	}
}
add_action( 'after_setup_theme', 'merler_theme_setup' );

/**
 * Убираем лишнее из шапки — меню должно быть лёгким.
 */
function merler_theme_cleanup() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'merler_theme_cleanup' );

/**
 * Атрибут loading="lazy" не нужен первой картинке — она в первом экране.
 *
 * @param array $attr Атрибуты.
 * @return array
 */
function merler_theme_hero_image_attr( $attr ) {
	if ( isset( $attr['class'] ) && false !== strpos( $attr['class'], 'hero-building' ) ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$attr['decoding']      = 'async';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'merler_theme_hero_image_attr' );
