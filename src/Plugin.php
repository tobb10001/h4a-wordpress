<?php

declare(strict_types=1);

namespace Tobb10001\H4aWordpress;

use Tobb10001\H4aIntegration\Models\Team;
use Tobb10001\H4aWordpress\Persistence\WpdbAdapter;
use Tobb10001\H4aWordpress\Util\WpNoticeManager;

use function add_menu_page;

class Plugin
{
    private string $mainfile;
    private WpdbAdapter $wpdbAdapter;
    private WpNoticeManager $noticeManager;

    public const MANAGE_CAPABILITY = 'manage_h4ac';
    public const NONCE_FIELD_NAME = '_h4ac_nonce';
    public const NONCE_EDIT_TEAMS = 'h4ac_nonce_edit_teams';

    /**
     * @param string $mainfile The Plugin file which contains the plugin header.
     */
    public function __construct(string $mainfile)
    {
        $this->mainfile = $mainfile;
        $this->wpdbAdapter = new WpdbAdapter();
        $this->noticeManager = new WpNoticeManager();
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

        // menu page
        add_action('admin_menu', function () {
            add_menu_page(
                'Handball4Aall Client',
                'H4A Client',
                self::MANAGE_CAPABILITY,
                'h4a-client',
                [$this, 'menupage']
            );
        });

        // saving settings
        add_action('admin_init', [$this, 'saveSettings']);

        // displaying notices
        $this->noticeManager->init();
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

    /**
     * Display the plugin's menupage.
     */
    public function menupage(): void
    {
        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            wp_die('Fehlende Berechtigung.');
        }

        $teams = $this->wpdbAdapter->getTeams();

        require_once __DIR__ . '/templates/menupage.php';
        h4ac_print_menupage($teams, self::NONCE_EDIT_TEAMS, self::NONCE_FIELD_NAME);
    }

    /**
     * Determines if there is data sent from the menupage and if so, triggers
     * the appropriate action to handle the data (i.e. save it for most if not
     * all cases).
     */
    public function saveSettings(): void
    {
        $availableActions = [
            'h4ac-edit-team' => [$this, 'saveTeam'],
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST'
            || !isset($_POST['action'])
            || !array_key_exists($_POST['action'], $availableActions)) {
            // no action is requested from this plugin
            return;
        }

        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            $this->postRedirectGet('error', 'Fehlende Berechtigung.');
        }

        $availableActions[$_POST['action']]();
        $this->postRedirectGet();
    }

    /** region Post Actions */
    /**
     * Saves a team sent from the menupage to the database.
     * Creates a notice to communicate outcome to user.
     */
    private function saveTeam()
    {
        $tid = sanitize_text_field($_POST['id']);
        if ((int) $tid == -1) {
            $tid = null;
        }

        $team = new Team([
            'id' => $tid,
            'internalName' => sanitize_text_field($_POST['internalName']),
            'identificators' => sanitize_text_field($_POST['identificators']) ?: null,
            'leagueUrl' => sanitize_text_field($_POST['leagueUrl']) ?: null,
            'cupUrl' => sanitize_text_field($_POST['cupUrl']) ?: null,
        ]);

        $success = $this->wpdbAdapter->saveTeam($team);

        $success ?
            $this->noticeManager->addNotice(
                'Team erfolgreich gespeichert.',
                'success'
            ) :
            $this->noticeManager->addNotice(
                'Fehler beim Speichern des Teams: '
                    . $this->wpdbAdapter->getLastError(),
                'error',
            );
    }
    /** endregion */
}
