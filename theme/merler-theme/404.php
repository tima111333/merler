<?php
/**
 * Страница не найдена.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="wrap page-simple" id="menu-content">
	<h1 class="page-title"><?php esc_html_e( 'Такой страницы нет', 'merler-theme' ); ?></h1>
	<p><?php esc_html_e( 'Возможно, ссылка устарела. Меню ресторана открывается по кнопке ниже.', 'merler-theme' ); ?></p>
	<p class="page-back"><a class="button-primary-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Открыть меню', 'merler-theme' ); ?></a></p>
</main>

<?php
get_footer();
