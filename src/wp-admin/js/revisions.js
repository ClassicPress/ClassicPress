/**
 * @file Revisions interface functions.
 *
 * Backbone-free since CP-2.5.0.
 *
 * @output wp-admin/js/revisions.js
 */

/* global console, _wpRevisionsSettings, ajaxurl */

document.addEventListener( 'DOMContentLoaded', function() {

	/**
	 * Native HTML5 range sliders
	 *
	 * Lays one slider over another to create illusion of two handles on one track
	 *
	 * @since CP-2.1.0
	 */
	var fromRevision, toRevision,
		chunks = {}, // This object will hold all the diffs and their metadata
		revisionsArea = document.querySelector( '.revisions' ),
		fromSliderWrapper = document.querySelector( '.from-slider-wrapper' ),
		fromSlider = document.getElementById( 'from-slider' ),
		toSlider = document.getElementById( 'to-slider' ),
		ticksOptions = document.querySelectorAll( '#ticks option' ),
		list = document.getElementById( 'revisions-list' ).value.split( ', ' ),
		fromAuthorCard = document.querySelector( '.diff-meta-from .author-card' ),
		toAuthorCard = document.querySelector( '.diff-meta-to .author-card' ),
		diff = document.querySelector( '.diff' ),
		queryParams = new URLSearchParams( window.location.search ),
		compareTwoRevisions = document.getElementById( 'compare-two-revisions' );


	// Set initial positions for each slider
	if ( queryParams.has( 'from' ) ) { // Compare Two mode
		compareTwoRevisions.checked = true;
		fromSliderWrapper.style.display = 'inline';
		revisionsArea.classList.add( 'comparing-two-revisions' );

		fromSlider.value = list.indexOf( queryParams.get( 'from' ) );
		toSlider.value = list.indexOf( queryParams.get( 'to' ) );
		getDiff( queryParams.get( 'from' ) + ':' + queryParams.get( 'to' ) );
	} else {
		fromSliderWrapper.style.display = 'none';
		revisionsArea.classList.remove( 'comparing-two-revisions' );
		toSlider.value = list.indexOf( queryParams.get( 'revision' ) );
		getDiff( '0:' + queryParams.get( 'revision' ) ); // Will return an array of proximal diffs
	}

	// Track changes in From slider
	fromSlider.addEventListener( 'input', function() {

		// In Compare Two mode, ensure From is not equal to or greater than To
		if ( isVisible( fromSlider ) && fromSlider.value >= toSlider.value ) {
			fromSlider.value = parseInt( toSlider.value, 10 ) - 1;
		}

		// Call appropriate revisions
		fromRevision = isVisible( fromSlider ) ? list[ fromSlider.value ] : list[ 0 ];
		toRevision = list[ toSlider.value ];
		compareRevisions( fromRevision + ':' + toRevision );

		// Update URL
		queryParams.delete( 'revision' );
		queryParams.set( 'from', fromRevision );
		queryParams.set( 'to', toRevision );
		history.pushState( null, null, '?' + queryParams.toString() );
	} );

	// Track changes in To slider
	toSlider.addEventListener( 'input', function() {

		// Call appropriate revisions
		if ( isVisible( fromSlider ) ) {

			// In Compare Two mode, ensure To is not equal to or less than From
			if ( toSlider.value <= fromSlider.value ) {
				toSlider.value = parseInt( fromSlider.value, 10 ) + 1;
			}
			fromRevision = list[ fromSlider.value ];
			toRevision = list[ toSlider.value ];
			queryParams.delete( 'revision' );
			queryParams.set( 'from', fromRevision );
			queryParams.set( 'to', toRevision );
		} else {
			fromRevision = list[ parseInt( toSlider.value, 10 ) - 1 ];
			toRevision = list[ toSlider.value ];
			queryParams.delete( 'from' );
			queryParams.delete( 'to' );
			queryParams.set( 'revision', toRevision );
		}
		compareRevisions( fromRevision + ':' + toRevision );

		// Update URL
		history.pushState( null, null, '?' + queryParams.toString() );
	} );

	// Update To slider after Previous or Next button pressed
	document.addEventListener( 'click', function( e ) {
		if ( e.target.className === 'button' ) {
			if ( e.target.parentNode.className === 'revisions-previous' ) {
				toSlider.value = parseInt( toSlider.value, 10 ) - 1;
				toSlider.dispatchEvent( new Event( 'input' ) );
			} else if ( e.target.parentNode.className === 'revisions-next' ) {
				toSlider.value = parseInt( toSlider.value, 10 ) + 1;
				toSlider.dispatchEvent( new Event( 'input' ) );
			}
		} else if ( e.target.className.includes( 'restore-revision' ) ) {
			document.location = e.target.dataset.restore;
		}
	} );

	// Provide fuller info about a revision when hovering over its summary
	ticksOptions.forEach( function( option ) {
		var tooltip = document.getElementById( 'current-tooltip' );

		option.addEventListener( 'mouseover', function() {
			tooltip.style.backgroundColor = '#fff';
			tooltip.innerHTML = option.dataset.tooltip.replace( '{{', '<span style="color:#d63638">' ).replace( '}}', '</span>' ).replace( '[[', '<strong>' ).replace( ']]', '</strong><br>' );
		} );
		option.addEventListener( 'mouseout', function() {
			tooltip.style.backgroundColor = 'transparent';
			tooltip.innerHTML = '';
		} );
	} );

	// Change mode
	compareTwoRevisions.addEventListener( 'change', function( e ) {

		// Compare Two mode
		if ( e.target.checked ) {
			fromSliderWrapper.style.display = 'inline';
			revisionsArea.classList.add( 'comparing-two-revisions' );

			// Ensure From is not equal to or greater than To
			if ( fromSlider.value >= toSlider.value ) {
				fromSlider.value = parseInt( toSlider.value, 10 ) - 1;
				fromSlider.dispatchEvent( new Event( 'input' ) );
			}

			// If we were on the first revision before switching to two-handled mode,
			// bump the To slider position over one.
			if ( toSlider.value == 0 ) {
				fromSlider.value = 0;
				fromSlider.dispatchEvent( new Event( 'input' ) );
				toSlider.value = 1;
				toSlider.dispatchEvent( new Event( 'input' ) );
			}

			queryParams.delete( 'revision' );
			queryParams.set( 'from', list[ fromSlider.value ] );
			queryParams.set( 'to', list[ toSlider.value ] );
			history.replaceState( null, null, '?' + queryParams.toString() );

		} else {
			fromSliderWrapper.style.display = 'none';
			revisionsArea.classList.remove( 'comparing-two-revisions' );

			// When switching back to single-handled mode, reset From slider to
			// one position before the To slider.
			if ( toSlider.value != 0 ) {
				fromSlider.value = parseInt( toSlider.value, 10 ) - 1;
				fromSlider.dispatchEvent( new Event( 'input' ) );
			}

			queryParams.delete( 'from' );
			queryParams.delete( 'to' );
			queryParams.set( 'revision', list[ toSlider.value ] );
			history.replaceState( null, null, '?' + queryParams.toString() );
		}
	} );

	/**
	 * Checks whether the relevant diff has already been retrieved and stored in the chunks object.
	 *
	 * If so, displays it. If not, retrieves it, adds it to the chunks object, and displays it.
	 *
	 * @since CP-2.5.0
	 */
	function compareRevisions( revisions ) {
		if ( chunks.hasOwnProperty( revisions ) ) {
			fromAuthorCard.innerHTML = chunks[ revisions ].author_left;
			toAuthorCard.innerHTML = chunks[ revisions ].author_right;
			diff.innerHTML = chunks[ revisions ].diffs;
		} else {
			getDiff( revisions );
		}
	}

	/**
	 * Retrieves one or more diffs and their associated metadata.
	 *
	 * Adds diffs to the chunks object and displays the most appropriate one.
	 *
	 * @since CP-2.5.0
	 */
	function getDiff( revisions ) {
		var split = revisions.split( ':' ),
			formData = new FormData();

		if ( split[0] === '0' ) {

			// Return an array of proximal diffs
			for( let i = list.length - 2; i >= 0; i-- ) {
				formData.append( 'compare[]', list[i] + ':' + list[i + 1] );
			}
			formData.append( 'compare[]', '0:' + list[0] );
		} else {

			// Return a specific diff
			if ( split[0] === 'undefined' ) {
				revisions = '0:' + split[1];
			}
			formData.append( 'compare[]', revisions );
		}

		formData.append( 'action', 'get-revision-diffs' );
		formData.append( 'post_id', _wpRevisionsSettings.postId );

		fetch( ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			var index = revisions;

			// Add to the diffs already stored in the chunks object
			Object.assign( chunks, result.data );

			// If an array has been retrieved, choose the appropriate diff for display
			if ( split[0] === '0' ) {
				if ( list[ list.indexOf( split[1] ) - 1 ] !== undefined ) {
					index = list[ list.indexOf( split[1] ) - 1 ] + ':' + split[1];
				}
			}

			// Display the appropriate diff and metadata
			fromAuthorCard.innerHTML = chunks[ index ].author_left;
			toAuthorCard.innerHTML = chunks[ index ].author_right;
			diff.innerHTML = chunks[ index ].diffs;
		} )
		.catch( function( error ) {
			console.error( _wpRevisionsSettings.error, error );
		} );
	}

	/**
	 * Helper function copied from jQuery
	 *
	 * @since CP-2.1.0
	 */
	function isVisible( elem ) {
		return !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length );
	}
} );
