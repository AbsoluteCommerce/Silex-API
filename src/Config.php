<?php
namespace Absolute\SilexApi;

class Config
{
    const DEBUG    = 'debug';
    const SCHEME   = 'scheme';
    const HOSTNAME = 'hostname';

    /** @var array */
    private $corsHeaders = [
        'Access-Control-Allow-Origin'    => '*',
        'Access-Control-Allow-Methods'   => 'GET,POST,PUT,PATCH,DELETE,OPTIONS',
        'Access-Control-Allow-Headers'   => 'Accept,Content-Type,Authorization,Origin,api_key',
        'Access-Control-Request-Headers' => 'Accept,Content-Type,Authorization,Origin,api_key',
    ];
    
    /** @var array */
    private $config = [
        self::DEBUG    => false,
        self::SCHEME   => 'http',
        self::HOSTNAME => 'localhost',
    ];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->config = array_merge(
            $this->config,
            $data
        );
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
     * @return bool
     */
    public function getIsDebug()
    {
        $isDebug = (bool)$this->getConfig(self::DEBUG);
        
        return $isDebug;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        $scheme = $this->getConfig(self::SCHEME);
        
        return $scheme;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        $hostname = $this->getConfig(self::HOSTNAME);
        
        return $hostname;
    }

    /**
     * @return array
     */
    public function getCorsHeaders()
    {
        return $this->corsHeaders;
    }
}
