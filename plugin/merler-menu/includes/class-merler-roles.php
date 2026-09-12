<?php
/**
 * Роль «Менеджер меню» и права администратора.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Права доступа.
 */
class Merler_Roles {

	const ROLE = 'merler_manager';

	/**
	 * Права на блюда, разделы, настройки и отзывы.
	 *
	 * @return array
	 */
	public static function caps() {
		return array(
			// Блюда.
			'edit_merler_dish'              => true,
			'read_merler_dish'              => true,
			'delete_merler_dish'            => true,
			'edit_merler_dishes'            => true,
			'edit_others_merler_dishes'     => true,
			'publish_merler_dishes'         => true,
			'read_private_merler_dishes'    => true,
			'delete_merler_dishes'          => true,
			'delete_private_merler_dishes'  => true,
			'delete_published_merler_dishes' => true,
			'delete_others_merler_dishes'   => true,
			'edit_private_merler_dishes'    => true,
			'edit_published_merler_dishes'  => true,
			// Отзывы.
			'edit_merler_review'            => true,
			'read_merler_review'            => true,
			'delete_merler_review'          => true,
			'edit_merler_reviews'           => true,
			'edit_others_merler_reviews'    => true,
			'publish_merler_reviews'        => true,
			'read_private_merler_reviews'   => true,
			'delete_merler_reviews'         => true,
			'delete_private_merler_reviews' => true,
			'delete_published_merler_reviews' => true,
			'delete_others_merler_reviews'  => true,
			'edit_private_merler_reviews'   => true,
			'edit_published_merler_reviews' => true,
			// Разделы и настройки.
			'manage_merler_sections'        => true,
			'manage_merler_settings'        => true,
			// Минимум для входа в админку и работы с медиафайлами.
			'read'                          => true,
			'upload_files'                  => true,
		);
	}

	/**
	 * Создать роль «Менеджер меню».
	 */
	public static function add_role() {
		remove_role( self::ROLE );
		add_role( self::ROLE, __( 'Менеджер меню', 'merler-menu' ), self::caps() );
	}

	/**
	 * Выдать права администратору и редактору.
	 */
	public static function grant_admin_caps() {
		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( array_keys( self::caps() ) as $cap ) {
				if ( in_array( $cap, array( 'read', 'upload_files' ), true ) ) {
					continue;
				}
				if ( 'editor' === $role_name && 'manage_merler_settings' === $cap ) {
					continue;
				}
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Убрать роль и права (используется при удалении плагина).
	 */
	public static function remove_all() {
		remove_role( self::ROLE );

		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( array_keys( self::caps() ) as $cap ) {
				if ( in_array( $cap, array( 'read', 'upload_files' ), true ) ) {
					continue;
				}
				$role->remove_cap( $cap );
			}
		}
	}
}
