<?php
namespace Absolute\SilexApi;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Absolute\SilexApi\Generation\Route\RouteRegistrar;
use Absolute\SilexApi\Factory\ModelFactory;
use Absolute\SilexApi\Factory\RequestFactory;
use Absolute\SilexApi\Factory\ResponseFactory;
use Absolute\SilexApi\Factory\ResourceFactory;
use Absolute\SilexApi\Generation\Docs\Swagger;

class SilexApi
{
    const DI_MODEL_FACTORY    = 'model_factory';
    const DI_REQUEST_FACTORY  = 'request_factory';
    const DI_RESPONSE_FACTORY = 'response_factory';
    const DI_RESOURCE_FACTORY = 'resource_factory';
    
    /**
     * @param Application $app
     * @param Config $config
     */
    public static function init(Application $app, Config $config)
    {
        // set Silex debug mode
        $app['debug'] = $config->getIsDebug();

        // handle HTTP Basic Auth
        if ($credentials = $config->getBasicAuthCredentials()) {
            $app->before(function(HttpRequest $httpRequest) use ($credentials) {
                $username = $httpRequest->headers->get('php_auth_user');
                $password = $httpRequest->headers->get('php_auth_pw');

                if (!$username
                    || !$password
                    || !isset($credentials[$username])
                    || $password != $credentials[$username]
                ) {
                    return new HttpResponse(
                        HttpResponse::$statusTexts[HttpResponse::HTTP_UNAUTHORIZED],
                        HttpResponse::HTTP_UNAUTHORIZED
                    );
                }
                
                return null;
            });
        }

        // return 500 by default, to be proven otherwise in the application
        http_response_code(HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        $app->error(function (\Exception $e) use ($app) {
            if (($e instanceof HttpException)) {
                $statusCode = $e->getStatusCode();
                $message = $app['debug']
                    ? (string)$e
                    : $e->getMessage();
            } else {
                $statusCode = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;
                $message = $app['debug']
                    ? (string)$e
                    : HttpResponse::$statusTexts[$statusCode];
            }
            
            return new HttpResponse(
                $message,
                $statusCode
            );
        });

        // include auto-generated routes for the client
        $routes = new RouteRegistrar;
        $routes->register($app);
        
        // include default factories
        $factories = [
            self::DI_MODEL_FACTORY    => ModelFactory::class,
            self::DI_REQUEST_FACTORY  => RequestFactory::class,
            self::DI_RESPONSE_FACTORY => ResponseFactory::class,
            self::DI_RESOURCE_FACTORY => ResourceFactory::class,
        ];
        foreach ($factories as $_di => $_factoryClass) {
            if ($app->offsetExists($_di)) {
                continue;
            }

            $app[$_di] = function () use ($_factoryClass) {
                return new $_factoryClass;
            };
        }
    }

    /**
     * @param Config $config
     * @param array $headers
     * @return string
     */
    public static function swagger(Config $config, array $headers = [])
    {
        // send headers
        $headers = array_merge(
            $config->getCorsHeaders(),
            $headers
        );
        self::_sendHeaders($headers);
        
        $swagger = new Swagger;
        $swaggerJson = $swagger->parse([
            '{SCHEME}'   => $config->getScheme(),
            '{HOSTNAME}' => $config->getHostname(),
            '{BASEPATH}' => $config->getBasePath(),
        ]);
        
        // return the Swagger JSON
        return $swaggerJson;
    }

    /**
     * @param array $headers
     */
    private static function _sendHeaders(array $headers)
    {
        foreach ($headers as $_header => $_value) {
            header("{$_header}: {$_value}");
        }
    }
}
