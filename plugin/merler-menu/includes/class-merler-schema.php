<?php
/**
 * JSON-LD и метатеги для поиска и мессенджеров.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Разметка schema.org и Open Graph.
 */
class Merler_Schema {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'output' ), 5 );
	}

	/**
	 * Выводить ли разметку на этой странице.
	 *
	 * @return bool
	 */
	private static function is_menu_page() {
		if ( is_front_page() ) {
			return true;
		}
		return is_page() && ( is_page_template( 'page-menu.php' ) || is_page_template( 'templates/page-menu.php' ) );
	}

	/**
	 * Вывод.
	 */
	public static function output() {
		if ( ! self::is_menu_page() ) {
			return;
		}

		$menu = Merler_Query::get_menu();
		if ( empty( $menu ) ) {
			return;
		}

		$name        = get_bloginfo( 'name' );
		$url         = merler_menu_url();
		$phone       = merler_option( 'phone' );
		$address     = merler_option( 'address' );
		$building_id = (int) merler_option( 'building_id' );
		$image       = $building_id ? wp_get_attachment_image_url( $building_id, 'large' ) : '';

		$sections = array();
		foreach ( $menu as $section ) {
			$items = self::items( $section['dishes'] );

			$sub = array();
			foreach ( $section['children'] as $child ) {
				$sub[] = array(
					'@type'          => 'MenuSection',
					'name'           => $child['name'],
					'hasMenuItem'    => self::items( $child['dishes'] ),
				);
			}

			$entry = array(
				'@type' => 'MenuSection',
				'name'  => $section['name'],
			);
			if ( $items ) {
				$entry['hasMenuItem'] = $items;
			}
			if ( $sub ) {
				$entry['hasMenuSection'] = $sub;
			}

			$sections[] = $entry;
		}

		$restaurant = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Restaurant',
			'name'        => $name,
			'url'         => $url,
			'servesCuisine' => array( 'Европейская', 'Арабская', 'Дагестанская' ),
			'hasMenu'     => array(
				'@type'          => 'Menu',
				'name'           => merler_option( 'hero_title', __( 'Меню ресторана', 'merler-menu' ) ),
				'inLanguage'     => 'ru-RU',
				'hasMenuSection' => $sections,
			),
		);

		if ( $phone ) {
			$restaurant['telephone'] = $phone;
		}
		if ( $address ) {
			$restaurant['address'] = array(
				'@type'         => 'PostalAddress',
				'streetAddress' => $address,
			);
		}
		if ( $image ) {
			$restaurant['image'] = $image;
		}

		echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $restaurant, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";

		// Open Graph.
		$description = merler_option( 'greeting' );
		echo '<meta property="og:type" content="restaurant.menu">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $name ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
			echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		}
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}

	/**
	 * Блюда раздела в формате schema.org.
	 *
	 * @param array $dishes Блюда.
	 * @return array
	 */
	private static function items( $dishes ) {
		$out = array();

		foreach ( $dishes as $dish ) {
			if ( 'out_of_stock' === $dish['status'] ) {
				continue;
			}

			$item = array(
				'@type' => 'MenuItem',
				'name'  => $dish['title'],
			);

			if ( '' !== $dish['description'] ) {
				$item['description'] = $dish['description'];
			}

			if ( $dish['price'] > 0 ) {
				$item['offers'] = array(
					'@type'         => 'Offer',
					'price'         => (string) $dish['price'],
					'priceCurrency' => 'RUB',
				);
			}

			if ( '' !== $dish['weight'] ) {
				$item['suggestedServingSize'] = $dish['weight'];
			}

			if ( $dish['thumb_id'] ) {
				$src = wp_get_attachment_image_url( $dish['thumb_id'], 'merler-card' );
				if ( $src ) {
					$item['image'] = $src;
				}
			}

			$out[] = $item;
		}

		return $out;
	}
}
