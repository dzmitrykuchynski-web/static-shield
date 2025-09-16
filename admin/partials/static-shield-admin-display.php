<?php
/**
 * Admin area view for the plugin
 *
 * @package    Static_Shield
 * @subpackage Static_Shield/admin/partials
 */
?>

<div class="static-shield-admin">
    <div class="sidebar">
        <div class="plugin-logo">
            <h2>Static Shield</h2>
        </div>
        <p class="version-number">Version: <b><?php echo esc_html(STATIC_SHIELD_VERSION); ?></b></p>

        <!-- Manual Export -->
        <div class="generate-buttons-container">
            <form method="post">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($manualExportNonce); ?>">
                <button id="run-export-btn" type="submit" name="static_shield_manual_export" class="components-button generate is-active-item">
                    <span class="dashicons dashicons-update"></span> Run Export Now
                </button>
            </form>
        </div>

        <!-- Navigation -->
        <div class="nav-section">
            <h4 class="settings-headline">Tools</h4>
            <button type="button" class="components-button nav-tab active" data-target="activity-log">
                <span class="dashicons dashicons-update"></span> Activity Log
            </button>
        </div>

        <div class="nav-section">
            <h4 class="settings-headline">Settings</h4>
            <button type="button" class="components-button nav-tab" data-target="cloudflare-settings">
                <span class="dashicons dashicons-cloud"></span> Cloudflare Settings
            </button>
        </div>
    </div>

    <div class="main">
        <!-- Activity Log Tab -->
        <div id="tab-activity-log" class="tab-content active">
            <div class="card">
                <h3>Activity Log</h3>
                <div class="terminal">
                    <?php if (!empty($exportLog)): ?>
                        <?php foreach ($exportLog as $line): ?>
                            <?php
                            $class = 'log-info';
                            if (strpos($line, '[error]') !== false) {
                                $class = 'log-error';
                            } elseif (strpos($line, '[warning]') !== false) {
                                $class = 'log-warning';
                            }
                            ?>
                            <div class="<?php echo esc_attr($class); ?>">
                                <?php echo esc_html($line); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No export logs yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cloudflare Settings Tab -->
        <div id="tab-cloudflare-settings" class="tab-content" style="display:none;">
            <div class="card settings-card">
                <h3>Cloudflare Settings</h3>
                <form id="static-shield-cf-form" method="post" action="">
                    <?php wp_nonce_field('static_shield_save_cf_settings'); ?>
                    <label>
                        API Token
                        <input type="text" name="static_shield_cf_api_key"
                               value="<?php echo esc_attr(get_option('static_shield_cf_api_key')); ?>">
                    </label>
                    <label>
                        Access Key ID
                        <input type="text" name="static_shield_cf_access_key_id"
                               value="<?php echo esc_attr(get_option('static_shield_cf_access_key_id')); ?>">
                    </label>
                    <label>
                        Secret Access Key
                        <input type="text" name="static_shield_cf_secret_access_key"
                               value="<?php echo esc_attr(get_option('static_shield_cf_secret_access_key')); ?>">
                    </label>
                    <label>
                        Account ID
                        <input type="text" name="static_shield_cf_account_id"
                               value="<?php echo esc_attr(get_option('static_shield_cf_account_id')); ?>">
                    </label>
                    <label>
                        Bucket Name
                        <input type="text" name="static_shield_cf_bucket"
                               value="<?php echo esc_attr(get_option('static_shield_cf_bucket')); ?>">
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="static_shield_use_cf" value="1"
                            <?php checked(1, get_option('static_shield_use_cf')); ?>>
                        <span class="slider"></span>
                        <span class="toggle-label">Enable Workers</span>
                    </label>
                    <input type="submit" value="Save Settings">
                </form>
            </div>
        </div>
    </div>
</div>
