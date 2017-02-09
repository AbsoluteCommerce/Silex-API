<?php
namespace Absolute\SilexApi\Controller;

use Silex\Application;
use Absolute\SilexApi\Config\App;

class SwaggerController
{
    /** @var array */
    private $headers = [
        'Access-Control-Allow-Origin:'    => '*',
        'Access-Control-Allow-Methods:'   => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        'Access-Control-Allow-Headers:'   => 'Accept, Content-Type',
        'Access-Control-Request-Headers:' => 'Accept, Content-Type',
    ];
    
    /** @var Application */
    private $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    /**
     * @return string
     */
    public function execute()
    {
        $this->_sendHeaders();
        return $this->_getSwagger();
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    private function _getSwagger()
    {
        $replace = [
            '{SCHEME}'   => $this->app[App::SCHEME],
            '{HOSTNAME}' => $this->app[App::HOSTNAME],
        ];
        $swagger = file_get_contents(GENERATION_DIR . 'swagger.json');
        $swagger = str_replace(array_keys($replace), array_values($replace), $swagger);
        
        return $swagger;
    }

    /**
     * 
     */
    private function _sendHeaders()
    {
        foreach ($this->getHeaders() as $_header => $_value) {
            header("{$_header}: {$_value}");
        }
    }
}
