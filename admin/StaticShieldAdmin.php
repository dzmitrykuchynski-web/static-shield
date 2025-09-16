<?php
namespace StaticShield\Admin;

use StaticShield\StaticShieldExporter;
use WP_Post;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.alreadymedia.com/
 * @since      1.0.0
 *
 * @package    Static_Shield
 * @subpackage Static_Shield/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Static_Shield
 * @subpackage Static_Shield/admin
 */
class StaticShieldAdmin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $pluginName    The ID of this plugin.
	 */
	private $pluginName;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $pluginName       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $pluginName, $version ) {
		$this->pluginName = $pluginName;
		$this->version = $version;

	}

    /**
     * Enqueue admin styles.
     */
    public function enqueueStyles() {
        wp_enqueue_style(
            $this->pluginName,
            STATIC_SHIELD_URL . 'admin/css/static-shield-admin.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue admin scripts.
     */
    public function enqueueScripts() {
        wp_enqueue_script(
            $this->pluginName,
            STATIC_SHIELD_URL . 'admin/js/static-shield-admin.js',
            ['jquery'],
            $this->version,
            true
        );
    }

    /**
     * Add admin menu page.
     */
    public function addAdminMenu() {
        add_menu_page(
            'Static Shield',
            'Static Shield',
            'manage_options',
            $this->pluginName,
            [$this, 'renderAdminPage'],
            'dashicons-shield',
            75
        );
    }

    /**
     * Render admin page using partial template.
     */
    public function renderAdminPage() {
        $apiKey = esc_attr( get_option('static_shield_api_key') );
        $manualExportNonce = wp_create_nonce('static_shield_manual_export');
        $exporter  = new StaticShieldExporter();
        $exportLog = $exporter->getLog();

        // Include partial for admin display
        include STATIC_SHIELD_PATH . 'admin/partials/static-shield-admin-display.php';
    }

    /**
     * Register plugin settings.
     */
    public function registerSettings() {
        register_setting('static_shield_options_group', 'static_shield_api_key');

        add_settings_section(
            'static_shield_main_section',
            'Cloudflare R2 Settings',
            function() { echo '<p>Enter your Cloudflare R2 API key below.</p>'; },
            'static_shield'
        );

        add_settings_field(
            'static_shield_api_key',
            'R2 API Key',
            [$this, 'renderApiKeyField'],
            'static_shield',
            'static_shield_main_section'
        );
    }

    /**
     * Render API key input field.
     */
    public function renderApiKeyField() {
        $apiKey = esc_attr( get_option('static_shield_api_key') );
        echo "<input type='text' name='static_shield_api_key' value='{$apiKey}' class='regular-text'>";
    }

    /**
     * Handle manual export action.
     */
    public function handleManualExport() {
        if ( isset($_POST['static_shield_manual_export'])
            && check_admin_referer('static_shield_manual_export') ) {

            $exporter = new StaticShieldExporter();
            $exporter->runExport();

            add_action('admin_notices', function() use ($exporter) {
                $zipPath = content_url( 'static-shield-builds.zip' );
                echo '<div class="notice notice-success is-dismissible">
                    <p>Manual export completed! 
                       <a href="' . esc_url($zipPath) . '" target="_blank">Download ZIP</a>
                    </p>
                  </div>';
            });
        }
    }

    /**
     * Trigger static export after post update.
     *
     * @param int     $postId
     * @param WP_Post $post
     * @param bool    $update
     */
    public function handlePostUpdate( $postId, $post, $update ) {
        if ( wp_is_post_autosave( $postId ) || wp_is_post_revision( $postId ) ) {
            return;
        }

        if ( $update ) {
            $exporter = new StaticShieldExporter();
            $exporter->runExport();

            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Static build regenerated after post update!</p></div>';
            });
        }
    }


    /**
     * Add a "Settings" link next to Deactivate button in plugins list.
     *
     * @param array $links Existing links.
     * @return array Modified links.
     */
    public function addPluginActionLinks( $links ) {
        $settingsLink = '<a href="' . admin_url( 'admin.php?page=' . $this->pluginName ) . '">Settings</a>';
        $links[] = $settingsLink;
        return $links;
    }

    public function registerAjax() {
        add_action('wp_ajax_static_shield_get_logs', [$this, 'ajaxGetLogs']);
        add_action('wp_ajax_static_shield_save_cf_token', [$this, 'ajaxSaveCfToken']);
    }

    public function ajaxSaveCfToken() {
        if ( ! current_user_can('manage_options') ) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $nonce = $_POST['_wpnonce'] ?? '';
        if ( ! wp_verify_nonce($nonce, 'static_shield_save_cf_settings') ) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        $token = sanitize_text_field($_POST['token'] ?? '');
        update_option('static_shield_api_key', $token);

        wp_send_json_success(['message' => 'Token saved']);
    }

    public function ajaxGetLogs() {
        if ( ! current_user_can('manage_options') ) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $exporter = new StaticShieldExporter();
        $logs     = $exporter->getLog();

        wp_send_json_success([
            'logs' => $logs,
        ]);
    }
}
