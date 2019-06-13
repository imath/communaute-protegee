/* global bpRestrictCommunity */
( function( $ ) {
	// Register form
	if ( typeof bpRestrictCommunity !== 'undefined' ) {
		$( '[name="signup_form"]' ).append( $( '<input>' ).prop( 'type', 'hidden' ).prop( 'name', bpRestrictCommunity.field_key ) );

		// This will be checked on the server side to try to prevent spam registrations
		$( '[name="signup_form"]' ).on( 'submit', function( event ) {
			$( event.currentTarget ).find( 'input[name="' + bpRestrictCommunity.field_key + '"]' ).val( $( 'input[name="signup_email"]' ).val() );
		} );
	}
} )( jQuery );
