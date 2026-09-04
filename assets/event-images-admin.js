( function () {
	'use strict';

	function initializePicker( container ) {
		const input = container.querySelector( '#formica-event-image-ids' );
		const list = container.querySelector( '.formica-event-images__list' );
		const addButton = container.querySelector( '.formica-event-images__add' );
		let frame;

		function selectedIds() {
			return Array.from( list.querySelectorAll( '[data-attachment-id]' ) ).map( function ( item ) {
				return parseInt( item.dataset.attachmentId, 10 );
			} );
		}

		function syncInput() {
			input.value = selectedIds().join( ',' );
		}

		function shareUrl( attachmentId ) {
			return container.dataset.shareUrlTemplate.replace( '999999999', attachmentId );
		}

		function addImage( attachment ) {
			if ( 'image' !== attachment.type || selectedIds().includes( attachment.id ) ) {
				return;
			}

			const item = document.createElement( 'li' );
			const preview = document.createElement( 'img' );
			const details = document.createElement( 'div' );
			const title = document.createElement( 'strong' );
			const url = document.createElement( 'input' );
			const actions = document.createElement( 'div' );
			const copy = document.createElement( 'button' );
			const remove = document.createElement( 'button' );
			const previewUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

			item.className = 'formica-event-images__item';
			item.dataset.attachmentId = attachment.id;
			preview.src = previewUrl;
			preview.alt = '';
			details.className = 'formica-event-images__details';
			title.textContent = attachment.title || attachment.filename;
			url.className = 'formica-event-images__url';
			url.type = 'text';
			url.value = shareUrl( attachment.id );
			url.readOnly = true;
			copy.className = 'button formica-event-images__copy';
			copy.type = 'button';
			copy.textContent = container.dataset.copyLabel;
			remove.className = 'button-link-delete formica-event-images__remove';
			remove.type = 'button';
			remove.textContent = container.dataset.removeLabel;

			actions.append( copy, remove );
			details.append( title, url, actions );
			item.append( preview, details );
			list.append( item );
			syncInput();
		}

		function fallbackCopy( value ) {
			const helper = document.createElement( 'textarea' );
			helper.value = value;
			helper.style.position = 'fixed';
			helper.style.opacity = '0';
			document.body.append( helper );
			helper.select();
			document.execCommand( 'copy' );
			helper.remove();
		}

		addButton.addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = wp.media( {
					title: addButton.textContent,
					button: { text: addButton.textContent },
					library: { type: 'image' },
					multiple: true,
				} );

				frame.on( 'select', function () {
					frame.state().get( 'selection' ).toJSON().forEach( addImage );
				} );
			}

			frame.open();
		} );

		list.addEventListener( 'click', function ( event ) {
			const removeButton = event.target.closest( '.formica-event-images__remove' );
			const copyButton = event.target.closest( '.formica-event-images__copy' );

			if ( removeButton ) {
				removeButton.closest( '.formica-event-images__item' ).remove();
				syncInput();
			}

			if ( copyButton ) {
				const value = copyButton.closest( '.formica-event-images__details' ).querySelector( '.formica-event-images__url' ).value;
				const copyPromise = navigator.clipboard && window.isSecureContext
					? navigator.clipboard.writeText( value )
					: Promise.resolve( fallbackCopy( value ) );

				copyPromise
					.catch( function () {
						fallbackCopy( value );
					} )
					.then( function () {
						copyButton.textContent = container.dataset.copiedLabel;
						window.setTimeout( function () {
							copyButton.textContent = container.dataset.copyLabel;
						}, 1600 );
					} );
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.formica-event-images' ).forEach( initializePicker );
	} );
} )();
