/*
 * Improves main menu accessibility by enabling main menu to be closed
 * using Escape button and re-opened using Down arrow
 *
 * Also ensures page is reloaded after resizing
 *
 * Author: Tim Kaye
 */
document.addEventListener( 'DOMContentLoaded', function () {
	'use strict'; // satisfy code inspectors

	// Exit if no Primary Menu is set
	if ( ! document.getElementById( 'primary-menu' ) ) {
		return;
	}

	// Provide accessible labels for sub-menus
	const subMenus = document.querySelectorAll( '.sub-menu' );
	subMenus.forEach(sub => {
		sub.setAttribute( 'role', 'group' );
		sub.setAttribute( 'aria-label', 'submenu' );
	}, false);

	/* SHOW AND HIDE MENU AND TOGGLE BUTTONS ON MOBILE */
	if ( window.matchMedia( "screen and (max-width: 899px)" ).matches ) {
		const menuToggle = document.getElementById( 'menu-toggle' );
		const menuToggleClose = document.getElementById( 'menu-toggle-close' );
		const primaryMenu = document.getElementById( 'primary-menu' );
		const mobileSearch = document.getElementById( 'mobile-search' );
		const mobileLinks = primaryMenu.querySelectorAll( 'a' );
		const parents = primaryMenu.querySelectorAll( '.menu-item-has-children > a' );

		const form = document.createElement( 'form' );
		form.role = 'search';
		form.className = 'search-form';
		form.action = document.querySelector( '#inner-header .logo a' ).href;
		form.innerHTML = '<label><span class="screen-reader-text">'+cp_menu_object.SearchFor+'</span><input type="search" class="search-field" placeholder="'+cp_menu_object.SearchFor+'" value="" name="s"></label><input type="submit" class="search-submit" value="'+cp_menu_object.Search+'">';
		primaryMenu.append(form);

		// Toggle menu open and closed
		menuToggle.addEventListener( 'click', function (e) {
			menuToggle.style.display = 'none';
			primaryMenu.style.display = 'flex';
			menuToggleClose.style.display = 'block';

			menuToggle.setAttribute( 'aria-expanded', true);
			primaryMenu.classList.add( 'is-open' );
			primaryMenu.setAttribute( 'aria-hidden', false);

			subMenus.forEach(sub => sub.classList.add( 'closed' ));
			parents.forEach(sub => sub.setAttribute( 'aria-expanded', false));
			menuToggleClose.focus();
		}, false);

		menuToggleClose.addEventListener( 'click', function (e) {
			menuToggle.style.display = 'block';
			menuToggleClose.style.display = 'none';
			primaryMenu.style.display = 'none';

			menuToggle.setAttribute( 'aria-expanded', false);
			primaryMenu.classList.remove( 'is-open' );
			primaryMenu.setAttribute( 'aria-hidden', true);

			menuToggle.focus();
		}, false);

		// Submenus
		parents.forEach( function( parent ) {
			const button = document.createElement( 'button' );
			button.role = 'button';
			button.setAttribute( 'aria-haspopup', true );
			button.setAttribute( 'aria-expanded', false );
			button.innerHTML = '<span>&#x25BC;<span class="screen-reader-text">'+cp_menu_object.OpenSubMenu+'</span></span>';

			parent.parentNode.classList.remove( 'menu-item-has-children' );
			parent.after( button );

			button.addEventListener( 'click', function(e) {

				let submenu = button.nextElementSibling;
				if (submenu.className.includes( 'closed' ) ) {
					submenu.style.display = 'block';
					submenu.classList.remove( 'closed' );
					submenu.classList.add( 'open' );
					submenu.setAttribute( 'aria-hidden', false );
					button.setAttribute( 'aria-expanded', true );
				} else {
					submenu.style.display = 'none';
					submenu.classList.remove( 'open' );
					submenu.classList.add( 'closed' );
					submenu.setAttribute( 'aria-hidden', true );
					button.setAttribute( 'aria-expanded', false );
				}
			});
		}, false);

		// Close menu using Escape key
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				if (primaryMenu.querySelector( '.open' ) == null ) {
					if ( menuToggleClose.hasAttribute( 'aria-expanded' ) ) {
						menuToggleClose.click();
						menuToggle.focus(); // focus on menu button
					}
				} else {
					let opens = primaryMenu.querySelectorAll( '.open' );
					opens.forEach(function (open) {
						open.style.display = 'none';
						open.classList.remove( 'open' );
						open.classList.add( 'closed' );
						open.setAttribute( 'aria-expanded', false);
					});
				}
			} else if ( e.key === 'Tab' ) { // Prevent tabbing out of mobile menu
				if ( e.shiftKey ) {
					if (e.target === mobileLinks[0]) {
						e.preventDefault();
						menuToggleClose.focus();
					}
				} else if ( e.target === menuToggleClose ) {
					e.preventDefault();
					mobileLinks[0].focus();
				}
			}
		}, false);

	} else {
		const menuItems = [...document.querySelectorAll( '#primary-menu .menu-item a' )];

		/* Show or hide sub-menus by pressing appropriate keys for accessibility */
		document.addEventListener( 'keydown', function (e) {
			for ( let i = 0, n = subMenus.length; i < n; i++ ) {
				if ( e.key === 'Escape' ) {
					let size = subMenus[i].getBoundingClientRect();
					if ( size.height !== 0 ) {
						subMenus[i].previousElementSibling.focus();
						subMenus[i].style.display = 'none';
					}
				}
				else if ( e.key === 'ArrowDown' ) {
					if ( menuItems.includes( e.target ) ) {
						e.preventDefault();
					}
					if ( subMenus[i].style.display === 'none' ) {
						subMenus[i].removeAttribute( 'style' );
					}
				}
				else if ( e.key === 'Tab' ) {
					if ( subMenus[i].style.display === 'none' ) {
						setTimeout(function () {
							subMenus[i].removeAttribute( 'style' );
						}, 100);
					}
				}
			}
		}, false);
	}

	/* RELOAD ON RESIZE BEYOND MEDIA QUERY BREAKPOINT */
	var windoe = window;
	var windowWidth = window.innerWidth;

	window.addEventListener( 'resize', function () {
		if ( ( windowWidth >= 900 && window.innerWidth < 900 ) || ( windowWidth < 900 && window.innerWidth >= 900 ) ) {
			if ( windoe.RT ) {
				clearTimeout(windoe.RT);
			}
			windoe.RT = setTimeout( function () {
				this.location.reload(false); /* false to get page from cache */
			}, 100 );
		}
	}, false );

} );
