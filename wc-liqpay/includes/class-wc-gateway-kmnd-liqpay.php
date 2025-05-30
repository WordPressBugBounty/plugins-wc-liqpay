<?php
/**
 * File Gateway class liqpay.
 *
 * @package     WCLickpay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Payment Gateway class liqpay.
 *
 * @class       WC_Gateway_Kmnd_Liqpay
 * @extends     WC_Payment_Gateway
 */
class WC_Gateway_Kmnd_Liqpay extends WC_Payment_Gateway {

	/**
	 * Construct function.
	 */
	public function __construct() {

		$this->id = 'liqpay';

		// Method title in admin.
		$this->method_title = __( 'LiqPay', 'wcliqpay' );
		// Method description in admin.
		$this->method_description = __( 'Pay using the payment system LiqPay', 'wcliqpay' );
		// Method title in front-end.
		$this->title = $this->get_option( 'title' );
		// Method description in front-end.
		$this->description = $this->get_option( 'description' );
		$this->icon        = $this->get_option( 'icon' );
		$this->has_fields  = false;

		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'handle_callback' ) );
		add_action( 'woocommerce_api_liqpay_return', array( $this, 'handle_return' ) );
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );
	}

	/**
	 * Change status after payment
	 *
	 * @param string   $status - filter status.
	 * @param integer  $order_id - order id.
	 * @param WC_Order $order - WC_Order order object.
	 * @return string
	 */
	public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
		if ( $order && 'liqpay' === $order->get_payment_method() ) {
			$status = $this->get_option( 'status' );
		}
		return $status;
	}

	/**
	 * Save metabox rro id field to the product page.
	 *
	 * @param int $post_id - Id product.
	 * @return void
	 */
	public static function save_rro_id_metabox( $post_id ) {
		// phpcs:ignore
		if ( ! isset( $_POST['product_rro_id_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['product_rro_id_meta_box_nonce'], 'product_rro_id_meta_box_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['product_rro_id'] ) ) {
			// phpcs:ignore
			update_post_meta( $post_id, 'product_rro_id', $_POST['product_rro_id'] );
		}
	}

	/**
	 * Dave variation metabox.
	 *
	 * @param (int) $variation_id - Id product.
	 * @param (int) $i - Index variation.
	 * @return void
	 */
	public static function save_rro_id_metabox_variable( $variation_id, $i ) {
		// phpcs:ignore
		if ( isset( $_POST['product_rro_id'][$i] ) ) {
			// phpcs:ignore
			update_post_meta( $variation_id, 'product_rro_id', sanitize_text_field( $_POST['product_rro_id'][$i] ) );
		}
	}

	/**
	 * Add metabox rro id field to the product page variable.
	 *
	 * @param int     $loop           Position in the loop.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Post data.
	 * @return void
	 */
	public static function add_rro_id_metabox_variable( $loop, $variation_data, $variation ) {
		$custom_value = get_post_meta( $variation->ID, 'product_rro_id', true );
		woocommerce_wp_text_input(
			array(
				'id'            => "product_rro_id{$loop}",
				'name'          => "product_rro_id[{$loop}]",
				'value'         => $custom_value,
				'label'         => esc_html__( 'Liqpay product ID for RRO', 'wcliqpay' ),
				'wrapper_class' => 'form-row form-row-first',
				'desc_tip'      => true,
				/* translators: %s: Product variation ID */
				'description'   => sprintf( esc_html__( 'If left blank, the product ID will be used. - %s', 'wcliqpay' ), $variation->ID ),
			)
		);
	}

	/**
	 * Add metabox rro id field to the product page.
	 *
	 * @return void
	 */
	public static function add_rro_id_metabox() {
		add_meta_box(
			'product_rro_id',
			__( 'Liqpay settings', 'wcliqpay' ),
			array( __CLASS__, 'render_rro_id_metabox' ),
			'product',
			'side',
			'high'
		);
	}

	/**
	 * Render metabox rro id field to the product page.
	 *
	 * @return void
	 */
	public static function render_rro_id_metabox() {
		global $post;
		$product_rro_id = get_post_meta( $post->ID, 'product_rro_id', true );
		wp_nonce_field( 'product_rro_id_meta_box_nonce', 'product_rro_id_meta_box_nonce' );
		echo '<label for="product_rro_id">' . esc_html__( 'Liqpay product ID for RRO', 'wcliqpay' ) . '</label>';
		echo '<input type="text" id="product_rro_id" name="product_rro_id" value="' . esc_attr( $product_rro_id ) . '" style="width: 100%;margin-top: 5px;" />';
		echo '<small>' . sprintf( esc_html__( 'If left blank, the product ID will be used. - %s', 'wcliqpay' ), $post->ID ) . '</small>';
	}

	/**
	 * Initialize form fields for the plugin.
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$statuses       = wc_get_order_statuses();
		$allow_statuses = array_filter(
			array_combine(
				array_map( fn( $key ) => str_replace( 'wc-', '', $key ), array_keys( $statuses ) ),
				array_map( fn( $key ) => wc_get_order_status_name( $key ), array_keys( $statuses ) )
			),
			fn( $key ) => ! in_array(
				$key,
				array(
					'on-hold',
					'pending',
					'cancelled',
					'refunded',
					'failed',
					'checkout-draft',
				)
			),
			ARRAY_FILTER_USE_KEY
		);

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
				'default'     => __( 'Pay using the payment system LiqPay', 'wcliqpay' ),
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
				'description' => __( 'Interface language for liqpay pages', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'icon'                => array(
				'title'       => __( 'Logotype', 'wcliqpay' ),
				'type'        => 'text',
				'default'     => WC_LIQPAY_DIR . 'assets/images/logo_liqpay.svg',
				'description' => __( 'Full path to the logo, located on the order page', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'status'              => array(
				'title'       => __( 'Order status', 'wcliqpay' ),
				'type'        => 'select',
				'default'     => 'processing',
				'options'     => $allow_statuses,
				'description' => __( 'Order status after successful payment', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'order_description'   => array(
				'title'       => __( 'Purpose of payment', 'wcliqpay' ),
				'type'        => 'text',
				'default'     => __( 'Payment for order №[order_number]', 'wcliqpay' ),
				'description' => __( 'Payment for order №[order_number]', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'redirect_page_error' => array(
				'title'       => __( 'URL error Payment page', 'wcliqpay' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'URL page to go to after gateway LiqPay', 'wcliqpay' ),
				'desc_tip'    => true,
			),
			'enabled_rro'         => array(
				'title'       => __( 'Enable/Disable send RRO', 'wcliqpay' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable/Disable send RRO', 'wcliqpay' ),
				'description' => sprintf(
					/* translators: %s - URL to Liqpay services page */
					__( 'More details at the link %s', 'wcliqpay' ),
					'<a href="https://www.liqpay.ua/products_services/services_rro">https://www.liqpay.ua/products_services/services_rro</a>'
				),
				'default'     => 'yes',
			),
			'debug'               => array(
				'title'       => __( 'Enable/Disable debug mode', 'wcliqpay' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'description' => sprintf(
					/* translators: %s - Link to WooCommerce logs page */
					__( 'Open WooCommerce logs page: %s', 'wcliqpay' ),
					'<a href="' . admin_url( 'admin.php?page=wc-status&tab=logs' ) . '">' . __( 'Go to logs', 'wcliqpay' ) . '</a>'
				),
			),
		);
	}

	/**
	 * Process the payment by redirecting to LiqPay's checkout.
	 *
	 * @param int $order_id - Ordre id.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Initialize the LiqPay SDK.
		$liqpay = new LiqPay( $this->get_option( 'public_key' ), $this->get_option( 'private_key' ) );

		$enabled_rro = ( 'yes' === $this->get_option( 'enabled_rro' ) );

		if ( $enabled_rro ) {
			// Add order items to rro_info.
			$rro_info = array(
				'items'           => array(),
				'delivery_emails' => array( $order->get_billing_email() ),
			);
		}

		foreach ( $order->get_items() as $item_id => $item ) {
			$product = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : false;
			if ( ! $product ) {
				continue;
			}

			if ( $enabled_rro ) {
				$product_rro_id_meta = get_post_meta( $product->get_id(), 'product_rro_id', true );

				if ( $product_rro_id_meta ) {
					$rro_product_id        = $product_rro_id_meta;
					$source_rro_product_id = 'meta';
				} else {
					$rro_product_id        = $product->get_id();
					$source_rro_product_id = 'original_id_product';
				}

				$item_data           = array(
					'amount' => $item->get_quantity(),
					'price'  => (float) $product->get_price(),
					'cost'   => (float) $item->get_total(),
					'id'     => (string) $rro_product_id,
				);
				$rro_info['items'][] = $item_data;

				if ( 'yes' === $this->get_option( 'debug' ) ) {
					$rro_info_debug['items'][] = array_merge(
						$item_data,
						array(
							'source_rro_product_id' => $source_rro_product_id,
						)
					);
				}
			}
		}

		$description = str_replace( '[order_number]', $order->get_id(), $this->get_option( 'order_description' ) );
		// Set the required parameters for the payment.
		$params = array(
			'version'     => '3',
			'action'      => 'pay',
			'amount'      => $order->get_total(),
			'email'       => $order->get_billing_email(),
			'currency'    => get_woocommerce_currency(),
			'description' => $description,
			'order_id'    => $order->get_id(),
			'result_url'  => add_query_arg(
				array(
					'wc-api'   => 'liqpay_return',
					'order_id' => $order->get_id(),
				),
				home_url( '/' )
			),
			'server_url'  => WC()->api_request_url( strtolower( get_class( $this ) ) ),
			'language'    => $this->get_option( 'lang' ),
		);

		if ( $enabled_rro ) {
			$params['rro_info'] = $rro_info;
			if ( 'yes' === $this->get_option( 'debug' ) ) {
				$this->print_debug_data(
					'LIQPAY: rro id source data:',
					array(
						'order_id'       => $order->get_id(),
						'rro_info_debug' => $rro_info_debug,
					)
				);
			}
		}

		// Filter "wc_liqpay_request_filter" to query array before sending data to liqpay.
		$params = apply_filters( 'wc_liqpay_request_filter', $params, $order );

		if ( 'yes' === $this->get_option( 'debug' ) ) {
			$this->print_debug_data( 'LIQPAY: Init data:', $params );
		}
		// Generate the LiqPay payment link.
		$payment_link = $liqpay->cnb_link( $params );

		// Redirect to the payment link.
		return array(
			'result'   => 'success',
			'redirect' => $payment_link,
		);
	}

	/**
	 * Handle callback from LiqPay to update the order status.
	 */
	public function handle_callback() {
		if ( 'yes' === $this->get_option( 'debug' ) ) {
			$this->print_debug_data( 'LIQPAY: handle_callback -- _POST data:', $_POST );
		}
		// phpcs:disable
		// Get data and signature from LiqPay's callback.
		$data      = isset( $_POST['data'] ) ? $_POST['data'] : null;
		$signature = isset( $_POST['signature'] ) ? $_POST['signature'] : null;
		// phpcs:enable

		if ( ! $data || ! $signature ) {
			// Missing data or signature.
			$this->print_debug_data(
				'LIQPAY: handle_callback -- Missing data or signature:',
				array(
					'data'      => $data,
					'signature' => $signature,
				)
			);
			wp_die( 'Invalid data received', 'LiqPay Callback', array( 'response' => 400 ) );
		}

		// Decode and parse the data from LiqPay.
		$liqpay       = new LiqPay( $this->get_option( 'public_key' ), $this->get_option( 'private_key' ) );
		$decoded_data = json_decode( base64_decode( $data ), true );

		if ( 'yes' === $this->get_option( 'debug' ) ) {
			$this->print_debug_data( 'LIQPAY: handle_callback -- Incoming decoded_data:', $decoded_data );
		}
		// Verify the signature.
		$generated_signature = $liqpay->str_to_sign( $this->get_option( 'private_key' ) . $data . $this->get_option( 'private_key' ) );
		if ( $signature !== $generated_signature ) {
			if ( 'yes' === $this->get_option( 'debug' ) ) {
				$this->print_debug_data(
					'LIQPAY: handle_callback -- Signature verification failed:',
					array(
						'data'      => $data,
						'signature' => $signature,
					)
				);
			}
			wp_die( 'Signature verification failed', 'LiqPay Callback', array( 'response' => 400 ) );
		}

		// Get the order ID and status from the LiqPay response.
		$order_id = isset( $decoded_data['order_id'] ) ? $decoded_data['order_id'] : null;
		$status   = isset( $decoded_data['status'] ) ? $decoded_data['status'] : null;

		if ( ! $order_id || ! $status ) {
			if ( 'yes' === $this->get_option( 'debug' ) ) {
				$this->print_debug_data(
					'LIQPAY: handle_callback -- Missing order ID or status:',
					array(
						'$status'   => $status,
						'$order_id' => $order_id,
					)
				);
			}
			wp_die( 'Missing order ID or status', 'LiqPay Callback', array( 'response' => 400 ) );
		}

		// Retrieve the order using the order ID.
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			if ( 'yes' === $this->get_option( 'debug' ) ) {
				$this->print_debug_data(
					'LIQPAY: handle_callback -- order not found:',
					array(
						'$order_id' => $order_id,
					)
				);
			}
			wp_die( 'Order not found', 'LiqPay Callback', array( 'response' => 404 ) );
		}

		// Update the order status based on the payment result.
		if ( 'success' === $status || 'sandbox' === $status ) {
			// Mark the order as "processing" or "completed" based on your workflow.
			$order->update_status( $this->get_option( 'status' ), __( 'Payment successful via LiqPay.', 'wcliqpay' ) );
			$order->payment_complete();
			if ( 'yes' === $this->get_option( 'debug' ) ) {
				$this->print_debug_data(
					'LIQPAY: handle_callback -- Payment successful via LiqPay.',
					array(
						'$status' => $status,
					)
				);
			}
		} elseif ( 'reversed' === $status ) {
			wc_create_refund(
				array(
					'amount'         => $order->get_total(),
					'reason'         => __( 'Payment refunded via Liqpay', 'wcliqpay' ),
					'order_id'       => $order->get_id(),
					'refund_payment' => false, // Do not process refunds through the payment gateway because the funds have already been refunded.
				)
			);
			$order->update_status( 'refunded', __( 'Payment refunded via Liqpay', 'wcliqpay' ) );
			if ( 'yes' === $this->get_option( 'debug' ) ) {
				$this->print_debug_data(
					'LIQPAY: handle_callback -- Payment refunded via LiqPay.',
					array(
						'$status' => $status,
					)
				);
			}
		} else {
			// If the status is not successful, mark the order as failed.
			$order->update_status( 'failed', __( 'Payment failed via LiqPay.', 'wcliqpay' ) );
			if ( 'yes' === $this->get_option( 'debug' ) ) {
				$this->print_debug_data(
					'LIQPAY: handle_callback -- Payment failed via LiqPay.',
					array(
						'$status' => $status,
					)
				);
			}
		}

		// Send a 200 response back to LiqPay to acknowledge receipt.
		header( 'HTTP/1.1 200 OK' );
		exit;
	}

	/**
	 * Return to site handler.
	 *
	 * @return void
	 */
	public function handle_return() {

		if ( 'yes' === $this->get_option( 'debug' ) ) {
			$this->print_debug_data(
				'LIQPAY: handle_return -- _GET data.',
				array(
					'$_GET' => $_GET,
				)
			);
		}

		//phpcs:disable
		if ( ! isset( $_GET['order_id'] ) ) {
			wp_redirect( home_url() );
			exit;
		}

		$order_id = intval( $_GET['order_id'] );
		$order    = wc_get_order( $order_id );
		//phpcs:enable
		if ( ! $order ) {
			if ( 'yes' === $this->get_option( 'debug' ) ) {
				$this->print_debug_data(
					'LIQPAY: handle_return -- Order not found.',
					array(
						'$order_id' => $order_id,
					)
				);
			}
			wp_safe_redirect( home_url() );
			exit;
		}

		// Check the payment status via LiqPay API.
		$result = $this->check_order_status( $order_id );

		if ( is_wp_error( $result ) ) {
			if ( 'yes' === $this->get_option( 'debug' ) ) {
				$this->print_debug_data(
					'LIQPAY: handle_return -- Payment verification failed.',
					array(
						'$result' => $result,
					)
				);
			}
			// Payment failed or error occurred.
			wc_add_notice( __( 'Payment verification failed. Please contact us for assistance.', 'wcliqpay' ), 'error' );
			$redirect_url = $this->get_option( 'redirect_page_error' ) ? $this->get_option( 'redirect_page_error' ) : wc_get_cart_url();
			wp_safe_redirect( $redirect_url );
			exit;
		}

		if ( 'yes' === $this->get_option( 'debug' ) ) {
			$this->print_debug_data(
				'LIQPAY: handle_return -- Payment was successful, redirect to received page.',
				array(
					'$redirect_url' => $this->get_return_url( $order ),
				)
			);
		}
		// Payment was successful, redirect to received page.
		wp_safe_redirect( $this->get_return_url( $order ) );
		exit;
	}

	/**
	 * Check order status.
	 *
	 * @param int $order_id - Order id.
	 * @return bool|WP_Error
	 */
	public function check_order_status( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			$this->print_debug_data(
				'LIQPAY: check_order_status -- Order not found.',
				array(
					'$order' => $order,
				)
			);
			return new WP_Error( 'invalid_order', 'Order not found.' );
		}

		// Initialize the LiqPay SDK.
		$liqpay = new LiqPay( $this->get_option( 'public_key' ), $this->get_option( 'private_key' ) );

		// Set the required parameters to check the status.
		$params = array(
			'version'  => '3',
			'action'   => 'status',
			'order_id' => $order_id,
		);

		// Send the API request to LiqPay.
		try {
			$response = $liqpay->api( 'request', $params );
			$this->print_debug_data(
				'LIQPAY: check_order_status -- response data.',
				array(
					'$order' => $response,
				)
			);
			if ( ! empty( $response->status ) ) {
				// Check if the payment is completed.
				if ( in_array( $response->status, array( 'success', 'sandbox', 'wait_accept' ), true ) ) {
					// Update WooCommerce order status to the status saved in the 'status' field.
					$order->update_status( $this->get_option( 'status' ), __( 'Payment confirmed via LiqPay API.', 'wcliqpay' ) );
					$order->payment_complete();
					return true;
				} else {
					// Payment not successful.
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


	/**
	 * Print debug log info
	 *
	 * @param string $title
	 * @param array  $data
	 * @return void
	 */
	function print_debug_data( $title, $data ) {
		if ( class_exists( 'WC_Logger' ) && function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->info( $title, $data );
			return true;
		}
		return false;
	}
}
