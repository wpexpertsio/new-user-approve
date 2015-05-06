<?php

class NewUserApproveEmailTest extends WP_UnitTestCase {

	function testSendEmailToAdmin() {
		$admin_email = get_option( 'admin_email' );

		$user_id = $this->factory->user->create();
		$user = new WP_User( $user_id );

		$email = $GLOBALS['phpmailer']->mock_sent[0];

		$to = $email['to'][0];
		$this->assertTrue( in_array( $admin_email, $to ) );
	}

}
