
<?php require_once('includes/template.php'); // WordPress Dashboard Functions ?>

<?php
if ( isset( $_GET['user'] ) && isset( $_GET['status'] ) ) {
	echo '<div id="message" class="updated fade"><p>' . __( 'User successfully updated.', 'new-user-approve' ) . '</p></div>';
}
?>

<div class="wrap">
	<h2><?php _e( 'User Registration Approval', 'new-user-approve' ); ?></h2>

	<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
	<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>

	<div id="poststuff" class="columns metabox-holder">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="postbox-container-1" class="postbox-container column-secondary">
				<?php do_meta_boxes( 'users_page_new-user-approve-admin', 'side', $this ); ?>
			</div>
			<div id="postbox-container-2" class="postbox-container column-primary">
				<?php do_meta_boxes( 'users_page_new-user-approve-admin', 'main', $this ); ?>
			</div>
		</div>
	</div>
</div>
