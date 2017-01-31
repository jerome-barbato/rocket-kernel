<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */


namespace Rocket\Application;

/**
 * Class SingletonTrait
 *
 * @package Rocket\Application
 */
trait SingletonTrait {

    /**
     * Instance
     *
     * @var Singleton
     */
    protected static $_instance;


    /**
     * Constructor
     */
    protected function __construct() {}

    /**
     * Get instance
     *
     * @return Singleton
     */
    public static function getInstance() {

        $numargs = func_num_args();

        if (null === static::$_instance) {

            if( $numargs == 1 )
                static::$_instance = new static(func_get_arg(0));
            elseif( $numargs == 2 )
                static::$_instance = new static(func_get_arg(1));
            else
                static::$_instance = new static();
        }

        return static::$_instance;
    }
}
