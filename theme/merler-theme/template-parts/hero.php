<?php
/**
 * Первый экран: логотип, иллюстрация здания, приветствие.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

$merler_logo     = merler_theme_image( 'logo_id', 'medium', array( 'class' => 'logo', 'alt' => get_bloginfo( 'name' ) ) );
$merler_building = merler_theme_image( 'building_id', 'large', array( 'class' => 'building hero-building', 'alt' => '' ) );
$merler_kicker   = merler_theme_option( 'hero_title', __( 'Меню ресторана', 'merler-theme' ) );
$merler_greeting = merler_theme_option( 'greeting' );
?>
<header class="wrap hero">
	<div class="hero-left">
		<div class="hero-logo">
			<?php if ( $merler_logo ) : ?>
				<?php echo $merler_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<span class="hero-word"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
				<span class="hero-sub"><?php esc_html_e( 'Бутик-отель', 'merler-theme' ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( $merler_kicker ) : ?>
			<p class="hero-kicker"><?php echo esc_html( $merler_kicker ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( $merler_building ) : ?>
		<?php echo $merler_building; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>

	<?php if ( $merler_greeting ) : ?>
		<p class="hero-greet"><?php echo esc_html( $merler_greeting ); ?></p>
	<?php endif; ?>
</header>
