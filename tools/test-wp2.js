/**
 * Этап 8, часть 2. Сценарии клиента, роль менеджера, крайние случаи, скорость.
 * Запуск: node tools/test-wp2.js
 */
const { createRequire } = require( 'module' );
const path = require( 'path' );
const fs = require( 'fs' );

const req = createRequire( 'D:/vortex-rift-points/package.json' );
const { chromium } = req( 'playwright' );

const BASE = process.env.MERLER_URL || 'http://localhost:8932';

// Доступы локального тестового стенда — только через переменные окружения.
const ADMIN_USER = process.env.MERLER_ADMIN_USER || 'merler';
const ADMIN_PASS = process.env.MERLER_ADMIN_PASS || '';
const MANAGER_USER = process.env.MERLER_MANAGER_USER || 'manager';
const MANAGER_PASS = process.env.MERLER_MANAGER_PASS || '';
const SHOTS = path.join( __dirname, '..', 'docs', 'shots-test' );
const LOG = path.join( __dirname, '..', 'docs', 'test-log-2.txt' );

fs.mkdirSync( SHOTS, { recursive: true } );
fs.writeFileSync( LOG, '' );

const results = [];

function say( line ) {
	fs.appendFileSync( LOG, line + '\n' );
	console.log( line );
}

function check( name, ok, detail ) {
	results.push( { name: name, ok: !! ok, detail: detail || '' } );
	say( ( ok ? 'OK   ' : 'FAIL ' ) + name + ( detail ? ' — ' + detail : '' ) );
}

async function login( page, user, pass ) {
	await page.goto( BASE + '/wp-login.php', { waitUntil: 'networkidle' } );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', pass );
	await page.click( '#wp-submit' );
	await page.waitForLoadState( 'networkidle' );
}

async function run() {
	// Готовим данные: менеджер, пустой раздел, длинное название, фото, статусы.
	if ( process.env.MERLER_SCENARIO ) {
		require( 'child_process' ).execFileSync(
			'C:/Users/Tima/AppData/Local/Temp/php82/php.exe',
			[ process.env.MERLER_SCENARIO ],
			{ stdio: 'inherit' }
		);
	}

	const browser = await chromium.launch();

	/* ── Крайние случаи на сайте ──────────────────── */
	const phone = await browser.newContext( {
		viewport: { width: 375, height: 812 },
		isMobile: true,
		hasTouch: true,
		deviceScaleFactor: 2,
		locale: 'ru-RU'
	} );
	const page = await phone.newPage();
	page.setDefaultTimeout( 15000 );

	await page.goto( BASE + '/', { waitUntil: 'networkidle' } );

	// Пустой раздел не выводится.
	const emptySection = await page.locator( '#sec-napitki' ).count();
	const emptyChip = await page.locator( '.chip', { hasText: 'Напитки' } ).count();
	check( 'Пустой раздел не выводится', 0 === emptySection && 0 === emptyChip );

	// Длинное название не ломает вёрстку.
	const overflow = await page.evaluate( () => ( {
		client: document.documentElement.clientWidth,
		scroll: document.documentElement.scrollWidth
	} ) );
	check( 'Длинное название не ломает вёрстку', overflow.scroll <= overflow.client + 1, overflow.scroll + ' / ' + overflow.client );

	// Блюдо без веса — граммовки нет.
	const longCard = page.locator( '.card', { hasText: 'Дегустационный сет' } ).first();
	const weightCount = await longCard.locator( '.card-weight' ).count();
	check( 'У блюда без веса граммовка не выводится', 0 === weightCount );

	// Статусы.
	const soon = await page.locator( '.card[data-status="soon"] .status-flag' ).first().textContent();
	const out = await page.locator( '.card[data-status="out_of_stock"] .status-flag' ).first().textContent();
	check( 'Статус «Скоро» показывается', 'Скоро' === soon.trim(), soon.trim() );
	check( 'Статус «Нет в наличии» показывается', 'Нет в наличии' === out.trim(), out.trim() );

	// Фото: адаптивные размеры и WebP не обязателен, но srcset должен быть.
	const photo = await page.evaluate( () => {
		const img = document.querySelector( '.card-photo' );
		return img ? { src: img.getAttribute( 'src' ), srcset: img.getAttribute( 'srcset' ) || '', loading: img.getAttribute( 'loading' ) } : null;
	} );
	check(
		'Фото блюда выводится с srcset и ленивой загрузкой',
		!! photo && photo.srcset.length > 10 && 'lazy' === photo.loading,
		photo ? 'loading=' + photo.loading + ', вариантов в srcset: ' + ( photo.srcset ? photo.srcset.split( ',' ).length : 0 ) : 'фото нет'
	);

	await page.screenshot( { path: path.join( SHOTS, 'edge-cases.png' ) } );

	/* ── Скорость на медленном 3G ─────────────────── */
	const slow = await browser.newContext( {
		viewport: { width: 375, height: 812 },
		isMobile: true,
		hasTouch: true,
		locale: 'ru-RU'
	} );
	const sp = await slow.newPage();
	const client = await sp.context().newCDPSession( sp );

	await client.send( 'Network.emulateNetworkConditions', {
		offline: false,
		latency: 150,
		downloadThroughput: ( 1.6 * 1024 * 1024 ) / 8,
		uploadThroughput: ( 750 * 1024 ) / 8
	} );

	let bytes = 0;
	sp.on( 'response', async ( r ) => {
		try {
			const len = r.headers()['content-length'];
			if ( len ) {
				bytes += parseInt( len, 10 );
			}
		} catch ( e ) {}
	} );

	const started = Date.now();
	await sp.goto( BASE + '/', { waitUntil: 'domcontentloaded' } );
	const domReady = Date.now() - started;
	await sp.waitForLoadState( 'load' );
	const loaded = Date.now() - started;

	check( 'Первый экран быстрее 2 с на медленном соединении', domReady < 2000, domReady + ' мс до готовности разметки' );
	say( '     полная загрузка: ' + loaded + ' мс, вес переданного: ' + Math.round( bytes / 1024 ) + ' КБ' );

	// Меню читается без JS.
	const noJs = await browser.newContext( { javaScriptEnabled: false, viewport: { width: 375, height: 812 } } );
	const njp = await noJs.newPage();
	await njp.goto( BASE + '/', { waitUntil: 'domcontentloaded' } );
	const njCards = await njp.locator( '.card' ).count();
	check( 'Меню читается с выключенным JavaScript', njCards > 100, njCards + ' карточек' );
	await njp.screenshot( { path: path.join( SHOTS, 'no-js.png' ) } );

	/* ── Роль «Менеджер меню» ─────────────────────── */
	const mgr = await browser.newContext( { viewport: { width: 1440, height: 900 }, locale: 'ru-RU' } );
	const mp = await mgr.newPage();
	mp.setDefaultTimeout( 15000 );

	await login( mp, MANAGER_USER, MANAGER_PASS );
	check( 'Менеджер входит в админку', mp.url().indexOf( 'wp-admin' ) > -1 );

	const menuText = await mp.locator( '#adminmenu' ).textContent();
	const forbidden = [ 'Плагины', 'Внешний вид', 'Пользователи', 'Инструменты' ];
	const visible = forbidden.filter( ( w ) => menuText.indexOf( w ) > -1 );
	check( 'Менеджер не видит лишних разделов', 0 === visible.length, visible.length ? 'видит: ' + visible.join( ', ' ) : 'видит только меню и медиафайлы' );

	const canDishes = menuText.indexOf( 'Меню ресторана' ) > -1;
	const canMedia = menuText.indexOf( 'Медиафайлы' ) > -1;
	check( 'Менеджер видит меню и медиафайлы', canDishes && canMedia );

	await mp.screenshot( { path: path.join( SHOTS, 'manager-admin.png' ) } );

	// Менеджер может отредактировать цену.
	await mp.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish', { waitUntil: 'networkidle' } );
	const mgrRows = await mp.locator( '#the-list tr' ).count();
	check( 'Менеджер видит список блюд', mgrRows > 5, mgrRows + ' строк' );

	// Настройки плагина ему доступны, а системные — нет.
	await mp.goto( BASE + '/wp-admin/options-general.php', { waitUntil: 'domcontentloaded' } );
	const denied = ( await mp.content() ).indexOf( 'Недостаточно прав' ) > -1 || ( await mp.content() ).indexOf( 'не разрешён' ) > -1 || ( await mp.content() ).indexOf( 'Извините' ) > -1;
	check( 'Системные настройки менеджеру закрыты', denied );

	/* ── Сценарии администратора ──────────────────── */
	const admin = await browser.newContext( { viewport: { width: 1440, height: 900 }, locale: 'ru-RU' } );
	const ap = await admin.newPage();
	ap.setDefaultTimeout( 20000 );

	await login( ap, ADMIN_USER, ADMIN_PASS );

	// Массовая смена статуса.
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish', { waitUntil: 'networkidle' } );
	await ap.locator( '#cb-select-all-1' ).check();
	await ap.selectOption( '#bulk-action-selector-top', 'merler_soon' );
	await ap.locator( '#doaction' ).click();
	await ap.waitForLoadState( 'networkidle' );
	const bulkNotice = await ap.locator( '.notice-success' ).first().textContent().catch( () => '' );
	check( 'Массовая смена статуса работает', bulkNotice.indexOf( 'Статус изменён' ) > -1, bulkNotice.trim() );

	// Вернуть обратно.
	await ap.locator( '#cb-select-all-1' ).check();
	await ap.selectOption( '#bulk-action-selector-top', 'merler_in_stock' );
	await ap.locator( '#doaction' ).click();
	await ap.waitForLoadState( 'networkidle' );

	// Дублирование блюда.
	const firstRow = ap.locator( '#the-list tr' ).first();
	const title = await firstRow.locator( '.row-title' ).textContent();
	await firstRow.hover();
	await firstRow.locator( 'a:has-text("Дублировать")' ).click();
	await ap.waitForLoadState( 'networkidle' );
	const copyTitle = await ap.locator( '#title' ).inputValue();
	check( 'Дублирование блюда работает', copyTitle.indexOf( '(копия)' ) > -1, copyTitle );

	// Повторный импорт не плодит дубли.
	const beforeCount = await countDishes( ap );
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish&page=merler-tools', { waitUntil: 'networkidle' } );
	ap.once( 'dialog', ( d ) => d.accept() );
	await ap.locator( 'input[value="Загрузить печатное меню"]' ).click();
	await ap.waitForLoadState( 'networkidle' );
	const importNotice = await ap.locator( '.notice-success' ).first().textContent();
	const afterCount = await countDishes( ap );
	check( 'Повторный импорт не создаёт дублей', beforeCount === afterCount, beforeCount + ' → ' + afterCount + '; ' + importNotice.trim() );

	// Импорт сбрасывает статусы к значениям из файла — возвращаем демонстрационные.
	if ( process.env.MERLER_SCENARIO ) {
		require( 'child_process' ).execFileSync(
			'C:/Users/Tima/AppData/Local/Temp/php82/php.exe',
			[ process.env.MERLER_SCENARIO ],
			{ stdio: 'ignore' }
		);
	}

	// Экспорт отдаёт корректный JSON.
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish&page=merler-tools', { waitUntil: 'networkidle' } );
	const [ download ] = await Promise.all( [
		ap.waitForEvent( 'download' ),
		ap.locator( 'input[value="Скачать меню в JSON"]' ).click()
	] );
	const file = path.join( SHOTS, 'export.json' );
	await download.saveAs( file );

	let exportOk = false;
	let exportDetail = '';
	try {
		const data = JSON.parse( fs.readFileSync( file, 'utf8' ) );
		let n = 0;
		data.sections.forEach( ( s ) => {
			n += s.dishes.length;
			( s.subsections || [] ).forEach( ( x ) => {
				n += x.dishes.length;
			} );
		} );
		exportOk = n > 110 && data.sections.length > 10;
		exportDetail = data.sections.length + ' разделов, ' + n + ' блюд';
	} catch ( e ) {
		exportDetail = 'JSON не читается';
	}
	check( 'Экспорт отдаёт корректный JSON', exportOk, exportDetail );

	// Режим без фото.
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish&page=merler-settings', { waitUntil: 'networkidle' } );
	await ap.locator( 'input[name="merler_settings[no_photo_mode]"]' ).check();
	await ap.locator( '#submit' ).click();
	await ap.waitForLoadState( 'networkidle' );

	await page.goto( BASE + '/', { waitUntil: 'networkidle' } );
	const rowsVisible = await page.locator( '.row:visible' ).count();
	const cardsVisible = await page.locator( '.card:visible' ).count();
	check( 'Режим без фото переключает вид', rowsVisible > 100 && 0 === cardsVisible, rowsVisible + ' строк, ' + cardsVisible + ' карточек' );
	await page.screenshot( { path: path.join( SHOTS, 'no-photo-mode.png' ) } );

	// Вернуть обратно.
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish&page=merler-settings', { waitUntil: 'networkidle' } );
	await ap.locator( 'input[name="merler_settings[no_photo_mode]"]' ).uncheck();
	await ap.locator( '#submit' ).click();
	await ap.waitForLoadState( 'networkidle' );

	await browser.close();

	const failed = results.filter( ( r ) => ! r.ok );
	say( '\nИтог: ' + ( results.length - failed.length ) + ' из ' + results.length + ' проверок пройдено.' );

	fs.writeFileSync(
		path.join( __dirname, '..', 'docs', 'test-report-2.json' ),
		JSON.stringify( results, null, 2 )
	);

	if ( failed.length ) {
		process.exitCode = 1;
	}
}

async function countDishes( page ) {
	await page.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish', { waitUntil: 'networkidle' } );
	const text = await page.locator( '.displaying-num' ).first().textContent();
	return parseInt( text.replace( /\D+/g, '' ), 10 );
}

run().catch( ( e ) => {
	try {
		fs.appendFileSync( LOG, 'УПАЛ: ' + e.message + '\n' );
	} catch ( x ) {}
	console.error( 'Тест упал:', e.message );
	process.exitCode = 1;
} );
