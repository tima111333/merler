<?php
/**
 * Кнопка «Оценить» и окно с отзывом.
 *
 * @package Merler_Theme
 */

defined( 'ABSPATH' ) || exit;

$merler_review_url  = merler_theme_option( 'review_url' );
$merler_privacy_url = merler_theme_option( 'privacy_url' );
?>
<button class="fab fab-rate" type="button" aria-haspopup="dialog" aria-label="<?php esc_attr_e( 'Оценить ресторан', 'merler-theme' ); ?>">
	<span aria-hidden="true">⭐</span><span class="fab-label"> <?php esc_html_e( 'Оценить', 'merler-theme' ); ?></span>
</button>

<div class="overlay overlay-rate" hidden></div>

<aside class="rate-dialog" role="dialog" aria-modal="true" aria-labelledby="merler-rate-title" hidden>
	<button class="sheet-close" type="button" aria-label="<?php esc_attr_e( 'Закрыть', 'merler-theme' ); ?>">×</button>

	<div class="rate-body">
		<h2 class="rate-title" id="merler-rate-title"><?php esc_html_e( 'Как вам у нас?', 'merler-theme' ); ?></h2>

		<div class="stars" role="radiogroup" aria-label="<?php esc_attr_e( 'Оценка от 1 до 5', 'merler-theme' ); ?>">
			<?php for ( $merler_i = 1; $merler_i <= 5; $merler_i++ ) : ?>
				<button type="button" class="star" role="radio" aria-checked="false"
					data-value="<?php echo esc_attr( (string) $merler_i ); ?>"
					aria-label="<?php
					/* translators: %d: number of stars */
					echo esc_attr( sprintf( _n( '%d звезда', '%d звёзд', $merler_i, 'merler-theme' ), $merler_i ) );
					?>">★</button>
			<?php endfor; ?>
		</div>

		<div class="rate-actions" hidden>
			<?php if ( $merler_review_url ) : ?>
				<a class="rate-btn rate-btn-primary" href="<?php echo esc_url( $merler_review_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Оставить отзыв на картах', 'merler-theme' ); ?>
				</a>
			<?php endif; ?>
			<button type="button" class="rate-btn js-rate-direct"><?php esc_html_e( 'Написать нам напрямую', 'merler-theme' ); ?></button>
		</div>

		<form class="rate-form" hidden>
			<label class="rate-label" for="merler-rate-text"><?php esc_html_e( 'Что понравилось или что поправить?', 'merler-theme' ); ?></label>
			<textarea id="merler-rate-text" name="text" rows="4" maxlength="2000"></textarea>

			<label class="rate-label" for="merler-rate-phone"><?php esc_html_e( 'Телефон, если хотите ответа (необязательно)', 'merler-theme' ); ?></label>
			<input type="tel" id="merler-rate-phone" name="phone" maxlength="30" autocomplete="tel">

			<label class="rate-consent" hidden>
				<input type="checkbox" name="consent" value="1">
				<span>
					<?php esc_html_e( 'Согласен на обработку персональных данных', 'merler-theme' ); ?>
					<?php if ( $merler_privacy_url ) : ?>
						<a href="<?php echo esc_url( $merler_privacy_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'политика', 'merler-theme' ); ?></a>
					<?php endif; ?>
				</span>
			</label>

			<div class="rate-hp" aria-hidden="true">
				<label><?php esc_html_e( 'Не заполняйте это поле', 'merler-theme' ); ?>
					<input type="text" name="merler_hp" tabindex="-1" autocomplete="off">
				</label>
			</div>

			<button type="submit" class="rate-btn rate-btn-primary"><?php esc_html_e( 'Отправить', 'merler-theme' ); ?></button>
			<p class="rate-message" role="status" aria-live="polite"></p>
		</form>
	</div>
</aside>
