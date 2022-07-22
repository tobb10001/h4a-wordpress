<?php

declare(strict_types=1);

namespace Tobb10001\H4aWordpress;

use Tobb10001\H4aIntegration\Updater;
use Tobb10001\H4aWordpress\Persistence\WpdbAdapter;
use Tobb10001\H4aWordpress\Util\WpNoticeManager;

class Plugin
{
    private string $mainfile;
    private WpdbAdapter $wpdbAdapter;
    private WpNoticeManager $noticeManager;
    private Settings $settings;
    private Updater $updater;

    public const MANAGE_CAPABILITY = 'manage_h4ac';
    public const UPDATE_HOOK = 'h4ac_update';

    /**
     * @param string $mainfile The Plugin file which contains the plugin header.
     */
    public function __construct(string $mainfile)
    {
        $this->mainfile = $mainfile;
        $this->wpdbAdapter = new WpdbAdapter();
        $this->noticeManager = new WpNoticeManager();
        $this->settings = new Settings(
            self::MANAGE_CAPABILITY,
            $this->wpdbAdapter,
            $this->noticeManager,
            $this->mainfile,
        );
        $this->updater = new Updater($this->wpdbAdapter);
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

        // cronjob to update data
        add_filter('cron_schedules', function ($schedules) {
            $schedules['five_minutes'] = [
                'interval' => 60 * 5,
                'display' => 'Every five minutes',
            ];
            return $schedules;
        });
        add_action(self::UPDATE_HOOK, [$this->updater, 'update']);
        if (!wp_next_scheduled(self::UPDATE_HOOK)) {
            wp_schedule_event(time(), 'five_minutes', self::UPDATE_HOOK);
        }
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
