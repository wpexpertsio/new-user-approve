<?php

class NewUserApproveUserTest extends WP_UnitTestCase {

	function testStatusOfNewUser() {
		$user_id = $this->factory->user->create();
		$status = pw_new_user_approve()->get_user_status( $user_id );

		// default status for new user is pending
		$this->assertTrue( $status == 'pending' );
	}

	function testStatusOfUserAlreadyAdded() {
		$user = get_user_by( 'login', 'admin' );

		$user_status = get_user_meta( $user->ID, 'pw_user_status', true );
		$this->assertEmpty( $user_status );

		$status = pw_new_user_approve()->get_user_status( $user->ID );
		$this->assertTrue( $status == 'approved' );
	}

}

