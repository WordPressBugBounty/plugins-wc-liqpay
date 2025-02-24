<?php
/**
 * Register type block payment method Lickpay.
 *
 * @package WCLickpay/Block
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * WCLickpay payment method integration
 */
final class Payment_Method_Type_Liqpay extends AbstractPaymentMethodType {

	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = 'liqpay';

	/**
	 * Init function
	 *
	 * @return void
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_liqpay_settings', array() );
		wp_register_script(
			'wc-payment-method-liqpay',
			plugins_url( 'assets/liqpay-payment-block.js', __FILE__ ),
			array( 'wc-blocks-registry', 'wc-settings' ),
			'1.0.0',
			true
		);
		wp_localize_script(
			'wc-payment-method-liqpay',
			'liqpayLocalize',
			array(
				'title'       => $this->get_setting( 'title' ),
				'description' => $this->get_setting( 'description' ),
				'not_edit'    => __( 'Liqpay editing is not available in this context', 'wcliqpay' ),
			)
		);
	}

	/**
	 * Return satate activation.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Return settings method.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
		);
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		return array( 'wc-payment-method-liqpay' );
	}

	/**
	 * Register handles for admin.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles_for_admin() {
		return $this->get_payment_method_script_handles();
	}
}
