<?php

declare(strict_types=1);

namespace Tobb10001\H4aWordpress;

use Tobb10001\H4aWordpress\Persistence\WpdbAdapter;

class Plugin
{
    private string $mainfile;
    private WpdbAdapter $wpdbAdapter;

    /**
     * @param string $mainfile The Plugin file which contains the plugin header.
     */
    public function __construct(string $mainfile)
    {
        $this->mainfile = $mainfile;
        $this->wpdbAdapter = new WpdbAdapter();
    }

    public function run(): void
    {
        register_activation_hook($this->mainfile, [$this, 'activate']);
        register_deactivation_hook($this->mainfile, [$this, 'deactivate']);
        register_uninstall_hook($this->mainfile, ['Plugin', 'uninstall']);
    }

    public function activate(): void
    {
        $this->wpdbAdapter->createTables();
    }

    public function deactivate(): void
    {
    }

    public static function uninstall(): void
    {
        (new WpdbAdapter())->dropTables();
    }
}
