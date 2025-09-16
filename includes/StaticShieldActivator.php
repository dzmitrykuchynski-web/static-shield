<?php
namespace StaticShield;

/**
 * Fired during plugin activation
 *
 * @link       https://www.alreadymedia.com/
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

        if ( get_option( 'static_shield_last_log' ) === false ) {
            add_option( 'static_shield_api_key', '' );
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
