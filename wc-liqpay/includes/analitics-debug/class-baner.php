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
class Baner {

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

		require_once WC_LIQPAY_PATH . 'includes/notice/class-wp-admin-notice.php';

		$analytics_notice = self::get_baner_html();
		if ( empty( $analytics_notice ) ) {
			return;
		}

		$html = sprintf(
			'<div class="liqpay_notice_baner">%s</div>',
			$analytics_notice
		);

		WP_Admin_Notice::add(
			$html,
			'info',
			true,
			'analitics-debug',
			180 * DAY_IN_SECONDS
		);
	}

	/**
	 * Fetches the banner HTML from the LiqPay API.
	 */
	private static function get_baner_html() {
		$cache_key   = 'wclickpay_banner_cache';
		$cached_html = get_transient( $cache_key );

		if ( $cached_html !== false ) {
			return $cached_html;
		}

		$response = wp_remote_get( 'https://liqpay_statistic.dev.komanda.dev/wp-json/liqpay/v1/banner' );
		if ( is_wp_error( $response ) ) {
			return '';
		}
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return '';
		}
		$data_json = json_decode( $body, true );
		if ( is_array( $data_json ) && isset( $data_json['code'] ) ) {
			set_transient( $cache_key, $data_json['code'], DAY_IN_SECONDS );
			return $data_json['code'];
		} else {
			return '';
		}
	}
}

Baner::init();
