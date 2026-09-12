<?php
/**
 * Подвал.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="site-footer">
	<div class="footer-mark">
		<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-mark.png' ); ?>" width="140" height="104" alt="" loading="lazy">
	</div>
	<p>
		<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
		<?php
		$hours = merler_theme_option( 'hours' );
		if ( $hours ) :
			?>
			· <?php echo esc_html( $hours ); ?>
		<?php endif; ?>
	</p>
</footer>

<button class="fab fab-top" type="button" aria-label="<?php esc_attr_e( 'Наверх', 'merler-theme' ); ?>">↑</button>

<?php if ( merler_theme_option( 'enable_rating' ) ) : ?>
	<?php get_template_part( 'template-parts/rate' ); ?>
<?php endif; ?>

<?php get_template_part( 'template-parts/dish-modal' ); ?>

<?php wp_footer(); ?>
</body>
</html>
