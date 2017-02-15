<?php
namespace Absolute\SilexApi\Factory;

use Silex\Application;

class ResourceFactory
{
    /**
     * @param string $className
     * @param Application $app
     */
    public static function get($className, Application $app)
    {
        return new $className($app);
    }
}
