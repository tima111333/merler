<?php
/**
 * Сборка структуры меню для вывода.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Меню одним запросом + кэш.
 */
class Merler_Query {

	/**
	 * Полная структура меню.
	 *
	 * Возвращает массив разделов:
	 * [ 'id', 'name', 'slug', 'note', 'dishes' => [], 'children' => [ то же самое ] ]
	 *
	 * @return array
	 */
	public static function get_menu() {
		$key    = 'merler_menu_' . Merler_Cache::version();
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$menu = self::build();
		set_transient( $key, $menu, DAY_IN_SECONDS );

		return $menu;
	}

	/**
	 * Собрать меню из базы.
	 *
	 * @return array
	 */
	private static function build() {
		$roots = Merler_Taxonomy::get_tree( true );
		if ( empty( $roots ) ) {
			return array();
		}

		$dishes = get_posts(
			array(
				'post_type'      => 'merler_dish',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'no_found_rows'  => true,
			)
		);

		// Раскладываем блюда по разделам.
		$by_term = array();
		foreach ( $dishes as $dish ) {
			$data = merler_get_dish( $dish );
			if ( 'hidden' === $data['status'] ) {
				continue;
			}

			$terms = wp_get_post_terms( $dish->ID, Merler_Taxonomy::TAX, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			// Блюдо показываем в самом глубоком из назначенных разделов.
			$term_id = self::deepest_term( $terms );
			$by_term[ $term_id ][] = $data;
		}

		$menu = array();
		foreach ( $roots as $root ) {
			$section = self::section_data( $root, $by_term );

			foreach ( $root->children as $child ) {
				$sub = self::section_data( $child, $by_term );
				if ( ! empty( $sub['dishes'] ) ) {
					$section['children'][] = $sub;
				}
			}

			if ( ! empty( $section['dishes'] ) || ! empty( $section['children'] ) ) {
				$menu[] = $section;
			}
		}

		return $menu;
	}

	/**
	 * Данные раздела.
	 *
	 * @param WP_Term $term    Термин.
	 * @param array   $by_term Блюда по разделам.
	 * @return array
	 */
	private static function section_data( $term, $by_term ) {
		return array(
			'id'       => (int) $term->term_id,
			'name'     => $term->name,
			'slug'     => $term->slug,
			'note'     => isset( $term->merler_note ) ? $term->merler_note : '',
			'dishes'   => isset( $by_term[ $term->term_id ] ) ? $by_term[ $term->term_id ] : array(),
			'children' => array(),
		);
	}

	/**
	 * Самый глубокий термин из списка.
	 *
	 * @param array $term_ids ID терминов.
	 * @return int
	 */
	private static function deepest_term( $term_ids ) {
		$best  = (int) $term_ids[0];
		$depth = -1;

		foreach ( $term_ids as $id ) {
			$term = get_term( (int) $id, Merler_Taxonomy::TAX );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}
			$d = $term->parent ? 1 : 0;
			if ( $d > $depth ) {
				$depth = $d;
				$best  = (int) $id;
			}
		}

		return $best;
	}

	/**
	 * Сколько всего блюд выводится.
	 *
	 * @return int
	 */
	public static function count_dishes() {
		$n = 0;
		foreach ( self::get_menu() as $section ) {
			$n += count( $section['dishes'] );
			foreach ( $section['children'] as $child ) {
				$n += count( $child['dishes'] );
			}
		}
		return $n;
	}
}
