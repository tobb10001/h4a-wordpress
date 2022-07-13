<?php

declare(strict_types=1);

namespace Tobb10001\H4aWordpress;

use Tobb10001\H4aWordpress\Persistence\WpdbAdapter;
use Tobb10001\H4aWordpress\Util\WpNoticeManager;

class Plugin
{
    private string $mainfile;
    private WpdbAdapter $wpdbAdapter;
    private WpNoticeManager $noticeManager;
    private Settings $settings;

    public const MANAGE_CAPABILITY = 'manage_h4ac';

    /**
     * @param string $mainfile The Plugin file which contains the plugin header.
     */
    public function __construct(string $mainfile)
    {
        $this->mainfile = $mainfile;
        $this->wpdbAdapter = new WpdbAdapter();
        $this->noticeManager = new WpNoticeManager();
        $this->settings = new Settings(self::MANAGE_CAPABILITY, $this->wpdbAdapter, $this->noticeManager);
    }

    /**
     * Initialize the plugin with the WordPress API.
     */
    public function run(): void
    {
        // activation, deactivation, uninstallation
        register_activation_hook($this->mainfile, [$this, 'activate']);
        register_deactivation_hook($this->mainfile, [$this, 'deactivate']);
        register_uninstall_hook($this->mainfile, ['Plugin', 'uninstall']);


        // displaying notices
        $this->noticeManager->init();
        $this->settings->init();
    }

    /** region Activation / Deactivation */
    /**
     * Plugin activation.
     */
    public function activate(): void
    {
        $this->wpdbAdapter->createTables();

        // add custom capability to manage the plugin internals
        $initialRoles = ['administrator', 'editor'];
        foreach ($initialRoles as $role) {
            get_role($role)->add_cap(self::MANAGE_CAPABILITY, true);
        }
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate(): void
    {
        // No duties so far.
    }

    /**
     * Plugin uninstallation.
     */
    public static function uninstall(): void
    {
        (new WpdbAdapter())->dropTables();

        // add custom capability to manage the plugin internals
        global $wp_roles;
        $allRoles = array_keys($wp_roles->get_names());
        foreach ($allRoles as $role) {
            get_role($role)->remove_cap(self::MANAGE_CAPABILITY);
        }
    }
    /** endregion */
}
