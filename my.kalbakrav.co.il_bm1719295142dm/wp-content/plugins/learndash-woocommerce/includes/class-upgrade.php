<?php
/**
 * Upgrade class file.
 *
 * @since 1.0.0
 *
 * @package LearnDash\WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrade class.
 *
 * @since 1.0.0
 */
class Learndash_Woocommerce_Upgrade {
	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'check_upgrade' ] );
	}

	/**
	 * Check if an upgrade is needed. If so, call the upgrade method.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function check_upgrade() {
		if ( did_action( 'admin_init' ) > 1 ) {
			return;
		}

		/**
		 * Saved version.
		 *
		 * @var string|false $saved_version The saved version.
		 */
		$saved_version   = get_option( 'learndash_woocommerce_version', false );
		$current_version = LEARNDASH_WOOCOMMERCE_VERSION;

		if ( $saved_version !== false && $saved_version < $current_version ) {
			self::upgrade( $saved_version, $current_version );
			update_option( 'learndash_woocommerce_version', $current_version, true );
		}
	}

	/**
	 * Upgrade.
	 *
	 * @since 1.0.0
	 *
	 * @param string $from_version The version we are upgrading from.
	 * @param string $to_version   The version we are upgrading to.
	 *
	 * @return void
	 */
	public static function upgrade( $from_version, $to_version ) {
		if ( ( $from_version <= '1.8.0.6' || ! $from_version ) && $to_version >= '1.8.0.7' ) {
			$queue = get_option( 'learndash_woocommerce_silent_course_enrollment_queue', [] );
			// Delete first so autoload value can be updated in DB.
			delete_option( 'learndash_woocommerce_silent_course_enrollment_queue' );

			update_option( 'learndash_woocommerce_silent_course_enrollment_queue', $queue, false );
		}
	}
}

Learndash_Woocommerce_Upgrade::init();
