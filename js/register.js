/* global bpRestrictCommunity */
( function( $ ) {
	// Register form
	if ( typeof bpRestrictCommunity !== 'undefined' ) {
		// Make the BP Button like a WP one
		$( '#register [name="signup_submit"]' ).addClass( 'button button-primary button-large' ).prop( 'id', 'wp-submit' );

		if ( ! $('#buddypress').hasClass( 'buddypress-wrap' ) ) {
			$( '#signup_password' ).first().remove();
			$( '#pass-strength-result' ).first().remove();
			$( 'label[for="signup_password_confirm"]' ).remove();
			$( '#signup_password_confirm' ).first().remove();
		}

		// Remove Field visibility
		$( '.field-visibility-settings-notoggle, .field-visibility-settings-toggle, .field-visibility-settings' ).remove();

		// Add an hidden field
		$( 'form[name="signup_form"]' ).append( $( '<input>' ).prop( 'type', 'hidden' ).prop( 'name', bpRestrictCommunity.field_key ) );

		// Move the Privacy policy control into the last registration section.
		$( 'form[name="signup_form"]' ).find( '.register-section' ).last().append( $( '.privacy-policy-accept' ) );

		// Make sure the description of the form is the form tag.
		if ( $( '#register-page aside.bp-feedback' ).length && $( 'form[name="signup_form"] .layout-wrap' ).length ) {
			$( 'form[name="signup_form"] .layout-wrap' ).prepend( $( '#register-page aside.bp-feedback' ) );
		}

		// This will be checked on the server side to try to prevent spam registrations
		$( 'form[name="signup_form"]' ).on( 'submit', function( event ) {
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
		} );

	// Register completed step or Activate form
	} else if ( $( '#register' ).hasClass( 'completed-confirmation' ) ) {
		$( 'aside.bp-feedback' ).appendTo( 'form[name="signup_form"] .layout-wrap' );
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
