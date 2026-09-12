/* Генерация QR-кода и настольной таблички A6. Всё локально, без внешних сервисов. */
( function () {
	'use strict';

	var state = {
		url: merlerQR.url,
		logoData: ''
	};

	/* ── QR как матрица ────────────────────────────── */

	function matrix( text ) {
		var qr = qrcode( 0, 'H' ); // Уровень коррекции H: код читается даже с логотипом в центре.
		qr.addData( text );
		qr.make();
		return qr;
	}

	function svgMarkup( text, size, withLogo, logoHref ) {
		var qr = matrix( text );
		var count = qr.getModuleCount();
		var quiet = 4; // Обязательное белое поле вокруг кода.
		var total = count + quiet * 2;
		var path = [];
		var r, c;

		for ( r = 0; r < count; r++ ) {
			for ( c = 0; c < count; c++ ) {
				if ( qr.isDark( r, c ) ) {
					path.push( 'M' + ( c + quiet ) + ' ' + ( r + quiet ) + 'h1v1h-1z' );
				}
			}
		}

		var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '" viewBox="0 0 ' + total + ' ' + total + '" shape-rendering="crispEdges">';
		svg += '<rect width="' + total + '" height="' + total + '" fill="#ffffff"/>';
		svg += '<path d="' + path.join( '' ) + '" fill="#14120E"/>';

		if ( withLogo && logoHref ) {
			// Логотип — не больше 20% площади кода.
			var box = Math.round( total * 0.22 );
			var pos = ( total - box ) / 2;
			svg += '<rect x="' + ( pos - 0.6 ) + '" y="' + ( pos - 0.6 ) + '" width="' + ( box + 1.2 ) + '" height="' + ( box + 1.2 ) + '" rx="1" fill="#ffffff"/>';
			svg += '<image x="' + pos + '" y="' + pos + '" width="' + box + '" height="' + box + '" preserveAspectRatio="xMidYMid meet" href="' + logoHref + '"/>';
		}

		svg += '</svg>';

		return svg;
	}

	function download( name, content, type ) {
		var blob = new Blob( [ content ], { type: type } );
		var url = URL.createObjectURL( blob );
		var a = document.createElement( 'a' );
		a.href = url;
		a.download = name;
		document.body.appendChild( a );
		a.click();
		document.body.removeChild( a );
		window.setTimeout( function () {
			URL.revokeObjectURL( url );
		}, 1000 );
	}

	function svgToPng( svg, size, filename ) {
		var img = new Image();
		var blob = new Blob( [ svg ], { type: 'image/svg+xml;charset=utf-8' } );
		var url = URL.createObjectURL( blob );

		img.onload = function () {
			var canvas = document.createElement( 'canvas' );
			canvas.width = size;
			canvas.height = size;
			var ctx = canvas.getContext( '2d' );
			ctx.fillStyle = '#ffffff';
			ctx.fillRect( 0, 0, size, size );
			ctx.imageSmoothingEnabled = false;
			ctx.drawImage( img, 0, 0, size, size );
			URL.revokeObjectURL( url );

			canvas.toBlob( function ( pngBlob ) {
				var pngUrl = URL.createObjectURL( pngBlob );
				var a = document.createElement( 'a' );
				a.href = pngUrl;
				a.download = filename;
				document.body.appendChild( a );
				a.click();
				document.body.removeChild( a );
				window.setTimeout( function () {
					URL.revokeObjectURL( pngUrl );
				}, 1000 );
			}, 'image/png' );
		};

		img.onerror = function () {
			URL.revokeObjectURL( url );
			window.alert( 'Не удалось собрать PNG. Скачайте SVG — он подходит для типографии.' );
		};

		img.src = url;
	}

	/* ── Логотип в base64, чтобы SVG был самодостаточным ── */

	function loadLogo() {
		return new Promise( function ( resolve ) {
			if ( ! merlerQR.logo ) {
				resolve( '' );
				return;
			}

			var img = new Image();
			img.crossOrigin = 'anonymous';

			img.onload = function () {
				try {
					var canvas = document.createElement( 'canvas' );
					canvas.width = img.naturalWidth;
					canvas.height = img.naturalHeight;
					canvas.getContext( '2d' ).drawImage( img, 0, 0 );
					resolve( canvas.toDataURL( 'image/png' ) );
				} catch ( e ) {
					resolve( merlerQR.logo );
				}
			};

			img.onerror = function () {
				resolve( '' );
			};

			img.src = merlerQR.logo;
		} );
	}

	/* ── Табличка A6 ───────────────────────────────── */

	function tentSvg() {
		var qrSvg = svgMarkup( state.url, 300, false, '' );
		var qrInner = qrSvg.replace( /^<svg[^>]*>/, '' ).replace( /<\/svg>$/, '' );
		var qrSize = 56; // мм
		var qrX = ( 105 - qrSize ) / 2;
		var count = matrix( state.url ).getModuleCount() + 8;

		var wifi = '';
		if ( merlerQR.wifiShow && merlerQR.wifiName ) {
			wifi = '<text x="52.5" y="139" text-anchor="middle" font-family="Arial, sans-serif" font-size="3.4" fill="' + merlerQR.accent + '">'
				+ 'Wi-Fi: ' + escapeXml( merlerQR.wifiName )
				+ ( merlerQR.wifiPass ? ' · ' + escapeXml( merlerQR.wifiPass ) : '' )
				+ '</text>';
		}

		var logo = state.logoData
			? '<image x="32.5" y="12" width="40" height="26" preserveAspectRatio="xMidYMid meet" href="' + state.logoData + '"/>'
			: '<text x="52.5" y="30" text-anchor="middle" font-family="Georgia, serif" font-size="11" letter-spacing="1" fill="' + merlerQR.accent + '">МЕРЛЕР</text>';

		return '<svg xmlns="http://www.w3.org/2000/svg" width="105mm" height="148mm" viewBox="0 0 105 148">'
			+ '<rect width="105" height="148" fill="' + merlerQR.bg + '"/>'
			+ '<rect x="4" y="4" width="97" height="140" rx="4" fill="none" stroke="' + merlerQR.accent + '" stroke-width="0.4" opacity="0.45"/>'
			+ logo
			+ '<text x="52.5" y="50" text-anchor="middle" font-family="Arial, sans-serif" font-size="5" fill="' + merlerQR.accent + '">'
			+ escapeXml( merlerQR.i18n.scan ) + '</text>'
			+ '<g transform="translate(' + qrX + ' 58) scale(' + ( qrSize / count ) + ')">' + qrInner + '</g>'
			+ '<text x="52.5" y="130" text-anchor="middle" font-family="Arial, sans-serif" font-size="4.6" fill="#14120E">'
			+ escapeXml( merlerQR.phone ) + '</text>'
			+ wifi
			+ '</svg>';
	}

	function escapeXml( s ) {
		return String( s || '' )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	/* ── Отрисовка страницы ────────────────────────── */

	function render() {
		var plain = document.getElementById( 'merler-qr-plain' );
		var logo = document.getElementById( 'merler-qr-logo' );
		var tent = document.getElementById( 'merler-tent' );

		if ( plain ) {
			plain.innerHTML = svgMarkup( state.url, 220, false, '' );
		}
		if ( logo ) {
			logo.innerHTML = svgMarkup( state.url, 220, true, state.logoData );
		}
		if ( tent ) {
			tent.innerHTML = tentSvg();
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var input = document.getElementById( 'merler-qr-url' );

		loadLogo().then( function ( data ) {
			state.logoData = data;
			render();
		} );

		if ( input ) {
			input.addEventListener( 'input', function () {
				state.url = input.value.trim() || merlerQR.url;
				render();
			} );
		}

		document.querySelectorAll( '[data-qr-download]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var withLogo = 'logo' === btn.getAttribute( 'data-qr-download' );
				var format = btn.getAttribute( 'data-format' );
				var svg = svgMarkup( state.url, 2000, withLogo, state.logoData );
				var name = 'merler-qr' + ( withLogo ? '-logo' : '' );

				if ( 'svg' === format ) {
					download( name + '.svg', svg, 'image/svg+xml' );
				} else {
					svgToPng( svg, 2000, name + '-2000.png' );
				}
			} );
		} );

		var tentSvgBtn = document.getElementById( 'merler-tent-svg' );
		if ( tentSvgBtn ) {
			tentSvgBtn.addEventListener( 'click', function () {
				download( 'merler-tablichka-a6.svg', tentSvg(), 'image/svg+xml' );
			} );
		}

		var printBtn = document.getElementById( 'merler-tent-print' );
		if ( printBtn ) {
			printBtn.addEventListener( 'click', function () {
				var win = window.open( '', '_blank' );
				if ( ! win ) {
					window.alert( 'Разрешите всплывающие окна, чтобы открыть печать.' );
					return;
				}
				win.document.write(
					'<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Табличка «Мерлер»</title>'
					+ '<style>@page{size:105mm 148mm;margin:0}html,body{margin:0;padding:0}svg{display:block}</style>'
					+ '</head><body>' + tentSvg() + '</body></html>'
				);
				win.document.close();
				win.focus();
				window.setTimeout( function () {
					win.print();
				}, 400 );
			} );
		}
	} );
}() );
