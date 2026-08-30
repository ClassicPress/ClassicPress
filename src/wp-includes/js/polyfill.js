/**
 * Element.prototype.setHTML Polyfill (Whitelist Architecture)
 * Supports Safari and older browser variants natively.
 * Includes dynamic custom data-* attribute routing.
 */
// Use only if the browser does not already have native setHTML
if ( ! ( 'setHTML' in Element.prototype ) ) {

	// 1. Establish strict safety baselines
	const ALLOWED_TAGS = ['P', 'BR', 'SPAN', 'DIV', 'A', 'IMG', 'B', 'I', 'STRONG', 'EM', 'U', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'UL', 'OL', 'LI'];
	const ALLOWED_ATTR = ['id', 'class', 'href', 'src', 'alt', 'title', 'target'];

	Element.prototype.setHTML = function( input, options = {} ) {
		// 2. Map and parse parameters to uppercase/lowercase for accurate matrix comparisons
		const config = options.sanitizer || {};
		const validTags = config.allowElements ? config.allowElements.map( t => t.toUpperCase() ) : ALLOWED_TAGS;
		const validAttrs = config.allowAttributes ? config.allowAttributes.map( a => a.toLowerCase() ) : ALLOWED_ATTR;

		// 3. Mount content safely within an inert browser memory fragment
		const template = document.createElement( 'template' );
		template.innerHTML = input;
		const fragment = template.content;

		// 4. Create an optimized flat tree iterator
		const walker = document.createTreeWalker( fragment, NodeFilter.SHOW_ELEMENT );
		let currentNode = walker.currentNode;
		const nodesToRemove = [];

		while ( currentNode ) {
			if ( currentNode !== fragment ) {
				const tagName = currentNode.tagName;

				// Tag Filter: Flag for complete destruction if missing from the whitelist
				if ( ! validTags.includes( tagName ) ) {
					nodesToRemove.push( currentNode );
				} else {
					// Attribute Filter: Strip unapproved data points out of approved elements
					const attrs = currentNode.attributes;
					if ( attrs ) {
						for ( let i = attrs.length - 1; i >= 0; i-- ) {
							const attrName = attrs[i].name.toLowerCase();
							const attrValue = attrs[i].value.trim().toLowerCase();

							// Evaluate structural integrity rules
							const isDataAttr = attrName.startsWith( 'data-' ) && attrName.length > 5;
							const isAllowed = isDataAttr || validAttrs.includes( attrName );
							const isMaliciousUri = ( 'href' === attrName || 'src' === attrName ) && attrValue.startsWith( 'javascript:' );

							if ( ! isAllowed || isMaliciousUri ) {
								currentNode.removeAttribute( attrs[i].name );
							}
						}
					}
				}
			}
			currentNode = walker.nextNode();
		}

		// 5. Safely execute destruction of forbidden DOM structures
		nodesToRemove.forEach( node => node.remove() );

		// 6. Purge container contents and inject the safe node fragment layout
		this.innerHTML = '';
		this.append( fragment );
	};
}
