/**
 * Выгрузка страницы меню в статику для GitHub Pages.
 * Берёт готовую страницу с локального WordPress и делает её самодостаточной:
 * относительные пути, без обращений к admin-ajax, без админских скриптов.
 *
 * Запуск: node tools/export-static.js
 */
const fs = require( 'fs' );
const path = require( 'path' );
const http = require( 'http' );

const BASE = process.env.MERLER_URL || 'http://localhost:8933';
const ROOT = path.join( __dirname, '..' );
const OUT = path.join( ROOT, 'build', 'demo' );
const THEME = path.join( ROOT, 'theme', 'merler-theme' );

/**
 * Забрать страницу.
 *
 * @param {string} url Адрес.
 * @return {Promise<string>} Тело ответа.
 */
function get( url ) {
	return new Promise( ( resolve, reject ) => {
		http.get( url, ( res ) => {
			if ( 200 !== res.statusCode ) {
				reject( new Error( 'HTTP ' + res.statusCode + ' — ' + url ) );
				return;
			}
			let body = '';
			res.setEncoding( 'utf8' );
			res.on( 'data', ( c ) => {
				body += c;
			} );
			res.on( 'end', () => resolve( body ) );
		} ).on( 'error', reject );
	} );
}

( async () => {
	fs.rmSync( OUT, { recursive: true, force: true } );
	fs.mkdirSync( OUT, { recursive: true } );

	let html = await get( BASE + '/' );

	// 1. Пути к ассетам темы делаем относительными.
	const themeUrl = BASE + '/wp-content/themes/merler-theme/';
	html = html.split( themeUrl ).join( 'assets-theme/' );
	html = html.split( '/wp-content/themes/merler-theme/' ).join( 'assets-theme/' );

	// 2. Выкидываем всё, что требует PHP: версии ассетов, emoji-скрипты, ссылки на админку.
	html = html.replace( /\?ver=[\w.\-]+/g, '' );
	html = html.replace( /<link rel=["']https:\/\/api\.w\.org[^>]*>/g, '' );
	html = html.replace( /<link rel=["'](EditURI|alternate|shortlink)["'][^>]*>/g, '' );
	html = html.replace( /<script[^>]*id=["']wp-emoji[^>]*>[\s\S]*?<\/script>/g, '' );
	html = html.replace( /<style[^>]*id=["']wp-emoji[^>]*>[\s\S]*?<\/style>/g, '' );
	html = html.replace( /<meta name=["']generator["'][^>]*>/g, '' );
	html = html.replace( /<script type=["']speculationrules["']>[\s\S]*?<\/script>/g, '' );

	// 3. Абсолютные ссылки на локальный сервер — на настоящий адрес меню либо на текущую страницу.
	html = html.split( BASE + '/wp-admin/admin-ajax.php' ).join( '' );
	html = html.split( BASE + '/' ).join( './' );
	html = html.split( BASE ).join( '.' );

	// 4. Пометка, что это демонстрация: отзыв отправить некуда, поэтому объясняем это гостю.
	const demoScript = `
<script>
/* Статическая демонстрация: WordPress и отправка отзывов остаются на боевом сайте. */
document.addEventListener( 'DOMContentLoaded', function () {
	var form = document.querySelector( '.rate-form' );
	if ( ! form ) {
		return;
	}
	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();
		var msg = form.querySelector( '.rate-message' );
		if ( msg ) {
			msg.textContent = 'Это демонстрация меню. На рабочем сайте отзыв уходит администратору письмом и сохраняется в админке.';
			msg.className = 'rate-message is-ok';
		}
	}, true );
} );
</script>
`;
	html = html.replace( '</body>', demoScript + '</body>' );

	fs.writeFileSync( path.join( OUT, 'index.html' ), html );

	// 5. Копируем ассеты темы.
	fs.cpSync( path.join( THEME, 'assets' ), path.join( OUT, 'assets-theme', 'assets' ), { recursive: true } );

	// 6. GitHub Pages не должен прогонять файлы через Jekyll.
	fs.writeFileSync( path.join( OUT, '.nojekyll' ), '' );

	// 7. Короткая страница-пояснение для разработчика, если кто-то откроет репозиторий.
	fs.writeFileSync(
		path.join( OUT, 'README.md' ),
		'# Демонстрация меню «Мерлер»\n\n'
		+ 'Статическая копия страницы меню, собранная из рабочего сайта на WordPress '
		+ 'скриптом `tools/export-static.js`. Здесь работают лента категорий, поиск и карточки блюд.\n\n'
		+ 'Админка, отправка отзывов и QR-код живут на настоящем сайте — GitHub Pages умеет только статику.\n\n'
		+ 'Исходники: [tima111333/merler](https://github.com/tima111333/merler)\n'
	);

	const size = fs.readFileSync( path.join( OUT, 'index.html' ) ).length;
	const cards = ( html.match( /class="card/g ) || [] ).length;

	console.log( 'build/demo готов: index.html ' + Math.round( size / 1024 ) + ' КБ' );
	console.log( 'карточек в разметке: ' + cards );
	console.log( 'осталось ссылок на localhost: ' + ( html.match( /localhost/g ) || [] ).length );
	console.log( 'осталось ссылок на wp-content: ' + ( html.match( /wp-content/g ) || [] ).length );
} )().catch( ( e ) => {
	console.error( 'Не получилось:', e.message );
	process.exitCode = 1;
} );
