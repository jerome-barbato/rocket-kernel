<?php

/*
 * AJGL Composer Symlinker
 *
 * Copyright (C) Antonio J. García Lagar <aj@garcialagar.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rocket\System;

use Composer\Script\Event;
use Composer\Util\Filesystem;

/**
 * Script to symlink resources installed with composer.
 *
 * @author Antonio J. García Lagar <aj@garcialagar.es>
 */
class Symlink
{
    public  function create(Event $event)
    {
        $symlinks = $this->get($event);

        $fs = new Filesystem();

        foreach ($event->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {

            if (isset($symlinks[$package->getName()])) {

                $packageDir = $event->getComposer()->getInstallationManager()->getInstallPath($package);

                $symlinkDefinitions = $symlinks[$package->getName()];

                foreach ($symlinkDefinitions as $target => $link) {

                    if ($fs->isAbsolutePath($target)) {

                        throw new \InvalidArgumentException(
                            "Invalid symlink target path '$target' for package'{$package->getName()}'."
                            . ' It must be relative.'
                        );
                    }

                    if ($fs->isAbsolutePath($link)) {

                        throw new \InvalidArgumentException(
                            "Invalid symlink link path '$link' for package'{$package->getName()}'."
                            . ' It must be relative.'
                        );
                    }

                    $targetPath = $packageDir . DIRECTORY_SEPARATOR . $target;
                    $linkPath = getcwd() . DIRECTORY_SEPARATOR . $link;

                    if (!file_exists($targetPath)) {

                        throw new \RuntimeException(
                            "The target path '$targetPath' for package'{$package->getName()}' does not exist."
                        );
                    }

                    if( !file_exists($linkPath) ){

                        $event->getIO()->write(sprintf("  Symlinking <comment>%s</comment> to <comment>%s</comment>", str_replace(getcwd(), '', $targetPath), str_replace(getcwd(), '', $linkPath)));

                        $fs->ensureDirectoryExists(dirname($linkPath));
                        $fs->relativeSymlink($targetPath, $linkPath);

                    }
                }
            }
        }
    }

    protected  function get(Event $event)
    {
        $options = $event->getComposer()->getPackage()->getExtra();

        $symlinks = array();

        if (isset($options['symlinks']) && is_array($options['symlinks'])) {
            $symlinks = $options['symlinks'];
        }

        return $symlinks;
    }
}
