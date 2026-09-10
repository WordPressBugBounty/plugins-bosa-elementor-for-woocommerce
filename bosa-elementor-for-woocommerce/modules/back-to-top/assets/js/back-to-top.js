( function () {
	'use strict';

	var button = document.querySelector( '.bew-btt-button' );
	if ( ! button ) {
		return;
	}

	var offset      = parseInt( button.getAttribute( 'data-scroll-offset' ), 10 ) || 300;
	var isVisible   = false;
	var isScrolling = false;
	var ticking     = false;

	function toggleButton() {
		var scrollY    = window.pageYOffset || document.documentElement.scrollTop;
		var shouldShow = scrollY > offset;

		if ( shouldShow !== isVisible ) {
			isVisible = shouldShow;
			if ( isVisible ) {
				button.classList.add( 'bew-btt-visible' );
			} else {
				button.classList.remove( 'bew-btt-visible' );
			}
		}
		ticking = false;
	}

	function onScroll() {
		if ( ! ticking ) {
			requestAnimationFrame( toggleButton );
			ticking = true;
		}
	}

	function scrollToTop() {
		if ( isScrolling ) {
			return;
		}
		isScrolling = true;

		window.scrollTo( { top: 0, behavior: 'smooth' } );

		function checkDone() {
			if ( ( window.pageYOffset || document.documentElement.scrollTop ) <= 0 ) {
				isScrolling = false;
				return;
			}
			requestAnimationFrame( checkDone );
		}
		requestAnimationFrame( checkDone );

		setTimeout( function () {
			isScrolling = false;
		}, 2000 );
	}

	window.addEventListener( 'scroll', onScroll, { passive: true } );
	button.addEventListener( 'click', scrollToTop );

	toggleButton();
}() );
