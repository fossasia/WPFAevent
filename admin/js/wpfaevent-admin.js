(function( $ ) {
	'use strict';

	$( function() {
		$( '.wpfaevent-custom-tabs' ).each( function() {
			var $box = $( this );
			var $list = $box.find( '.wpfaevent-custom-tabs-list' );
			var $empty = $box.find( '.wpfaevent-custom-tabs-empty' );
			var $template = $box.find( '.wpfaevent-custom-tab-template' );
			var toggleEmptyState = function() {
				$empty.prop( 'hidden', $list.find( '.wpfaevent-custom-tab-row' ).length > 0 );
			};

			$box.on( 'click', '.wpfaevent-add-custom-tab', function( event ) {
				var nextIndex = parseInt( $box.attr( 'data-next-index' ), 10 );
				var templateHtml = $template.html();
				var $newRow;

				event.preventDefault();

				if ( isNaN( nextIndex ) ) {
					nextIndex = $list.find( '.wpfaevent-custom-tab-row' ).length;
				}

				if ( ! templateHtml ) {
					return;
				}

				$list.append( templateHtml.replace( /\{\{INDEX\}\}/g, nextIndex ) );
				$box.attr( 'data-next-index', nextIndex + 1 );
				toggleEmptyState();

				$newRow = $list.find( '.wpfaevent-custom-tab-row' ).last();
				$newRow.find( 'input[type="text"]' ).trigger( 'focus' );
			} );

			$box.on( 'click', '.wpfaevent-remove-custom-tab', function( event ) {
				event.preventDefault();
				$( this ).closest( '.wpfaevent-custom-tab-row' ).remove();
				toggleEmptyState();
			} );

			toggleEmptyState();
		} );
	} );

})( jQuery );
