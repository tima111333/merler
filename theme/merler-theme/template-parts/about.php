<?php
/**
 * Блок «О нас и контакты».
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

$merler_phone     = merler_theme_option( 'phone' );
$merler_whatsapp  = merler_theme_option( 'whatsapp' );
$merler_instagram = merler_theme_option( 'instagram' );
$merler_address   = merler_theme_option( 'address' );
$merler_map       = merler_theme_option( 'map_url' );
$merler_hours     = merler_theme_option( 'hours' );
$merler_building  = merler_theme_image( 'building_id', 'medium', array( 'class' => 'building', 'alt' => '' ) );

$merler_phone_link = function_exists( 'merler_phone_digits' ) ? merler_phone_digits( $merler_phone ) : preg_replace( '/\D+/', '', (string) $merler_phone );
$merler_wa_link    = function_exists( 'merler_phone_digits' ) ? merler_phone_digits( $merler_whatsapp ) : preg_replace( '/\D+/', '', (string) $merler_whatsapp );
?>
<section class="about" id="about">
	<div class="about-text">
		<h2><?php echo esc_html( merler_theme_option( 'about_title', __( 'О ресторане', 'merler-theme' ) ) ); ?></h2>

		<?php if ( merler_theme_option( 'about_text' ) ) : ?>
			<p><?php echo esc_html( merler_theme_option( 'about_text' ) ); ?></p>
		<?php endif; ?>

		<?php if ( $merler_hours ) : ?>
			<p class="about-hours"><?php echo esc_html( $merler_hours ); ?></p>
		<?php endif; ?>

		<?php if ( $merler_address ) : ?>
			<p class="about-address">
				<?php if ( $merler_map ) : ?>
					<a href="<?php echo esc_url( $merler_map ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $merler_address ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $merler_address ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<?php if ( merler_theme_option( 'wifi_show' ) && merler_theme_option( 'wifi_name' ) ) : ?>
			<p class="about-wifi">
				<?php
				printf(
					/* translators: 1: wifi name, 2: wifi password */
					esc_html__( 'Wi-Fi: %1$s · пароль %2$s', 'merler-theme' ),
					esc_html( merler_theme_option( 'wifi_name' ) ),
					esc_html( merler_theme_option( 'wifi_pass' ) )
				);
				?>
			</p>
		<?php endif; ?>

		<div class="contacts">
			<?php if ( $merler_phone_link ) : ?>
				<a class="primary" href="tel:+<?php echo esc_attr( $merler_phone_link ); ?>"><?php echo esc_html( $merler_phone ); ?></a>
			<?php endif; ?>

			<?php if ( $merler_wa_link ) : ?>
				<a href="https://wa.me/<?php echo esc_attr( $merler_wa_link ); ?>" target="_blank" rel="noopener">WhatsApp</a>
			<?php endif; ?>

			<?php if ( $merler_instagram ) : ?>
				<a href="https://instagram.com/<?php echo esc_attr( ltrim( $merler_instagram, '@' ) ); ?>" target="_blank" rel="noopener">Instagram</a>
			<?php endif; ?>

			<?php if ( $merler_map ) : ?>
				<a href="<?php echo esc_url( $merler_map ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'На карте', 'merler-theme' ); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $merler_building ) : ?>
		<div class="about-image">
			<?php echo $merler_building; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>
</section>
