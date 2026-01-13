<?php
/**
 * Tests for Xmoney_Payments_Status_Updater class.
 *
 * @package Xmoney_Payments
 */

/**
 * Test class for Status Updater.
 */
class Test_Status_Updater extends Xmoney_Payments_Test_Case {

	/**
	 * Test class exists.
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'Xmoney_Payments_Status_Updater' ), 'Status Updater class should exist' );
	}

	/**
	 * Test result_statuses array exists and contains expected values.
	 */
	public function test_result_statuses_defined() {
		$statuses = Xmoney_Payments_Status_Updater::$result_statuses;

		$this->assertIsArray( $statuses, 'result_statuses should be an array' );
		$this->assertArrayHasKey( 'UNCERTAIN', $statuses );
		$this->assertArrayHasKey( 'IN_PROGRESS', $statuses );
		$this->assertArrayHasKey( 'COMPLETE_OK', $statuses );
		$this->assertArrayHasKey( 'COMPLETE_FAIL', $statuses );
		$this->assertArrayHasKey( 'CANCEL_OK', $statuses );
		$this->assertArrayHasKey( 'REFUND_OK', $statuses );
		$this->assertArrayHasKey( 'VOID_OK', $statuses );
		$this->assertArrayHasKey( 'CHARGE_BACK', $statuses );
		$this->assertArrayHasKey( 'THREE_D_PENDING', $statuses );
		$this->assertArrayHasKey( 'EXPIRING', $statuses );
	}

	/**
	 * Test result_statuses values.
	 */
	public function test_result_statuses_values() {
		$statuses = Xmoney_Payments_Status_Updater::$result_statuses;

		$this->assertEquals( 'uncertain', $statuses['UNCERTAIN'] );
		$this->assertEquals( 'in-progress', $statuses['IN_PROGRESS'] );
		$this->assertEquals( 'complete-ok', $statuses['COMPLETE_OK'] );
		$this->assertEquals( 'complete-failed', $statuses['COMPLETE_FAIL'] );
		$this->assertEquals( 'cancel-ok', $statuses['CANCEL_OK'] );
		$this->assertEquals( 'refund-ok', $statuses['REFUND_OK'] );
		$this->assertEquals( 'void-ok', $statuses['VOID_OK'] );
		$this->assertEquals( 'charge-back', $statuses['CHARGE_BACK'] );
		$this->assertEquals( '3d-pending', $statuses['THREE_D_PENDING'] );
		$this->assertEquals( 'expiring', $statuses['EXPIRING'] );
	}

	/**
	 * Test update_status_i_p_n with COMPLETE_OK status.
	 */
	public function test_update_status_ipn_complete_ok() {
		$order = $this->create_test_order();

		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['COMPLETE_OK']
		);

		// Refresh order.
		$order = wc_get_order( $order->get_id() );

		$this->assertEquals( 'processing', $order->get_status(), 'Order status should be processing after COMPLETE_OK' );
	}

	/**
	 * Test update_status_i_p_n with COMPLETE_FAIL status.
	 */
	public function test_update_status_ipn_complete_fail() {
		$order = $this->create_test_order();

		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['COMPLETE_FAIL']
		);

		// Refresh order.
		$order = wc_get_order( $order->get_id() );

		$this->assertEquals( 'failed', $order->get_status(), 'Order status should be failed after COMPLETE_FAIL' );
	}

	/**
	 * Test update_status_i_p_n with THREE_D_PENDING status.
	 */
	public function test_update_status_ipn_3d_pending() {
		$order = $this->create_test_order();

		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['THREE_D_PENDING']
		);

		// Refresh order.
		$order = wc_get_order( $order->get_id() );

		$this->assertEquals( 'on-hold', $order->get_status(), 'Order status should be on-hold after THREE_D_PENDING' );
	}

	/**
	 * Test update_status_i_p_n with REFUND_OK status.
	 */
	public function test_update_status_ipn_refund_ok() {
		$order = $this->create_test_order();

		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['REFUND_OK']
		);

		// Refresh order.
		$order = wc_get_order( $order->get_id() );

		$this->assertEquals( 'refunded', $order->get_status(), 'Order status should be refunded after REFUND_OK' );
	}

	/**
	 * Test update_status_i_p_n with CANCEL_OK status.
	 */
	public function test_update_status_ipn_cancel_ok() {
		$order = $this->create_test_order();

		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['CANCEL_OK']
		);

		// Refresh order.
		$order = wc_get_order( $order->get_id() );

		$this->assertEquals( 'refunded', $order->get_status(), 'Order status should be refunded after CANCEL_OK' );
	}

	/**
	 * Test update_status_i_p_n with VOID_OK status.
	 */
	public function test_update_status_ipn_void_ok() {
		$order = $this->create_test_order();

		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['VOID_OK']
		);

		// Refresh order.
		$order = wc_get_order( $order->get_id() );

		$this->assertEquals( 'refunded', $order->get_status(), 'Order status should be refunded after VOID_OK' );
	}

	/**
	 * Test update_status_i_p_n with CHARGE_BACK status.
	 */
	public function test_update_status_ipn_charge_back() {
		$order = $this->create_test_order();

		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['CHARGE_BACK']
		);

		// Refresh order.
		$order = wc_get_order( $order->get_id() );

		$this->assertEquals( 'refunded', $order->get_status(), 'Order status should be refunded after CHARGE_BACK' );
	}

	/**
	 * Test update_status_i_p_n with IN_PROGRESS status.
	 * Note: In the IPN handler, IN_PROGRESS falls through to COMPLETE_OK due to switch case structure.
	 */
	public function test_update_status_ipn_in_progress() {
		$order = $this->create_test_order();

		Xmoney_Payments_Status_Updater::update_status_i_p_n(
			$order->get_id(),
			Xmoney_Payments_Status_Updater::$result_statuses['IN_PROGRESS']
		);

		// Refresh order.
		$order = wc_get_order( $order->get_id() );

		// Note: Due to fall-through in switch statement, IN_PROGRESS ends up as processing.
		$this->assertEquals( 'processing', $order->get_status(), 'Order status should be processing after IN_PROGRESS (falls through to COMPLETE_OK)' );
	}

	/**
	 * Test xmoney_payments_update_from_inline with success status.
	 */
	public function test_update_from_inline_success() {
		$order = $this->create_test_order();

		$payment_response = array(
			'status'        => 'complete-ok',
			'transactionId' => 123456,
		);

		$result = xmoney_payments_update_from_inline( $order->get_id(), $payment_response );

		$this->assertTrue( $result, 'Update from inline should return true' );

		// Refresh order and check status.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'processing', $order->get_status(), 'Order should be processing after inline success' );
	}

	/**
	 * Test xmoney_payments_update_from_inline with orderStatus.
	 */
	public function test_update_from_inline_order_status() {
		$order = $this->create_test_order();

		$payment_response = array(
			'orderStatus'   => 'complete-ok',
			'transactionId' => 123456,
		);

		$result = xmoney_payments_update_from_inline( $order->get_id(), $payment_response );

		$this->assertTrue( $result, 'Update from inline should return true with orderStatus' );
	}

	/**
	 * Test xmoney_payments_update_from_inline with failed status.
	 */
	public function test_update_from_inline_failed() {
		$order = $this->create_test_order();

		$payment_response = array(
			'status'        => 'failed',
			'transactionId' => 123456,
		);

		$result = xmoney_payments_update_from_inline( $order->get_id(), $payment_response );

		$this->assertTrue( $result );

		// Refresh order and check status.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'failed', $order->get_status(), 'Order should be failed after inline failure' );
	}

	/**
	 * Test xmoney_payments_update_from_inline with invalid order.
	 */
	public function test_update_from_inline_invalid_order() {
		$result = xmoney_payments_update_from_inline( 99999999, array( 'status' => 'complete-ok' ) );

		$this->assertInstanceOf( 'WP_Error', $result, 'Invalid order should return WP_Error' );
	}

	/**
	 * Test xmoney_payments_update_from_inline adds order note.
	 */
	public function test_update_from_inline_adds_order_note() {
		$order = $this->create_test_order();

		$payment_response = array(
			'status' => 'complete-ok',
			'id'     => 'TX123456',
		);

		xmoney_payments_update_from_inline( $order->get_id(), $payment_response );

		// Get order notes.
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'internal',
			)
		);

		$note_contents = wp_list_pluck( $notes, 'content' );
		$found_note    = false;

		foreach ( $note_contents as $content ) {
			if ( strpos( $content, 'xMoney Inline' ) !== false ) {
				$found_note = true;
				break;
			}
		}

		$this->assertTrue( $found_note, 'Order note should be added after inline payment' );
	}
}

