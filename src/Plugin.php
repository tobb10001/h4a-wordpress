<?php

declare(strict_types=1);

namespace Tobb10001\H4aWordpress;

use Tobb10001\H4aIntegration\Models\Team;
use Tobb10001\H4aWordpress\Persistence\WpdbAdapter;
use function add_menu_page;

class Plugin
{
    private string $mainfile;
    private WpdbAdapter $wpdbAdapter;
    private ?array $notice = null;

    public const COOKIE_NOTICE = 'h4ac_notice';
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
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
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
        add_action('admin_init', function () {
            if (!array_key_exists(self::COOKIE_NOTICE, $_COOKIE)) {
                return;
            }

            $this->notice = json_decode(stripslashes($_COOKIE[self::COOKIE_NOTICE]));
            setcookie(self::COOKIE_NOTICE, '', -1);
        });
        add_action('admin_notices', [$this, 'showNotice']);
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
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
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
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function menupage(): void
    {
        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            wp_die('Fehlende Berechtigung.');
        }

        $teams = $this->wpdbAdapter->getTeams();

        require_once __DIR__ . '/html/menupage.php';
        print_menupage($teams, self::NONCE_EDIT_TEAMS, self::NONCE_FIELD_NAME);
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
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

        $severity = $notice = $dismissible = null;
        $availableActions[$_POST['action']]($severity, $notice, $dismissible);
        $this->postRedirectGet($severity, $notice, $dismissible);
    }

    public function showNotice(): void
    {
        if (is_null($this->notice)) {
            return;
        }

        list($severity, $notice, $dismissible) = $this->notice;

        $dismissibleText = $dismissible ? 'is-dismissible' : ''; ?>
            <div class="notice notice-<?= $severity ?> <?= $dismissibleText ?>">
                <p><?= $notice ?></p>
            </div>
        <?php
    }

    /**
     * Preform a Post/Redirect/Get-Redirect to the current page, that optionally
     * shows a notice on arrival.
     * @param ?string $severity Type of notice, one of ['warning', 'error', 'info', 'success']
     * @param ?string $notice Message to display to the user.
     * @param bool $dismissible Whether to make the notice dismissible or not.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function postRedirectGet(
        ?string $severity = null,
        ?string $notice = null,
        ?bool $dismissible = true,
    ) {
        if (!is_null($notice)) {
            $avaliableSeverities = ['warning', 'error', 'info', 'success'];
            $daySeconds = 24 * 60 * 60;
            $severity = in_array($severity, $avaliableSeverities) ? $severity : 'info';
            $dismissible = $dismissible ?? true;
            setcookie(self::COOKIE_NOTICE, json_encode([$severity, $notice, $dismissible]), time() + $daySeconds);
        }

        wp_redirect(admin_url("admin.php?page=" . $_GET["page"]), 303);
        exit;
    }

    /** region Post Actions */
    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.UnusedPrivateMethods)
     */
    private function saveTeam(&$severity, &$notice, &$dismissible)
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

        list($severity, $notice, $dismissible) = $success ?
            ['success', 'Team erfolgrecich gespeichert.', true] :
            [
                'error',
                'Fehler beim Speichern des Teams: '
                    . $this->wpdbAdapter->getLastError(),
                true
            ]
        ;
    }
    /** endregion */
}
