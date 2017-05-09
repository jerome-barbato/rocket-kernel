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
            'views'  => BASE_URI . '/app/views'
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


	/**
	 * Load Remote JSON Data
	 * @param $file
	 * @param bool $offset
	 * @param bool $process
	 * @return array
	 */
	protected function getRemoteData($file, $offset = false, $process = false)
	{
		$retriever = new DataRetriever( $this->config );
		return $retriever->getRemote( $file, $offset, $process );
	}

	/**
	 * Download file
	 * @param $remote_file
	 * @param $local_file
	 * @return bool
	 */
	protected function downloadFile($remote_file, $local_file)
	{
		$retriever = new DataRetriever( $this->config );
		return $retriever->download( $remote_file, $local_file );
	}

	/**
	 * @param $path
	 * @param string $extensions
	 * @return array
	 */
	public function listFiles($path, $extensions="*.*")
	{
		$path  = rtrim($path, '/').'/';
		$paths = glob(BASE_URI.$path . "*", GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
		$files = glob(BASE_URI.$path . $extensions);

		foreach ($paths as $key => $path)
		{
			$directory = explode("/", $path);
			unset($directory[count($directory) - 1]);
			$directories[end($directory)] = $this->listFiles(str_replace(BASE_URI, '', $path), $extensions);

			foreach ($files as $file)
			{
				if (strpos(substr($file, 2), ".") !== false){

					$filename = preg_replace('/\\.[^.\\s]{3,5}/', '', substr($file, (strrpos($file, "/") + 1)));

					if( !in_array($filename, $directories) )
						$directories[] = $filename;
				}
			}
		}

		if (isset($directories))
		{
			return $directories;
		}
		else
		{
			$files2return = [];
			foreach ($files as $key => $file)
				$files2return[] = preg_replace('/\\.[^.\\s]{3,5}/', '', substr($file, (strrpos($file, "/") + 1)));
			return $files2return;
		}
	}
}
