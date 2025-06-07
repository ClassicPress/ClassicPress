/* global MediaElementPlayer */

document.addEventListener( 'DOMContentLoaded', function() {

	// Script set by wp_playlist_shortcode function in wp-includes/media.php
	var playlists = document.querySelectorAll( '.wp-playlist-script' ),
		mediaElements = document.querySelectorAll( 'audio, video' );

	// Set up playlists when page loads
	playlists.forEach( function( playlist ) {
		var playlistData = JSON.parse( playlist.innerHTML ),
			type = playlistData.type,
			tracks = playlistData.tracks,
			firstTrack = tracks[0],
			title = firstTrack.title ? firstTrack.title : '',
			album = firstTrack.meta.album ? firstTrack.meta.album : '',
			artist = firstTrack.meta.artist ? firstTrack.meta.artist : '',
			img = document.createElement( 'img' ),
			div = document.createElement( 'div' ),
			ol = document.createElement( 'ol' ),
			playlistEl = playlist.closest( '.wp-playlist' ),
			source = document.createElement( 'source' );

		img.src = firstTrack.image.src;
		img.alt = '';

		div.className = 'wp-playlist-caption';
		div.innerHTML = '<span class="wp-playlist-item-meta wp-playlist-item-title">' + title + '</span>' +
			'<span class="wp-playlist-item-meta wp-playlist-item-album">' + album +  '</span>' +
			'<span class="wp-playlist-item-meta wp-playlist-item-artist">' + artist + '</span>';

		ol.className = 'wp-playlist-tracks';
		if ( playlistEl.parentNode.className === 'textwidget' ) {
			ol.style = 'margin-left:-1em';
		}

		tracks.forEach( function( track ) {
			var li = document.createElement( 'li' ),
				title = track.title ? track.title : '',
				album = track.meta.album ? track.meta.album : '',
				artist = track.meta.artist ? track.meta.artist : '',
				length = track.meta.length_formatted ? track.meta.length_formatted : '';

			li.className = 'wp-playlist-item';
			li.dataset.title = title;
			li.dataset.album = album;
			li.dataset.artist = artist;
			li.dataset.img = track.image.src;
			li.dataset.src = track.src;
			li.dataset.type = track.type;
			li.dataset.caption = track.caption;
			li.dataset.description = track.description;
			li.innerHTML = '<div class="wp-playlist-caption">' +
				'<span class="wp-playlist-item-title">' + title + '</span> ' +
				'<span class="wp-playlist-item-artist">' + artist + '</span></div>' +
				'<div class="wp-playlist-item-length">' + length + '</div>';
			ol.append( li );
		} );

		if ( type === 'audio' ) {
			playlistEl.querySelector( '.wp-playlist-current-item' ).append( img );
			playlistEl.querySelector( '.wp-playlist-current-item' ).append( div );
		} else {
			playlistEl.querySelector( type ).preload = 'metadata';
		}

		source.type = firstTrack.type;
		source.src = firstTrack.src;
		playlistEl.querySelector( type ).append( source );
		playlistEl.querySelector( type ).src = firstTrack.src;
		playlistEl.querySelector( '.wp-playlist-prev' ).after( ol );

		// Play next item in playlist
		playlistEl.querySelector( type ).addEventListener( 'ended', function() {
			var item = playlistEl.querySelector( '.wp-playlist-playing' ),
				index = [...item.parentNode.children].indexOf( item );

			playlistEl.querySelector( '.wp-playlist-playing' ).classList.remove( 'wp-playlist-playing' );

			index++;
			if ( index === tracks.length ) {
				index = 0;
			}
			playlistEl.querySelectorAll( '.wp-playlist-item' )[ index ].classList.add( 'wp-playlist-playing' );
			item = playlistEl.querySelector( '.wp-playlist-playing' );

			playlistEl.querySelector( type ).src = item.dataset.src;
			playlistEl.querySelector( 'source' ).type = item.dataset.type;
			playlistEl.querySelector( 'source' ).src = item.dataset.src;
			if ( type === 'audio' ) {
				playlistEl.querySelector( 'img' ).src = item.dataset.img;
				playlistEl.querySelector( '.wp-playlist-item-title' ).textContent = item.dataset.title;
				playlistEl.querySelector( '.wp-playlist-item-album' ).textContent = item.dataset.album;
				playlistEl.querySelector( '.wp-playlist-item-artist' ).textContent = item.dataset.artist;
			}

			// Timeout required for promise
			setTimeout( function() {
				playlistEl.querySelector( type ).play();
			}, 200 );
		} );
	} );

	// Pay track when clicking on it
	document.addEventListener( 'click', function( e ) {
		var item, playlist, type;
		if ( e.target.closest( '.wp-playlist-caption' ) ) {
			playlist = e.target.closest( '.wp-playlist' );
			if ( playlist.querySelector( '.wp-playlist-playing' ) ) {
				playlist.querySelector( '.wp-playlist-playing' ).classList.remove( 'wp-playlist-playing' );
			}

			item = e.target.closest( '.wp-playlist-item' );
			item.classList.add( 'wp-playlist-playing' );

			type = playlist.className.split( ' ' )[1];
			type = type.split( '-' )[1];
			playlist.querySelector( type ).src = item.dataset.src;

			if ( type === 'audio' ) {
				playlist.querySelector( 'img' ).src = item.dataset.img;
				playlist.querySelector( '.wp-playlist-item-title' ).textContent = item.dataset.title;
				playlist.querySelector( '.wp-playlist-item-album' ).textContent = item.dataset.album;
				playlist.querySelector( '.wp-playlist-item-artist' ).textContent = item.dataset.artist;
			}
			playlist.querySelector( 'source' ).type = item.dataset.type;
			playlist.querySelector( 'source' ).src = item.dataset.src;

			// Timeout required for promise
			setTimeout( function() {
				playlist.querySelector( type ).play();
			}, 200 );

		// Ensure that current track shows in boldface
		} else if ( e.target.closest( '.mejs-play' ) || e.target.closest( '.mejs-overlay-play' ) ) {
			playlist = e.target.closest( '.wp-playlist' );
			if ( ! playlist.querySelector( '.wp-playlist-playing' ) ) {
				e.target.closest( '.wp-playlist' ).querySelector( '.wp-playlist-item ' ).classList.add( 'wp-playlist-playing' );
			}
		}
	} );

	// Initialize medialement player
	mediaElements.forEach( ( el ) => new MediaElementPlayer( el ) );
} );
