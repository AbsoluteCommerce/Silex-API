<?php
namespace Absolute\SilexApi\Response\Adapter;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Model\ModelInterface;

interface AdapterInterface
{
    /**
     * @param HttpRequest $request
     * @param ModelInterface|ModelInterface[] $model
     * @return ModelInterface
     */
    public function prepareResponse(HttpRequest $request, $model);
}
