
<?php require_once('includes/template.php'); // WordPress Dashboard Functions ?>

<?php
if ( isset( $_GET['user'] ) && isset( $_GET['status'] ) ) {
	echo '<div id="message" class="updated fade"><p>' . __( 'User successfully updated.', 'new-user-approve' ) . '</p></div>';
}
?>

<div class="wrap">
	<h2><?php _e( 'User Registration Approval', 'new-user-approve' ); ?></h2>

	<h3 class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'users.php?page=new-user-approve-admin&tab=pending_users' ) ); ?>"
		   class="nav-tab<?php echo $active_tab == 'pending_users' ? ' nav-tab-active' : ''; ?>"><span><?php _e( 'Users Pending Approval', 'new-user-approve' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'users.php?page=new-user-approve-admin&tab=approved_users' ) ); ?>"
		   class="nav-tab<?php echo $active_tab == 'approved_users' ? ' nav-tab-active' : ''; ?>"><span><?php _e( 'Approved Users', 'new-user-approve' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'users.php?page=new-user-approve-admin&tab=denied_users' ) ); ?>"
		   class="nav-tab<?php echo $active_tab == 'denied_users' ? ' nav-tab-active' : ''; ?>"><span><?php _e( 'Denied Users', 'new-user-approve' ); ?></span></a>
	</h3>

	<?php if ( $active_tab == 'pending_users' ) : ?>
		<div id="pw_pending_users">
			<?php $this->user_table( 'pending' ); ?>
		</div>
	<?php elseif ( $active_tab == 'approved_users' ) : ?>
		<div id="pw_approved_users">
			<?php $this->user_table( 'approved' ); ?>
		</div>
	<?php
	elseif ( $active_tab == 'denied_users' ) : ?>
		<div id="pw_denied_users">
			<?php $this->user_table( 'denied' ); ?>
		</div>
	<?php endif; ?>
</div>
