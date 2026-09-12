<?php
/**
 * Plugin Name:       Меню «Мерлер»
 * Plugin URI:        https://xn--e1aarcvc.xn--p1ai/
 * Description:       Меню ресторана бутик-отеля «Мерлер»: блюда, разделы, настройки, отзывы, импорт-экспорт и QR-код. Данные хранятся в плагине и не теряются при смене темы.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Мерлер
 * Text Domain:       merler-menu
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

define( 'MERLER_VERSION', '1.0.0' );
define( 'MERLER_FILE', __FILE__ );
define( 'MERLER_DIR', plugin_dir_path( __FILE__ ) );
define( 'MERLER_URL', plugin_dir_url( __FILE__ ) );

require_once MERLER_DIR . 'includes/functions.php';
require_once MERLER_DIR . 'includes/class-merler-post-type.php';
require_once MERLER_DIR . 'includes/class-merler-taxonomy.php';
require_once MERLER_DIR . 'includes/class-merler-meta.php';
require_once MERLER_DIR . 'includes/class-merler-query.php';
require_once MERLER_DIR . 'includes/class-merler-settings.php';
require_once MERLER_DIR . 'includes/class-merler-roles.php';
require_once MERLER_DIR . 'includes/class-merler-importer.php';
require_once MERLER_DIR . 'includes/class-merler-reviews.php';
require_once MERLER_DIR . 'includes/class-merler-schema.php';
require_once MERLER_DIR . 'includes/class-merler-qr.php';
require_once MERLER_DIR . 'includes/class-merler-cache.php';

if ( is_admin() ) {
	require_once MERLER_DIR . 'admin/class-merler-admin.php';
	require_once MERLER_DIR . 'admin/class-merler-metabox.php';
	require_once MERLER_DIR . 'admin/class-merler-columns.php';
	require_once MERLER_DIR . 'admin/class-merler-bulk.php';
	require_once MERLER_DIR . 'admin/class-merler-order.php';
	require_once MERLER_DIR . 'admin/class-merler-dashboard.php';
}

/**
 * Запуск плагина.
 */
function merler_init_plugin() {
	load_plugin_textdomain( 'merler-menu', false, dirname( plugin_basename( MERLER_FILE ) ) . '/languages' );

	Merler_Post_Type::init();
	Merler_Taxonomy::init();
	Merler_Meta::init();
	Merler_Settings::init();
	Merler_Reviews::init();
	Merler_Schema::init();
	Merler_Cache::init();

	add_action( 'after_setup_theme', 'merler_image_sizes' );

	if ( is_admin() ) {
		Merler_Admin::init();
		Merler_Metabox::init();
		Merler_Columns::init();
		Merler_Bulk::init();
		Merler_Order::init();
		Merler_Dashboard::init();
		Merler_Importer::init();
		Merler_QR::init();
	}
}
add_action( 'plugins_loaded', 'merler_init_plugin' );

/**
 * Активация: типы записей, роли, стартовые данные.
 */
function merler_activate() {
	Merler_Post_Type::register();
	Merler_Taxonomy::register();
	Merler_Roles::add_role();
	Merler_Roles::grant_admin_caps();

	// Если меню пустое — наполняем его из data/menu-data.json.
	$existing = get_posts(
		array(
			'post_type'              => 'merler_dish',
			'post_status'            => 'any',
			'numberposts'            => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( empty( $existing ) ) {
		$file = MERLER_DIR . 'data/menu-data.json';
		if ( file_exists( $file ) ) {
			Merler_Importer::import_file( $file );
		}
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'merler_activate' );

/**
 * Деактивация.
 */
function merler_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'merler_deactivate' );

/**
 * Размеры фотографий блюд — в плагине, чтобы не зависеть от темы.
 */
function merler_image_sizes() {
	add_image_size( 'merler-card', 600, 450, true );
	add_image_size( 'merler-card-2x', 1200, 900, true );
	add_image_size( 'merler-thumb', 220, 220, true );
}
