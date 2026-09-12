<?php
/**
 * Подраздел меню (например, «Хинкалы» внутри дагестанской кухни).
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

$merler_sub    = isset( $args['subsection'] ) ? $args['subsection'] : null;
$merler_parent = isset( $args['parent_name'] ) ? $args['parent_name'] : '';

if ( ! $merler_sub || empty( $merler_sub['dishes'] ) ) {
	return;
}
?>
<div class="subsection" id="sub-<?php echo esc_attr( $merler_sub['slug'] ); ?>">
	<h3 class="subsection-title">
		<?php echo esc_html( $merler_sub['name'] ); ?>
		<?php if ( ! empty( $merler_sub['note'] ) ) : ?>
			<span class="subsection-note"><?php echo esc_html( $merler_sub['note'] ); ?></span>
		<?php endif; ?>
	</h3>

	<div class="grid">
		<?php foreach ( $merler_sub['dishes'] as $merler_dish ) : ?>
			<?php
			get_template_part(
				'template-parts/dish-card',
				null,
				array(
					'dish'         => $merler_dish,
					'search_extra' => trim( $merler_sub['name'] . ' ' . $merler_parent ),
				)
			);
			?>
		<?php endforeach; ?>
	</div>
</div>
