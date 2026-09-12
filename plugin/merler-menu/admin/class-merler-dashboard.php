<?php
/**
 * Виджет консоли и страница-инструкция.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Подсказки клиенту.
 */
class Merler_Dashboard {

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'widget' ) );
	}

	/**
	 * Виджет на главной странице консоли.
	 */
	public static function widget() {
		if ( ! current_user_can( 'edit_merler_dishes' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'merler_help_widget',
			__( 'Как редактировать меню', 'merler-menu' ),
			array( __CLASS__, 'render_widget' )
		);
	}

	/**
	 * Содержимое виджета.
	 */
	public static function render_widget() {
		$dishes = wp_count_posts( 'merler_dish' );
		$count  = isset( $dishes->publish ) ? (int) $dishes->publish : 0;
		?>
		<p>
			<?php
			printf(
				/* translators: %d: number of dishes */
				esc_html( _n( 'Сейчас на сайте %d блюдо.', 'Сейчас на сайте %d блюд.', $count, 'merler-menu' ) ),
				$count
			);
			?>
		</p>
		<ol class="merler-steps">
			<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=merler_dish' ) ); ?>"><?php esc_html_e( 'Поменять цену', 'merler-menu' ); ?></a> — <?php esc_html_e( 'в списке блюд наведите на название, нажмите «Свойства», исправьте цену, «Обновить».', 'merler-menu' ); ?></li>
			<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=merler_dish' ) ); ?>"><?php esc_html_e( 'Добавить блюдо', 'merler-menu' ); ?></a> — <?php esc_html_e( 'название, цена, вес, состав, раздел, фото. «Опубликовать».', 'merler-menu' ); ?></li>
			<li><?php esc_html_e( 'Поставить на стоп', 'merler-menu' ); ?> — <?php esc_html_e( 'в списке отметьте блюда галочками, «Действия» → «Поставить на стоп».', 'merler-menu' ); ?></li>
			<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=merler_dish&page=merler-order' ) ); ?>"><?php esc_html_e( 'Поменять порядок', 'merler-menu' ); ?></a> — <?php esc_html_e( 'перетащите блюда мышкой.', 'merler-menu' ); ?></li>
			<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=merler_dish&page=merler-settings' ) ); ?>"><?php esc_html_e( 'Телефон, адрес, картинки', 'merler-menu' ); ?></a> — <?php esc_html_e( 'страница «Настройки меню».', 'merler-menu' ); ?></li>
			<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=merler_dish&page=merler-tools' ) ); ?>"><?php esc_html_e( 'Сделать резервную копию', 'merler-menu' ); ?></a> — <?php esc_html_e( 'кнопка «Скачать меню в JSON».', 'merler-menu' ); ?></li>
			<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=merler_dish&page=merler-help' ) ); ?>"><?php esc_html_e( 'Полная инструкция', 'merler-menu' ); ?></a></li>
		</ol>
		<?php
	}

	/**
	 * Страница «Инструкция».
	 */
	public static function render_help_page() {
		if ( ! current_user_can( 'edit_merler_dishes' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'merler-menu' ) );
		}
		?>
		<div class="wrap merler-help">
			<h1><?php esc_html_e( 'Как редактировать меню', 'merler-menu' ); ?></h1>

			<h2><?php esc_html_e( 'Поменять цену', 'merler-menu' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Откройте «Меню ресторана» → «Все блюда».', 'merler-menu' ); ?></li>
				<li><?php esc_html_e( 'Наведите мышку на название блюда и нажмите «Свойства».', 'merler-menu' ); ?></li>
				<li><?php esc_html_e( 'Исправьте цену и нажмите «Обновить». Страница меню обновится сразу.', 'merler-menu' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'Добавить блюдо', 'merler-menu' ); ?></h2>
			<ol>
				<li><?php esc_html_e( '«Меню ресторана» → «Добавить блюдо».', 'merler-menu' ); ?></li>
				<li><?php esc_html_e( 'Впишите название, цену, вес и состав.', 'merler-menu' ); ?></li>
				<li><?php esc_html_e( 'Справа выберите раздел меню и при желании загрузите фото.', 'merler-menu' ); ?></li>
				<li><?php esc_html_e( 'Нажмите «Опубликовать».', 'merler-menu' ); ?></li>
			</ol>

			<h2><?php esc_html_e( 'Поставить блюдо на стоп', 'merler-menu' ); ?></h2>
			<p><?php esc_html_e( 'В карточке блюда выберите статус «Нет в наличии» — карточка станет полупрозрачной с плашкой. Статус «Скрыто» убирает блюдо с сайта полностью. Несколько блюд можно перевести сразу: отметьте галочками и выберите действие над списком.', 'merler-menu' ); ?></p>

			<h2><?php esc_html_e( 'Поменять фото', 'merler-menu' ); ?></h2>
			<p><?php esc_html_e( 'Откройте блюдо, справа в блоке «Фото блюда» нажмите «Убрать фото», затем «Загрузить фото».', 'merler-menu' ); ?></p>
			<p><strong><?php esc_html_e( 'Требования к фото:', 'merler-menu' ); ?></strong> <?php esc_html_e( 'горизонтальное, соотношение примерно 4:3, ширина от 1200 пикселей, вес до 1 МБ. Снимайте сверху или под углом 45°, при дневном свете.', 'merler-menu' ); ?></p>

			<h2><?php esc_html_e( 'Поменять порядок', 'merler-menu' ); ?></h2>
			<p><?php esc_html_e( 'Страница «Порядок блюд»: перетащите блюдо или целый раздел мышкой за значок слева.', 'merler-menu' ); ?></p>

			<h2><?php esc_html_e( 'Поменять контакты и картинки', 'merler-menu' ); ?></h2>
			<p><?php esc_html_e( 'Страница «Настройки меню»: телефон, WhatsApp, адрес, часы работы, логотип, иллюстрация здания, цвета.', 'merler-menu' ); ?></p>

			<h2><?php esc_html_e( 'QR-код', 'merler-menu' ); ?></h2>
			<p><?php esc_html_e( 'Страница «QR-код»: скачайте SVG для типографии или готовую табличку A6. Код ведёт на постоянный адрес — при изменении блюд и цен перепечатывать его не нужно.', 'merler-menu' ); ?></p>

			<h2><?php esc_html_e( 'Если что-то сломалось', 'merler-menu' ); ?></h2>
			<p><?php esc_html_e( 'На странице «Импорт и экспорт» есть кнопка «Скачать меню в JSON» — делайте копию перед большими правками. Оттуда же меню восстанавливается обратно.', 'merler-menu' ); ?></p>
		</div>
		<?php
	}
}
