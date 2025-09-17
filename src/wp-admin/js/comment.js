/**
 * @output wp-admin/js/comment.js
 */

/**
 * Binds to the document ready event.
 *
 * @since 2.5.0
 *
 * Rewritten in vanilla JavaScript.
 *
 * @since CP-2.5.0
 */
document.addEventListener( 'DOMContentLoaded', function() {

	var timestampdiv = document.getElementById( 'timestampdiv' ),
		timestamp = document.getElementById( 'timestamp' ),
		stamp = timestamp.innerHTML,
		details = timestamp.nextElementSibling,
		summary = details.querySelector( 'summary' ),
		timestampwrap = timestampdiv.querySelector( '.timestamp-wrap' ),
		mmId = document.getElementById( 'mm' ),
		jjId = document.getElementById( 'jj' ),
		aaId = document.getElementById( 'aa' ),
		hhId = document.getElementById( 'hh' ),
		mnId = document.getElementById( 'mn' );

	/**
	 * Resets the time stamp values when the cancel button is clicked.
	 *
	 * @listens .cancel-timestamp:click
	 *
	 * @param {Event} event The event object.
	 * @return {void}
	 */

	timestampdiv.querySelector( '.cancel-timestamp' ).addEventListener( 'click', function( event ) {
		// Close disclosure widget and set focus
		details.removeAttribute( 'open' );
		summary.focus();

		// Restore original values
		mmId.value = document.getElementById( 'hidden_mm' ).value;
		jjId.value = document.getElementById( 'hidden_jj' ).value;
		aaId.value = document.getElementById( 'hidden_aa' ).value;
		hhId.value = document.getElementById( 'hidden_hh' ).value;
		mnId.value = document.getElementById( 'hidden_mn' ).value;
		timestamp.innerHTML = stamp;
		event.preventDefault();
	} );

	/**
	 * Sets the time stamp values when the ok button is clicked.
	 *
	 * @listens .save-timestamp:click
	 *
	 * @param {Event} event The event object.
	 * @return {void}
	 */
	timestampdiv.querySelector( '.save-timestamp' ).addEventListener( 'click', function( event ) { // Crazyhorse - multiple OK cancels.
		var aa = aaId.value,
			mm = mmId.value,
			jj = jjId.value,
			hh = hhId.value,
			mn = mnId.value,
			newD = new Date( aa, mm - 1, jj, hh, mn );

		event.preventDefault();

		if ( newD.getFullYear() != aa || ( 1 + newD.getMonth() ) != mm || newD.getDate() != jj || newD.getMinutes() != mn ) {
			timestampwrap.classList.add( 'form-invalid' );
			return;
		} else {
			timestampwrap.classList.remove( 'form-invalid' );
		}

		timestamp.innerHTML =
			wp.i18n.__( 'Submitted on:' ) + ' <b>' +
			/* translators: 1: Month, 2: Day, 3: Year, 4: Hour, 5: Minute. */
			wp.i18n.__( '%1$s %2$s, %3$s at %4$s:%5$s' )
				.replace( '%1$s', document.querySelector( 'option[value="' + mm + '"]', '#mm' ).dataset.text )
				.replace( '%2$s', parseInt( jj, 10 ) )
				.replace( '%3$s', aa )
				.replace( '%4$s', ( '00' + hh ).slice( -2 ) )
				.replace( '%5$s', ( '00' + mn ).slice( -2 ) ) +
				'</b> ';

		// Close disclosure widget and set focus
		details.removeAttribute( 'open' );
		summary.focus();
	} );
} );
