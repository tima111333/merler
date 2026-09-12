/* Подстановка значений в форму быстрого редактирования. */
( function ( $ ) {
	'use strict';

	if ( ! window.inlineEditPost ) {
		return;
	}

	var original = window.inlineEditPost.edit;

	window.inlineEditPost.edit = function ( id ) {
		original.apply( this, arguments );

		var postId = 0;

		if ( 'object' === typeof id ) {
			postId = parseInt( this.getId( id ), 10 );
		} else {
			postId = parseInt( id, 10 );
		}

		if ( ! postId ) {
			return;
		}

		var data = document.getElementById( 'merler-inline-' + postId );
		var row = document.getElementById( 'edit-' + postId );

		if ( ! data || ! row ) {
			return;
		}

		var price = row.querySelector( 'input[name="merler_price"]' );
		var weight = row.querySelector( 'input[name="merler_weight"]' );
		var status = row.querySelector( 'select[name="merler_status"]' );

		if ( price ) {
			price.value = data.getAttribute( 'data-price' ) || '';
		}
		if ( weight ) {
			weight.value = data.getAttribute( 'data-weight' ) || '';
		}
		if ( status ) {
			status.value = data.getAttribute( 'data-status' ) || 'in_stock';
		}
	};
}( jQuery ) );
