<?php

/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Application;

use Dflydev\DotAccessData\Data as DotAccessData;
use Twig_Environment;

/**
 * Class ApplicationTrait
 *
 * @package Rocket\Application
 */
trait ApplicationTrait {

    abstract protected function registerRoutes();
    protected function registerServicesProvider(){}

    /** @var $config DotAccessData */
    public $paths, $config;


    /**
     * Define generic path for project.
     * @return array
     */
    public function getPaths(){

        $this->paths = [
            'config' => BASE_URI . '/app/config',
            'views'  => BASE_URI . '/app/views'
        ];

        return $this->paths;
    }


    /**
     * YML Config loader
     * Will automatically load global and local config
     * @param array $additional_yml
     * @return DotAccessData|mixed
     * @internal param array $added_configs given values will be added after global and before local configuration files.
     */
    protected function getConfig($additional_yml = []) {

        $yml_names   = ['global'];
        $yml_names[] = $additional_yml;
        $yml_names[] = 'local';

        $data = [];
        foreach ($yml_names as $yml_name) {

            $file = $this->paths['config'] . '/' . $yml_name . '.yml';

            if (file_exists($file))
                $data = array_merge($data, \Spyc::YAMLLoad($file));
        }

        $config = new DotAccessData($data);

        if( $config->get('environment') == "production" )
            $config->set('debug', false);

        $this->config = $config;

        return $this->config;
    }


    /**
     * Return asset url like in TWIG
     */
    public function asset_url($file)
    {
        return BASE_PATH.'/public'.$file;
    }


    /**
     * Return upload url like in TWIG
     */
    public function upload_url($file)
    {
        return BASE_PATH.'/upload'.$file;
    }

    /**
     * Start Customer Application to rely framework.
     */
    public static function load(){
        new \Customer\Application();
    }
}
