<?php
/**
 * Сброс кэша меню и совместимость с кэш-плагинами.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Кэш меню.
 */
class Merler_Cache {

	const VERSION_OPTION = 'merler_cache_version';

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'save_post_merler_dish', array( __CLASS__, 'flush' ) );
		add_action( 'deleted_post', array( __CLASS__, 'flush' ) );
		add_action( 'trashed_post', array( __CLASS__, 'flush' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'flush' ) );
		add_action( 'created_' . Merler_Taxonomy::TAX, array( __CLASS__, 'flush' ) );
		add_action( 'edited_' . Merler_Taxonomy::TAX, array( __CLASS__, 'flush' ) );
		add_action( 'delete_' . Merler_Taxonomy::TAX, array( __CLASS__, 'flush' ) );
		add_action( 'update_option_merler_settings', array( __CLASS__, 'flush' ) );
	}

	/**
	 * Версия кэша: меняется — старые transient'ы перестают использоваться.
	 *
	 * @return int
	 */
	public static function version() {
		$version = (int) get_option( self::VERSION_OPTION, 0 );
		if ( $version <= 0 ) {
			$version = 1;
			update_option( self::VERSION_OPTION, $version, false );
		}
		return $version;
	}

	/**
	 * Сбросить кэш меню и попросить кэш-плагины обновить страницу.
	 */
	public static function flush() {
		update_option( self::VERSION_OPTION, self::version() + 1, false );

		// Популярные кэш-плагины.
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache(); // WP Super Cache.
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain(); // WP Rocket.
		}
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' ); // LiteSpeed Cache.
		}
		if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
			autoptimizeCache::clearall();
		}

		/**
		 * Для любых других кэшей.
		 */
		do_action( 'merler_menu_flushed' );
	}
}
