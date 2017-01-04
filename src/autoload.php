<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Kernel;

function kernel_autoload($class) {

    $class = str_replace(__NAMESPACE__ . '\\', '', $class);

    $ds = DIRECTORY_SEPARATOR;
    $model = __DIR__ . $ds . 'Model' . $ds;
    $trait = __DIR__ . $ds . 'Trait' . $ds;

    if (file_exists($model.$class.'.php')) {

        require_once $model.$class.'.php';
    } else if (file_exists($trait.$class.'.php')) {

        require_once $trait.$class.'.php';
    } else {
        return false;
    }
    return true;
}

spl_autoload_register(__NAMESPACE__ . '\kernel_autoload');