<?php
namespace Absolute\SilexApi\Controller;

use Silex\Application;

class ApiController
{
    /** @var Application */
    private $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    /**
     * 
     */
    public function execute()
    {
        # require SRC_DIR . 'routes.php';

        $this->app->run();
    }
}