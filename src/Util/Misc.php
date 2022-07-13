<?php

declare(strict_types=1);

namespace Tobb10001\H4aWordpress\Util;

/** http://phpsadness.com/sad/41 */
abstract class Misc
{
    /**
     * Preform a Post/Redirect/Get-Redirect to the current page.
     *
     */
    public static function postRedirectGet()
    {
        wp_redirect(admin_url("admin.php?page=" . $_GET["page"]), 303);
        exit;
    }
}
