<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin/partials
 */
?>

<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin/partials
 */
?>

<div class="sidebar">
    <h2>Static Shield</h2>

    <div>
        <h4>Tools</h4>
        <button>Activity Log</button>
    </div>

    <div>
        <h4>Settings</h4>
        <button>General</button>
        <button>Claudflare Settings</button>
    </div>
</div>

<div class="main">
    <div class="card">
        <h3>Activity Log</h3>
        <div class="terminal">
            <?php if (!empty($exportLog)): ?>
                <?php foreach ($exportLog as $line): ?>
                    <?php echo esc_html($line) . '<br>'; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No export logs yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3>Export Log</h3>
        <input type="text" placeholder="Search..." style="padding:5px; width:100%; margin-bottom:10px;">
        <table>
            <thead>
            <tr>
                <th>Code</th>
                <th>URL</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>200</td>
                <td><a href="#">/wp-content/themes/slot/dist/img/Algeria.png</a></td>
                <td>Added by Theme Assets Crawler</td>
            </tr>
            <tr>
                <td>200</td>
                <td><a href="#">/wp-includes/css/dist/edit-widgets/style.min.css</a></td>
                <td>Added by Includes Directory Crawler</td>
            </tr>
            <tr>
                <td>200</td>
                <td><a href="#">/</a></td>
                <td>Origin URL</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="card settings-card">
        <h3>Static Shield Settings</h3>
        <form method="post" action="">

        </form>

        <h4>Manual Export</h4>
        <form method="post">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($manualExportNonce); ?>">
            <input type="submit" name="static_shield_manual_export" value="Run Export Now">
        </form>
    </div>
</div>
