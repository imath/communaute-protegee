<?php
/**
 * Register template
 */
bp_get_template_part( 'members/communite-blindee-header' );
add_action( 'template_notices', 'communaute_blindee_registration_feedback', 0 );
?>

<div id="register" class="<?php echo sanitize_html_class( bp_get_current_signup_step() ); ?>">
	<h1><a href="<?php echo esc_url( communaute_blindee()->register_header_url ); ?>" title="<?php echo esc_attr( communaute_blindee()->register_header_title ); ?>" tabindex="-1"><?php bloginfo( 'name' ); ?></a></h1>

	<?php if ( 'completed-confirmation' == bp_get_current_signup_step() ) : ?>

		<?php

		/**
		 * Fires before the display of the registration confirmed messages.
		 *
		 * @since 1.5.0
		 */
		do_action( 'bp_before_registration_confirmed' ); ?>

		<div id="template-notices" role="alert" aria-atomic="true">
			<?php if ( bp_registration_needs_activation() ) : ?>
				<p class="message"><?php _e( 'You have successfully created your account! To begin using this site you will need to activate your account via the email we have just sent to your address.', 'communaute-blindee' ); ?></p>
			<?php else : ?>
				<p class="message"><?php _e( 'You have successfully created your account! Please log in using the username and password you have just created.', 'communaute-blindee' ); ?></p>
			<?php endif; ?>
		</div>

	<?php else : ?>
		<div id="template-notices" role="alert" aria-atomic="true">
			<?php do_action( 'template_notices' ); ?>
		</div>
	<?php endif ;?>

	<div id="buddypress">
		<form action="" name="signup_form" id="signup-form" class="standard-form signup-form" method="post">

			<?php if ( 'privacy-policy' === bp_get_current_signup_step() ) : ?>

				<div class="register-section privacy-policy">
					<h2 class="bp-heading"><?php esc_html_e( 'Privacy policy', 'communaute-blindee' ); ?></h2>
					<p class="description instructions">
						<?php esc_html_e( 'To receive the privacy policy, fill the following field with your email address and hit the "Receive our Privacy policy by email" button.', 'communaute-blindee' ); ?>
						<?php esc_html_e( 'Your email will not be saved on our website.', 'communaute-blindee' ); ?>
					</p>

					<label for="privacy-policy-email"><?php _e( 'Email Address', 'communaute-blindee' ); ?> <?php _e( '(required)', 'communaute-blindee' ); ?></label>
					<input type="email" name="privacy_policy_email" id="privacy-policy-email" <?php bp_form_field_attributes( 'email', array( 'aria-required' => 'true' ) ); ?>/>
				</div>

				<p class="submit">
					<?php wp_nonce_field( 'send-privacy-policy', '_communaute_blindee_status' ); ?>
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Receive our Privacy policy by email', 'communaute-blindee' ); ?>">
				</p>

			<?php elseif ( 'request-details' === bp_get_current_signup_step() ) : ?>

				<div class="register-section default-profile" id="basic-details-section">
					<h2 class="bp-heading"><?php esc_html_e( 'Account Details', 'communaute-blindee' ); ?></h2>

					<label for="signup_username"><?php _e( 'Username', 'communaute-blindee' ); ?> <?php _e( '(required)', 'communaute-blindee' ); ?></label>
					<?php

					/**
					 * Fires and displays any member registration username errors.
					 *
					 * @since 1.1.0
					 */
					do_action( 'bp_signup_username_errors' ); ?>
					<input type="text" name="signup_username" id="signup_username" value="<?php bp_signup_username_value(); ?>" <?php bp_form_field_attributes( 'username' ); ?>/>

					<label for="signup_email"><?php _e( 'Email Address', 'communaute-blindee' ); ?> <?php _e( '(required)', 'communaute-blindee' ); ?></label>
					<?php

					/**
					 * Fires and displays any member registration email errors.
					 *
					 * @since 1.1.0
					 */
					do_action( 'bp_signup_email_errors' ); ?>
					<input type="email" name="signup_email" id="signup_email" value="<?php bp_signup_email_value(); ?>" <?php bp_form_field_attributes( 'email' ); ?>/>

					<label for="signup_password"><?php _e( 'Choose a Password', 'communaute-blindee' ); ?> <?php _e( '(required)', 'communaute-blindee' ); ?></label>
					<?php

					/**
					 * Fires and displays any member registration password errors.
					 *
					 * @since 1.1.0
					 */
					do_action( 'bp_signup_password_errors' ); ?>

					<?php bp_get_template_part( 'members/register-password' ); ?>
				</div>

				<?php if ( bp_is_active( 'xprofile' ) ) : ?>

					<?php

					/**
					 * Fires before the display of member registration xprofile fields.
					 *
					 * @since 1.2.4
					 */
					do_action( 'bp_before_signup_profile_fields' ); ?>

					<div class="register-section" id="profile-details-section">

						<h2><?php _e( 'Profile Details', 'communaute-blindee' ); ?></h2>

						<?php /* Use the profile field loop to render input fields for the 'base' profile field group */ ?>
						<?php if ( bp_is_active( 'xprofile' ) ) : if ( bp_has_profile( array( 'profile_group_id' => 1, 'fetch_field_data' => false ) ) ) : while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

						<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

							<div<?php bp_field_css_class( 'editfield' ); ?>>
								<fieldset>

								<?php
								$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
								$field_type->edit_field_html();

								/**
								 * Fires before the display of the visibility options for xprofile fields.
								 *
								 * @since 1.7.0
								 */
								do_action( 'bp_custom_profile_edit_fields_pre_visibility' ); ?>

								<?php

								/**
								 * Fires after the display of the visibility options for xprofile fields.
								 *
								 * @since 1.1.0
								 */
								do_action( 'bp_custom_profile_edit_fields' ); ?>

								</fieldset>
							</div>

						<?php endwhile; ?>

						<input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

						<?php endwhile; endif; endif; ?>

						<?php

						/**
						 * Fires and displays any extra member registration xprofile fields.
						 *
						 * @since 1.9.0
						 */
						do_action( 'bp_signup_profile_fields' ); ?>

					</div><!-- #profile-details-section -->

				<?php endif; ?>

				<?php
				/**
				 * Fires before the display of the registration submit buttons.
				 *
				 * @since 1.1.0
				 */
				do_action( 'bp_before_registration_submit_buttons' ); ?>

				<?php if ( bp_signup_requires_privacy_policy_acceptance() ) : ?>
				<div class="privacy-policy-accept">
					<?php do_action( 'bp_signup_signup_privacy_policy_errors' ); ?>

					<label for="signup-privacy-policy-accept">
						<input type="hidden" name="signup-privacy-policy-check" value="1" />

						<?php /* translators: link to Privacy Policy */ ?>
						<input type="checkbox" name="signup-privacy-policy-accept" id="signup-privacy-policy-accept" required /> <?php printf( esc_html__( 'I have read and agree to this site\'s %s.', 'communaute-blindee' ), sprintf( '<a href="%s">%s</a>', esc_url( get_privacy_policy_url() ), esc_html__( 'Privacy Policy', 'communaute-blindee' ) ) ); ?>
					</label>
				</div>

				<?php endif; ?>

				<div class="submit">
					<input type="submit" name="signup_submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Complete Sign Up', 'communaute-blindee' ); ?>" />
				</div>

				<?php

				/**
				 * Fires after the display of the registration submit buttons.
				 *
				 * @since 1.1.0
				 */
				do_action( 'bp_after_registration_submit_buttons' ); ?>

				<?php wp_nonce_field( 'bp_new_signup' ); ?>

			<?php elseif ( 'completed-confirmation' == bp_get_current_signup_step() ) : ?>

				<h2 class="bp-heading completed-signup"><?php printf( esc_html__( 'See you soon on %s', 'communaute-blindee' ), get_bloginfo( 'name', 'display' ) ); ?></h2>

				<?php

				/**
				 * Fires after the display of the registration confirmed messages.
				 *
				 * @since 1.5.0
				 */
				do_action( 'bp_after_registration_confirmed' ); ?>

			<?php endif; // completed-confirmation signup step ?>

			<?php

			/**
			* Fires and displays any custom signup steps.
			*
			* @since 1.1.0
			*/
			do_action( 'bp_custom_signup_steps' );?>
		</form>
	</div>

</div>
<div class="clear"></div>

<?php bp_get_template_part( 'members/communite-blindee-footer' ); ?>
