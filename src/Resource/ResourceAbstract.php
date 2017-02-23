<?php
namespace Absolute\SilexApi\Resource;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Absolute\SilexApi\SilexApi;
use Absolute\SilexApi\Factory\ModelFactory;

abstract class ResourceAbstract
{
    /** @var Application */
    protected $app;
    
    /** @var HttpResponse */
    protected $httpResponse;
    
    /** @var ModelFactory */
    protected $modelFactory;

    /**
     * @param Application $app
     * @param HttpResponse $httpResponse
     */
    public function __construct(Application $app, HttpResponse $httpResponse)
    {
        $this->app = $app;
        $this->httpResponse = $httpResponse;
        $this->modelFactory = $this->app[SilexApi::DI_MODEL_FACTORY];
    }
}
