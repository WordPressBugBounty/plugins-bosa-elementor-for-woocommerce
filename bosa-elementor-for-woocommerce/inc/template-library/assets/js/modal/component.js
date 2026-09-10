/* global $e */
/**
 * $e component: bosa-library
 * Registers `$e.run('bew-library/open')`.
 * Uses ES6 class syntax required by Elementor 4.x (ComponentBase is an ES6 class).
 */
class BEWLibraryComponent extends $e.modules.ComponentBase {

	getNamespace() {
		return 'bew-library';
	}

	defaultCommands() {
		return {
			open: this.openModal.bind( this ),
		};
	}

	openModal( args ) {
		if ( window.openBEWLibraryModal ) {
			window.openBEWLibraryModal( args );
		}
	}
}
