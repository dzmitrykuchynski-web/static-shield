<?php
namespace StaticShield;

use StaticShield\Admin\StaticShieldAdmin;

/**
 * Core plugin class.
 *
 * Defines internationalization, admin-specific hooks, and public-facing hooks.
 * Keeps unique identifier and version.
 *
 * @link       https://www.example.com/
 * @since      1.0.0
 *
 * @package    Static_Shield
 * @subpackage Static_Shield/includes
 */
class StaticShield {

    /**
     * Loader to maintain and register all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      StaticShieldLoader    $loader
     */
    protected $loader;

    /**
     * Unique identifier of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string
     */
    protected $pluginName;

    /**
     * Current plugin version.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string
     */
    protected $version;

    /**
     * Initialize core functionality.
     *
     * Sets plugin name, version, loads dependencies, locale,
     * admin hooks and public hooks.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = defined( 'STATIC_SHIELD_VERSION' ) ? STATIC_SHIELD_VERSION : '1.0.0';
        $this->pluginName = 'static-shield';

        $this->loadDependencies();
        $this->setLocale();
        $this->defineAdminHooks();
    }

    /**
     * Load plugin dependencies.
     *
     * Includes and initializes:
     * - Loader: orchestrates hooks.
     * - I18n: internationalization.
     * - Admin: admin area hooks.
     * - Public: public-facing hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function loadDependencies() {
        $this->loader = new StaticShieldLoader();
    }

    /**
     * Set locale for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function setLocale() {
        $pluginI18n = new StaticShieldI18n();
        $this->loader->addAction( 'plugins_loaded', $pluginI18n, 'loadPluginTextdomain' );
    }

    /**
     * Register admin area hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function defineAdminHooks() {
        $pluginAdmin = new StaticShieldAdmin( $this->getPluginName(), $this->getVersion() );

        $this->loader->addAction( 'admin_enqueue_scripts', $pluginAdmin, 'enqueueStyles' );
        $this->loader->addAction( 'admin_enqueue_scripts', $pluginAdmin, 'enqueueScripts' );
        $this->loader->addAction( 'admin_menu', $pluginAdmin, 'addAdminMenu' );
        $this->loader->addAction( 'admin_init', $pluginAdmin, 'registerSettings' );
        $this->loader->addAction( 'admin_init', $pluginAdmin, 'handleManualExport' );
        $this->loader->addAction( 'save_post', $pluginAdmin, 'handlePostUpdate', 10, 3 );
        $this->loader->addFilter( 'plugin_action_links_' . STATIC_SHIELD_BASENAME, $pluginAdmin, 'addPluginActionLinks' );

        $pluginAdmin->registerHooks($this->loader);
    }

    /**
     * Run loader to execute hooks.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Get plugin name.
     *
     * @since     1.0.0
     * @return    string
     */
    public function getPluginName() {
        return $this->pluginName;
    }

    /**
     * Get loader instance.
     *
     * @since     1.0.0
     * @return    Loader
     */
    public function getLoader() {
        return $this->loader;
    }

    /**
     * Get plugin version.
     *
     * @since     1.0.0
     * @return    string
     */
    public function getVersion() {
        return $this->version;
    }
}
