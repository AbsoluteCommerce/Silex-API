<?php
namespace Absolute\SilexApi\Command;

use Symfony\Component\Console\Application;

class CommandRegistrar
{
    /**
     * @param Application $application
     */
    public static function register(Application $application)
    {
        $skipFiles = [
            '.',
            '..',
            'AbstractCommand.php',
            'CommandRegistrar.php',
        ];
        
        $files = scandir(__DIR__);
        foreach ($files as $_filename) {
            if (in_array($_filename, $skipFiles)) {
                continue;
            }

            $_commandClass = substr($_filename, 0, strpos($_filename, '.'));
            $_commandWithNamespace = '\\Absolute\\SilexApi\\Command\\' . $_commandClass;
            $application->add(new $_commandWithNamespace);
        }
    }
}
