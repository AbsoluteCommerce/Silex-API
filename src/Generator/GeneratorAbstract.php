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
}
