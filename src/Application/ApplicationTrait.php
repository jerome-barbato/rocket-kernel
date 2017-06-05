<?php

/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Application;

use Dflydev\DotAccessData\Data as DotAccessData;
use Rocket\Helper\DataRetriever;

/**
 * Class ApplicationTrait
 *
 * @package Rocket\Application
 */
trait ApplicationTrait {

    /** @var $config DotAccessData */
    public $paths, $config;

    /**
     * Start Customer Application to rely framework.
     */
    public static function load()
    {
        new \Customer\Application();
    }

    /**
     * Define generic path for project.
     *
     * @return array
     */
    public function getPaths()
    {

        $this->paths = [
            'config' => BASE_URI . '/app/config',
            'views'  => BASE_URI . '/app/views',
            'resources' => BASE_URI . '/app/resources'
        ];

        return $this->paths;
    }

    /**
     * Return asset url like in TWIG
     */
    public function asset_url($file)
    {
        return BASE_PATH . '/public' . $file;
    }

    /**
     * Return upload url like in TWIG
     */
    public function upload_url($file)
    {
        return BASE_PATH . '/upload' . $file;
    }

    abstract protected function registerRoutes();

    protected function registerServicesProvider() { }

    /**
     * YML Config loader
     * Will automatically load global and local config
     *
     * @param array $additional_yml
     * @return DotAccessData|mixed
     * @internal param array $added_configs given values will be added after global and before local configuration files.
     */
    protected function getConfig($additional_yml = [])
    {

        $yml_names   = ['global'];
        $yml_names[] = $additional_yml;
        $yml_names[] = 'local';

        $data = [];
        foreach ( $yml_names as $yml_name ) {

            $file = $this->paths['config'] . '/' . $yml_name . '.yml';

            if ( file_exists( $file ) ) {
                $data = array_merge( $data, \Spyc::YAMLLoad( $file ) );
            }
        }

        $config = new DotAccessData( $data );

        if ( $config->get( 'environment', 'production' ) == "production" ) {
            $config->set( 'debug', false );
        }

        $this->config = $config;

        return $this->config;
    }

	protected function getPageStatus( $path )
	{
		$file = $this->paths['resources'] . '/status.yml';

		if ( file_exists( $file ) )
		{
			$data  = new DotAccessData( \Spyc::YAMLLoad( $file ));
			$route = str_replace( '/', '.', trim( str_replace( BASE_PATH, '', $path ), '/'));

			if( empty($route) )
				$route = 'index';

			return $data->get($route);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load Local JSON Data
	 * @param $file
	 * @param bool $offset
	 * @param bool $process
	 * @return array
	 */
	protected function getLocalData($file, $offset = false, $process = false)
	{
		$data = new DataRetriever( $this->config );
		return $data->getLocal( $file, $offset, $process );
	}
}
