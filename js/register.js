( function( $ ) {
	// Register form
	if ( typeof bpRestrictCommunity !== 'undefined' ) {
		// Make the BP Button like a WP one
		$( '#register #signup_submit' ).addClass( 'button button-primary button-large' );

		// Remove Field visibility
		$( '.field-visibility-settings-notoggle, .field-visibility-settings-toggle, .field-visibility-settings' ).remove();

		// Add an hidden field
		$( '#signup_form' ).append( $( '<input>' ).prop( 'type', 'hidden' ).prop( 'name', bpRestrictCommunity.field_key ) );

		// This will be checked on the server side to try to prevent spam registrations
		$( '#signup_form' ).on( 'submit', function( event ) {
			$( event.currentTarget ).find( 'input[name="' + bpRestrictCommunity.field_key + '"]' ).val( $( 'input[name="signup_email"]' ).val() );
		} );

		if ( $( '#blog-details' ).length && true !== $( '#signup_with_blog' ).prop( 'checked' ) ) {
			$( '#blog-details' ).hide();
		}

		$( '#signup_with_blog' ).on( 'click', function( event ) {
			if ( true === $( event.currentTarget ).prop( 'checked' ) ) {
				$( '#blog-details' ).show();
			} else {
				$( '#blog-details' ).hide();
			}
		} )

	// Register completed step or Activate form
	} else {
		if ( $( '#activate-page' ).length && $( '#activation-form' ).length ) {
			$( '#activate input[type="submit"]' ).addClass( 'button button-primary button-large' );

			var elements = [];

			$( '#activate-page' ).children().each( function( e, elt ) {
				if ( $( elt ).prop( 'id' ) !== 'activation-form' ) {
					elements.push( $( elt ).get( 0 ) );
					$( elt ).remove();
				}
			} );

			$.each( elements, function( e, elt ) {
				$( '#activation-form' ).prepend( elt );
			} );
		}
	}
} )( jQuery );
