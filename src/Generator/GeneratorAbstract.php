<?php
namespace Absolute\SilexApi\Generator;

abstract class GeneratorAbstract implements GeneratorInterface
{
    /** @var GeneratorConfig */
    protected $config;

    /**
     * @param GeneratorConfig $config
     */
    public function __construct(GeneratorConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    protected function _getClientNamespace()
    {
        $namespace = $this->config->getNamespace();
        $namespace = rtrim($namespace, '\\') . '\\';

        return $namespace;
    }

    /**
     * @param InputInterface $input
     * @return string
     * @throws GenerationException
     */
    protected function _getGenerationDir(InputInterface $input)
    {
        $clientData = $this->_getClientData($input);
        $generationDir = $clientData['generation_path'];
        $generationDir = rtrim($generationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_writable($generationDir)) {
            throw new GenerationException(sprintf('Generation directory not writable: %s', $generationDir));
        }

        return $generationDir;
    }
}
