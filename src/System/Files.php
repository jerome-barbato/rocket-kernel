<?php

namespace Rocket\System;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\IOException;
use Composer\Script\Event;
use Composer\Util\Filesystem;

class Files
{
    /**
     * @param Event $event
     */
    public function copy(Event $event)
    {
        $files = $this->get($event, 'copy-file');

        $finder = new Finder;
        $fs  = new Filesystem();
        $sfs = new \Symfony\Component\Filesystem\Filesystem();
        $io = $event->getIO();

        foreach ($event->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {

            if (isset($files[$package->getName()])) {

                $packageDir = $event->getComposer()->getInstallationManager()->getInstallPath($package);

                $filesDefinitions = $files[$package->getName()];

                foreach ($filesDefinitions as $from => $to) {

                    if ($fs->isAbsolutePath($from)) {

                        throw new \InvalidArgumentException(
                            "Invalid target path '$from' for package'{$package->getName()}'."
                            . ' It must be relative.'
                        );
                    }

                    if ($fs->isAbsolutePath($to)) {

                        throw new \InvalidArgumentException(
                            "Invalid link path '$to' for package'{$package->getName()}'."
                            . ' It must be relative.'
                        );
                    }

                    $from = $packageDir . DIRECTORY_SEPARATOR . $from;
                    $to = getcwd() . DIRECTORY_SEPARATOR . $to;

                    $fs->ensureDirectoryExists(dirname($to));

                    if (is_dir($from)) {

                        $finder->files()->in($from);

                        foreach ($finder as $file) {

                            $dest = sprintf('%s/%s', $to, $file->getRelativePathname());

                            try {

                                if( file_exists($dest) )
                                    $fs->unlink($dest);

                                $sfs->copy($file, $dest);

                            } catch (IOException $e) {

                                throw new \InvalidArgumentException(sprintf('<error>Could not copy %s</error>', $file->getBaseName()));
                            }
                        }
                    } else {

                        try {

                            if( file_exists($to) )
                                $fs->unlink($to);

                            $sfs->copy($from, $to);

                            $io->write(sprintf('  Copying <comment>%s</comment> to <comment>%s</comment>.', str_replace(getcwd(), '', $from), str_replace(getcwd(), '', $to)));

                        } catch (IOException $e) {

                            throw new \InvalidArgumentException(sprintf('<error>Could not copy %s</error>', $from));
                        }
                    }
                }
            }
        }
    }


    /**
     * @param Event $event
     */
    public function remove(Event $event)
    {
        $files = $this->get($event, 'remove-file');

        $fs  = new Filesystem();
        $sfs = new \Symfony\Component\Filesystem\Filesystem();
        $io = $event->getIO();

        foreach ($event->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {

            if (isset($files[$package->getName()])) {

                $filesDefinitions = $files[$package->getName()];

                foreach ($filesDefinitions as $file) {

                    if ($fs->isAbsolutePath($file)) {

                        throw new \InvalidArgumentException(
                            "Invalid target path '$file' for package'{$package->getName()}'."
                            . ' It must be relative.'
                        );
                    }

                    $file = getcwd() . DIRECTORY_SEPARATOR . $file;

                    try {

                        if( is_dir($file) ){

                            $fs->removeDirectory($file);
                            $io->write(sprintf('  Removing directory <comment>%s</comment>.', str_replace(getcwd(), '', $file)));
                        }
                        elseif( file_exists($file) ){

                            $fs->unlink($file);
                            $io->write(sprintf('  Removing file <comment>%s</comment>.', str_replace(getcwd(), '', $file)));
                        }


                    } catch (IOException $e) {

                        throw new \InvalidArgumentException(sprintf('<error>Could not remove %s</error>', $file));
                    }
                }
            }
        }
    }


    /**
     * @param Event $event
     */
    public function create(Event $event)
    {
        $files = $this->get($event, 'create-folder');

        $fs  = new Filesystem();
        $sfs = new \Symfony\Component\Filesystem\Filesystem();
        $io = $event->getIO();

        foreach ($event->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {

            if (isset($files[$package->getName()])) {

                $filesDefinitions = $files[$package->getName()];

                foreach ($filesDefinitions as $file => $permissions) {

                    if ($fs->isAbsolutePath($file)) {

                        throw new \InvalidArgumentException(
                            "Invalid target path '$file' for package'{$package->getName()}'."
                            . ' It must be relative.'
                        );
                    }

                    $file = getcwd() . DIRECTORY_SEPARATOR . $file;

                    try {

                        if( !is_dir($file) && !file_exists($file)){

                            $permissions = intval($permissions, 8);
                            mkdir($file, $permissions);
                            $io->write(sprintf('  Creating directory <comment>%s</comment>.', str_replace(getcwd(), '', $file)));

                        }


                    } catch (IOException $e) {
                        throw new \InvalidArgumentException(sprintf('<error>Could not create %s</error>', $e));
                    }
                }
            }
        }
    }

    protected function get(Event $event, $id)
    {
        $options = $event->getComposer()->getPackage()->getExtra();

        $symlinks = array();

        if (isset($options[$id]) && is_array($options[$id])) {
            $symlinks = $options[$id];
        }

        return $symlinks;
    }
}