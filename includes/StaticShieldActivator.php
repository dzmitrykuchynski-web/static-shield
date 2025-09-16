<?php
namespace StaticShield;

/**
 * Fired during plugin activation
 *
 * @link       https://www.example.com/
 * @since      1.0.0
 *
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */

class StaticShieldActivator {
    /**
     * Run tasks on plugin activation.
     *
     * @since    1.0.0
     * @return   void
     */
    public static function activate() {
        if ( get_option( 'static_shield_version' ) === false ) {
            add_option( 'static_shield_version', STATIC_SHIELD_VERSION );
        } else {
            update_option( 'static_shield_version', STATIC_SHIELD_VERSION );
        }

        if ( get_option( 'static_shield_api_key' ) === false ) {
            add_option( 'static_shield_api_key', '' );
        }

        if ( get_option( 'static_shield_cf_account_id' ) === false ) {
            add_option( 'static_shield_cf_account_id', '' );
        }

        if ( get_option( 'static_shield_cf_bucket' ) === false ) {
            add_option( 'static_shield_cf_bucket', '' );
        }

        if ( get_option( 'static_shield_use_cf' ) === false ) {
            add_option( 'static_shield_use_cf', 0 );
        }

        if ( get_option( 'static_shield_cf_access_key_id' ) === false ) {
            add_option( 'static_shield_cf_access_key_id', '' );
        }

        if ( get_option( 'static_shield_cf_secret_access_key' ) === false ) {
            add_option( 'static_shield_cf_secret_access_key', '' );
        }

        if ( get_option( 'static_shield_last_log' ) === false ) {
            add_option( 'static_shield_last_log', [] );
        } else {
            update_option( 'static_shield_last_log', [] );
        }

        $uploadDir = wp_upload_dir();
        $staticShieldDir = trailingslashit( $uploadDir['basedir'] ) . 'static-shield-builds';

        if ( ! file_exists( $staticShieldDir ) ) {
            wp_mkdir_p( $staticShieldDir );
        }
    }
}
