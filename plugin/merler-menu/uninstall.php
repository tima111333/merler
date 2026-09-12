<?php
/**
 * Удаление плагина: чистим настройки и роль. Блюда и отзывы остаются в базе.
 *
 * @package Merler_Menu
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'merler_settings' );
delete_option( 'merler_cache_version' );

// Роль «Менеджер меню» и выданные права.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-merler-roles.php';
if ( class_exists( 'Merler_Roles' ) ) {
	Merler_Roles::remove_all();
}

// Временные записи кэша.
global $wpdb;
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_merler_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_merler_' ) . '%'
	)
);
