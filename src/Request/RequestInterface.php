<?php
namespace Absolute\SilexApi\Request;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Model\ModelInterface;

interface RequestInterface
{
    /**
     * @param HttpRequest $request
     * @param string $field
     */
    public function getQuery(HttpRequest $request, string $field);
    
    /**
     * @param HttpRequest $request
     * @param ModelInterface $model
     */
    public function hydrateModel(HttpRequest $request, ModelInterface $model);
}
