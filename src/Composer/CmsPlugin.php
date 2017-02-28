<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Class CmsPlugin
 *
 * @package Rocket\Composer
 */
class CmsPlugin implements PluginInterface
{
    /**
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $installer = new CmsInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }
}
