/* Перетаскивание блюд и разделов. */
( function () {
	'use strict';

	var status = document.getElementById( 'merler-order-status' );
	var timer = null;

	function say( text, isError ) {
		if ( ! status ) {
			return;
		}
		status.textContent = text;
		status.className = 'merler-order-status is-visible' + ( isError ? ' is-error' : '' );
		window.clearTimeout( timer );
		timer = window.setTimeout( function () {
			status.className = 'merler-order-status';
		}, 2500 );
	}

	function save( type, ids ) {
		var body = new URLSearchParams();
		body.append( 'action', 'merler_save_order' );
		body.append( 'nonce', merlerOrder.nonce );
		body.append( 'type', type );
		ids.forEach( function ( id ) {
			body.append( 'ids[]', id );
		} );

		fetch( merlerOrder.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( json ) {
				if ( json && json.success ) {
					say( merlerOrder.saved, false );
				} else {
					say( merlerOrder.error, true );
				}
			} )
			.catch( function () {
				say( merlerOrder.error, true );
			} );
	}

	function collect( list ) {
		var type = list.getAttribute( 'data-sortable' );
		var attr = 'dishes' === type ? 'data-post' : 'data-term';
		var ids = [];

		Array.prototype.forEach.call( list.children, function ( li ) {
			var id = li.getAttribute( attr );
			if ( id ) {
				ids.push( id );
			}
		} );

		return { type: type, ids: ids };
	}

	document.querySelectorAll( '[data-sortable]' ).forEach( function ( list ) {
		Sortable.create( list, {
			handle: '.merler-handle',
			animation: 140,
			ghostClass: 'merler-ghost',
			onEnd: function () {
				var data = collect( list );
				if ( data.ids.length ) {
					save( data.type, data.ids );
				}
			}
		} );
	} );
}() );
