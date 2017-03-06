<?php
namespace Absolute\SilexApi\Factory;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ResourceFactory
{
    /**
     * @param string $className
     * @param Application $app
     * @param HttpResponse $httpResponse
     */
    public static function get(
        $className,
        Application $app,
        HttpResponse $httpResponse
    ) {
        return new $className($app, $httpResponse);
    }
}
