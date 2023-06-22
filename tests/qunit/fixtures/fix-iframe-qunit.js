// ensure QUnit is available within iframes
if ( window.parent ) {
	window.QUnit = window.parent.window.QUnit;
}
