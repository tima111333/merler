<?php
/**
 * Типы записей: блюда и отзывы.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Регистрация типов записей.
 */
class Merler_Post_Type {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_gutenberg' ), 10, 2 );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
	}

	/**
	 * Типы записей.
	 */
	public static function register() {
		$dish_labels = array(
			'name'               => __( 'Блюда', 'merler-menu' ),
			'singular_name'      => __( 'Блюдо', 'merler-menu' ),
			'menu_name'          => __( 'Меню ресторана', 'merler-menu' ),
			'add_new'            => __( 'Добавить блюдо', 'merler-menu' ),
			'add_new_item'       => __( 'Новое блюдо', 'merler-menu' ),
			'edit_item'          => __( 'Редактировать блюдо', 'merler-menu' ),
			'new_item'           => __( 'Новое блюдо', 'merler-menu' ),
			'view_item'          => __( 'Посмотреть блюдо', 'merler-menu' ),
			'search_items'       => __( 'Искать блюда', 'merler-menu' ),
			'not_found'          => __( 'Блюд пока нет', 'merler-menu' ),
			'not_found_in_trash' => __( 'В корзине пусто', 'merler-menu' ),
			'all_items'          => __( 'Все блюда', 'merler-menu' ),
			'featured_image'     => __( 'Фото блюда', 'merler-menu' ),
			'set_featured_image' => __( 'Загрузить фото', 'merler-menu' ),
			'remove_featured_image' => __( 'Убрать фото', 'merler-menu' ),
			'use_featured_image' => __( 'Использовать как фото блюда', 'merler-menu' ),
		);

		register_post_type(
			'merler_dish',
			array(
				'labels'              => $dish_labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_position'       => 20,
				'menu_icon'           => 'dashicons-carrot',
				'hierarchical'        => false,
				'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'capability_type'     => array( 'merler_dish', 'merler_dishes' ),
				'map_meta_cap'        => true,
			)
		);

		register_post_type(
			'merler_review',
			array(
				'labels'              => array(
					'name'          => __( 'Отзывы', 'merler-menu' ),
					'singular_name' => __( 'Отзыв', 'merler-menu' ),
					'menu_name'     => __( 'Отзывы', 'merler-menu' ),
					'all_items'     => __( 'Отзывы гостей', 'merler-menu' ),
					'edit_item'     => __( 'Отзыв', 'merler-menu' ),
					'search_items'  => __( 'Искать отзывы', 'merler-menu' ),
					'not_found'     => __( 'Отзывов пока нет', 'merler-menu' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=merler_dish',
				'show_in_rest'        => false,
				'supports'            => array( 'title', 'editor' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'capability_type'     => array( 'merler_review', 'merler_reviews' ),
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Блочный редактор для блюд не нужен — форма должна быть простой.
	 *
	 * @param bool   $use       Использовать ли Gutenberg.
	 * @param string $post_type Тип записи.
	 * @return bool
	 */
	public static function disable_gutenberg( $use, $post_type ) {
		if ( 'merler_dish' === $post_type || 'merler_review' === $post_type ) {
			return false;
		}
		return $use;
	}

	/**
	 * Подсказка в поле заголовка.
	 *
	 * @param string  $text Текст.
	 * @param WP_Post $post Запись.
	 * @return string
	 */
	public static function title_placeholder( $text, $post ) {
		if ( $post instanceof WP_Post && 'merler_dish' === $post->post_type ) {
			return __( 'Название блюда, например «Хинкал аварский»', 'merler-menu' );
		}
		return $text;
	}
}
