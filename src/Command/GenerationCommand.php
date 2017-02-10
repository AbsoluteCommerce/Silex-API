<?php
namespace Absolute\SilexApi\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Absolute\SilexApi\Generator\GeneratorConfig;
use Absolute\SilexApi\Generator\GeneratorInterface;
use Absolute\SilexApi\Generator\RouteGenerator;
use Absolute\SilexApi\Generator\ModelGenerator;
use Absolute\SilexApi\Generator\ResourceInterfaceGenerator;
use Absolute\SilexApi\Generator\ResourceClassGenerator;
use Absolute\SilexApi\Generator\SwaggerGenerator;

class GenerationCommand extends Command
{
    const ARGUMENT_DATA_FILE = 'data_file';
    
    const OPTION_IMPLEMENT_RESOURCES = 'implement_resources';
    
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('absolute:silexapi:generation')
            ->setDescription('Generate Client API Data.')
            ->addArgument(
                self::ARGUMENT_DATA_FILE,
                InputArgument::REQUIRED,
                'Path to your GeneratorConfig data file.'
            )
            ->addOption(
                self::OPTION_IMPLEMENT_RESOURCES,
                null,
                InputOption::VALUE_NONE,
                'Whether to auto-generate any missing Resource classes.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_writeInfo($output, 'Started API Data Generation!');
        
        $this->_generateRequiredData($input, $output);
        $this->_implementResources($input, $output);
        
        $this->_writeInfo($output, 'Completed API Data Generation!');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function _generateRequiredData(InputInterface $input, OutputInterface $output)
    {
        $generatorConfig = $this->_getGeneratorConfig($input);

        $generators = [
            new RouteGenerator($generatorConfig),
            new ModelGenerator($generatorConfig),
            new ResourceInterfaceGenerator($generatorConfig),
            new SwaggerGenerator($generatorConfig),
        ];

        $this->_writeInfo($output, 'Generating Client API Data...');
        $progress = new ProgressBar($output, count($generators));
        $progress->setFormat('<comment>%generator%</comment> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory%');

        $progress->start();
        foreach ($generators as $_generator) {
            /** @var GeneratorInterface $_generator */
            
            $progress->setMessage('Generating ' . get_class($_generator), 'generator');
            $progress->display();
            
            $_generator->generate();

            $progress->advance();
        }
        $progress->setMessage('Generation Complete', 'generator');
        $progress->finish();
        $output->writeln('');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function _implementResources(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption(self::OPTION_IMPLEMENT_RESOURCES)) {
            return;
        }

        $generatorConfig = $this->_getGeneratorConfig($input);

        $this->_writeInfo($output, 'Creating missing Resources in ' . $generatorConfig->getResourceDir());
        
        $generator = new ResourceClassGenerator($generatorConfig);
        $generator->generate();
    }

    /**
     * @param InputInterface $input
     * @return GeneratorConfig
     */
    private function _getGeneratorConfig(InputInterface $input)
    {
        /** @var array|GeneratorConfig $clientDataFile */
        $clientDataFile = require($input->getArgument(self::ARGUMENT_DATA_FILE));
        
        if ($clientDataFile instanceof GeneratorConfig) {
            $generatorConfig = $clientDataFile;
        } else {
            $generatorConfig = new GeneratorConfig($clientDataFile);
        }
        
        return $generatorConfig;
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     */
    private function _writeInfo(OutputInterface $output, $message)
    {
        $time = date('Y-M-d H:i:s');
        $output->writeln('');
        $output->writeln("<info>[{$time}] {$message}</info>");
    }
}
