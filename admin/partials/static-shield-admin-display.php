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

        <div class="nav-section">
            <h4 class="settings-headline">DNS Management</h4>
            <button type="button" class="components-button nav-tab" data-target="dns-settings">
                <span class="dashicons dashicons-networking"></span> DNS Records
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
                    <label>
                        Cloudflare Worker
                        <input type="text" name="static_shield_cf_worker"
                               value="<?php echo esc_attr(get_option('static_shield_cf_worker')); ?>">
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
        <!-- DNS Management Tab -->
        <div id="tab-dns-settings" class="tab-content" style="display:none;">
            <div class="card settings-card">
                <h3>DNS Records</h3>
                <table class="widefat" id="dns-records-table">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Content</th>
                        <th>TTL</th>
                        <th>Proxied</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <h4>Add Record</h4>
                <form id="dns-add-form" class="dns-add-form">
                    <!-- Custom Select -->
                    <div class="custom-select-wrapper">
                        <label for="dns-type">Record Type</label>
                        <div class="custom-select" tabindex="0">
                            <span class="selected">A</span>
                            <ul class="options" role="listbox">
                                <li role="option" data-value="A">A</li>
                                <li role="option" data-value="AAAA">AAAA</li>
                                <li role="option" data-value="CAA">CAA</li>
                                <li role="option" data-value="CERT">CERT</li>
                                <li role="option" data-value="CNAME">CNAME</li>
                                <li role="option" data-value="DNSKEY">DNSKEY</li>
                                <li role="option" data-value="DS">DS</li>
                                <li role="option" data-value="HTTPS">HTTPS</li>
                                <li role="option" data-value="LOC">LOC</li>
                                <li role="option" data-value="MX">MX</li>
                                <li role="option" data-value="NAPTR">NAPTR</li>
                                <li role="option" data-value="NS">NS</li>
                                <li role="option" data-value="PTR">PTR</li>
                                <li role="option" data-value="SMIMEA">SMIMEA</li>
                                <li role="option" data-value="SRV">SRV</li>
                                <li role="option" data-value="SSHFP">SSHFP</li>
                                <li role="option" data-value="SVCB">SVCB</li>
                                <li role="option" data-value="TLSA">TLSA</li>
                                <li role="option" data-value="TXT">TXT</li>
                                <li role="option" data-value="URI">URI</li>
                            </ul>
                        </div>
                        <input type="hidden" name="type" id="dns-type" value="A">
                    </div>

                    <input type="text" name="name" placeholder="Name">
                    <input type="text" name="content" placeholder="Content">
                    <input type="number" name="ttl" placeholder="TTL" value="3600">

                    <!-- Toggle for Proxied -->
                    <label class="toggle-switch">
                        <input type="checkbox" name="proxied" value="1">
                        <span class="slider"></span>
                        <span class="toggle-label">Proxied</span>
                    </label>

                    <button type="submit" class="button button-primary">Add Record</button>
                </form>
            </div>
        </div>
    </div>
</div>
