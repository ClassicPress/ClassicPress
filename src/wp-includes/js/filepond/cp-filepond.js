/* global _cpFilepondLabels, FilePond */

/*
 * Create a abstracted Filepond.create so i18n strings are always passed in from core
 */
const originalCreate = FilePond.create;

FilePond.create = function( element, options = {} ) {
	return originalCreate( element, {
		..._cpFilepondLabels,
		...options
	} );
};
