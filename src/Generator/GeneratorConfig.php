<?php
namespace Absolute\SilexApi\Generator;

use Absolute\SilexApi\Exception\GenerationException;

class GeneratorConfig
{
    const PHP7_HINTS = 'php7_hints';
    
    const API_VERSION      = 'api_version';
    const API_NAME         = 'api_name';
    const API_DESCRIPTION  = 'api_description';
    const API_EMAIL        = 'api_email';
    const API_LICENSE_NAME = 'api_license_name';
    const API_LICENSE_URL  = 'api_license_url';
    const API_PATH         = 'api_path';
    
    const NAMESPACE_RESOURCE = 'namespace_resource';
    const GENERATION_DIR     = 'generation_dir';
    const RESOURCE_DIR       = 'resource_dir';
    const RESOURCES          = 'resources';
    const MODELS             = 'models';
    
    /** @var array */
    private $data;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init($data);
    }

    /**
     * @param array $data
     */
    public function init(array $data)
    {
        $this->data = $data;
        $this->validate();
    }

    /**
     * @throws GenerationException
     */
    public function validate()
    {
        $this->getNamespace(self::NAMESPACE_RESOURCE);
        $this->getGenerationDir();
        $this->getResourceDir();
    }

    /**
     * @return bool
     */
    public function isPhp7Hints()
    {
        $isHints = (bool)$this->data[self::PHP7_HINTS];
        
        return $isHints;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        $apiVersion = $this->data[self::API_VERSION];
        
        return $apiVersion;
    }

    /**
     * @return string
     */
    public function getApiName()
    {
        $apiName = $this->data[self::API_NAME];
        
        return $apiName;
    }

    /**
     * @return string
     */
    public function getApiDescription()
    {
        $apiDescription = $this->data[self::API_DESCRIPTION];
        
        return $apiDescription;
    }

    /**
     * @return string
     */
    public function getApiEmail()
    {
        $apiEmail = $this->data[self::API_EMAIL];
        
        return $apiEmail;
    }

    /**
     * @return string
     */
    public function getApiLicenseName()
    {
        $apiLicenseName = $this->data[self::API_LICENSE_NAME];
        
        return $apiLicenseName;
    }

    /**
     * @return string
     */
    public function getApiLicenseUrl()
    {
        $apiLicenseUrl = $this->data[self::API_LICENSE_URL];
        
        return $apiLicenseUrl;
    }

    /**
     * @return string
     */
    public function getApiPath()
    {
        $apiPath = $this->data[self::API_PATH];
        $apiPath = '/' . trim($apiPath, '/');
        
        return $apiPath;
    }
    
    /**
     * @param string $type
     * @return string
     * @throws GenerationException
     */
    public function getNamespace($type)
    {
        switch ($type) {
            case self::NAMESPACE_RESOURCE:
                $namespace = $this->data[self::NAMESPACE_RESOURCE];
                break;
            
            default:
                throw new GenerationException(sprintf('Unexpected Namespace Type: %s.', $type));
                break;
        }
        
        if (empty($namespace)) {
            throw new GenerationException(sprintf('Missing Namespace: %s.', $type));
        }
        
        $namespace = rtrim($namespace, '\\');
        
        return $namespace;
    }

    /**
     * @param string $path
     * @return string
     * @throws GenerationException
     */
    public function getGenerationDir($path = null)
    {
        $generationDir = $this->data[self::GENERATION_DIR];
        if (empty($generationDir)) {
            throw new GenerationException(sprintf('Missing: %s.', self::GENERATION_DIR));
        }

        $preparedDir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $generationDir);
        $preparedDir = realpath(rtrim($preparedDir, DIRECTORY_SEPARATOR));
        if (!$preparedDir) {
            throw new GenerationException(sprintf('Generation directory does not exist: %s', $generationDir));
        }
        
        $preparedDir = $preparedDir . DIRECTORY_SEPARATOR;
        if (!is_writable($preparedDir)) {
            throw new GenerationException(sprintf('Generation directory is not writable: %s', $preparedDir));
        }
        
        if ($path === null) {
            return $preparedDir;
        }
        
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $preparedDirWithPath = $preparedDir . $path . DIRECTORY_SEPARATOR;
        @mkdir($preparedDirWithPath, 0777, true);

        return $preparedDirWithPath;
    }

    /**
     * @param string $path
     * @return string
     * @throws GenerationException
     */
    public function getResourceDir($path = null)
    {
        $resourceDir = $this->data[self::RESOURCE_DIR];
        if (empty($resourceDir)) {
            throw new GenerationException(sprintf('Missing: %s.', self::RESOURCE_DIR));
        }

        $preparedDir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $resourceDir);
        $preparedDir = realpath(rtrim($preparedDir, DIRECTORY_SEPARATOR));
        if (!$preparedDir) {
            throw new GenerationException(sprintf('Resource directory does not exist: %s', $resourceDir));
        }
        
        $preparedDir = $preparedDir . DIRECTORY_SEPARATOR;
        if (!is_writable($preparedDir)) {
            throw new GenerationException(sprintf('Resource directory is not writable: %s', $preparedDir));
        }

        if ($path === null) {
            return $preparedDir;
        }

        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $preparedDirWithPath = $preparedDir . $path . DIRECTORY_SEPARATOR;
        @mkdir($preparedDirWithPath, 0777, true);

        return $preparedDirWithPath;
    }

    /**
     * @return array
     */
    public function getResources()
    {
        $resources = $this->data[self::RESOURCES];
        
        return $resources;
    }

    /**
     * @return array
     */
    public function getModels()
    {
        $models = $this->data[self::MODELS];
        
        return $models;
    }
}
