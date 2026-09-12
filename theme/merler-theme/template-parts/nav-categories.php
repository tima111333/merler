<?php
/**
 * Прилипающая лента категорий и поиск.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

$merler_sections = isset( $args['menu'] ) ? $args['menu'] : array();
$merler_search   = (bool) merler_theme_option( 'enable_search', 1 );
?>
<nav class="navbar" aria-label="<?php esc_attr_e( 'Разделы меню', 'merler-theme' ); ?>">
	<div class="navbar-inner">
		<div class="chips" id="merler-chips" role="list">
			<?php foreach ( $merler_sections as $merler_index => $merler_section ) : ?>
				<a class="chip<?php echo 0 === $merler_index ? ' is-active' : ''; ?>"
					role="listitem"
					href="#sec-<?php echo esc_attr( $merler_section['slug'] ); ?>">
					<?php echo esc_html( $merler_section['name'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>

		<span class="chips-caption" aria-hidden="true"><?php esc_html_e( 'Разделы меню', 'merler-theme' ); ?></span>

		<button class="icon-btn js-chips-toggle" type="button" aria-expanded="false" aria-controls="merler-chips"
			aria-label="<?php esc_attr_e( 'Показать все разделы', 'merler-theme' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
				<path d="M6 9l6 6 6-6"></path>
			</svg>
		</button>

		<?php if ( $merler_search ) : ?>
			<button class="icon-btn js-search" type="button" aria-expanded="false" aria-controls="merler-search" aria-label="<?php esc_attr_e( 'Поиск по меню', 'merler-theme' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
					<circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path>
				</svg>
			</button>
		<?php endif; ?>
	</div>

	<?php if ( $merler_search ) : ?>
		<div class="searchbar" id="merler-search">
			<input type="search" class="js-search-input"
				placeholder="<?php esc_attr_e( 'Найти блюдо или ингредиент…', 'merler-theme' ); ?>"
				aria-label="<?php esc_attr_e( 'Поиск по меню', 'merler-theme' ); ?>">
		</div>
	<?php endif; ?>
</nav>
