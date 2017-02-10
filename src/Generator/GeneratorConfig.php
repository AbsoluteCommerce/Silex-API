<?php
namespace Absolute\SilexApi\Generator;

use Absolute\SilexApi\Exception\GenerationException;

class GeneratorConfig
{
    const NAMESPACE      = 'namespace';
    const GENERATION_DIR = 'generation_dir';
    const RESOURCES      = 'resources';
    const MODELS         = 'models';
    
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
        $this->getNamespace();
        $this->getGenerationDir();
        $this->getResources();
        $this->getModels();
    }
    
    /**
     * @param string $suffix
     * @return string
     * @throws GenerationException
     */
    public function getNamespace($suffix = null)
    {
        $namespace = $this->data[self::NAMESPACE];
        if (empty($namespace)) {
            throw new GenerationException(sprintf('Missing: %s.', self::NAMESPACE));
        }
        
        $namespace = rtrim($namespace, '\\') . '\\';
        
        if ($suffix === null) {
            return $namespace;
        }

        $suffix = ltrim($suffix, '\\');
        $namespaceWithSuffix = $namespace . $suffix;
        
        return $namespaceWithSuffix;
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
            throw new GenerationException(sprintf('Missing: %s.', self::NAMESPACE));
        }
        
        if (!is_writable($generationDir)) {
            throw new GenerationException(sprintf('Generation directory not writable: %s', $generationDir));
        }

        $generationDir = rtrim($generationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($path === null) {
            return $generationDir;
        }
        
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $generationDirWithPath = $generationDir . $path . DIRECTORY_SEPARATOR;
        @mkdir($generationDirWithPath, 0777, true);

        return $generationDirWithPath;
    }

    /**
     * @return array
     */
    public function getResources()
    {
        $operations = $this->data[self::RESOURCES];
        
        return $operations;
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
