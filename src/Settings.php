<?php

declare(strict_types=1);

namespace Tobb10001\H4aWordpress;

use Tobb10001\H4aIntegration\Models\Team;
use Tobb10001\H4aWordpress\Persistence\WpdbAdapter;
use Tobb10001\H4aWordpress\Util\Misc;
use Tobb10001\H4aWordpress\Util\WpNoticeManager;

class Settings
{
    private string $capability;
    private WpNoticeManager $noticeManager;
    private string $pluginFile;
    private WpdbAdapter $wpdbAdapter;

    public const NONCE_FIELD_NAME = '_h4ac_nonce';
    public const NONCE_EDIT_TEAMS = 'h4ac_nonce_edit_teams';

    /**
     * @param string $capability The capability needed to modify settings.
     * @param WpdbAdapter $wpdbAdapter Adapter to communicate to the database.
     * @param WpNoticeManager $noticeManager Notice Manager to use.
     */
    public function __construct(
        string $capability,
        WpdbAdapter $wpdbAdapter,
        WpNoticeManager $noticeManager,
        string $pluginFile,
    ) {
        $this->capability = $capability;
        $this->wpdbAdapter = $wpdbAdapter;
        $this->noticeManager = $noticeManager;
        $this->pluginFile = $pluginFile;
    }

    /**
     * Hook functionality to WordPress.
     */
    public function init(): void
    {
        // display menupage
        add_action('admin_menu', function () {
            add_menu_page(
                'Handball4Aall Client',
                'H4A Client',
                $this->capability,
                'h4a-client',
                [$this, 'menupage']
            );
        });
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script(
                'h4ac-admin-script',
                plugin_dir_url($this->pluginFile) . 'src/static/admin.js',
                [],
                false,
                true,
            );
        });

        // saving settings
        add_action('admin_init', [$this, 'saveSettings']);
    }

    /**
     * Display the plugin's menupage.
     */
    public function menupage(): void
    {
        if (!current_user_can($this->capability)) {
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
            'h4ac-delete-team' => [$this, 'deleteTeam'],
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST'
            || !isset($_POST['action'])
            || !array_key_exists($_POST['action'], $availableActions)) {
            // no action is requested from this plugin
            return;
        }

        if (!current_user_can($this->capability)) {
            Misc::postRedirectGet('error', 'Fehlende Berechtigung.');
        }

        $availableActions[$_POST['action']]();
        Misc::postRedirectGet();
    }

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

    private function deleteTeam()
    {
        $tid = (int) sanitize_text_field($_POST['id']);

        $success = $this->wpdbAdapter->deleteTeam($tid);

        $success ?
            $this->noticeManager->addNotice(
                'Team erfolgreich gelöscht.',
                'success'
            ) :
            $this->noticeManager->addNotice(
                'Fehler beim löschen des Teams.',
                'error'
            );
    }
}
