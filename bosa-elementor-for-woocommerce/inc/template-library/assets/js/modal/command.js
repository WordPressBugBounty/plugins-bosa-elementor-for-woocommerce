/* global $e */
/**
 * $e command: bew-library/open
 * Opens (or re-shows) the Bosa Library modal.
 */
var BosaLibraryOpenCommand = $e.commandsInternal.Base.extend({

	apply: function ( args ) {
		if ( ! window.BEWLibraryModal ) {
			return;
		}
		window.BEWLibraryModal.show();
		if ( args && args.tab ) {
			window.BEWLibraryModal.setTab( args.tab );
		}
	},

} );
