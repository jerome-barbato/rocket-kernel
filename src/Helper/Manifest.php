<?php

namespace Rocket\Helper;

class Manifest{

	private $scripts, $styles;

	public function __construct()
	{
		$this->scripts = [];
		$this->styles = [];

		if( file_exists( BASE_URI . '/web/manifest.json' ) ){

			$bundles = json_decode(file_get_contents(BASE_URI . '/web/manifest.json'), true);
			foreach ($bundles as $bundle )
			{
				if( isset($bundle['scripts']) )
					$this->scripts[] = str_replace('BASE_PATH', BASE_PATH, $bundle['scripts']);

				if( isset($bundle['styles']) )
					$this->styles[] = str_replace('BASE_PATH', BASE_PATH, $bundle['styles']);
			}
		}
	}

	public function getScripts(){

		return implode("\n", $this->scripts);
	}

	public function getStyles(){

		return implode("\n", $this->styles);
	}
}