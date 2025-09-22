<?php
namespace StaticShield\Admin;

use StaticShield\StaticShieldDeployer;
use StaticShield\StaticShieldExporter;
use StaticShield\StaticShieldWorkerClient;
use WP_Post;

/**
 * The admin-specific functionality of the Static Shield plugin.
 *
 * Handles WordPress admin menu, settings, manual export actions,
 * AJAX handlers, and post-update triggers.
 *
 * @link       https://www.example.com/
 * @since      1.0.0
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
     * @var      string
     */
    private $pluginName;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string $pluginName The name of this plugin.
     * @param    string $version    The version of this plugin.
     */
    public function __construct( $pluginName, $version ) {
        $this->pluginName = $pluginName;
        $this->version    = $version;
    }

    /**
     * Enqueue admin styles for the plugin settings page.
     *
     * @since 1.0.0
     * @return void
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
     * Enqueue admin JavaScript for the plugin settings page.
     *
     * @since 1.0.0
     * @return void
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
     * Add the Static Shield admin menu page to WordPress dashboard.
     *
     * @since 1.0.0
     * @return void
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
     * Render the plugin admin settings page.
     *
     * Loads the template from the partials directory.
     *
     * @since 1.0.0
     * @return void
     */
    public function renderAdminPage() {
        $apiKey            = esc_attr( get_option('static_shield_cf_api_key') );
        $manualExportNonce = wp_create_nonce('static_shield_manual_export');
        $exporter          = new StaticShieldExporter();
        $exportLog         = $exporter->getLog();

        // Include partial for admin display
        include STATIC_SHIELD_PATH . 'admin/partials/static-shield-admin-display.php';
    }

    /**
     * Register plugin settings for Cloudflare R2 and Worker integration.
     *
     * @since 1.0.0
     * @return void
     */
    public function registerSettings() {
        register_setting('static_shield_options_group', 'static_shield_cf_api_key');
        register_setting('static_shield_options_group', 'static_shield_cf_account_id');
        register_setting('static_shield_options_group', 'static_shield_cf_bucket');
        register_setting('static_shield_options_group', 'static_shield_cf_access_key_id');
        register_setting('static_shield_options_group', 'static_shield_cf_secret_access_key');
        register_setting('static_shield_options_group', 'static_shield_use_cf');
        register_setting('static_shield_options_group', 'static_shield_cf_worker');
    }

    /**
     * Handle manual export triggered from admin UI.
     *
     * Exports site to ZIP and uploads to R2 if enabled.
     *
     * @since 1.0.0
     * @return void
     */
    public function handleManualExport() {
        if ( isset($_POST['static_shield_manual_export'])
            && check_admin_referer('static_shield_manual_export') ) {

            $exporter = new StaticShieldExporter();
            $zipPath  = $exporter->runExport();

            if (get_option('static_shield_use_cf')) {
                $deployer = new StaticShieldDeployer();
                $deployer->uploadToR2($zipPath);
            }

            add_action('admin_notices', function() {
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
     * Trigger automatic static export after a post update.
     *
     * @since 1.0.0
     *
     * @param int     $postId Post ID.
     * @param WP_Post $post   Post object.
     * @param bool    $update Whether this is an existing post being updated.
     * @return void
     */
    public function handlePostUpdate( $postId, $post, $update ) {
        if ( wp_is_post_autosave( $postId ) || wp_is_post_revision( $postId ) ) {
            return;
        }

        if ( $update ) {
            $exporter = new StaticShieldExporter();
            $zipPath  = $exporter->runExport();

            if (get_option('static_shield_use_cf')) {
                $deployer = new StaticShieldDeployer();
                $deployer->uploadToR2($zipPath);
            }

            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Static build regenerated after post update!</p></div>';
            });
        }
    }

    /**
     * Add a "Settings" link next to the Deactivate button in the plugin list.
     *
     * @since 1.0.0
     *
     * @param array $links Existing action links.
     * @return array Modified action links with "Settings".
     */
    public function addPluginActionLinks( $links ) {
        $settingsLink = '<a href="' . admin_url( 'admin.php?page=' . $this->pluginName ) . '">Settings</a>';
        $links[] = $settingsLink;
        return $links;
    }

    /**
     * Register AJAX actions for retrieving logs and saving settings.
     *
     * @since 1.0.0
     * @return void
     */
    public function registerAjax() {
        add_action('wp_ajax_static_shield_get_logs', [$this, 'ajaxGetLogs']);
        add_action('wp_ajax_static_shield_save_domain_settings', [$this, 'ajaxSaveDomainSettings']);
        add_action('wp_ajax_static_shield_save_worker_settings', [$this, 'ajaxSaveWorkerSettings']);
        add_action('wp_ajax_static_shield_dns_list', [$this, 'ajaxDnsList']);
        add_action('wp_ajax_static_shield_dns_add', [$this, 'ajaxDnsAdd']);
        add_action('wp_ajax_static_shield_dns_delete', [$this, 'ajaxDnsDelete']);
    }

    /**
     * AJAX handler for saving Cloudflare Domain settings.
     *
     * @since 1.0.0
     * @return void Sends JSON response.
     */
    public function ajaxSaveDomainSettings() {
        if ( ! current_user_can('manage_options') ) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $nonce = $_POST['_wpnonce'] ?? '';
        if ( ! wp_verify_nonce($nonce, 'static_shield_save_domain_settings') ) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        $apiKey    = sanitize_text_field($_POST['api_key'] ?? '');
        $workerUrl = sanitize_text_field($_POST['cf_worker_url'] ?? '');

        update_option('static_shield_cf_api_key', $apiKey);
        update_option('static_shield_cf_worker', $workerUrl);

        wp_send_json_success(['message' => 'Domain settings saved']);
    }

    /**
     * AJAX handler for saving Cloudflare Worker and R2 settings.
     *
     * @since 1.0.0
     * @return void Sends JSON response.
     */
    public function ajaxSaveWorkerSettings() {
        if ( ! current_user_can('manage_options') ) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $nonce = $_POST['_wpnonce'] ?? '';
        if ( ! wp_verify_nonce($nonce, 'static_shield_save_worker_settings') ) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        $accountId   = sanitize_text_field($_POST['account_id'] ?? '');
        $bucket      = sanitize_text_field($_POST['bucket'] ?? '');
        $accessKeyId = sanitize_text_field($_POST['access_key_id'] ?? '');
        $secretKey   = sanitize_text_field($_POST['secret_access_key'] ?? '');
        $useCf       = isset($_POST['use_cf']) ? 1 : 0;

        update_option('static_shield_cf_account_id', $accountId);
        update_option('static_shield_cf_bucket', $bucket);
        update_option('static_shield_cf_access_key_id', $accessKeyId);
        update_option('static_shield_cf_secret_access_key', $secretKey);
        update_option('static_shield_use_cf', $useCf);

        wp_send_json_success(['message' => 'Worker settings saved']);
    }

    /**
     * Get list of DNS records via Worker
     */
    public function ajaxDnsList() {
        $client = new StaticShieldWorkerClient();
        $records = $client->listDnsRecords();

        if (is_wp_error($records)) {
            wp_send_json_error(['message' => $records->get_error_message()]);
        }

        wp_send_json_success(['records' => $records]);
    }

    /**
     * Add DNS record
     */
    public function ajaxDnsAdd() {
        $client = new StaticShieldWorkerClient();

        $record = [
            'type'    => sanitize_text_field($_POST['type']),
            'name'    => sanitize_text_field($_POST['name']),
            'content' => sanitize_text_field($_POST['content']),
            'ttl'     => intval($_POST['ttl'] ?? 3600),
            'proxied' => isset($_POST['proxied']) ? (bool) $_POST['proxied'] : false,
        ];

        $result = $client->addDnsRecord($record);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['record' => $result]);
    }

    /**
     * Delete DNS record
     */
    public function ajaxDnsDelete() {
        $id = sanitize_text_field($_POST['id']);
        $client = new StaticShieldWorkerClient();

        $result = $client->deleteDnsRecord($id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['deleted' => $id]);
    }

    /**
     * AJAX handler for retrieving export logs.
     *
     * @since 1.0.0
     * @return void Sends JSON response with logs.
     */
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
