<?php
/**
 * File add support block.
 *
 * @package     WCLickpay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * General class - add support block.
 */
class Support_Block_Liqpay {

	/**
	 * Init function.
	 */
	public function __construct() {
		add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_payment_method_integrations' ) );
	}

	/**
	 * Construct function.
	 */
	/**
	 * Register payment method integrations bundled with blocks.
	 *
	 * @param PaymentMethodRegistry $payment_method_registry Payment method registry instance.
	 */
	public function register_payment_method_integrations( $payment_method_registry ) {
		$payment_method_registry->register( new Payment_Method_Type_Liqpay() );
	}
}

new Support_Block_Liqpay();
