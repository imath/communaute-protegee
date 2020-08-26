/**
 * DOM customizations.
 */
class CPCustomize {
	constructor( { fieldKey } ) {
		this.fieldKey = fieldKey;
		this.tagsToRemove = [
			'.field-visibility-settings-notoggle',
			'.field-visibility-settings-toggle',
			'.field-visibility-settings'
		];
		this.blogDetails = null;
	}

	onFormSubmit( event ) {
		const formElements = event.srcElement ? Object.values( event.srcElement ) : [];
		let signupEmail = '';

		formElements.forEach( ( formElement ) => {
			if ( 'signup_email' === formElement.name || 'privacy_policy_email' === formElement.name ) {
				signupEmail = formElement.value;
			}

			if ( this.fieldKey === formElement.name ) {
				const dynamicField = event.srcElement.querySelector( '[name="' + formElement.name + '"]' );

				if ( null !== dynamicField ) {
					dynamicField.value = signupEmail;
				}
			}
		} );
	}

	toggleBlogForm( event ) {
		if ( true === event.target.checked ) {
			this.blogDetails.style.display = 'block';
		} else {
			this.blogDetails.style.display = 'none';
		}
	}

	customizeSignUp() {
		const bpMainContainer = document.querySelector( '#buddypress' );
		const signupForm = document.querySelector( 'form[name="signup_form"]' );
		const signupSubmit = null !== signupForm ? signupForm.querySelector( '[name="signup_submit"]' ) : null;
		const extraTplNotices = null !== signupForm ? signupForm.querySelector( '#template-notices' ) : null;
		const privacyPolicy = null !== signupForm ? signupForm.querySelector( '.privacy-policy-accept' ) : null;
		const nouveauRegisterWrapper = document.querySelector( '#register-page form[name="signup_form"] .layout-wrap' );
		const blogDetails = document.querySelector( '#blog-details' );
		const blogCheckbox = document.querySelector( '[name="signup_with_blog"]' );

		// Customize Form.
		if ( null !== signupForm ) {
			const hiddenField = document.createElement( 'input' );

			// Adds a dynamic field to check the user is a human.
			hiddenField.setAttribute( 'type', 'hidden' );
			hiddenField.setAttribute( 'name', this.fieldKey )
			signupForm.appendChild( hiddenField );

			// Listens to form submit.
			signupForm.addEventListener( 'submit', this.onFormSubmit.bind( this ) );

			// Removes Legacy Template Pack extra notices container.
			if ( null !== extraTplNotices ) {
				extraTplNotices.remove();
			}

			// Moves Nouveau Template Pack Feedback selector into the Form.
			if ( null !== nouveauRegisterWrapper ) {
				nouveauRegisterWrapper.prepend( document.querySelector( '#register-page aside.bp-feedback' ) );
			}

			// Adds extra inputs and labels of the Legacy template pack to the tags to remove.
			if ( bpMainContainer && ! bpMainContainer.classList.contains( 'buddypress-wrap' ) ) {
				this.tagsToRemove.push( '#signup_password',	'#pass-strength-result', 'label[for="signup_password_confirm"]', '#signup_password_confirm' );
			}

			// Removes not needed tags.
			this.tagsToRemove.forEach( ( selector ) => {
				const extraSelector = signupForm.querySelectorAll( selector );

				if ( extraSelector.length ) {
					extraSelector[0].remove();
				}
			} );

			if ( null !== blogDetails && null !== blogCheckbox ) {
				this.blogDetails = blogDetails;

				if ( ! blogCheckbox.getAttribute( 'checked' ) ) {
					this.blogDetails.style.display = 'none';
				}

				blogCheckbox.addEventListener( 'click', this.toggleBlogForm.bind( this ) );
			}

			// Makes sure the Submit button style is consistent with the login form.
			if ( null !== privacyPolicy ) {
				signupForm.insertBefore( privacyPolicy, signupSubmit.parentNode );
				privacyPolicy.classList.add( 'register-section' );
				privacyPolicy.style.marginTop = '1.5em';
			}

			// Makes sure the Submit button style is consistent with the login form.
			if ( null !== signupSubmit ) {
				signupSubmit.setAttribute( 'id', 'wp-submit' );
				signupSubmit.classList.add( 'button', 'button-primary', 'button-large' );
			}
		}
	}

	start() {
		if ( this.fieldKey ) {
			this.customizeSignUp();
		}
	}
}

const settings = communauteProtegee && communauteProtegee.settings ? communauteProtegee.settings : {};
const cpCustomize = new CPCustomize( settings );

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', cpCustomize.start() );
} else {
	cpCustomize.start();
}
