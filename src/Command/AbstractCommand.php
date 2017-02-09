<?php
namespace Absolute\SilexApi\Command;

use Absolute\SilexApi\Exception\GenerationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    const DATA_FILE = 'data_file';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->addArgument(
            self::DATA_FILE,
            InputArgument::REQUIRED,
            'Path to your source data file.'
        );
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function _getClientData(InputInterface $input)
    {
        #todo this source data needs to be documented, validated and const'd for ease of use
        
        $clientData = require($input->getArgument(self::DATA_FILE));

        return $clientData;
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function _getClientNamespace(InputInterface $input)
    {
        $clientData = $this->_getClientData($input);
        $namespace = $clientData['namespace'];
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
