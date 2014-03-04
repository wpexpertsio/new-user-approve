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

	function testStatusUpdate() {
		$user_id = $this->factory->user->create();

		$result = pw_new_user_approve()->update_user_status( 'hello', 'approve' );
		$this->assertFalse( $result );

		$result = pw_new_user_approve()->update_user_status( $user_id, 'hello' );
		$this->assertFalse( $result );

		$result = pw_new_user_approve()->update_user_status( $user_id, 'approve' );
		$this->assertTrue( $result );

		add_filter( 'new_user_approve_validate_status_update', '__return_false' );

		$another_user = $this->factory->user->create();
		$result = pw_new_user_approve()->update_user_status( $another_user, 'deny' );
		$this->assertFalse( $result );

		remove_filter( 'new_user_approve_validate_status_update', '__return_false' );

		$result = pw_new_user_approve()->update_user_status( $user_id, 'deny' );
		$this->assertTrue( $result );
	}

	function testValidStatuses() {
		$statuses = pw_new_user_approve()->get_valid_statuses();

		$this->assertEquals( $statuses, array( 'pending', 'approved', 'denied' ) );
	}
}

