<?php
/**
 * Разделы меню на первом экране: все названия сразу, в потоке страницы.
 *
 * Прилипающая лента (nav-categories.php) появляется позже, когда гость
 * пролистает этот блок.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

$merler_sections = isset( $args['menu'] ) ? $args['menu'] : array();

if ( empty( $merler_sections ) ) {
	return;
}
?>
<nav class="wrap menu-index" aria-label="<?php esc_attr_e( 'Разделы меню', 'merler-theme' ); ?>">
	<?php foreach ( $merler_sections as $merler_section ) : ?>
		<a class="chip" href="#sec-<?php echo esc_attr( $merler_section['slug'] ); ?>">
			<?php echo esc_html( $merler_section['name'] ); ?>
		</a>
	<?php endforeach; ?>
</nav>
