<?php
/**
 * Analytics Debug Class File
 *
 * This file contains the Analitics_Debug class for displaying debug notices related to analytics in the WordPress admin area.
 *
 * @package WCLickpay
 */

/**
 * Class for displaying debug notices related to analytics.
 *
 * This class is responsible for showing specific debug messages in the WordPress admin area.
 * It utilizes the WP_Admin_Notice class for displaying these notices.
 *
 * @package WCLickpay
 */
class Analitics_Debug {

	/**
	 * Initializes the analytics debug notices.
	 *
	 * This method includes the WP_Admin_Notice class and adds a specific
	 * debug notice related to the LiqPay gateway when debug mode is enabled.
	 * It should be called on a suitable WordPress action hook, such as 'admin_init'.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'wcliqpay_maybe_send_analytics_to_remote' ) );

		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		if ( get_option( 'off_analytics_notice_displayed' ) ) {
			return;
		}

		add_action(
			'admin_enqueue_scripts',
			function () {

				require_once WC_LIQPAY_PATH . 'includes/notice/class-wp-admin-notice.php';

				$analytics_notice = sprintf(
				// Opening <p> provided by wcliqpay_notice_html().
					'<strong>%1$s</strong><br>%2$s</p>',
					__( 'Would you allow Payment Gateway for LiqPay for Woocommerce to collect non-sensitive diagnostic data from this website?', 'wcliqpay' ),
					__( 'This will help us improve the LiqPay payment gateway plugin for Woocommerce for you in the future.', 'wcliqpay' )
				);

				$analytics_notice .= sprintf(
					'<p><button onclick="this.closest(\'.analitics-debug\').querySelector(\'.wcliqpay-analytics-data-container\').style.display = \'block\'; this.style.display = \'none\';" class="button-link hide-if-no-js button-wcliqpay-reveal wcliqpay-preview-analytics-data">%s</button></p>',
					/* translators: button text, click will expand data collection preview */
					__( 'What info will we collect?', 'wcliqpay' )
				);

				$analytics_notice .= sprintf(
					'<div class="wcliqpay-analytics-data-container" style="display:none"><p class="description">%1$s</p>%2$s</div>',
					__( 'Below is a detailed view of all data Payment Gateway for LiqPay for Woocommerce will collect if granted permission. Our will never transmit any domain names or email addresses, IP addresses, or third-party API keys.', 'wcliqpay' ),
					self::data_collection_preview_table()
				);

				$analytics_notice .= sprintf(
					'<p><a href="%1$s" class="button button-primary">%2$s</a> <a href="%3$s" class="button button-secondary">%4$s</a>',
					// Closing </p> provided by wcliqpay_notice_html().
					wp_nonce_url( admin_url( 'admin-post.php?action=wcliqpay_analytics_optin&value=yes' ), 'analytics_optin_nonce' ),
					/* translators: button text for data collection opt-in */
					__( 'Yes, allow', 'wcliqpay' ),
					wp_nonce_url( admin_url( 'admin-post.php?action=wcliqpay_analytics_optin&value=no' ), 'analytics_optin_nonce' ),
					/* translators: button text for data collection opt-in */
					__( 'No, thanks', 'wcliqpay' )
				);

				WP_Admin_Notice::add(
					$analytics_notice,
					'info',
					true,
					'analitics-debug'
				);
				WP_Admin_Notice::init();
			}
		);
		add_action( 'admin_post_wcliqpay_analytics_optin', array( __CLASS__, 'wcliqpay_analytics_optin' ) );
	}

	/**
	 * Send tchnical data.
	 *
	 * @return void
	 */
	public static function wcliqpay_maybe_send_analytics_to_remote() {
		if ( ! get_option( 'wcliqpay_analytics_enabled' ) ) {
			return;
		}
		if ( get_transient( 'wcliqpay_send_remote_analytics_data' ) ) {
			return;
		}

		$data = self::analytics_data();

		wp_remote_post(
			'https://liqpay_statistic.dev.komanda.dev/wp-json/wcliqpay/v1/collect',
			array(
				'method'  => 'POST',
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			)
		);

		set_transient( 'wcliqpay_send_remote_analytics_data', 1, 7 * DAY_IN_SECONDS );
	}

	/**
	 * Reset option for debug notice.
	 *
	 * @return void
	 */
	public static function reset_options() {
		delete_option( 'wcliqpay_analytics_enabled' );
		delete_option( 'off_analytics_notice_displayed' );
		delete_transient( 'wcliqpay_send_remote_analytics_data' );
	}

	/**
	 * Handler for the analytics opt-in action.
	 */
	public static function wcliqpay_analytics_optin() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'analytics_optin_nonce' ) ) {
			wp_nonce_ays( '' );
		}

		if ( ! current_user_can( 'administrator' ) ) {
			wp_safe_redirect( wp_get_referer() );
			die();
		}

		if ( isset( $_GET['value'] ) && 'yes' === $_GET['value'] ) {
			update_option( 'wcliqpay_analytics_enabled', 1 );
		}

		update_option( 'off_analytics_notice_displayed', 1 );

		wp_safe_redirect( wp_get_referer() );
		die();
	}

	/**
	 * Get technical data.
	 *
	 * @return array
	 */
	public static function analytics_data() {
		global $wp_version, $is_nginx, $is_apache, $is_iis7, $is_IIS;
		$data['web_server'] = 'Unknown';
		if ( $is_nginx ) {
			$data['web_server'] = 'NGINX';
		} elseif ( $is_apache ) {
			$data['web_server'] = 'Apache';
		} elseif ( $is_iis7 ) {
			$data['web_server'] = 'IIS 7';
		} elseif ( $is_IIS ) {
			$data['web_server'] = 'IIS';
		}

		$locale = explode( '_', get_locale() );
		$theme  = wp_get_theme();

		$data['php_version']       = preg_replace( '@^(\d\.\d+).*@', '\1', phpversion() );
		$data['wordpress_version'] = preg_replace( '@^(\d\.\d+).*@', '\1', $wp_version );
		$data['current_theme']     = $theme->get( 'Name' );
		$data['active_plugins']    = self::get_active_plugins();
		$data['locale']            = $locale[0];
		$data['multisite']         = is_multisite();

		if ( ! function_exists( 'get_core_updates' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		if ( ! class_exists( 'WP_Debug_Data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
		}
		if ( ! class_exists( 'WP_Site_Health' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
		}

		WP_Site_Health::get_instance();

		WP_Debug_Data::check_for_updates();
		$data['info'] = WP_Debug_Data::debug_data();

		return $data;
	}

	/**
	 * Get active plugins.
	 *
	 * @return array
	 */
	public static function get_active_plugins() {
		$plugins        = array();
		$active_plugins = array_intersect_key( get_plugins(), array_flip( array_filter( array_keys( get_plugins() ), 'is_plugin_active' ) ) );

		foreach ( $active_plugins as $plugin ) {
			$plugins[] = $plugin['Name'];
		}

		return $plugins;
	}

	/**
	 * Preview table send data.
	 *
	 * @return string
	 */
	public static function data_collection_preview_table() {
		$data = self::analytics_data();

		if ( ! $data ) {
			return;
		}

		$html  = '<table class="wcliqpay-data-table widefat striped">';
		$html .= '<tbody>';

		$html .= '<tr>';
		$html .= '<td class="column-primary">';
		$html .= sprintf( '<strong>%s</strong>', __( 'Server type:', 'wcliqpay' ) );
		$html .= '</td>';
		$html .= '<td>';
		$html .= sprintf( '<em>%s</em>', $data['web_server'] );
		$html .= '</td>';
		$html .= '</tr>';

		$html .= '<tr>';
		$html .= '<td class="column-primary">';
		$html .= sprintf( '<strong>%s</strong>', __( 'PHP version number:', 'wcliqpay' ) );
		$html .= '</td>';
		$html .= '<td>';
		$html .= sprintf( '<em>%s</em>', $data['php_version'] );
		$html .= '</td>';
		$html .= '</tr>';

		$html .= '<tr>';
		$html .= '<td class="column-primary">';
		$html .= sprintf( '<strong>%s</strong>', __( 'WordPress version number:', 'wcliqpay' ) );
		$html .= '</td>';
		$html .= '<td>';
		$html .= sprintf( '<em>%s</em>', $data['wordpress_version'] );
		$html .= '</td>';
		$html .= '</tr>';

		$html .= '<tr>';
		$html .= '<td class="column-primary">';
		$html .= sprintf( '<strong>%s</strong>', __( 'WordPress multisite:', 'wcliqpay' ) );
		$html .= '</td>';
		$html .= '<td>';
		$html .= sprintf( '<em>%s</em>', $data['multisite'] ? 'true' : 'false' );
		$html .= '</td>';
		$html .= '</tr>';

		$html .= '<tr>';
		$html .= '<td class="column-primary">';
		$html .= sprintf( '<strong>%s</strong>', __( 'Current theme:', 'wcliqpay' ) );
		$html .= '</td>';
		$html .= '<td>';
		$html .= sprintf( '<em>%s</em>', $data['current_theme'] );
		$html .= '</td>';
		$html .= '</tr>';

		$html .= '<tr>';
		$html .= '<td class="column-primary">';
		$html .= sprintf( '<strong>%s</strong>', __( 'Current site language:', 'wcliqpay' ) );
		$html .= '</td>';
		$html .= '<td>';
		$html .= sprintf( '<em>%s</em>', $data['locale'] );
		$html .= '</td>';
		$html .= '</tr>';

		$html .= '<tr>';
		$html .= '<td class="column-primary">';
		$html .= sprintf( '<strong>%s</strong>', __( 'Active plugins:', 'wcliqpay' ) );
		$html .= '</td>';
		$html .= '<td>';
		$html .= sprintf( '<em>%s</em>', __( 'Plugin names of all active plugins', 'wcliqpay' ) );
		$html .= '</td>';
		$html .= '</tr>';

		$html .= '<tr>';
		$html .= '<td class="column-primary">';
		$html .= sprintf( '<strong>%s</strong>', __( 'Information from the "Site Health"', 'wcliqpay' ) );
		$html .= '</td>';
		$html .= '<td>';
		$html .= sprintf( '<em>%s</em>', __( 'Technical information about the WordPress system and settings', 'wcliqpay' ) );
		$html .= '</td>';
		$html .= '</tr>';

		$html .= '</tbody>';
		$html .= '</table>';

		return $html;
	}
}

Analitics_Debug::init();
