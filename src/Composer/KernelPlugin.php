<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Class KernelPlugin
 *
 * @package Rocket\Composer
 */
class KernelPlugin implements PluginInterface
{

    public function activate(Composer $composer, IOInterface $io)
    {

    }

    public function getCapabilities()
    {
        return [
            'Composer\Plugin\Capability\CommandProvider' => 'Rocket\Composer\Command\KernelCommandProvider'
        ];
    }
}
