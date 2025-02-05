<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_kmnd_Liqpay extends WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'liqpay';
		$this->method_title       = 'LiqPay';
		$this->method_description = 'Allows payments with LiqPay.';
		$this->has_fields         = false;

		$this->init_form_fields();
		$this->init_settings();

		foreach ( $this->settings as $key => $val ) {
			$this->$key = $val;
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'handle_callback' ) );
		add_action( 'woocommerce_api_liqpay_return', array( $this, 'handle_return' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_rro_id_metabox' ) );
		add_action( 'save_post_product', array( $this, 'save_rro_id_metabox' ) );
	}

	/**
	 * Save metabox rro id field to the product page.
	 */
	public function save_rro_id_metabox( $post_id ) {
		if ( ! isset( $_POST['product_rro_id_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['product_rro_id_meta_box_nonce'], 'product_rro_id_meta_box_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['product_rro_id'] ) ) {
			update_post_meta( $post_id, 'product_rro_id', sanitize_text_field( $_POST['product_rro_id'] ) );
		}
	}

	/**
	 * Add metabox rro id field to the product page.
	 */
	public function add_rro_id_metabox() {
		add_meta_box(
			'product_rro_id',
			__( 'Liqpay settings', 'wcliqpay' ),
			array( $this, 'render_rro_id_metabox' ),
			'product',
			'side',
			'high'
		);
	}

	/**
	 * Render metabox rro id field to the product page.
	 */
	public function render_rro_id_metabox() {
		global $post;
		$product_rro_id = get_post_meta( $post->ID, 'product_rro_id', true );
		wp_nonce_field( 'product_rro_id_meta_box_nonce', 'product_rro_id_meta_box_nonce' );
		echo '<label for="product_rro_id">' . esc_html__( 'Liqpay product ID for rro', 'wcliqpay' ) . '</label>';
		echo '<input type="text" id="product_rro_id" name="product_rro_id" value="' . esc_attr( $product_rro_id ) . '" style="width: 100%;margin-top: 5px;" />';
	}

	/**
	 * Initialize form fields for the plugin.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'             => array(
				'title'   => __( 'Turn on/Switch off', 'wcliqpay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Turn on', 'wcliqpay' ),
				'default' => 'yes',
			),
			'title'               => array(
				'title'       => __( 'Heading', 'wcliqpay' ),
				'type'        => 'textarea',
				'description' => __( 'Title that appears on the checkout page', 'wcliqpay' ),
				'default'     => __( 'LiqPay' ),
				'desc_tip'    => true,
			),
			'description'         => array(
				'title'       => __( 'Description', 'wcliqpay' ),
				'type'        => 'textarea',
				'description' => __( 'Description that appears on the checkout page', 'wcliqpay' ),
				'default'     => __( 'Pay using the payment system LiqPay::Pay with LiqPay payment system', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'pay_message'         => array(
				'title'       => __( 'Message before payment', 'wcliqpay' ),
				'type'        => 'textarea',
				'description' => __( 'Message before payment', 'wcliqpay' ),
				'default'     => __( 'Thank you for your order, click the button below to continue::Thank you for your order, click the button' ),
				'desc_tip'    => true,
			),
			'public_key'          => array(
				'title'       => __( 'Public key', 'wcliqpay' ),
				'type'        => 'text',
				'description' => __( 'Public key LiqPay. Required parameter', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'private_key'         => array(
				'title'       => __( 'Private key', 'wcliqpay' ),
				'type'        => 'text',
				'description' => __( 'Private key LiqPay. Required parameter', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'lang'                => array(
				'title'       => __( 'Language', 'wcliqpay' ),
				'type'        => 'select',
				'default'     => 'uk',
				'options'     => array(
					'uk' => __( 'uk' ),
					'en' => __( 'en' ),
				),
				'description' => __( 'Interface language (For uk + en install multi-language plugin. Separating languages ​​with :: .)', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'lang_function'       => array(
				'title'       => __( 'Language detection function', 'wcliqpay' ),
				'type'        => 'text',
				'default'     => 'pll_current_language',
				'description' => __( 'The function of determining the language of your plugin', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'icon'                => array(
				'title'       => __( 'Logotype', 'wcliqpay' ),
				'type'        => 'text',
				'default'     => WC_LIQPAY_DIR . 'assets/images/logo_liqpay.svg',
				'description' => __( 'Full path to the logo, located on the order page', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'button'              => array(
				'title'       => __( 'Button', 'wcliqpay' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Full path to the image of the button to go to LiqPay', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'status'              => array(
				'title'       => __( 'Order status', 'wcliqpay' ),
				'type'        => 'text',
				'default'     => 'processing',
				'description' => __( 'Order status after successful payment', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'order_description'   => array(
				'title'       => __( 'Purpose of payment', 'wcliqpay' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Payment for order №', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'redirect_page_error' => array(
				'title'       => __( 'URL error Payment page', 'wcliqpay' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'URL page to go to after gateway LiqPay', 'wcliqpay' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Process the payment by redirecting to LiqPay's checkout.
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Initialize the LiqPay SDK
		$liqpay = new LiqPay( $this->public_key, $this->private_key );

		// Add order items to rro_info
		$rro_info = array(
			'items'           => array(),
			'delivery_emails' => array( $order->get_billing_email() ),
		);

		foreach ( $order->get_items() as $item_id => $item ) {
			$product             = $item->get_product();
			$product_rro_id      = get_post_meta( $product->get_id(), 'product_rro_id', true );
			$rro_info['items'][] = array(
				'amount' => $item->get_quantity(),
				'price'  => (float) $product->get_price(),
				'cost'   => (float) $item->get_total(),
				'id'     => (string) $product_rro_id,
			);
		}

		// Set the required parameters for the payment
		$params = array(
			'version'     => '3',
			'action'      => 'pay',
			'amount'      => $order->get_total(),
			'email'       => $order->get_billing_email(),
			'currency'    => get_woocommerce_currency(),
			'description' => $this->getDescription( $order->get_id() ),
			'order_id'    => $order->get_id(),
			'result_url'  => add_query_arg(
				array(
					'wc-api'   => 'liqpay_return',
					'order_id' => $order->get_id(),
				),
				home_url( '/' )
			),
			'server_url'  => WC()->api_request_url( strtolower( get_class( $this ) ) ),
			'language'    => $this->lang,
			'rro_info'    => $rro_info,
		);

		// Filter "wc_liqpay_request_filter" to query array before sending data to liqpay
		$params = apply_filters( 'wc_liqpay_request_filter', $params );

		// Generate the LiqPay payment link
		$payment_link = $liqpay->cnb_link( $params );

		// Redirect to the payment link
		return array(
			'result'   => 'success',
			'redirect' => $payment_link,
		);
	}

	private function getDescription( $order_id ) {
		$descriptions = array(
			'ru' => $this->order_description,
			'en' => $this->order_description,
			'uk' => $this->order_description,
		);
		return ( $descriptions[ $this->lang ] ?? $descriptions['ru'] ) . $order_id;
	}

	/**
	 * Handle callback from LiqPay to update the order status.
	 */
	public function handle_callback() {
		// Get data and signature from LiqPay's callback
		$data      = isset( $_POST['data'] ) ? $_POST['data'] : null;
		$signature = isset( $_POST['signature'] ) ? $_POST['signature'] : null;

		if ( ! $data || ! $signature ) {
			// Missing data or signature
			wp_die( 'Invalid data received', 'LiqPay Callback', array( 'response' => 400 ) );
		}

		// Decode and parse the data from LiqPay
		$liqpay       = new LiqPay( $this->public_key, $this->private_key );
		$decoded_data = json_decode( base64_decode( $data ), true );

		// Verify the signature
		$generated_signature = $liqpay->str_to_sign( $this->private_key . $data . $this->private_key );
		if ( $signature !== $generated_signature ) {
			wp_die( 'Signature verification failed', 'LiqPay Callback', array( 'response' => 400 ) );
		}

		// Get the order ID and status from the LiqPay response
		$order_id = isset( $decoded_data['order_id'] ) ? $decoded_data['order_id'] : null;
		$status   = isset( $decoded_data['status'] ) ? $decoded_data['status'] : null;

		if ( ! $order_id || ! $status ) {
			wp_die( 'Missing order ID or status', 'LiqPay Callback', array( 'response' => 400 ) );
		}
		file_put_contents( 'file.txt', '$status: ' . print_r( $status, 1 ) . "\n", FILE_APPEND );

		// Retrieve the order using the order ID
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_die( 'Order not found', 'LiqPay Callback', array( 'response' => 404 ) );
		}

		// Update the order status based on the payment result
		if ( $status === 'success' || $status === 'sandbox' ) {
			// Mark the order as "processing" or "completed" based on your workflow
			$order->update_status( $this->status, __( 'Payment successful via LiqPay.', 'wcliqpay' ) );
			$order->payment_complete();
		} else {
			// If the status is not successful, mark the order as failed
			$order->update_status( 'failed', __( 'Payment failed via LiqPay.', 'wcliqpay' ) );
		}

		// Send a 200 response back to LiqPay to acknowledge receipt
		header( 'HTTP/1.1 200 OK' );
		exit;
	}

	public function handle_return() {
		if ( ! isset( $_GET['order_id'] ) ) {
			wp_redirect( home_url() );
			exit;
		}

		$order_id = intval( $_GET['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_redirect( home_url() );
			exit;
		}

		// Check the payment status via LiqPay API
		$result = $this->check_order_status( $order_id );

		if ( is_wp_error( $result ) ) {
			// Payment failed or error occurred
			wc_add_notice( __( 'Payment verification failed. Please contact us for assistance.', 'wcliqpay' ), 'error' );
			wp_redirect( $this->redirect_page_error ?: wc_get_cart_url() );
			exit;
		}

		// Payment was successful, redirect to thank you page
		wp_redirect( $this->get_return_url( $order ) );
		exit;
	}

	public function check_order_status( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'invalid_order', 'Order not found.' );
		}

		// Initialize the LiqPay SDK
		$liqpay = new LiqPay( $this->public_key, $this->private_key );

		// Set the required parameters to check the status
		$params = array(
			'version'  => '3',
			'action'   => 'status',
			'order_id' => $order_id,
		);

		// Send the API request to LiqPay
		try {
			$response = $liqpay->api( 'request', $params );

			if ( ! empty( $response->status ) ) {
				// Check if the payment is completed
				if ( in_array( $response->status, array( 'success', 'sandbox', 'wait_accept' ) ) ) {
					// Update WooCommerce order status to the status saved in the 'status' field
					$order->update_status( $this->status, __( 'Payment confirmed via LiqPay API.', 'wcliqpay' ) );
					$order->payment_complete();
					return true;
				} else {
					// Payment not successful
					$order->update_status( 'failed', __( 'Payment failed via LiqPay API.', 'wcliqpay' ) );
					return new WP_Error( 'payment_failed', 'Payment not successful.' );
				}
			} else {
				return new WP_Error( 'api_error', 'Failed to retrieve order status from LiqPay.' );
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'api_exception', 'Exception occurred: ' . $e->getMessage() );
		}
	}
}
