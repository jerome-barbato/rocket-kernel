<?php

/*
 * Route middleware to easily implement multi-langue
 * todo: check to find a better way...
 */
namespace Rocket\Helper;

use Curl\Curl;


class DataRetrieverHelper {

    private $config, $error;

    public function __construct($config)
    {
        $this->config = $config;
        $this->error  = false;
    }


    /**
     * Download file to path
     */
    public function download($remote_file, $local_file)
    {
        if ( file_exists( $local_file ) ) {
            return true;
        }

        if ( $ressource_binary = @file_get_contents( $remote_file ) ) {

            // we create recursive directories folder
            $ressource_dir = dirname( $local_file );

            if ( !is_dir( $ressource_dir ) ) {

                if ( !@mkdir( $ressource_dir, 0755, true ) ) {

                    $this->error = "can't create folder : " . $ressource_dir;

                    return false;
                }
            }

            if ( @file_put_contents( $local_file, $ressource_binary ) ) {

                return true;
            }
            else {

                $this->error = "can't save remote file :" . $remote_file;

                return false;
            }
        }
        else {

            $this->error = "can't get remote file :" . $remote_file;
        }

        return false;
    }


    /**
     * Load Local JSON Data
     */
    public function getLocal($file, $offset = false, $process = false)
    {

        $file = BASE_URI . $this->config->get( 'data.local.path', '/app/resources/dico' ) . $file;

        if ( file_exists( $file ) ) {

            if ( $content = @file_get_contents( $file ) ) {

                $content = $this->replace( $this->config->get( 'data.local.replace', [] ), $content );

                /** @var callable $process */
                if ( $process and is_callable( $process ) ) {
                    $content = $process( $content );
                }

                $content = @json_decode( $content, true );

                if ( json_last_error() != JSON_ERROR_NONE ) {
                    return [];
                }

                if ( $content and !is_null( $content ) ) {

                    //Wordpress compatibility

                    if ( isset( $content['head'] ) and is_array( $content['head'] ) ) {
                        $content['head'] = implode( ' ', $content['head'] );
                    }

                    if ( isset( $content['footer'] ) and is_array( $content['footer'] ) ) {
                        $content['footer'] = implode( ' ', $content['footer'] );
                    }

                    return $this->offset( $content, $offset );
                }
            }
        }

        return [];
    }

    private function replace($replace, $content)
    {

        foreach ( $replace as $find => $replacement ) {

            if ( $replacement == 'BASE_PATH' ) {
                $replacement = BASE_PATH;
            }

            $content = str_replace( str_replace( '"', '', json_encode( $find ) ), str_replace( '"', '', json_encode( $replacement ) ), $content );
        }

        return $content;
    }

    private function offset($data, $offset = false)
    {

        if ( $offset ) {

            return isset( $data[$offset] ) ? $data[$offset] : [];
        }
        else {

            return $data;
        }
    }

    /**
     * Load Remote JSON Data
     *
     * @param      $file
     * @param bool $offset
     * @param bool $process
     * @return array
     */
    public function getRemote($file, $offset = false, $process = false)
    {
        if ( $this->config->get( 'data.remote' ) ) {

            $file = $this->config->get( 'data.remote.path' ) . $file;

            $curl = new Curl();
            $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
            $curl->setOpt(CURLOPT_MAXREDIRS, 5);
            $curl->setOpt(CURLOPT_HEADER, false);

            $curl->get( $file );

            // Check if url is correct, if error, returns a 404
            if ( !$curl->error ) {

                $content = $this->replace( $this->config->get( 'data.remote.replace', [] ), $curl->response );

                /** @var callable $process */
                if ( $process and is_callable( $process ) ) {
                    $content = $process( $content );
                }

                $content = @json_decode( $content, true );

                $curl->close();

                if ( json_last_error() != JSON_ERROR_NONE ) {
                    return [];
                }

                return $this->offset( $content, $offset );
            }
        }

        return [];
    }
}
