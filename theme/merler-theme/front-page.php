<?php
/**
 * Главная страница — меню ресторана.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$merler_menu = merler_theme_menu();

get_template_part( 'template-parts/hero' );

if ( empty( $merler_menu ) ) : ?>
	<main class="wrap" id="menu-content">
		<p class="menu-empty">
			<?php esc_html_e( 'Меню готовится. Загляните чуть позже или позвоните нам.', 'merler-theme' ); ?>
		</p>
	</main>
	<?php
else :
	get_template_part( 'template-parts/menu-index', null, array( 'menu' => $merler_menu ) );
	get_template_part( 'template-parts/nav-categories', null, array( 'menu' => $merler_menu ) );
	?>
	<main class="wrap" id="menu-content">
		<p class="no-results" hidden><?php esc_html_e( 'Ничего не нашлось. Попробуйте другое слово.', 'merler-theme' ); ?></p>

		<?php
		foreach ( $merler_menu as $merler_section ) {
			get_template_part( 'template-parts/section', null, array( 'section' => $merler_section ) );
		}

		get_template_part( 'template-parts/about' );
		?>
	</main>
	<?php
endif;

get_footer();
