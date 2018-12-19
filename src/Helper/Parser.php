<?php

namespace Rocket\Helper;

use Dflydev\DotAccessData\Data as DotAccessData;

/**
 * Class Parser
 *
 * Parser
 *
 * @package Rocket\Parser
 */
class Parser
{
    private $data, $paths, $io;

    public function __construct($io)
    {
        include_once 'vendor/autoload.php';

        $this->io    = $io;
        $this->data  = [];
        $this->paths = [
            'builder' => 'app/config/builder.yml',
            'web' =>  __DIR__.'/../../web'
        ];

        if( file_exists($this->paths['builder']) )
        {
            $builder = \Spyc::YAMLLoad($this->paths['builder']);
            $config = new DotAccessData($builder);

            $this->paths['asset'] = trim($config->get('paths.asset', '/src/FrontBundle/Resources/private'), '/');
            $this->paths['public'] = trim($config->get('paths.public', '/src/FrontBundle/Resources/public'), '/');
        }
        else
        {
            $this->io->writeError('<warning>No builder.yml file found</warning>');
        }
    }


    public function run()
    {
        $this->data['scss'] = $this->parseSCSS();

        ob_start();
        include $this->paths['web'].'/styleguide.php';
        $res = ob_get_contents(); // get the contents of the output buffer
        ob_end_clean();

        file_put_contents('web/styleguide.html', $res);
        $this->io->write('<info>styleguide.html generated</info>');
    }


    private function parseSCSS()
    {
        $data = [];

        $public_path = $this->paths['public'].'/css';

        $variables = $this->find([$this->paths['asset'].'/config/env.scss', $this->paths['asset'].'/config/var.scss'], '/\$(.*?)\s*?:\s*?(.*?)\s*?;/sm');

        foreach($variables as $name=>$value)
        {
            // Colors
            if( strpos($value,'#') !== false or strpos($value,'rgb') !== false )
                $data['colors'][$name] = $value;

            // Fonts
            if( $name == 'fonts' or $name == 'font-families' )
            {
                $value = explode(',', $value);
                foreach($value as $font)
                {
                    $font = explode(' ', trim(preg_replace('/\s+/', ' ',$font)));
                    if( strtolower($font[0]) != 'icons' )
                        $data['fonts'][$font[0]][] = ['variant'=>str_replace('Italic','',$font[1]), 'weight'=>$font[2], 'style'=>$font[3], 'stretch'=>$font[4]];
                }
            }

            // Icons
            if( $name == 'icons' )
            {
                $value = explode(',', $value);
                foreach($value as $icon)
                {
                    $icon = explode(' ', trim(preg_replace('/\s+/', ' ',$icon)));
                    $data['icons'][] = $icon[0];
                }
            }

            // Breakpoints
            if( strpos($name,'screen-') !== false and strpos($name,'-height') === false )
                $data['breakpoints'][str_replace('screen-', '', $name)] = $value;
        }

        $data['text'] = $this->find([$public_path.'/screen.css'], '/.text--([a-zA-Z0-9-_]*)/');
        $data['button'] = $this->find([$public_path.'/screen.css'], '/.button--([a-zA-Z0-9-_]*)/');

        return $data;
    }


    private function find($files, $pattern)
    {
        $raw_data = [[],[]];
        $data = [];

        foreach ($files as $file)
        {
            if( file_exists($file) )
            {
                $content = file_get_contents($file);
                preg_match_all($pattern, $content, $_data);

                if( count($_data) >= 3 )
                {
                    $raw_data[0] = array_merge($raw_data[0],$_data[1]);
                    $raw_data[1] = array_merge($raw_data[1],$_data[2]);
                }
                elseif( count($_data) >= 2 )
                {
                    $raw_data[0] = array_merge($raw_data[0],$_data[1]);
                }
            }
            else
            {
                $this->io->writeError('<warning>'.$file.' not found</warning>');
            }

            if( !empty($raw_data[1]) )
            {
                $i = 0;
                foreach ($raw_data[0] as $key)
                {
                    $data[$key] = $raw_data[1][$i];
                    $i++;
                }
            }
            else
            {
                $data = array_values(array_unique($raw_data[0]));
            }
        }

        return $data;
    }
}
