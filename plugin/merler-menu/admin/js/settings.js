/* Настройки меню: выбор картинок и палитра. */
( function ( $ ) {
	'use strict';

	$( function () {
		$( '.merler-color' ).wpColorPicker();

		$( '.merler-image-field' ).each( function () {
			var $field = $( this );
			var $input = $field.find( '.merler-image-id' );
			var $preview = $field.find( '.merler-image-preview' );
			var $remove = $field.find( '.merler-image-remove' );
			var frame = null;

			$field.on( 'click', '.merler-image-select', function ( e ) {
				e.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				frame = wp.media( {
					title: 'Выберите картинку',
					button: { text: 'Использовать' },
					library: { type: 'image' },
					multiple: false
				} );

				frame.on( 'select', function () {
					var image = frame.state().get( 'selection' ).first().toJSON();
					var url = image.sizes && image.sizes.medium ? image.sizes.medium.url : image.url;

					$input.val( image.id );
					$preview.html( $( '<img>', { src: url, alt: '' } ) );
					$remove.show();
				} );

				frame.open();
			} );

			$remove.on( 'click', function ( e ) {
				e.preventDefault();
				$input.val( '' );
				$preview.empty();
				$remove.hide();
			} );
		} );
	} );
}( jQuery ) );
