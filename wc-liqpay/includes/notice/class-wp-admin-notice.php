<?php
/**
 * WP Admin Notice Class File
 *
 * Provides functionality for displaying administrative notices in WordPress.
 *
 * @package WCLickpay
 */

/**
 * Class for displaying administrative notices in WordPress.
 *
 * This class allows for easy addition and display of administrative notices
 * of various types (info, warning, error, success) with the option to dismiss.
 *
 * @package WCLickpay
 */
class WP_Admin_Notice {

	/**
	 * Array of registered administrative notices.
	 *
	 * @var array
	 */
	protected static $notices = array();

	/**
	 * Initializes the class, adding an action hook for 'admin_notices'.
	 *
	 * This method should be called on a WordPress hook such as 'plugins_loaded' or 'admin_init'.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_notices', array( self::class, 'display_notices' ) );
		add_action( 'wp_ajax_wcliqpay_dismiss_notice', array( self::class, 'ajax_dismiss_notice' ) );
	}

	/**
	 * Handles AJAX request for dismissing notices.
	 *
	 * @return void
	 */
	public static function ajax_dismiss_notice() {
		if ( ! check_ajax_referer( 'wcliqpay_admin_notice_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'wcliqpay' ) );
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to dismiss this notice.', 'wcliqpay' ) );
			return;
		}

		if ( ! empty( $_POST['notice_id'] ) ) {
			$notice_id = sanitize_text_field( $_POST['notice_id'] );
			$timeout   = isset( $_POST['timeout'] ) ? absint( $_POST['timeout'] ) : 0;

			if ( $timeout > 0 ) {
				set_transient(
					'wcliqpay_notice_dismissed_' . $notice_id,
					true,
					$timeout
				);
			} else {
				// Permanent dismissal (1 year)
				set_transient(
					'wcliqpay_notice_dismissed_' . $notice_id,
					true,
					YEAR_IN_SECONDS
				);
			}

			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Adds a new administrative notice to the queue for display.
	 *
	 * @param string $message     The text of the message to be displayed.
	 * @param string $type        The type of the notice ('info', 'warning', 'error', 'success'). Default is 'info'.
	 * @param bool   $dismissible Whether the notice should be dismissible. Default is true.
	 * @param string $class_attr       An additional CSS class for the notice div element.
	 * @return void
	 */
	public static function add( $message, $type = 'info', $dismissible = true, $class_attr, $dismissible_timeout = null ) {
		self::$notices[] = array(
			'message'             => $message,
			'type'                => $type,
			'class'               => $class_attr,
			'dismissible'         => $dismissible,
			'dismissible-timeout' => $dismissible_timeout,
		);
	}

	/**
	 * Displays all registered administrative notices.
	 *
	 * This method is called by the 'admin_notices' hook and outputs the HTML
	 * for each notice in the queue.
	 *
	 * @return void
	 */
	public static function display_notices() {
		foreach ( self::$notices as $notice ) {
			$serialized_args = wp_json_encode( $notice, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$id              = hash( 'sha256', $serialized_args );

			// Check if notice was dismissed.
			if ( $notice['dismissible'] && get_transient( 'wcliqpay_notice_dismissed_' . $id ) ) {
				continue;
			}

			$class      = 'notice notice-' . esc_attr( $notice['type'] );
			$attributes = '';
			$script     = '';
			if ( $notice['dismissible'] ) {
				if ( $notice['dismissible-timeout'] && is_numeric( $notice['dismissible-timeout'] ) ) {
					$attributes = sprintf(
						' data-dismissible-timeout="%d" ',
						absint( $notice['dismissible-timeout'] )
					);
					$script     = sprintf(
						'<script type="text/javascript">
							var CurentNoticeId = "' . $id . '";
							jQuery(document).on("click", "#' . $id . ' .notice-dismiss", function(e) {
								var current_notice = jQuery( this ).closest("#' . $id . '");
								var timeout = "%s";
									jQuery.ajax({
										url: "' . admin_url( 'admin-ajax.php' ) . '",
										type: "POST",
										data: {
											action: "wcliqpay_dismiss_notice",
											notice_id: "' . $id . '",
											timeout: timeout,
											nonce: "' . wp_create_nonce( 'wcliqpay_admin_notice_nonce' ) . '",
										}
									});
							});
						</script>',
						absint( $notice['dismissible-timeout'] )
					);
				} else {
					$class .= ' wcliqpay-dismissable';

				}
				$class .= ' is-dismissible';
			}

			if ( ! empty( $notice['class'] ) ) {
				$class .= ' ' . esc_attr( $notice['class'] );
			}

			// phpcs:ignore
			printf('<div id="'.$id.'" class="%s" %s>%s</div>%s',esc_attr( $class ),$attributes,$notice['message'],$script);
		}

		// Clear the notices array after displaying them so they don't show again on every page load.
		self::$notices = array();
	}
}
