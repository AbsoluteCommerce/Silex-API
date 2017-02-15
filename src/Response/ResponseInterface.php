<?php
namespace Absolute\SilexApi\Response;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Model\ModelInterface;

interface ResponseInterface
{
    /**
     * @param HttpRequest $request
     * @param ModelInterface|ModelInterface[] $model
     * @return ModelInterface
     */
    public function prepareResponse(HttpRequest $request, $model);
}
