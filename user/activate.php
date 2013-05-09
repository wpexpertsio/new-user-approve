<?php

/**
 * If you want to override this template, copy this file to your theme in a folder titled
 * 'new-user-approve' and customize.
 */
?>

<?php get_header(); ?>

<div id="content">
	<div class="padder">
		<div class="page" id="activate-page">

			<h3><?php if ( new_user_approve_activated() ) :
				_e( 'Account Activated', 'buddypress' );
			else :
				_e( 'Activate your Account', 'buddypress' );
			endif; ?></h3>

		<?php if ( new_user_approve_activated() ) : ?>
			<p><?php _e( 'Your account was activated successfully! Your account details have been sent to you in a separate email.', 'buddypress' ); ?></p>
		<?php endif; ?>
		</div>
	</div>
</div>

<?php get_footer(); ?>
