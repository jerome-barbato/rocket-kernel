<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer\Command;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class KernelCommandProvider implements CommandProviderCapability
{
    public function getCommands()
    {
        return [new SyncCommand(), new DatabaseCommand(), new StyleguideCommand(), new CleanupCommand()];
    }
}
