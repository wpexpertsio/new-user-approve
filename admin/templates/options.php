<?php require_once('includes/template.php'); // WordPress Dashboard Functions ?>
	<div class="wrap">
		<form method="post" action="options.php">
			<?php settings_fields( 'nua-settings-group' ); ?>
			<?php do_settings_sections( 'nua-settings-group' ); ?>
	
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Send Emails</th>
					<td>
						<label for="prevent_approval_email">
							<input type="checkbox" name="prevent_approval_email" id="prevent_approval_email" value="1"<?php checked( 1 == get_option('prevent_approval_email') ); ?> /> Send Approval Email
						</label>
					</td>
				</tr>
			</table>
	
			<?php submit_button(); ?>
		</form>
</div>