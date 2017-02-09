<?php
namespace Absolute\SilexApi\Request\Adapter;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Generation\Model\ModelInterface;

class JsonAdapter
{
    const ACCEPT = 'application/json';

    /**
     * @param HttpRequest $request
     * @param ModelInterface $model
     * @return ModelInterface
     */
    public static function hydrateModel(HttpRequest $request, ModelInterface $model)
    {
        return $model;
    }
}
