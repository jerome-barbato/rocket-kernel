<?php

/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Kernel;

trait ApplicationKernel {

    abstract protected function registerRoutes();
    abstract protected function registerServicesProvider();

    protected $paths, $routes;


    /**
     * Define generic path for project.
     * @param null $custom_paths
     */
    public function definePaths($custom_paths = null){

        if (is_null($custom_paths)) {

            $this->paths = [

                'config' => BASE_URI . '/config',
                'views'  => [ BASE_URI . '/web/views', __DIR__.'/../web/views' ],
                'twig'   => BASE_URI . '/vendor/Twig/lib',
                'cache'  => BASE_URI . '/var/cache',
                'export' => BASE_URI . '/var/export'
            ];
        } else {
            $this->paths = $custom_paths;
        }
    }

    /**
     * YML Config loader
     * Will automatically load global and local config
     * @param array $added_configs given values will be added after global and before local configuration files.
     */
    protected function loadConfig($added_configs = []) {

        $configs = ['global'];
        array_push($configs, $added_configs);
        array_push($configs, 'local');

        $data = [];

        foreach ($configs as $config) {

            $file = $this->paths['config'] . '/' . $config . '.yml';

            if (file_exists($file))
                $data = array_merge($data, \Spyc::YAMLLoad($file));
        }

        $this['config'] = new Data($data);

        if( $this['config']->get('environment') == "production" )
            $this['config']->set('debug', false);
    }

    protected function addTwigGlobal() {

        $this['twig']->addGlobal('project', $this['config']->get('project', 'Rocket'));
        $this['twig']->addGlobal('debug', $this['config']->get('debug.javascript', 0));

        // Wordpress compatibility
        $this['twig']->addGlobal('head', '');
        $this['twig']->addGlobal('footer', '');
        $this['twig']->addGlobal('body_class', '');

        $this['twig']->addGlobal('environment', $this['config']->get('environment', 'production'));
        $this['twig']->addGlobal('base_url', BASE_PATH);
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

}