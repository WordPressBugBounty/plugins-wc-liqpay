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
	}

	/**
	 * Adds a new administrative notice to the queue for display.
	 *
	 * @param string $message     The text of the message to be displayed.
	 * @param string $type        The type of the notice ('info', 'warning', 'error', 'success'). Default is 'info'.
	 * @param bool   $dismissible Whether the notice should be dismissible. Default is true.
	 * @param string $class       An additional CSS class for the notice div element.
	 * @return void
	 */
	public static function add( $message, $type = 'info', $dismissible = true, $class ) {
		self::$notices[] = array(
			'message'     => $message,
			'type'        => $type,
			'class'       => $class,
			'dismissible' => $dismissible,
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
			$class = 'notice notice-' . esc_attr( $notice['type'] );

			if ( $notice['dismissible'] ) {
				$class .= ' is-dismissible';
			}

			if ( ! empty( $notice['class'] ) ) {
				$class .= ' ' . esc_attr( $notice['class'] );
			}

			// phpcs:ignore
			printf('<div class="%s"><p>%s</p></div>',esc_attr( $class ),$notice['message']);
		}

		// Clear the notices array after displaying them so they don't show again on every page load.
		self::$notices = array();
	}
}
