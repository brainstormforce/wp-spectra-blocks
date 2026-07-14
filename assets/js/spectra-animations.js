window.addEventListener( 'load', function () {
	setTimeout( function () {
		AOS.init();
		// Webfonts can finish swapping AFTER `load`, re-metricing text and
		// shifting the layout below AOS's cached trigger offsets — below-fold
		// reveals then never fire and their content stays at opacity 0 forever
		// (observed live 2026-07-04; AOS.refresh() un-froze all stuck
		// elements instantly). Recalculate once fonts settle.
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( function () {
				AOS.refresh();
			} );
		}
	}, 0 );
} );
