<?php
namespace StaticShield\Admin;

use StaticShield\StaticShieldDeployer;
use StaticShield\StaticShieldExporter;
use StaticShield\StaticShieldWorkerClient;
use StaticShield\StaticShieldLoader;
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
     * Transient key used to store admin notices between requests.
     *
     * @since   1.0.0
     * @access  private
     * @var     string
     */
    private $noticeTransientKey = 'static_shield_admin_notices';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string $pluginName The name of this plugin.
     * @param    string $version    The version of this plugin.
     */
    public function __construct($pluginName, $version) {
        $this->pluginName = $pluginName;
        $this->version    = $version;
    }

    /**
     * Register all hooks for admin side through the loader.
     *
     * @since 1.0.0
     * @param StaticShieldLoader $loader Loader instance.
     * @return void
     */
    public function registerHooks(StaticShieldLoader $loader) {
        // Assets
        $loader->addAction('admin_enqueue_scripts', $this, 'enqueueStyles');
        $loader->addAction('admin_enqueue_scripts', $this, 'enqueueScripts');

        // Menu + settings
        $loader->addAction('admin_menu', $this, 'addAdminMenu');
        $loader->addAction('admin_init', $this, 'registerSettings');

        // Manual export
        $loader->addAction('admin_post_static_shield_manual_export', $this, 'handleManualExport');

        // Post update trigger
        $loader->addAction('save_post', $this, 'handlePostUpdate', 10, 3);

        // Plugin list settings link
        $loader->addFilter('plugin_action_links_' . STATIC_SHIELD_BASENAME, $this, 'addPluginActionLinks');

        // AJAX endpoints
        $loader->addAction('wp_ajax_static_shield_get_logs', $this, 'ajaxGetLogs');
        $loader->addAction('wp_ajax_static_shield_save_domain_settings', $this, 'ajaxSaveDomainSettings');
        $loader->addAction('wp_ajax_static_shield_save_worker_settings', $this, 'ajaxSaveWorkerSettings');
        $loader->addAction('wp_ajax_static_shield_dns_list', $this, 'ajaxDnsList');
        $loader->addAction('wp_ajax_static_shield_dns_add', $this, 'ajaxDnsAdd');
        $loader->addAction('wp_ajax_static_shield_dns_delete', $this, 'ajaxDnsDelete');

        $loader->addAction('admin_notices', $this, 'printNotices');
    }

    /**
     * Enqueue admin styles.
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

        wp_localize_script($this->pluginName, 'StaticShieldData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'dnsNonce' => wp_create_nonce('static_shield_dns'),
            'saveDomainNonce' => wp_create_nonce('static_shield_save_domain_settings'),
            'saveWorkerNonce' => wp_create_nonce('static_shield_save_worker_settings'),
        ]);
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
        $apiKey            = esc_attr(get_option('static_shield_cf_api_key'));
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
     * Run export and optionally deploy to R2.
     *
     * Extracted to avoid duplication between manual and automated exports.
     *
     * @since 1.0.0
     * @access private
     * @return string|false Path to created ZIP on success, false on failure.
     */
    private function runExportAndDeploy() {
        $exporter = new StaticShieldExporter();
        $zipPath  = $exporter->runExport();

        if (!$zipPath || !file_exists($zipPath)) {
            $this->addNotice('Export failed: archive not created', 'error');
            return false;
        }

        if (get_option('static_shield_use_cf')) {
            $deployer = new StaticShieldDeployer();
            $ok = $deployer->uploadToR2($zipPath);
            if (!$ok) {
                $this->addNotice('Upload to R2 failed. Check logs.', 'error');
                return false;
            }
        }

        return $zipPath;
    }

    /**
     * Handle manual export triggered from admin UI.
     *
     * @since 1.0.0
     * @return void
     */
    public function handleManualExport() {
        if (!isset($_POST['static_shield_manual_export'])) {
            return;
        }

        if (!check_admin_referer('static_shield_manual_export')) {
            $this->addNotice('Invalid nonce for manual export', 'error');
            wp_safe_redirect(admin_url('admin.php?page=' . $this->pluginName));
            exit;
        }

        if (!current_user_can('manage_options')) {
            $this->addNotice('You are not permitted to run the export', 'error');
            wp_safe_redirect(admin_url('admin.php?page=' . $this->pluginName));
            exit;
        }

        $zipPath = $this->runExportAndDeploy();
        $uploads = wp_get_upload_dir();
        $downloadUrl = $uploads['baseurl'] . '/static-shield-builds/static-shield-builds.zip';

        if (file_exists($zipPath)) {
            $this->addNotice(
                'Manual export completed. <a href="' . esc_url($downloadUrl) . '" target="_blank">Download ZIP</a>',
                'success'
            );
        }

        wp_safe_redirect(admin_url('admin.php?page=' . $this->pluginName));
        exit;
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
    public function handlePostUpdate($postId, $post, $update) {
        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }

        if ($update) {
            $zipPath = $this->runExportAndDeploy();
            if ($zipPath) {
                $this->addNotice('Static build regenerated after post update!', 'success');
            }
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
    public function addPluginActionLinks($links) {
        $settingsLink = '<a href="' . admin_url( 'admin.php?page=' . $this->pluginName ) . '">Settings</a>';
        $links[] = $settingsLink;
        return $links;
    }

    /**
     * Add an admin notice to be displayed on next admin page load.
     *
     * Stores notices in a transient so they survive redirects.
     *
     * @since 1.0.0
     * @param string $message HTML-allowed message.
     * @param string $type    'success'|'error'|'warning' (defaults to 'success').
     * @return void
     */
    private function addNotice($message, $type = 'success') {
        $notices = get_transient($this->noticeTransientKey);
        if (!is_array($notices)) $notices = [];
        $notices[] = [
            'message' => $message,
            'type'    => $type,
        ];

        set_transient($this->noticeTransientKey, $notices, 60);
    }

    /**
     * Print and clear queued admin notices.
     *
     * @since 1.0.0
     * @return void
     */
    public function printNotices() {
        $notices = get_transient($this->noticeTransientKey);
        if (!is_array($notices) || empty($notices)) {
            return;
        }

        foreach ($notices as $n) {
            $class = 'notice-success';
            if ($n['type'] === 'error') $class = 'notice-error';
            if ($n['type'] === 'warning') $class = 'notice-warning';

            printf('<div class="notice %s is-dismissible"><p>%s</p></div>', esc_attr($class), wp_kses_post($n['message']));
        }

        delete_transient($this->noticeTransientKey);
    }

    /**
     * AJAX handler for saving domain settings.
     *
     * @since 1.0.0
     * @return void Sends JSON response.
     */
    public function ajaxSaveDomainSettings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['code' => 'unauthorized', 'message' => 'Unauthorized'], 403);
        }

        $nonce = $_POST['_wpnonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'static_shield_save_domain_settings')) {
            wp_send_json_error(['code' => 'invalid_nonce', 'message' => 'Invalid nonce'], 403);
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
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['code' => 'unauthorized', 'message' => 'Unauthorized'], 403);
        }

        $nonce = $_POST['_wpnonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'static_shield_save_worker_settings')) {
            wp_send_json_error(['code' => 'invalid_nonce', 'message' => 'Invalid nonce'], 403);
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
     * Get list of DNS records via Worker (AJAX).
     *
     * @since 1.0.0
     * @return void
     */
    public function ajaxDnsList() {
        check_ajax_referer('static_shield_dns', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['code' => 'unauthorized', 'message' => 'Unauthorized access'], 403);
        }

        $client  = new StaticShieldWorkerClient();
        $records = $client->listDnsRecords();

        if (is_wp_error($records)) {
            wp_send_json_error(['code' => 'dns_list_failed', 'message' => $records->get_error_message()]);
        }

        wp_send_json_success(['records' => $records]);
    }

    /**
     * Add DNS record (AJAX).
     *
     * @since 1.0.0
     * @return void
     */
    public function ajaxDnsAdd() {
        check_ajax_referer('static_shield_dns', '_wpnonce');

        if ( ! current_user_can('manage_options') ) {
            wp_send_json_error(['code' => 'unauthorized', 'message' => 'Unauthorized access'], 403);
        }

        $record = [
            'type'    => sanitize_text_field($_POST['type'] ?? ''),
            'name'    => sanitize_text_field($_POST['name'] ?? ''),
            'content' => sanitize_text_field($_POST['content'] ?? ''),
            'ttl'     => intval($_POST['ttl'] ?? 3600),
            'proxied' => isset($_POST['proxied']) ? (bool) $_POST['proxied'] : false,
        ];

        if (empty($record['type']) || empty($record['name']) || empty($record['content'])) {
            wp_send_json_error(['code' => 'invalid_input', 'message' => 'Record type, name, and content are required.'], 400);
        }

        $client = new StaticShieldWorkerClient();
        $result = $client->addDnsRecord($record);

        if (is_wp_error($result)) {
            wp_send_json_error(['code' => 'dns_add_failed', 'message' => $result->get_error_message()]);
        }

        wp_send_json_success(['record' => $result]);
    }

    /**
     * Delete DNS record (AJAX).
     *
     * @since 1.0.0
     * @return void
     */
    public function ajaxDnsDelete() {
        check_ajax_referer('static_shield_dns', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['code' => 'unauthorized', 'message' => 'Unauthorized access'], 403);
        }

        $id = sanitize_text_field($_POST['id'] ?? '');

        if (empty($id)) {
            wp_send_json_error(['code' => 'invalid_input', 'message' => 'Record ID is required.'], 400);
        }

        $client = new StaticShieldWorkerClient();
        $result = $client->deleteDnsRecord($id);

        if (is_wp_error($result)) {
            wp_send_json_error(['code' => 'dns_delete_failed', 'message' => $result->get_error_message()]);
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
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['code' => 'unauthorized', 'message' => 'Unauthorized'], 403);
        }

        $exporter = new StaticShieldExporter();
        $logs     = $exporter->getLog();

        wp_send_json_success([
            'logs' => $logs,
        ]);
    }
}
