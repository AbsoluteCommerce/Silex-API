<?php
namespace Absolute\SilexApi\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Absolute\SilexApi\Generator\GeneratorConfig;
use Absolute\SilexApi\Generator\GeneratorInterface;
use Absolute\SilexApi\Generator\RouteGenerator;
use Absolute\SilexApi\Generator\ResourceGenerator;
use Absolute\SilexApi\Generator\SwaggerGenerator;

class GenerationCommand extends Command
{
    const DATA_FILE = 'data_file';
    
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
                self::DATA_FILE,
                InputArgument::REQUIRED,
                'Path to your GeneratorConfig data file.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var array|GeneratorConfig $generatorConfig */
        $generatorConfig = require($input->getArgument(self::DATA_FILE));
        if (!$generatorConfig instanceof GeneratorConfig) {
            $generatorConfig = new GeneratorConfig($generatorConfig);
        }

        $generators = [
            new RouteGenerator($generatorConfig),
            new ResourceGenerator($generatorConfig),
            new SwaggerGenerator($generatorConfig),
        ];

        $output->writeln("<info>Generating Client API Data...</info>");
        $progress = new ProgressBar($output, count($generators));
        $progress->setFormat('<comment>Generating %generator%</comment> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory%');

        $progress->start();
        foreach ($generators as $_generator) {
            /** @var GeneratorInterface $_generator */
            
            $progress->setMessage(get_class($_generator), 'generator');
            $progress->display();
            
            $_generator->generate();

            $progress->advance();
        }
        $progress->finish();
    }
}
