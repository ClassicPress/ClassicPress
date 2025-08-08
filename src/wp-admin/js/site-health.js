/**
 * Interactions used by the Site Health modules in WordPress.
 *
 * @since CP-2.6.0
 *
 * @output wp-admin/js/site-health.js
 */

/* global ajaxurl, SiteHealth, wp */

document.addEventListener( 'DOMContentLoaded', function () {

    var { __, _n, sprintf } = wp.i18n,
		clipboard = document.querySelector( '.site-health-copy-buttons .copy-button' ),
		isStatusTab = document.querySelectorAll( '.health-check-body.health-check-status-tab' ).length,
		isDebugTab = document.querySelectorAll( '.health-check-body.health-check-debug-tab' ).length,
		pathsSizesSection = document.querySelector( '#health-check-accordion-block-wp-paths-sizes' ),
		menuCounterWrapper = document.querySelector( '#adminmenu .site-health-counter' ),
		menuCounter = document.querySelector( '#adminmenu .site-health-counter .count' ),
		successTimeout;

	/*
	 * Debug information copy section.
	 *
	 * Uses Clipboard API (with execCommand fallback for sites
	 * on neither https nor localhost).
	 *
	 * @since CP-2.2.0
	 */
	if ( clipboard ) {
		clipboard.addEventListener( 'click', function() {
			var copyText = clipboard.dataset.clipboardText,
				input = document.createElement( 'input' );

			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( copyText );
			} else {
				document.body.append( input );
				input.value = copyText;
				input.select();
				document.execCommand( 'copy' );
			}

			// Show success visual feedback.
			clearTimeout( successTimeout );
			clipboard.nextElementSibling.classList.remove( 'hidden' );
			clipboard.nextElementSibling.setAttribute( 'aria-hidden', 'false' );
			input.remove();

			// Hide success visual feedback after 3 seconds since last success and unfocus the trigger.
			successTimeout = setTimeout( function() {
				clipboard.nextElementSibling.classList.add( 'hidden' );
				clipboard.nextElementSibling.setAttribute( 'aria-hidden', 'true' );
			}, 3000 );

			// Handle success audible feedback.
			wp.a11y.speak( wp.i18n.__( 'Site information has been copied to your clipboard.' ) );
		} );
	}

	// Toggle "view passed" section
    document.querySelector( '.site-health-view-passed' ).addEventListener( 'click', function( e ) {
		var goodIssuesWrapper = document.getElementById( 'health-check-issues-good' );
		goodIssuesWrapper.classList.toggle( 'hidden' );
        e.target.setAttribute( 'aria-expanded', ! goodIssuesWrapper.className.includes( 'hidden' ) );
    } );

    function validateIssueData( issue ) {
        var minimumExpected = {
            test: 'string',
            label: 'string',
            description: 'string'
        };

        if ( typeof issue !== 'object' ) {
			return false;
		}

        for ( var key in minimumExpected ) {
            var expectedType = minimumExpected[key];
            if ( typeof issue[key] === 'undefined' || typeof issue[key] !== expectedType ) {
                return false;
            }
        }
        return true;
    }

    function appendIssue( issue ) {
        var count,
			template = wp.template( 'health-check-issue' ),
			issueWrapper = document.getElementById( 'health-check-issues-' + issue.status );

        if ( !validateIssueData( issue ) ) {
			return false;
		}

        SiteHealth.site_status.issues[issue.status]++;
        count = SiteHealth.site_status.issues[issue.status];

        if ( typeof issue.test === 'undefined' ) {
            issue.test = issue.status + count;
        }

        var heading = '';
        if ( issue.status === 'critical' ) {
            heading = sprintf(
                _n( '%s critical issue', '%s critical issues', count ),
                '<span class="issue-count">' + count + '</span>'
            );
        } else if ( issue.status === 'recommended' ) {
            heading = sprintf(
                _n( '%s recommended improvement', '%s recommended improvements', count ),
                '<span class="issue-count">' + count + '</span>'
            );
        } else if ( issue.status === 'good' ) {
            heading = sprintf(
                _n( '%s item with no issues detected', '%s items with no issues detected', count ),
                '<span class="issue-count">' + count + '</span>'
            );
        }

        if ( heading ) {
            issueWrapper.querySelector( '.site-health-issue-count-title' ).innerHTML = heading;
        }

        menuCounter.textContent = SiteHealth.site_status.issues.critical;

        if ( parseInt( SiteHealth.site_status.issues.critical, 10 ) > 0 ) {
            document.getElementById( 'health-check-issues-critical' ).classList.remove( 'hidden' );
            menuCounterWrapper.classList.remove( 'count-0' );
        } else {
            menuCounterWrapper.classList.add( 'count-0' );
        }
        if ( parseInt( SiteHealth.site_status.issues.recommended, 10 ) > 0 ) {
            document.getElementById( 'health-check-issues-recommended' ).classList.remove( 'hidden' );
        }

        issueWrapper.querySelector( '.issues' ).insertAdjacentHTML( 'beforeend', template( issue ) );
    }

    function recalculateProgression() {
        var r, c, pct,
			progress = document.querySelector( '.site-health-progress' ),
			wrapper = progress.closest( '.site-health-progress-wrapper' ),
			progressLabel = wrapper.querySelector( '.site-health-progress-label' ),
			circle = document.querySelector( '.site-health-progress svg #bar' ),
			totalTests =
				parseInt( SiteHealth.site_status.issues.good, 10 ) +
				parseInt( SiteHealth.site_status.issues.recommended, 10 ) +
				( parseInt( SiteHealth.site_status.issues.critical, 10 ) * 1.5 ),
			failedTests =
				( parseInt( SiteHealth.site_status.issues.recommended, 10 ) * 0.5 ) +
				( parseInt( SiteHealth.site_status.issues.critical, 10 ) * 1.5 ),
			val = 100 - Math.ceil( ( failedTests / totalTests ) * 100 );

        if ( totalTests === 0 ) {
            progress.classList.add( 'hidden' );
            return;
        }

        wrapper.classList.remove( 'loading' );
        r = circle.getAttribute( 'r' );
        c = Math.PI * ( r * 2 );
        val = Math.max( 0, Math.min( 100, val ) );

        pct = ( ( 100 - val ) / 100 ) * c + 'px';
        circle.style.strokeDashoffset = pct;

        if ( val >= 80 && parseInt( SiteHealth.site_status.issues.critical, 10 ) === 0 ) {
            wrapper.classList.add( 'green' );
            wrapper.classList.remove( 'orange' );
            progressLabel.textContent = __( 'Good' );
            wp.a11y.speak( __( 'All site health tests have finished running. Your site is looking good, and the results are now available on the page.' ) );
        } else {
            wrapper.classList.add( 'orange' );
            wrapper.classList.remove( 'green' );
            progressLabel.textContent = __( 'Should be improved' );
            wp.a11y.speak( __( 'All site health tests have finished running. There are items that should be addressed, and the results are now available on the page.' ) );
        }

        if ( isStatusTab ) {
            fetch( ajaxurl, {
                method: 'POST',
                body: new URLSearchParams( {
                    action: 'health-check-site-status-result',
                    _wpnonce: SiteHealth.nonce.site_status_result,
                    counts: JSON.stringify( SiteHealth.site_status.issues )
                } ),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            } );

            if ( val === 100 ) {
                document.querySelector( '.site-status-all-clear' ).classList.remove( 'hide' );
                document.querySelector( '.site-status-has-issues' ).classList.add( 'hide' );
            }
        }
    }

    function addFailedSiteHealthCheckNotice( url, description ) {
        var issue = {
            status: 'recommended',
            label: __( 'A test is unavailable' ),
            badge: { color: 'red', label: __( 'Unavailable' ) },
            description: '<p>' + url + '</p><p>' + description + '</p>',
            actions: ''
        };
        appendIssue( wp.hooks.applyFilters( 'site_status_test_result', issue ) );
    }

    function maybeRunNextAsyncTest() {
        var doCalculation = true;
        if ( SiteHealth.site_status.async.length >= 1 ) {
            SiteHealth.site_status.async.forEach( function( testObj ) {
                if ( testObj.completed ) {
					return;
				}
                doCalculation = false;
                testObj.completed = true;

                if ( typeof testObj.has_rest !== 'undefined' && testObj.has_rest ) {
                    wp.apiRequest( {
                        url: wp.url.addQueryArgs( testObj.test, { _locale: 'user' } ),
                        headers: testObj.headers
                    } ).done( function( response ) {
                        appendIssue( wp.hooks.applyFilters( 'site_status_test_result', response ) );
                    } ).fail( function( response ) {
                        var description = ( response.responseJSON && response.responseJSON.message ) || __( 'No details available' );
                        addFailedSiteHealthCheckNotice( testObj.url, description );
                    } ).always( maybeRunNextAsyncTest );
                } else {
                    fetch( ajaxurl, {
                        method: 'POST',
                        body: new URLSearchParams( {
                            action: 'health-check-' + testObj.test.replace( '_', '-' ),
                            _wpnonce: SiteHealth.nonce.site_status
                        } ),
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                    } ).then( function( r ) {
						r.json();
					} ).then( function( data ) {
                        appendIssue( wp.hooks.applyFilters( 'site_status_test_result', data.data ) );
                    } ).catch( function( response ) {
                        var description = ( response.responseJSON && response.responseJSON.message ) || __( 'No details available' );
                        addFailedSiteHealthCheckNotice( testObj.url, description );
                    } ).finally( maybeRunNextAsyncTest );
                }
            } );
        }
        if ( doCalculation ) {
            recalculateProgression();
        }
    }

    function getDirectorySizes() {
        var timestamp = Date.now(),
			timeout = setTimeout( function() {
				wp.a11y.speak( __( 'Please wait...' ) );
			}, 3000);

        wp.apiRequest({
            path: '/wp-site-health/v1/directory-sizes'
        } ).done( function( response ) {
            updateDirSizes( response || {} );
        } ).always( function() {
            var speakDelay,
				delay = Date.now() - timestamp;
            if ( document.querySelector( '.health-check-wp-paths-sizes.spinner' ) ) {
				document.querySelector( '.health-check-wp-paths-sizes.spinner' ).style.visibility = 'hidden';
			}
            recalculateProgression();

            if ( delay > 3000 ) {
                speakDelay = delay > 6000 ? 0 : 6500 - delay;
                setTimeout( function() {
                    wp.a11y.speak( __( 'All site health tests have finished running.' ) );
                }, speakDelay );
            } else {
                clearTimeout( timeout );
            }

            document.dispatchEvent( new Event( 'site-health-info-dirsizes-done' ) );
        } );
    }

    function updateDirSizes( data ) {
        var copyButton = document.querySelector( 'button.button.copy-button' ),
			clipboardText = copyButton.getAttribute( 'data-clipboard-text' );

        Object.entries( data ).forEach( function( [name, value] ) {
            var text = value.debug || value.size;
            if ( typeof text !== 'undefined' ) {
                clipboardText = clipboardText.replace( name + ': loading...', name + ': ' + text );
            }
        } );

        copyButton.setAttribute( 'data-clipboard-text', clipboardText );

        pathsSizesSection.querySelectorAll( 'td[class]' ).forEach( function( td ) {
            var name = td.getAttribute( 'class' );
            if ( data.hasOwnProperty( name ) && data[name].size ) {
                td.textContent = data[name].size;
            }
        } );
    }

    // Init
    if ( typeof SiteHealth !== 'undefined' ) {
        if ( SiteHealth.site_status.direct.length === 0 && SiteHealth.site_status.async.length === 0 ) {
            recalculateProgression();
        } else {
            SiteHealth.site_status.issues = { good: 0, recommended: 0, critical: 0 };
        }

        if ( SiteHealth.site_status.direct.length > 0 ) {
            SiteHealth.site_status.direct.forEach(appendIssue);
        }
        if ( SiteHealth.site_status.async.length > 0 ) {
            maybeRunNextAsyncTest();
        } else {
            recalculateProgression();
        }
    }

    if ( isDebugTab ) {
        if ( pathsSizesSection ) {
            getDirectorySizes();
        } else {
            recalculateProgression();
        }
    }

    // Toggle navigation visibility
    document.querySelectorAll( '.health-check-offscreen-nav-wrapper' ).forEach( function( el ) {
        el.addEventListener( 'click', function() {
			el.classList.toggle( 'visible' );
		} );
    } );

} );
