<?php
/**
 * Подробное окно блюда: модальное на компьютере, выезжающая панель на телефоне.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="overlay" hidden></div>

<aside class="sheet" role="dialog" aria-modal="true" aria-labelledby="merler-sheet-title" hidden>
	<div class="sheet-grab" aria-hidden="true"></div>
	<button class="sheet-close" type="button" aria-label="<?php esc_attr_e( 'Закрыть', 'merler-theme' ); ?>">×</button>

	<div class="sheet-media">
		<?php echo merler_theme_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<img class="sheet-photo" src="" alt="" hidden>
	</div>

	<div class="sheet-body">
		<h2 class="sheet-title" id="merler-sheet-title"></h2>
		<div class="sheet-foot">
			<span class="sheet-price"></span>
			<span class="sheet-weight"></span>
		</div>
		<p class="sheet-desc"></p>
	</div>
</aside>
