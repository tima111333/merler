<?php
/**
 * Раздел меню.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

$merler_section = isset( $args['section'] ) ? $args['section'] : null;

if ( ! $merler_section ) {
	return;
}
?>
<section class="section" id="sec-<?php echo esc_attr( $merler_section['slug'] ); ?>"
	aria-labelledby="h-<?php echo esc_attr( $merler_section['slug'] ); ?>">

	<h2 class="section-title" id="h-<?php echo esc_attr( $merler_section['slug'] ); ?>">
		<span><?php echo esc_html( $merler_section['name'] ); ?></span>
		<span class="orn" aria-hidden="true"></span>
	</h2>

	<?php if ( ! empty( $merler_section['note'] ) ) : ?>
		<p class="section-note"><?php echo esc_html( $merler_section['note'] ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $merler_section['dishes'] ) ) : ?>
		<div class="grid">
			<?php foreach ( $merler_section['dishes'] as $merler_dish ) : ?>
				<?php get_template_part( 'template-parts/dish-card', null, array( 'dish' => $merler_dish ) ); ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php foreach ( $merler_section['children'] as $merler_sub ) : ?>
		<?php get_template_part( 'template-parts/subsection', null, array( 'subsection' => $merler_sub ) ); ?>
	<?php endforeach; ?>
</section>
