<?php
namespace Absolute\SilexApi\Request;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Model\ModelInterface;

interface RequestInterface
{
    /**
     * @param HttpRequest $httpRequest
     * @param string $field
     */
    public function getQuery(HttpRequest $httpRequest, string $field);
    
    /**
     * @param HttpRequest $httpRequest
     * @param ModelInterface $model
     */
    public function hydrateModel(HttpRequest $httpRequest, ModelInterface $model);
}
