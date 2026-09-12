<?php
/**
 * Карточка блюда.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

$merler_dish = isset( $args['dish'] ) ? $args['dish'] : null;

if ( ! $merler_dish ) {
	return;
}

$merler_badges   = function_exists( 'merler_badges' ) ? merler_badges() : array();
$merler_statuses = function_exists( 'merler_statuses' ) ? merler_statuses() : array();
$merler_price    = function_exists( 'merler_price' ) ? merler_price( $merler_dish['price'] ) : $merler_dish['price'] . ' ₽';
$merler_has_photo = ! empty( $merler_dish['thumb_id'] );

$merler_status_label = '';
if ( in_array( $merler_dish['status'], array( 'soon', 'out_of_stock' ), true ) && isset( $merler_statuses[ $merler_dish['status'] ] ) ) {
	$merler_status_label = $merler_statuses[ $merler_dish['status'] ];
}

$merler_list_mode = (bool) merler_theme_option( 'no_photo_mode' );

$merler_photo = '';
if ( $merler_has_photo ) {
	$merler_photo = wp_get_attachment_image(
		$merler_dish['thumb_id'],
		'merler-card',
		false,
		array(
			'class'    => 'card-photo',
			'alt'      => $merler_dish['title'],
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);
}

$merler_full_photo = $merler_has_photo ? wp_get_attachment_image_url( $merler_dish['thumb_id'], 'merler-card-2x' ) : '';
?>
<?php if ( ! $merler_list_mode ) : ?>
<article class="card<?php echo $merler_has_photo ? '' : ' no-photo'; ?>"
	data-status="<?php echo esc_attr( $merler_dish['status'] ); ?>"
	data-title="<?php echo esc_attr( $merler_dish['title'] ); ?>"
	data-weight="<?php echo esc_attr( $merler_dish['weight'] ); ?>"
	data-price="<?php echo esc_attr( $merler_price ); ?>"
	data-desc="<?php echo esc_attr( $merler_dish['description'] ); ?>"
	data-photo="<?php echo esc_url( $merler_full_photo ); ?>"
	data-search="<?php echo esc_attr( mb_strtolower( $merler_dish['title'] . ' ' . $merler_dish['description'] ) ); ?>"
	tabindex="0" role="button"
	aria-label="<?php echo esc_attr( $merler_dish['title'] . ', ' . $merler_price ); ?>">

	<div class="card-media">
		<?php
		if ( $merler_photo ) {
			echo $merler_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo merler_theme_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		<?php if ( $merler_status_label ) : ?>
			<span class="status-flag"><?php echo esc_html( $merler_status_label ); ?></span>
		<?php endif; ?>
	</div>

	<div class="card-body">
		<?php if ( ! empty( $merler_dish['badges'] ) ) : ?>
			<div class="badges">
				<?php foreach ( $merler_dish['badges'] as $merler_badge ) : ?>
					<?php if ( isset( $merler_badges[ $merler_badge ] ) ) : ?>
						<span class="badge b-<?php echo esc_attr( $merler_badge ); ?>"><?php echo esc_html( $merler_badges[ $merler_badge ] ); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<h3 class="card-title"><?php echo esc_html( $merler_dish['title'] ); ?></h3>

		<?php if ( '' !== $merler_dish['description'] ) : ?>
			<p class="card-desc"><?php echo esc_html( $merler_dish['description'] ); ?></p>
		<?php endif; ?>

		<div class="card-foot">
			<?php if ( '' !== $merler_price ) : ?>
				<span class="card-price"><?php echo esc_html( $merler_price ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $merler_dish['weight'] ) : ?>
				<span class="card-weight"><?php echo esc_html( $merler_dish['weight'] ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</article>
<?php else : ?>
<div class="row" data-search="<?php echo esc_attr( mb_strtolower( $merler_dish['title'] . ' ' . $merler_dish['description'] ) ); ?>">
	<span class="row-name"><?php echo esc_html( $merler_dish['title'] ); ?></span>
	<span class="row-dots" aria-hidden="true"></span>
	<?php if ( '' !== $merler_dish['weight'] ) : ?>
		<span class="row-weight"><?php echo esc_html( $merler_dish['weight'] ); ?></span>
	<?php endif; ?>
	<span class="row-price"><?php echo esc_html( $merler_price ); ?></span>
	<?php if ( $merler_status_label ) : ?>
		<span class="row-status"><?php echo esc_html( $merler_status_label ); ?></span>
	<?php endif; ?>
</div>
<?php endif; ?>
