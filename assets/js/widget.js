/* رفتار باز و بسته شدن دکمه پشتیبانی */
( function () {
	'use strict';

	function init() {
		var widget = document.querySelector( '.support-widget' );
		if ( ! widget ) {
			return;
		}

		var toggle = widget.querySelector( '#support-widget-toggle' );
		var menu = widget.querySelector( '#support-widget-menu' );

		if ( ! toggle || ! menu ) {
			return;
		}

		function setOpen( open ) {
			if ( open ) {
				widget.classList.add( 'is-open' );
				menu.hidden = false;
				toggle.setAttribute( 'aria-expanded', 'true' );
			} else {
				widget.classList.remove( 'is-open' );
				menu.hidden = true;
				toggle.setAttribute( 'aria-expanded', 'false' );
			}
		}

		toggle.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			setOpen( menu.hidden );
		} );

		// بستن منو با کلیک بیرون از ویجت.
		document.addEventListener( 'click', function ( e ) {
			if ( ! widget.contains( e.target ) ) {
				setOpen( false );
			}
		} );

		// بستن منو با کلید Escape.
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				setOpen( false );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
