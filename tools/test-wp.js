/**
 * Этап 8. Сквозная проверка сайта и админки на живом WordPress.
 * Запуск: node tools/test-wp.js
 */
const { createRequire } = require( 'module' );
const path = require( 'path' );
const fs = require( 'fs' );

const req = createRequire( 'D:/vortex-rift-points/package.json' );
const { chromium } = req( 'playwright' );

const BASE = process.env.MERLER_URL || 'http://localhost:8932';

// Доступы локального тестового стенда — только через переменные окружения:
// MERLER_ADMIN_USER=... MERLER_ADMIN_PASS=... node tools/test-wp.js
const ADMIN_USER = process.env.MERLER_ADMIN_USER || 'merler';
const ADMIN_PASS = process.env.MERLER_ADMIN_PASS || '';
const SHOTS = path.join( __dirname, '..', 'docs', 'shots-test' );
fs.mkdirSync( SHOTS, { recursive: true } );

const results = [];
const consoleErrors = [];

const LOG = path.join( __dirname, '..', 'docs', 'test-log.txt' );
fs.writeFileSync( LOG, '' );

function say( line ) {
	fs.appendFileSync( LOG, line + '\n' );
	console.log( line );
}

function check( name, ok, detail ) {
	results.push( { name: name, ok: !! ok, detail: detail || '' } );
	say( ( ok ? 'OK   ' : 'FAIL ' ) + name + ( detail ? ' — ' + detail : '' ) );
}

async function run() {
	// Сбрасываем отзывы и счётчик частоты, чтобы прогон был воспроизводимым.
	if ( process.env.MERLER_RESET ) {
		try {
			require( 'child_process' ).execFileSync(
				'C:/Users/Tima/AppData/Local/Temp/php82/php.exe',
				[ process.env.MERLER_RESET ],
				{ stdio: 'inherit' }
			);
		} catch ( e ) {
			say( 'Сброс не выполнен: ' + e.message );
		}
	}

	const browser = await chromium.launch();

	/* ── Фронтенд на телефоне ─────────────────────── */
	const phone = await browser.newContext( {
		viewport: { width: 375, height: 812 },
		isMobile: true,
		hasTouch: true,
		deviceScaleFactor: 2,
		locale: 'ru-RU'
	} );
	const page = await phone.newPage();
	page.setDefaultTimeout( 15000 );

	page.on( 'console', ( m ) => {
		if ( 'error' === m.type() ) {
			consoleErrors.push( m.text() );
		}
	} );
	page.on( 'pageerror', ( e ) => consoleErrors.push( 'pageerror: ' + e.message ) );

	await page.goto( BASE + '/', { waitUntil: 'networkidle' } );

	// 1. Меню отрисовано сервером.
	const cards = await page.locator( '.card' ).count();
	check( 'Блюда выводятся (не меньше 115 из печатного меню)', cards >= 115, cards + ' карточек' );

	const sections = await page.locator( '.section' ).count();
	check( 'Разделы выводятся (14)', 14 === sections, sections + ' разделов' );

	const subs = await page.locator( '.subsection' ).count();
	check( 'Подразделы выводятся (6)', 6 === subs, subs + ' подразделов' );

	// 2. Нет горизонтальной прокрутки.
	const overflow = await page.evaluate( () => ( {
		client: document.documentElement.clientWidth,
		scroll: document.documentElement.scrollWidth
	} ) );
	check( 'Нет горизонтальной прокрутки', overflow.scroll <= overflow.client + 1, overflow.scroll + ' / ' + overflow.client );

	// 3. Первый экран компактный.
	const heroH = await page.evaluate( () => Math.round( document.querySelector( '.hero' ).getBoundingClientRect().height ) );
	check( 'Первый экран меньше 60% высоты', heroH < 812 * 0.6, heroH + 'px из 812' );

	// 4. Пустой вес не выводится.
	const emptyWeights = await page.evaluate( () => {
		let bad = 0;
		document.querySelectorAll( '.card' ).forEach( ( c ) => {
			const w = c.querySelector( '.card-weight' );
			if ( w && '' === w.textContent.trim() ) {
				bad++;
			}
		} );
		return bad;
	} );
	check( 'Нет пустых граммовок', 0 === emptyWeights, emptyWeights + ' пустых' );

	// 5. Блок разделов на первом экране: все названия видны сразу, ленты ещё нет.
	const indexNav = await page.evaluate( () => {
		const idx = document.querySelector( '.menu-index' );
		if ( ! idx ) {
			return null;
		}
		const chips = Array.prototype.slice.call( idx.querySelectorAll( '.chip' ) );
		const visible = chips.filter( ( c ) => {
			const r = c.getBoundingClientRect();
			return r.left >= -1 && r.right <= window.innerWidth + 1 && r.bottom <= window.innerHeight + 1;
		} );
		return {
			total: chips.length,
			visible: visible.length,
			stripHidden: ! document.querySelector( '.navbar' ).classList.contains( 'is-visible' )
		};
	} );
	check(
		'На первом экране видны все разделы без прокрутки',
		!! indexNav && indexNav.total === indexNav.visible && indexNav.stripHidden,
		indexNav ? 'видно ' + indexNav.visible + ' из ' + indexNav.total + ', лента спрятана: ' + indexNav.stripHidden : 'блока нет'
	);

	// 6. Прилипающая лента выезжает после прокрутки.
	await page.evaluate( () => window.scrollBy( 0, 600 ) );
	await page.waitForTimeout( 900 );
	const stripShown = await page.evaluate( () => {
		const nb = document.querySelector( '.navbar' );
		return nb.classList.contains( 'is-visible' ) && Math.round( nb.getBoundingClientRect().top ) === 0;
	} );
	check( 'После прокрутки лента выезжает и прилипает к верху', stripShown );

	// 7. Переход по категории и scroll-spy.
	await page.locator( '.menu-index .chip', { hasText: 'Десерты' } ).click();
	await page.waitForTimeout( 2200 );
	const activeChip = await page.locator( '.chip.is-active' ).first().textContent();
	check( 'Scroll-spy подсвечивает раздел', 'Десерты' === activeChip.trim(), 'активна: ' + activeChip.trim() );

	// 6. Поиск.
	await page.locator( '.js-search' ).click();
	await page.locator( '.js-search-input' ).fill( 'креветк' );
	await page.waitForTimeout( 400 );
	const visibleCards = await page.locator( '.card:visible' ).count();
	check( 'Поиск фильтрует мгновенно', visibleCards > 0 && visibleCards < 20, 'найдено ' + visibleCards );
	await page.screenshot( { path: path.join( SHOTS, 'search.png' ) } );

	await page.locator( '.js-search-input' ).fill( 'ыыыы' );
	await page.waitForTimeout( 300 );
	const noRes = await page.locator( '.no-results' ).isVisible();
	check( 'Пустой поиск показывает сообщение', noRes );

	await page.locator( '.js-search' ).click();
	await page.waitForTimeout( 300 );

	// 7. Карточка блюда открывается и закрывается.
	await page.locator( '.card' ).first().click();
	await page.waitForTimeout( 500 );
	const sheetOpen = await page.locator( '.sheet' ).isVisible();
	const sheetTitle = await page.locator( '.sheet-title' ).textContent();
	check( 'Окно блюда открывается', sheetOpen && sheetTitle.length > 3, sheetTitle );
	await page.screenshot( { path: path.join( SHOTS, 'sheet.png' ) } );

	await page.goBack();
	await page.waitForTimeout( 600 );
	const sheetClosed = ! ( await page.locator( '.sheet' ).isVisible() );
	const stillOnPage = page.url().indexOf( BASE ) === 0;
	check( 'Кнопка «Назад» закрывает окно, а не уводит с сайта', sheetClosed && stillOnPage );

	// 8. Оценка и отзыв.
	await page.locator( '.fab-rate' ).click();
	await page.waitForTimeout( 400 );
	await page.locator( '.star[data-value="5"]' ).click();
	const actionsVisible = await page.locator( '.rate-actions' ).isVisible();
	check( 'После оценки предлагаются оба варианта', actionsVisible );

	await page.locator( '.js-rate-direct' ).click();
	await page.locator( '#merler-rate-text' ).fill( 'Тестовый отзыв: всё понравилось.' );
	await page.locator( '#merler-rate-phone' ).fill( '+7 900 000-00-00' );
	await page.waitForTimeout( 200 );
	const consentVisible = await page.locator( '.rate-consent' ).isVisible();
	check( 'Телефон требует согласия на обработку данных', consentVisible );

	await page.locator( '.rate-consent input' ).check();
	await page.screenshot( { path: path.join( SHOTS, 'rate.png' ) } );
	await page.locator( '.rate-form button[type="submit"]' ).click();
	await page.waitForTimeout( 1500 );
	const msg = await page.locator( '.rate-message' ).textContent();
	check( 'Отзыв отправляется и сохраняется', msg.indexOf( 'Спасибо' ) > -1, msg.trim() );

	// 9. JSON-LD.
	const ld = await page.evaluate( () => {
		const el = document.querySelector( 'script[type="application/ld+json"]' );
		return el ? el.textContent : '';
	} );
	let ldOk = false;
	let ldDetail = 'разметки нет';
	try {
		const parsed = JSON.parse( ld );
		const secs = parsed.hasMenu && parsed.hasMenu.hasMenuSection ? parsed.hasMenu.hasMenuSection.length : 0;
		ldOk = 'Restaurant' === parsed['@type'] && secs > 10;
		ldDetail = 'Restaurant → Menu → ' + secs + ' разделов';
	} catch ( e ) {
		ldDetail = 'JSON не парсится';
	}
	check( 'JSON-LD валиден', ldOk, ldDetail );

	// 10. Ошибок в консоли нет.
	check( 'Нет ошибок JS', 0 === consoleErrors.length, consoleErrors.join( ' | ' ) );

	/* ── Админка ──────────────────────────────────── */
	const admin = await browser.newContext( { viewport: { width: 1440, height: 900 }, locale: 'ru-RU' } );
	const ap = await admin.newPage();
	ap.setDefaultTimeout( 15000 );
	const adminErrors = [];
	ap.on( 'pageerror', ( e ) => adminErrors.push( e.message ) );

	await ap.goto( BASE + '/wp-login.php', { waitUntil: 'networkidle' } );
	await ap.fill( '#user_login', ADMIN_USER );
	await ap.fill( '#user_pass', ADMIN_PASS );
	await ap.click( '#wp-submit' );
	await ap.waitForLoadState( 'networkidle' );
	check( 'Вход в админку', ap.url().indexOf( 'wp-admin' ) > -1 );

	// Виджет-инструкция.
	const widget = await ap.locator( '#merler_help_widget' ).count();
	check( 'Виджет «Как редактировать меню» на консоли', widget > 0 );
	await ap.screenshot( { path: path.join( SHOTS, 'admin-dashboard.png' ) } );

	// Список блюд.
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish', { waitUntil: 'networkidle' } );
	const rows = await ap.locator( '#the-list tr' ).count();
	check( 'Список блюд открывается', rows > 5, rows + ' строк на странице' );
	const cols = await ap.locator( 'th#merler_price' ).count();
	check( 'Колонка «Цена» есть', cols > 0 );
	await ap.screenshot( { path: path.join( SHOTS, 'admin-list.png' ) } );

	// Быстрое редактирование цены.
	const firstRow = ap.locator( '#the-list tr' ).first();
	const rowId = await firstRow.getAttribute( 'id' );
	await firstRow.hover();
	await firstRow.locator( '.editinline' ).click( { force: true } );
	await ap.waitForTimeout( 700 );
	const priceField = ap.locator( '#the-list tr[id^="edit-"] input[name="merler_price"]' );
	const prefilled = await priceField.inputValue();
	check( 'Quick Edit подставляет текущую цену', '' !== prefilled, 'цена в форме: ' + prefilled );

	await priceField.fill( '999' );
	await ap.locator( '#the-list tr[id^="edit-"] .inline-edit-save button.save' ).click();
	await ap.waitForTimeout( 1500 );
	const newPrice = await ap.locator( '#' + rowId + ' .column-merler_price' ).textContent();
	check( 'Quick Edit сохраняет цену', newPrice.indexOf( '999' ) > -1, newPrice.trim() );

	// Страница порядка.
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish&page=merler-order', { waitUntil: 'networkidle' } );
	const orderSections = await ap.locator( '.merler-section' ).count();
	check( 'Страница «Порядок блюд» работает', orderSections > 10, orderSections + ' разделов' );
	await ap.screenshot( { path: path.join( SHOTS, 'admin-order.png' ) } );

	// Настройки.
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish&page=merler-settings', { waitUntil: 'networkidle' } );
	const phoneField = await ap.locator( '#merler_phone' ).inputValue();
	check( 'Настройки меню открываются', phoneField.length > 5, 'телефон: ' + phoneField );
	await ap.screenshot( { path: path.join( SHOTS, 'admin-settings.png' ), fullPage: true } );

	// QR.
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish&page=merler-qr', { waitUntil: 'networkidle' } );
	await ap.waitForTimeout( 1200 );
	const qrModules = await ap.locator( '#merler-qr-plain svg' ).count();
	const tentDrawn = await ap.locator( '#merler-tent svg' ).count();
	check( 'QR-код генерируется', qrModules > 0 );
	check( 'Табличка A6 рисуется', tentDrawn > 0 );
	await ap.screenshot( { path: path.join( SHOTS, 'admin-qr.png' ), fullPage: true } );

	// Отзывы.
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_review', { waitUntil: 'networkidle' } );
	const reviewRows = await ap.locator( '#the-list tr' ).count();
	const reviewText = await ap.locator( '#the-list' ).textContent();
	check( 'Отзыв виден в админке', reviewRows > 0 && reviewText.indexOf( 'Тестовый' ) > -1, reviewRows + ' отзывов' );
	await ap.screenshot( { path: path.join( SHOTS, 'admin-reviews.png' ) } );

	// Инструменты: экспорт.
	await ap.goto( BASE + '/wp-admin/edit.php?post_type=merler_dish&page=merler-tools', { waitUntil: 'networkidle' } );
	const exportBtn = await ap.locator( 'input[value="Скачать меню в JSON"]' ).count();
	check( 'Страница импорта-экспорта работает', exportBtn > 0 );

	check( 'Нет ошибок JS в админке', 0 === adminErrors.length, adminErrors.join( ' | ' ) );

	await browser.close();

	/* ── Итог ─────────────────────────────────────── */
	const failed = results.filter( ( r ) => ! r.ok );
	console.log( '\nИтог: ' + ( results.length - failed.length ) + ' из ' + results.length + ' проверок пройдено.' );

	fs.writeFileSync(
		path.join( __dirname, '..', 'docs', 'test-report.json' ),
		JSON.stringify( results, null, 2 )
	);

	if ( failed.length ) {
		process.exitCode = 1;
	}
}

run().catch( ( e ) => {
	try { fs.appendFileSync( LOG, 'УПАЛ: ' + e.message + '\n' ); } catch ( x ) {}
	console.error( 'Тест упал:', e.message );
	process.exitCode = 1;
} );
