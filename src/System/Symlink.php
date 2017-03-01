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
class Symlink {



    protected function get(Event $event)
    {
        $options = $event->getComposer()->getPackage()->getExtra();

        $symlinks = [];

        if ( isset( $options['symlinks'] ) && is_array( $options['symlinks'] ) ) {
            $symlinks = $options['symlinks'];
        }

        return $symlinks;
    }
}
