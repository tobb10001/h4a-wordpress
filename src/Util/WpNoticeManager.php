<?php

declare(strict_types=1);

namespace Tobb10001\H4aWordpress\Util;

class WpNoticeManager
{
    private ?array $notice = null;

    public const DAY_SECONDS = 24 * 60 * 60;
    public const COOKIE = 'h4ac_notice';
    public const SEVERITIES = [
        'warning',
        'error',
        'info',
        'success',
    ];

    /**
     * Register functionality to WordPress hooks.
     */
    public function init()
    {
        add_action('admin_init', [$this, 'checkCookie']);
        add_action('admin_notices', [$this, 'showNotice']);
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function addNotice(
        string $notice,
        string $severity = 'info',
        bool $dismissible = true,
    ) {
        $severity = in_array($severity, self::SEVERITIES) ? $severity : 'info';
        setcookie(
            self::COOKIE,
            json_encode([$notice, $severity, $dismissible]),
            time() + self::DAY_SECONDS,
        );
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function checkCookie(): void
    {
        if (!array_key_exists(self::COOKIE, $_COOKIE)) {
            return;
        }

        $this->notice = json_decode(stripslashes($_COOKIE[self::COOKIE]));
        setcookie(self::COOKIE, '', -1);
    }

    public function showNotice(): void
    {
        if (is_null($this->notice)) {
            return;
        }

        list($notice, $severity, $dismissible) = $this->notice;
        $this->notice = null;

        $dismissibleText = $dismissible ? 'is-dismissible' : ''; ?>
            <div class="notice notice-<?= $severity ?> <?= $dismissibleText ?>">
                <p><?= $notice ?></p>
            </div>
        <?php
    }
}
