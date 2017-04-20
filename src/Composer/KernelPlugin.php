<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Rocket\System\FileManager;

/**
 * Class KernelPlugin
 *
 * @package Rocket\Composer
 */
class KernelPlugin implements PluginInterface, Capable
{
    protected $composer;
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io       = $io;

    }

    public function getCapabilities()
    {
        return [
            'Composer\Plugin\Capability\CommandProvider' => 'Rocket\Composer\Command\KernelCommandProvider'
        ];
    }

}
