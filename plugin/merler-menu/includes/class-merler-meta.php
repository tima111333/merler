<?php
/**
 * Мета-поля блюда.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Регистрация и санитизация полей блюда.
 */
class Merler_Meta {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Регистрация мета-полей.
	 */
	public static function register() {
		$auth = static function () {
			return current_user_can( 'edit_merler_dishes' );
		};

		register_post_meta(
			'merler_dish',
			'_merler_weight',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_weight' ),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			'merler_dish',
			'_merler_price',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_price' ),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			'merler_dish',
			'_merler_description',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_description' ),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			'merler_dish',
			'_merler_badges',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_badges' ),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			'merler_dish',
			'_merler_status',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( __CLASS__, 'sanitize_status' ),
				'auth_callback'     => $auth,
			)
		);
	}

	/**
	 * Вес: «330 г», «8 шт», «0,5 л».
	 *
	 * @param mixed $value Значение.
	 * @return string
	 */
	public static function sanitize_weight( $value ) {
		$value = sanitize_text_field( (string) $value );
		return mb_substr( trim( $value ), 0, 40 );
	}

	/**
	 * Цена — целое число рублей.
	 *
	 * @param mixed $value Значение.
	 * @return int
	 */
	public static function sanitize_price( $value ) {
		$value = preg_replace( '/[^\d]/', '', (string) $value );
		return max( 0, (int) $value );
	}

	/**
	 * Состав — многострочный текст без HTML.
	 *
	 * @param mixed $value Значение.
	 * @return string
	 */
	public static function sanitize_description( $value ) {
		$value = wp_strip_all_tags( (string) $value );
		$value = str_replace( array( "\r\n", "\r" ), "\n", $value );
		return mb_substr( trim( $value ), 0, 1000 );
	}

	/**
	 * Бейджи — только из известного списка.
	 *
	 * @param mixed $value Значение.
	 * @return array
	 */
	public static function sanitize_badges( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$allowed = array_keys( merler_badges() );
		$clean   = array();
		foreach ( $value as $badge ) {
			$badge = sanitize_key( $badge );
			if ( in_array( $badge, $allowed, true ) && ! in_array( $badge, $clean, true ) ) {
				$clean[] = $badge;
			}
		}
		return $clean;
	}

	/**
	 * Статус — только из известного списка.
	 *
	 * @param mixed $value Значение.
	 * @return string
	 */
	public static function sanitize_status( $value ) {
		$value = sanitize_key( (string) $value );
		return array_key_exists( $value, merler_statuses() ) ? $value : 'in_stock';
	}
}
