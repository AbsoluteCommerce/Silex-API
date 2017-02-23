<?php
namespace Absolute\SilexApi\Response;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Absolute\SilexApi\Model\ModelInterface;

interface ResponseInterface
{
    /**
     * @param HttpRequest $httpRequest
     * @param ModelInterface|ModelInterface[] $model
     * @return HttpResponse
     */
    public function prepareResponse(HttpRequest $httpRequest, $model);
}
