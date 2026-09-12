/**
 * Меню «Мерлер»: лента категорий, поиск, окно блюда, оценка.
 * Чистый JS, без jQuery. Страница работает и без него — это улучшение, а не основа.
 */
( function () {
	'use strict';

	var doc = document;
	var body = doc.body;

	/* ── Лента категорий и подсветка активной ──────── */

	var strip = doc.querySelector( '.chips' );
	var chips = Array.prototype.slice.call( doc.querySelectorAll( '.chip' ) );
	var sections = Array.prototype.slice.call( doc.querySelectorAll( '.section' ) );
	var chipById = {};

	chips.forEach( function ( chip ) {
		var href = chip.getAttribute( 'href' ) || '';
		if ( '#' === href.charAt( 0 ) ) {
			chipById[ href.slice( 1 ) ] = chip;
		}
	} );

	function setActive( id ) {
		var chip = chipById[ id ];
		if ( ! chip || ! strip ) {
			return;
		}

		chips.forEach( function ( c ) {
			c.classList.remove( 'is-active' );
		} );
		chip.classList.add( 'is-active' );

		var rect = chip.getBoundingClientRect();
		var stripRect = strip.getBoundingClientRect();

		if ( rect.left < stripRect.left + 12 || rect.right > stripRect.right - 12 ) {
			strip.scrollTo( {
				left: strip.scrollLeft + rect.left - stripRect.left - 16,
				behavior: 'smooth'
			} );
		}
	}

	/*
	 * Активный раздел — последний, чей заголовок уже прошёл под ленту категорий.
	 * У последнего раздела страница часто не может докрутиться до верха,
	 * поэтому в самом низу подсвечиваем его принудительно.
	 */
	var spyLockedUntil = 0;

	function updateSpy() {
		if ( ! sections.length || Date.now() < spyLockedUntil ) {
			return;
		}

		var atBottom = window.innerHeight + window.scrollY >= doc.documentElement.scrollHeight - 8;

		if ( atBottom ) {
			var last = sections[ sections.length - 1 ];
			if ( ! last.hidden ) {
				setActive( last.id );
				return;
			}
		}

		var current = '';

		sections.forEach( function ( section ) {
			if ( section.hidden ) {
				return;
			}
			if ( section.getBoundingClientRect().top <= 160 ) {
				current = section.id;
			}
		} );

		if ( ! current ) {
			for ( var i = 0; i < sections.length; i++ ) {
				if ( ! sections[ i ].hidden ) {
					current = sections[ i ].id;
					break;
				}
			}
		}

		if ( current ) {
			setActive( current );
		}
	}

	var spyTicking = false;

	window.addEventListener( 'scroll', function () {
		if ( spyTicking ) {
			return;
		}
		spyTicking = true;
		window.requestAnimationFrame( function () {
			updateSpy();
			spyTicking = false;
		} );
	}, { passive: true } );

	window.addEventListener( 'resize', updateSpy, { passive: true } );
	updateSpy();

	/* ── Развёрнутая и свёрнутая лента ─────────────── */

	var navbar = doc.querySelector( '.navbar' );
	var chipsToggle = doc.querySelector( '.js-chips-toggle' );

	/**
	 * Свернуть список разделов в одну строку.
	 *
	 * Развёрнутая панель висит поверх содержимого, поэтому страница
	 * не сдвигается и подправлять прокрутку не нужно.
	 */
	function collapseChips() {
		if ( ! navbar || ! navbar.classList.contains( 'is-expanded' ) ) {
			return;
		}

		navbar.classList.remove( 'is-expanded' );

		if ( chipsToggle ) {
			chipsToggle.setAttribute( 'aria-expanded', 'false' );
			chipsToggle.setAttribute( 'aria-label', 'Показать все разделы' );
		}

		var active = doc.querySelector( '.chip.is-active' );
		if ( active ) {
			setActive( active.getAttribute( 'href' ).slice( 1 ) );
		}
	}

	var expandedAt = 0;

	function expandChips() {
		if ( ! navbar ) {
			return;
		}
		expandedAt = Date.now();
		navbar.classList.add( 'is-expanded' );
		if ( chipsToggle ) {
			chipsToggle.setAttribute( 'aria-expanded', 'true' );
			chipsToggle.setAttribute( 'aria-label', 'Свернуть список разделов' );
		}
	}

	if ( chipsToggle ) {
		chipsToggle.addEventListener( 'click', function () {
			if ( navbar.classList.contains( 'is-expanded' ) ) {
				collapseChips();
			} else {
				expandChips();
			}
		} );
	}

	// Гость начал читать меню — панель уходит и освобождает экран.
	var lastScrollY = window.scrollY;

	window.addEventListener( 'scroll', function () {
		var justOpened = Date.now() - expandedAt < 400;

		if ( navbar && navbar.classList.contains( 'is-expanded' ) && ! justOpened
			&& Math.abs( window.scrollY - lastScrollY ) > 24 ) {
			collapseChips();
		}

		lastScrollY = window.scrollY;
	}, { passive: true } );

	// Клик мимо панели и Esc тоже закрывают её.
	doc.addEventListener( 'click', function ( e ) {
		if ( navbar && navbar.classList.contains( 'is-expanded' ) && ! e.target.closest( '.navbar' ) ) {
			collapseChips();
		}
	} );

	doc.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			collapseChips();
		}
	} );

	/*
	 * При нажатии на категорию держим подсветку на ней, пока страница едет:
	 * иначе по дороге мигают промежуточные разделы.
	 */
	var hasScrollEnd = 'onscrollend' in window;

	chips.forEach( function ( chip ) {
		chip.addEventListener( 'click', function () {
			var href = chip.getAttribute( 'href' ) || '';
			spyLockedUntil = Date.now() + ( hasScrollEnd ? 6000 : 1600 );
			collapseChips();
			setActive( href.slice( 1 ) );
		} );
	} );

	if ( hasScrollEnd ) {
		window.addEventListener( 'scrollend', function () {
			spyLockedUntil = 0;
			updateSpy();
		} );
	}

	/* ── Поиск ─────────────────────────────────────── */

	var searchToggle = doc.querySelector( '.js-search' );
	var searchBar = doc.querySelector( '.searchbar' );
	var searchInput = doc.querySelector( '.js-search-input' );
	var noResults = doc.querySelector( '.no-results' );

	function filter( query ) {
		query = ( query || '' ).trim().toLowerCase();
		var shown = 0;

		doc.querySelectorAll( '[data-search]' ).forEach( function ( el ) {
			var hit = ! query || el.getAttribute( 'data-search' ).indexOf( query ) > -1;
			el.hidden = ! hit;
			if ( hit && el.classList.contains( 'card' ) ) {
				shown++;
			}
		} );

		doc.querySelectorAll( '.subsection' ).forEach( function ( sub ) {
			sub.hidden = !! query && ! sub.querySelector( '[data-search]:not([hidden])' );
		} );

		sections.forEach( function ( section ) {
			section.hidden = !! query && ! section.querySelector( '[data-search]:not([hidden])' );
		} );

		var about = doc.getElementById( 'about' );
		if ( about ) {
			about.hidden = !! query;
		}

		if ( noResults ) {
			noResults.hidden = ! ( query && 0 === shown );
		}
	}

	if ( searchToggle && searchBar && searchInput ) {
		searchToggle.addEventListener( 'click', function () {
			var open = searchBar.classList.toggle( 'is-open' );
			searchToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

			if ( open ) {
				searchInput.focus();
			} else {
				searchInput.value = '';
				filter( '' );
			}
		} );

		searchInput.addEventListener( 'input', function () {
			filter( searchInput.value );
		} );

		searchInput.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				searchInput.value = '';
				filter( '' );
			}
		} );
	}

	/* ── Общая механика окон ───────────────────────── */

	var openDialog = null;
	var lastFocus = null;

	function show( dialog, overlay ) {
		lastFocus = doc.activeElement;
		overlay.hidden = false;
		dialog.hidden = false;

		window.requestAnimationFrame( function () {
			overlay.classList.add( 'is-open' );
			dialog.classList.add( 'is-open' );
		} );

		body.style.overflow = 'hidden';
		openDialog = { dialog: dialog, overlay: overlay };

		var close = dialog.querySelector( '.sheet-close' );
		if ( close ) {
			close.focus();
		}

		// «Назад» на телефоне закрывает окно, а не уводит с сайта.
		history.pushState( { merler: true }, '' );
	}

	function hide( fromPopstate ) {
		if ( ! openDialog ) {
			return;
		}

		var dialog = openDialog.dialog;
		var overlay = openDialog.overlay;

		dialog.classList.remove( 'is-open' );
		overlay.classList.remove( 'is-open' );
		body.style.overflow = '';

		window.setTimeout( function () {
			dialog.hidden = true;
			overlay.hidden = true;
		}, 220 );

		openDialog = null;

		if ( lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}

		if ( ! fromPopstate && history.state && history.state.merler ) {
			history.back();
		}
	}

	window.addEventListener( 'popstate', function () {
		hide( true );
	} );

	doc.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			hide( false );
		}
	} );

	/* ── Окно блюда ────────────────────────────────── */

	var sheet = doc.querySelector( '.sheet' );
	var sheetOverlay = doc.querySelector( '.overlay:not(.overlay-rate)' );

	function openDish( card ) {
		if ( ! sheet || ! sheetOverlay ) {
			return;
		}

		sheet.querySelector( '.sheet-title' ).textContent = card.getAttribute( 'data-title' ) || '';
		sheet.querySelector( '.sheet-price' ).textContent = card.getAttribute( 'data-price' ) || '';
		sheet.querySelector( '.sheet-weight' ).textContent = card.getAttribute( 'data-weight' ) || '';
		sheet.querySelector( '.sheet-desc' ).textContent = card.getAttribute( 'data-desc' ) || '';

		var photo = card.getAttribute( 'data-photo' );
		var img = sheet.querySelector( '.sheet-photo' );
		var ph = sheet.querySelector( '.ph' );

		if ( photo ) {
			img.src = photo;
			img.alt = card.getAttribute( 'data-title' ) || '';
			img.hidden = false;
			if ( ph ) {
				ph.hidden = true;
			}
		} else {
			img.hidden = true;
			img.removeAttribute( 'src' );
			if ( ph ) {
				ph.hidden = false;
			}
		}

		show( sheet, sheetOverlay );
	}

	doc.addEventListener( 'click', function ( e ) {
		var card = e.target.closest( '.card' );
		if ( card ) {
			openDish( card );
			return;
		}

		if ( e.target.closest( '.sheet-close' ) || e.target.classList.contains( 'overlay' ) ) {
			hide( false );
		}
	} );

	doc.addEventListener( 'keydown', function ( e ) {
		var card = e.target.closest ? e.target.closest( '.card' ) : null;
		if ( card && ( 'Enter' === e.key || ' ' === e.key ) ) {
			e.preventDefault();
			openDish( card );
		}
	} );

	// Свайп вниз закрывает панель.
	if ( sheet ) {
		var startY = null;

		sheet.addEventListener( 'touchstart', function ( e ) {
			startY = e.touches[ 0 ].clientY;
		}, { passive: true } );

		sheet.addEventListener( 'touchmove', function ( e ) {
			if ( null === startY || sheet.scrollTop > 0 ) {
				return;
			}
			if ( e.touches[ 0 ].clientY - startY > 70 ) {
				startY = null;
				hide( false );
			}
		}, { passive: true } );
	}

	/* ── Наверх ────────────────────────────────────── */

	var toTop = doc.querySelector( '.fab-top' );

	if ( toTop ) {
		window.addEventListener( 'scroll', function () {
			toTop.classList.toggle( 'is-visible', window.scrollY > 600 );
		}, { passive: true } );

		toTop.addEventListener( 'click', function () {
			window.scrollTo( { top: 0, behavior: 'smooth' } );
		} );
	}

	/* ── Оценить ───────────────────────────────────── */

	var rateBtn = doc.querySelector( '.fab-rate' );
	var rateDialog = doc.querySelector( '.rate-dialog' );
	var rateOverlay = doc.querySelector( '.overlay-rate' );

	if ( rateBtn && rateDialog && rateOverlay ) {
		var rating = 0;
		var stars = Array.prototype.slice.call( rateDialog.querySelectorAll( '.star' ) );
		var actions = rateDialog.querySelector( '.rate-actions' );
		var form = rateDialog.querySelector( '.rate-form' );
		var message = rateDialog.querySelector( '.rate-message' );
		var phone = rateDialog.querySelector( 'input[name="phone"]' );
		var consent = rateDialog.querySelector( '.rate-consent' );

		rateBtn.addEventListener( 'click', function () {
			show( rateDialog, rateOverlay );
		} );

		rateOverlay.addEventListener( 'click', function () {
			hide( false );
		} );

		stars.forEach( function ( star ) {
			star.addEventListener( 'click', function () {
				rating = parseInt( star.getAttribute( 'data-value' ), 10 );

				stars.forEach( function ( s ) {
					var on = parseInt( s.getAttribute( 'data-value' ), 10 ) <= rating;
					s.classList.toggle( 'is-on', on );
					s.setAttribute( 'aria-checked', on && parseInt( s.getAttribute( 'data-value' ), 10 ) === rating ? 'true' : 'false' );
				} );

				// Оба пути предлагаем одинаково, независимо от оценки.
				if ( actions ) {
					actions.hidden = false;
				}
			} );
		} );

		var direct = rateDialog.querySelector( '.js-rate-direct' );
		if ( direct && form ) {
			direct.addEventListener( 'click', function () {
				form.hidden = false;
				direct.hidden = true;
				form.querySelector( 'textarea' ).focus();
			} );
		}

		if ( phone && consent ) {
			phone.addEventListener( 'input', function () {
				consent.hidden = '' === phone.value.trim();
			} );
		}

		if ( form ) {
			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();

				if ( ! rating ) {
					message.textContent = merlerData.i18n.needStars;
					message.className = 'rate-message is-error';
					return;
				}

				var data = new FormData();
				data.append( 'action', 'merler_review' );
				data.append( 'nonce', merlerData.nonce );
				data.append( 'rating', rating );
				data.append( 'text', form.querySelector( 'textarea' ).value );
				data.append( 'phone', phone ? phone.value : '' );
				data.append( 'consent', form.querySelector( 'input[name="consent"]' ).checked ? '1' : '' );
				data.append( 'merler_hp', form.querySelector( 'input[name="merler_hp"]' ).value );

				message.textContent = merlerData.i18n.sending;
				message.className = 'rate-message';

				fetch( merlerData.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: data
				} )
					.then( function ( r ) {
						return r.json();
					} )
					.then( function ( json ) {
						if ( json && json.success ) {
							message.textContent = json.data && json.data.message ? json.data.message : merlerData.i18n.thanks;
							message.className = 'rate-message is-ok';
							form.querySelector( 'textarea' ).value = '';
							if ( phone ) {
								phone.value = '';
							}
						} else {
							message.textContent = json && json.data && json.data.message ? json.data.message : merlerData.i18n.error;
							message.className = 'rate-message is-error';
						}
					} )
					.catch( function () {
						message.textContent = merlerData.i18n.error;
						message.className = 'rate-message is-error';
					} );
			} );
		}
	}
}() );
