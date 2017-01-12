<?php

/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Application;

use Dflydev\DotAccessData\Data as DotAccessData;
use Twig_Environment;

trait ApplicationTrait {

    abstract protected function registerRoutes();
    abstract protected function registerServicesProvider();

    /** @var $config DotAccessData */
    protected $paths, $config;


    /**
     * Define generic path for project.
     * @return array
     */
    public function getPaths(){

        $this->paths = [
            'config' => BASE_URI . '/config',
            'views'  => [ BASE_URI . '/web/views', __DIR__.'/../../web/views' ],
            'twig'   => BASE_URI . '/vendor/Twig/lib'
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
     * Define Twig global environment variables
     * @param $twig Twig_Environment
     * @return mixed
     */
    protected function addTwigGlobal($twig) {

        $twig->addGlobal('project', $this->config->get('project', 'Rocket'));
        $twig->addGlobal('debug', $this->config->get('debug.javascript', 0));
        $twig->addGlobal('options', $this->config->get('options'));

        // Wordpress compatibility
        $twig->addGlobal('head', '');
        $twig->addGlobal('footer', '');
        $twig->addGlobal('body_class', '');

        $twig->addGlobal('environment', $this->config->get('environment', 'production'));
        $twig->addGlobal('base_url', BASE_PATH);

        return $twig;
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
     * Rely on framework
     */
    public static function run(){
        new \Customer\Application();
    }
}