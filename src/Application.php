<?php
namespace Absolute\SilexApi;

use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Response;
use Absolute\SilexApi\Generation\Routes\RouteRegistrar;
use Absolute\SilexApi\Generation\Docs\Swagger;

class Application
{
    const CONFIG_DEBUG    = 'debug';
    const CONFIG_SCHEME   = 'scheme';
    const CONFIG_HOSTNAME = 'hostname';

    /** @var array */
    private $headers = [
        'Access-Control-Allow-Origin:'    => '*',
        'Access-Control-Allow-Methods:'   => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        'Access-Control-Allow-Headers:'   => 'Accept, Content-Type',
        'Access-Control-Request-Headers:' => 'Accept, Content-Type',
    ];
    
    /** @var SilexApplication */
    private $app;
    
    /** @var array */
    private $config = [
        self::CONFIG_DEBUG    => false,
        self::CONFIG_SCHEME   => 'http',
        self::CONFIG_HOSTNAME => 'localhost',
    ];

    /**
     * @param SilexApplication $app
     * @param array $config
     */
    public function __construct(
        SilexApplication $app,
        array $config = []
    ) {
        $this->app = $app;
        
        $this->config = array_merge(
            $this->config,
            $config
        );
        
        // set Silex debug mode
        $this->app['debug'] = (bool)$this->getConfig(self::CONFIG_DEBUG);
    }

    /**
     * 
     */
    public function api()
    {
        // return 500 by default, to be proven otherwise in the application
        http_response_code(Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->app->error(function (\Exception $e) {
            $message = !$this->app['debug']
                ? (string)$e
                : Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR];
            return new Response(
                $message,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        });

        // include auto-generated routes for the client
        $routes = new RouteRegistrar;
        $routes->register($this->app);

        // send headers
        $this->_sendHeaders();
        
        // run the native Silex application
        $this->app->run();
    }

    /**
     * @return string
     */
    public function swagger()
    {
        $swagger = new Swagger;
        $swaggerJson = $swagger->parse([
            '{SCHEME}'   => $this->getConfig(self::CONFIG_SCHEME),
            '{HOSTNAME}' => $this->getConfig(self::CONFIG_HOSTNAME),
        ]);

        // send headers
        $this->_sendHeaders();
        
        // return the Swagger JSON
        return $swaggerJson;
    }

    /**
     * @param null|string $key
     * @return array|null|mixed
     */
    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        $value = array_key_exists($key, $this->config)
            ? $this->config[$key]
            : null;

        return $value;
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
     *
     */
    private function _sendHeaders()
    {
        foreach ($this->getHeaders() as $_header => $_value) {
            header("{$_header}: {$_value}");
        }
    }
}
