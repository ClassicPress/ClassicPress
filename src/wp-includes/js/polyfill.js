/**
 * Element.prototype.setHTML Polyfill (Whitelist Architecture)
 * Supports Safari and older browser variants natively.
 * Includes dynamic custom data-* attribute routing.
 */
if ( ! ( 'setHTML' in Element.prototype ) ) {

	// Establish strict safety baselines
	const ALLOWED_TAGS = ['P', 'BR', 'SPAN', 'DIV', 'A', 'IMG', 'B', 'I', 'STRONG', 'EM', 'U', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'UL', 'OL', 'LI'];
	const ALLOWED_ATTR = ['id', 'class', 'href', 'src', 'alt', 'title', 'target', 'style'];

	Element.prototype.setHTML = function( input, options = {} ) {
		const config = options.sanitizer || {};

		// Map and parse parameters to uppercase/lowercase for accurate matrix comparisons
		const validTags = config.allowElements ? config.allowElements.map( t => t.toUpperCase() ) : ALLOWED_TAGS;
		const validAttrs = config.allowAttributes ? config.allowAttributes.map( a => a.toLowerCase() ) : ALLOWED_ATTR;

		// Mount content safely within an inert browser memory fragment
		const template = document.createElement( 'template' );
		template.innerHTML = input;
		const fragment = template.content;

		// Use a NodeIterator to safely modify nodes while looping
		const iterator = document.createNodeIterator( fragment, NodeFilter.SHOW_ELEMENT );
		let currentNode;

		while ( ( currentNode = iterator.nextNode() ) ) {
			const tagName = currentNode.tagName;

			// Tag Filter: Flag for complete destruction if missing from the whitelist
			if ( ! validTags.includes( tagName ) ) {
				currentNode.remove();
				continue;
			}

			// Attribute Filter: Strip unapproved data points out of approved elements
			const attrs = currentNode.attributes;
			if ( attrs ) {
				for ( let i = attrs.length - 1; i >= 0; i-- ) {
					const attrName = attrs[i].name.toLowerCase();
					const attrValue = attrs[i].value.trim().toLowerCase();

					// Evaluate structural integrity rules
					const isDataAttr = attrName.startsWith( 'data-' ) && attrName.length > 5;
					const isAriaAttr = attrName.startsWith( 'aria-' ) && attrName.length > 5;
					const isAllowed = isDataAttr || isAriaAttr || validAttrs.includes( attrName );
					const isMaliciousUri = ( 'href' === attrName || 'src' === attrName ) && attrValue.startsWith( 'javascript:' );

					if ( ! isAllowed || isMaliciousUri ) {
						currentNode.removeAttribute( attrs[i].name );
					}
				}
			}
		}

		// Purge container contents and inject the safe node fragment layout
		this.replaceChildren( fragment );
	};
}
