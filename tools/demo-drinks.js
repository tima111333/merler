/**
 * Проверка сценария «клиент сам добавляет напитки» — только через админку,
 * без единой правки кода. Создаёт раздел, подраздел и позицию, снимает результат.
 *
 * Запуск: MERLER_ADMIN_PASS=... node tools/demo-drinks.js
 */
const { createRequire } = require( 'module' );
const path = require( 'path' );
const fs = require( 'fs' );

const req = createRequire( 'D:/vortex-rift-points/package.json' );
const { chromium } = req( 'playwright' );

const BASE = process.env.MERLER_URL || 'http://localhost:8933';
const USER = process.env.MERLER_ADMIN_USER || 'merler';
const PASS = process.env.MERLER_ADMIN_PASS || '';
const SHOTS = path.join( __dirname, '..', 'docs', 'shots-drinks' );

fs.mkdirSync( SHOTS, { recursive: true } );

const log = [];

function say( line ) {
	log.push( line );
	console.log( line );
}

/**
 * Создать раздел меню через обычную форму админки.
 *
 * @param {object} page   Страница.
 * @param {string} name   Название.
 * @param {string} slug   Адрес.
 * @param {string} parent Название родителя или пустая строка.
 * @param {number} order  Порядок.
 * @param {string} note   Короткая подпись.
 */
async function addSection( page, name, slug, parent, order, note ) {
	await page.goto( BASE + '/wp-admin/edit-tags.php?taxonomy=merler_section&post_type=merler_dish', { waitUntil: 'networkidle' } );
	await page.fill( '#tag-name', name );
	await page.fill( '#tag-slug', slug );

	if ( parent ) {
		await page.selectOption( '#parent', { label: '   ' + parent } ).catch( async () => {
			await page.selectOption( '#parent', { label: parent } );
		} );
	}

	await page.fill( '#merler_order', String( order ) );

	if ( note ) {
		await page.fill( '#merler_note', note );
	}

	await page.click( '#submit' );
	await page.waitForTimeout( 1200 );
	say( 'Раздел создан: ' + name + ( parent ? ' (внутри «' + parent + '»)' : '' ) );
}

/**
 * Добавить позицию меню через обычную форму админки.
 *
 * @param {object} page    Страница.
 * @param {string} title   Название.
 * @param {number} price   Цена.
 * @param {string} volume  Объём.
 * @param {string} desc    Состав.
 * @param {string} section Название раздела.
 */
async function addDish( page, title, price, volume, desc, section ) {
	await page.goto( BASE + '/wp-admin/post-new.php?post_type=merler_dish', { waitUntil: 'networkidle' } );
	await page.fill( '#title', title );
	await page.fill( '#merler_price', String( price ) );
	await page.fill( '#merler_weight', volume );
	await page.fill( '#merler_description', desc );

	// Раздел выбирается галочкой в блоке справа — как обычная рубрика.
	const label = page.locator( '#merler_sectionchecklist label', { hasText: section } ).first();
	await label.locator( 'input[type="checkbox"]' ).check();

	await page.click( '#publish' );
	await page.waitForLoadState( 'networkidle' );

	const notice = await page.locator( '#message' ).first().textContent().catch( () => '' );
	say( 'Позиция добавлена: ' + title + ' — ' + price + ' ₽, ' + volume + ( notice.indexOf( 'публикован' ) > -1 ? ' (опубликовано)' : '' ) );
}

( async () => {
	if ( ! PASS ) {
		console.error( 'Нужен пароль: MERLER_ADMIN_PASS=... node tools/demo-drinks.js' );
		process.exitCode = 1;
		return;
	}

	const browser = await chromium.launch();
	const admin = await browser.newContext( { viewport: { width: 1440, height: 900 }, locale: 'ru-RU' } );
	const page = await admin.newPage();
	page.setDefaultTimeout( 20000 );

	await page.goto( BASE + '/wp-login.php', { waitUntil: 'networkidle' } );
	await page.fill( '#user_login', USER );
	await page.fill( '#user_pass', PASS );
	await page.click( '#wp-submit' );
	await page.waitForLoadState( 'networkidle' );

	// 1. Раздел и подразделы — как это сделает администратор ресторана.
	await addSection( page, 'Напитки', 'napitki-demo', '', 150, '' );
	await addSection( page, 'Лимонады', 'limonady-demo', 'Напитки', 10, 'свежая партия каждое утро' );
	await addSection( page, 'Кофе', 'kofe-demo', 'Напитки', 20, '' );

	// 2. Позиции.
	await addDish( page, 'Лимонад облепиховый', 350, '0,4 л', 'Облепиха, мёд, розмарин, газированная вода', 'Лимонады' );
	await addDish( page, 'Лимонад тархун', 320, '0,4 л', 'Тархун, лайм, газированная вода', 'Лимонады' );
	await addDish( page, 'Эспрессо', 180, '40 мл', '', 'Кофе' );
	await addDish( page, 'Капучино', 280, '250 мл', '', 'Кофе' );

	await page.goto( BASE + '/wp-admin/edit-tags.php?taxonomy=merler_section&post_type=merler_dish', { waitUntil: 'networkidle' } );
	await page.screenshot( { path: path.join( SHOTS, 'admin-sections.png' ), fullPage: false } );

	// 3. Что получилось на сайте.
	const phone = await browser.newContext( {
		viewport: { width: 375, height: 812 },
		isMobile: true,
		hasTouch: true,
		deviceScaleFactor: 2,
		locale: 'ru-RU'
	} );
	const site = await phone.newPage();
	await site.goto( BASE + '/', { waitUntil: 'networkidle' } );

	const chip = await site.locator( '.chip', { hasText: 'Напитки' } ).count();
	say( 'Категория «Напитки» в ленте: ' + ( chip ? 'появилась' : 'НЕТ' ) );

	const subs = await site.locator( '#sec-napitki-demo .subsection-title' ).allTextContents();
	say( 'Подразделы на сайте: ' + subs.map( ( s ) => s.replace( /\s+/g, ' ' ).trim() ).join( ' | ' ) );

	const drinks = await site.locator( '#sec-napitki-demo .card' ).count();
	say( 'Позиций в разделе: ' + drinks );

	// Поиск по напиткам.
	await site.click( '.js-search' );
	await site.fill( '.js-search-input', 'лимонад' );
	await site.waitForTimeout( 400 );
	say( 'Поиск «лимонад»: ' + ( await site.locator( '.card:visible' ).count() ) + ' позиций' );
	await site.click( '.js-search' );
	await site.waitForTimeout( 300 );

	await site.locator( '.chip', { hasText: 'Напитки' } ).click();
	await site.waitForTimeout( 1500 );
	await site.screenshot( { path: path.join( SHOTS, 'site-drinks.png' ) } );

	await browser.close();

	fs.writeFileSync( path.join( SHOTS, 'log.txt' ), log.join( '\n' ) + '\n' );
} )().catch( ( e ) => {
	console.error( 'Не получилось:', e.message );
	process.exitCode = 1;
} );
