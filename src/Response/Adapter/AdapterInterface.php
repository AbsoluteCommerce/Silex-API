<?php
namespace Absolute\SilexApi\Response\Adapter;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Model\ModelInterface;

interface AdapterInterface
{
    /**
     * @param HttpRequest $request
     * @param ModelInterface $model
     * @return ModelInterface
     */
    public static function prepareResponse(HttpRequest $request, ModelInterface $model);
}
