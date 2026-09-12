<?php
/**
 * Отзывы гостей: приём, хранение, письмо.
 *
 * @package Merler_Menu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Отзывы.
 */
class Merler_Reviews {

	const LIMIT_PER_HOUR = 3;

	/**
	 * Хуки.
	 */
	public static function init() {
		add_action( 'wp_ajax_merler_review', array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_merler_review', array( __CLASS__, 'handle' ) );

		if ( is_admin() ) {
			add_filter( 'manage_merler_review_posts_columns', array( __CLASS__, 'columns' ) );
			add_action( 'manage_merler_review_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
			add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		}
	}

	/**
	 * Приём отзыва.
	 */
	public static function handle() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'merler_review' ) ) {
			wp_send_json_error( array( 'message' => __( 'Страница устарела. Обновите её и попробуйте снова.', 'merler-menu' ) ), 400 );
		}

		// Ловушка для ботов: поле скрыто от людей и должно остаться пустым.
		if ( ! empty( $_POST['merler_hp'] ) ) {
			wp_send_json_success( array( 'message' => __( 'Спасибо!', 'merler-menu' ) ) );
		}

		$ip_hash = self::ip_hash();
		$key     = 'merler_rl_' . $ip_hash;
		$count   = (int) get_transient( $key );
		if ( $count >= self::LIMIT_PER_HOUR ) {
			wp_send_json_error( array( 'message' => __( 'Спасибо, мы уже получили ваши отзывы. Напишите позже или позвоните нам.', 'merler-menu' ) ), 429 );
		}

		$rating = isset( $_POST['rating'] ) ? (int) $_POST['rating'] : 0;
		$rating = ( $rating >= 1 && $rating <= 5 ) ? $rating : 0;

		$text = isset( $_POST['text'] ) ? wp_strip_all_tags( wp_unslash( $_POST['text'] ) ) : '';
		$text = mb_substr( trim( $text ), 0, 2000 );

		$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$phone   = mb_substr( $phone, 0, 30 );
		$consent = ! empty( $_POST['consent'] ) ? 1 : 0;

		if ( ! $rating && '' === $text ) {
			wp_send_json_error( array( 'message' => __( 'Поставьте оценку или напишите пару слов.', 'merler-menu' ) ), 400 );
		}

		if ( '' !== $phone && ! $consent ) {
			wp_send_json_error( array( 'message' => __( 'Чтобы оставить телефон, нужно согласие на обработку данных.', 'merler-menu' ) ), 400 );
		}

		$title = sprintf(
			/* translators: 1: rating, 2: date */
			__( 'Оценка %1$d — %2$s', 'merler-menu' ),
			$rating,
			wp_date( 'd.m.Y H:i' )
		);

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'merler_review',
				'post_title'   => $title,
				'post_content' => $text,
				'post_status'  => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Не получилось сохранить отзыв. Попробуйте позже.', 'merler-menu' ) ), 500 );
		}

		update_post_meta( $post_id, '_merler_rating', $rating );
		update_post_meta( $post_id, '_merler_phone', $phone );
		update_post_meta( $post_id, '_merler_consent', $consent );
		update_post_meta( $post_id, '_merler_ip_hash', $ip_hash );
		update_post_meta( $post_id, '_merler_ua', isset( $_SERVER['HTTP_USER_AGENT'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '' );

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );

		self::notify( $rating, $text, $phone );

		wp_send_json_success( array( 'message' => __( 'Спасибо! Мы прочитаем каждое слово.', 'merler-menu' ) ) );
	}

	/**
	 * Письмо администратору.
	 *
	 * @param int    $rating Оценка.
	 * @param string $text   Текст.
	 * @param string $phone  Телефон.
	 */
	private static function notify( $rating, $text, $phone ) {
		$to = merler_option( 'review_email', get_option( 'admin_email' ) );
		if ( ! is_email( $to ) ) {
			return;
		}

		$subject = sprintf(
			/* translators: %d: rating */
			__( 'Отзыв о ресторане: %d из 5', 'merler-menu' ),
			$rating
		);

		$lines = array(
			sprintf( __( 'Оценка: %d из 5', 'merler-menu' ), $rating ),
			'',
			'' !== $text ? $text : __( '(без текста)', 'merler-menu' ),
			'',
			'' !== $phone ? sprintf( __( 'Телефон гостя: %s', 'merler-menu' ), $phone ) : __( 'Телефон не указан', 'merler-menu' ),
			'',
			sprintf( __( 'Все отзывы: %s', 'merler-menu' ), admin_url( 'edit.php?post_type=merler_review' ) ),
		);

		wp_mail( $to, $subject, implode( "\n", $lines ) );
	}

	/**
	 * Обезличенный отпечаток IP — сам адрес не храним.
	 *
	 * @return string
	 */
	private static function ip_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return substr( wp_hash( $ip . '|merler-review' ), 0, 24 );
	}

	/**
	 * Колонки списка отзывов.
	 *
	 * @param array $columns Колонки.
	 * @return array
	 */
	public static function columns( $columns ) {
		return array(
			'cb'             => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'          => __( 'Отзыв', 'merler-menu' ),
			'merler_rating'  => __( 'Оценка', 'merler-menu' ),
			'merler_text'    => __( 'Текст', 'merler-menu' ),
			'merler_phone'   => __( 'Телефон', 'merler-menu' ),
			'date'           => __( 'Когда', 'merler-menu' ),
		);
	}

	/**
	 * Содержимое колонок.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id ID записи.
	 */
	public static function column( $column, $post_id ) {
		if ( 'merler_rating' === $column ) {
			$rating = (int) get_post_meta( $post_id, '_merler_rating', true );
			echo esc_html( $rating ? str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) : '—' );
		}
		if ( 'merler_text' === $column ) {
			$post = get_post( $post_id );
			echo esc_html( $post ? wp_trim_words( $post->post_content, 18 ) : '' );
		}
		if ( 'merler_phone' === $column ) {
			$phone = (string) get_post_meta( $post_id, '_merler_phone', true );
			echo $phone ? esc_html( $phone ) : '—';
		}
	}

	/**
	 * Метабокс с деталями отзыва.
	 */
	public static function meta_box() {
		add_meta_box(
			'merler_review_details',
			__( 'Детали отзыва', 'merler-menu' ),
			array( __CLASS__, 'render_meta_box' ),
			'merler_review',
			'side'
		);
	}

	/**
	 * Вывод метабокса.
	 *
	 * @param WP_Post $post Запись.
	 */
	public static function render_meta_box( $post ) {
		$rating  = (int) get_post_meta( $post->ID, '_merler_rating', true );
		$phone   = (string) get_post_meta( $post->ID, '_merler_phone', true );
		$consent = (int) get_post_meta( $post->ID, '_merler_consent', true );
		?>
		<p><strong><?php esc_html_e( 'Оценка:', 'merler-menu' ); ?></strong> <?php echo esc_html( $rating ? $rating . ' / 5' : '—' ); ?></p>
		<p><strong><?php esc_html_e( 'Телефон:', 'merler-menu' ); ?></strong>
			<?php if ( $phone ) : ?>
				<a href="tel:<?php echo esc_attr( merler_phone_digits( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
			<?php else : ?>
				—
			<?php endif; ?>
		</p>
		<p><strong><?php esc_html_e( 'Согласие на обработку данных:', 'merler-menu' ); ?></strong> <?php echo $consent ? esc_html__( 'да', 'merler-menu' ) : esc_html__( 'нет', 'merler-menu' ); ?></p>
		<?php
	}
}
