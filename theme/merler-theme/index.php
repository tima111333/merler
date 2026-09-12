<?php
/**
 * Запасной шаблон: сайт-меню состоит из одной страницы.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="wrap page-simple" id="menu-content">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article class="page-article">
			<h1 class="page-title"><?php the_title(); ?></h1>
			<div class="page-content"><?php the_content(); ?></div>
		</article>
		<?php
	endwhile;
	?>

	<p class="page-back"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '← К меню ресторана', 'merler-theme' ); ?></a></p>
</main>

<?php
get_footer();
