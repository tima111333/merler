<?php
/**
 * Общие функции плагина.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Значение настройки меню.
 *
 * @param string $key     Ключ.
 * @param mixed  $default Значение по умолчанию.
 * @return mixed
 */
function merler_option( $key, $default = '' ) {
	$settings = Merler_Settings::get_all();
	return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
}

/**
 * Цена в рублях: «1 850 ₽».
 *
 * @param int|string $price Цена.
 * @return string
 */
function merler_price( $price ) {
	$price = (int) $price;
	if ( $price <= 0 ) {
		return '';
	}
	$currency = merler_option( 'currency', '₽' );
	return number_format_i18n( $price ) . ' ' . $currency;
}

/**
 * Список возможных бейджей.
 *
 * @return array
 */
function merler_badges() {
	return array(
		'chef'  => __( 'От шефа', 'merler-menu' ),
		'spicy' => __( 'Острое', 'merler-menu' ),
		'hit'   => __( 'Хит', 'merler-menu' ),
		'new'   => __( 'Новинка', 'merler-menu' ),
		'veg'   => __( 'Вегетарианское', 'merler-menu' ),
	);
}

/**
 * Список статусов блюда.
 *
 * @return array
 */
function merler_statuses() {
	return array(
		'in_stock'     => __( 'В наличии', 'merler-menu' ),
		'soon'         => __( 'Скоро', 'merler-menu' ),
		'out_of_stock' => __( 'Нет в наличии', 'merler-menu' ),
		'hidden'       => __( 'Скрыто', 'merler-menu' ),
	);
}

/**
 * Данные блюда для вывода.
 *
 * @param int|WP_Post $post Запись.
 * @return array
 */
function merler_get_dish( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}

	$badges     = get_post_meta( $post->ID, '_merler_badges', true );
	$all_badges = merler_badges();
	$badges     = is_array( $badges ) ? array_values( array_intersect( array_keys( $all_badges ), $badges ) ) : array();

	return array(
		'id'          => $post->ID,
		'slug'        => $post->post_name,
		'title'       => get_the_title( $post ),
		'weight'      => (string) get_post_meta( $post->ID, '_merler_weight', true ),
		'price'       => (int) get_post_meta( $post->ID, '_merler_price', true ),
		'description' => (string) get_post_meta( $post->ID, '_merler_description', true ),
		'badges'      => $badges,
		'status'      => merler_dish_status( $post->ID ),
		'order'       => (int) $post->menu_order,
		'thumb_id'    => (int) get_post_thumbnail_id( $post->ID ),
	);
}

/**
 * Статус блюда с подстраховкой на случай пустого значения.
 *
 * @param int $post_id ID записи.
 * @return string
 */
function merler_dish_status( $post_id ) {
	$status = (string) get_post_meta( $post_id, '_merler_status', true );
	return array_key_exists( $status, merler_statuses() ) ? $status : 'in_stock';
}

/**
 * Телефон в виде, пригодном для ссылки tel: / wa.me.
 *
 * @param string $phone Телефон.
 * @return string
 */
function merler_phone_digits( $phone ) {
	$digits = preg_replace( '/\D+/', '', (string) $phone );
	if ( '' === $digits ) {
		return '';
	}
	if ( '8' === substr( $digits, 0, 1 ) && 11 === strlen( $digits ) ) {
		$digits = '7' . substr( $digits, 1 );
	}
	return $digits;
}

/**
 * Абсолютный адрес страницы меню в punycode — для QR-кода и Open Graph.
 *
 * @return string
 */
function merler_menu_url() {
	$url = merler_option( 'menu_url', '' );
	if ( '' === $url ) {
		$url = home_url( '/' );
	}

	$parts = wp_parse_url( $url );
	if ( empty( $parts['host'] ) ) {
		return $url;
	}

	$host = $parts['host'];
	if ( preg_match( '/[^\x20-\x7f]/', $host ) ) {
		if ( function_exists( 'idn_to_ascii' ) ) {
			$ascii = idn_to_ascii( $host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46 );
		} else {
			require_once ABSPATH . WPINC . '/class-punycode.php';
			$ascii = false;
			if ( class_exists( 'Requests_IDNAEncoder' ) ) {
				$ascii = Requests_IDNAEncoder::encode( $host );
			} elseif ( class_exists( '\WpOrg\Requests\IdnaEncoder' ) ) {
				$ascii = \WpOrg\Requests\IdnaEncoder::encode( $host );
			}
		}
		if ( $ascii ) {
			$url = str_replace( $host, $ascii, $url );
		}
	}

	return $url;
}

/**
 * Есть ли у пользователя право управлять меню.
 *
 * @return bool
 */
function merler_user_can_manage() {
	return current_user_can( 'edit_merler_dishes' ) || current_user_can( 'manage_options' );
}

/**
 * Транслитерация названия в латинский адрес записи.
 *
 * WordPress из русского названия делает адрес вида «%d0%ba%d0%b0...». Гость его
 * не видит, но адрес — ключ импорта и экспорта меню, поэтому он должен быть читаемым.
 *
 * @param string $text Название.
 * @return string
 */
function merler_transliterate( $text ) {
	$map = array(
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
		'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
		'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
		'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
		'ә' => 'a', 'ғ' => 'g', 'қ' => 'k', 'ң' => 'n', 'ө' => 'o', 'ұ' => 'u', 'ү' => 'u',
		'һ' => 'h', 'і' => 'i',
	);

	$text = mb_strtolower( (string) $text );
	$text = strtr( $text, $map );

	return sanitize_title( $text );
}
