<?php
namespace Absolute\SilexApi\Generator;

use Absolute\SilexApi\Exception\GenerationException;

class GeneratorConfig
{
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
        $this->getResources();
        $this->getModels();
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

        $generationDir = realpath(rtrim($generationDir, DIRECTORY_SEPARATOR)) . DIRECTORY_SEPARATOR;
        if (!is_writable($generationDir)) {
            throw new GenerationException(sprintf('Generation directory not writable: %s', $generationDir));
        }
        
        if ($path === null) {
            return $generationDir;
        }
        
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $generationDirWithPath = $generationDir . $path . DIRECTORY_SEPARATOR;
        @mkdir($generationDirWithPath, 0777, true);

        return $generationDirWithPath;
    }

    /**
     * @return string
     * @throws GenerationException
     */
    public function getResourceDir()
    {
        $resourceDir = $this->data[self::RESOURCE_DIR];
        if (empty($resourceDir)) {
            throw new GenerationException(sprintf('Missing: %s.', self::RESOURCE_DIR));
        }

        $resourceDir = realpath(rtrim($resourceDir, DIRECTORY_SEPARATOR)) . DIRECTORY_SEPARATOR;
        if (!is_writable($resourceDir)) {
            throw new GenerationException(sprintf('Resource directory not writable: %s', $resourceDir));
        }
        
        return $resourceDir;
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
