<?php
namespace Absolute\SilexApi\Resource;

use Silex\Application;
use Absolute\SilexApi\SilexApi;
use Absolute\SilexApi\Factory\ModelFactory;

abstract class ResourceAbstract
{
    /** @var Application */
    protected $app;
    
    /** @var ModelFactory */
    protected $modelFactory;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->modelFactory = $this->app[SilexApi::DI_MODEL_FACTORY];
    }
}
