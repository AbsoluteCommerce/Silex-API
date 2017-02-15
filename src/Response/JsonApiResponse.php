<?php
namespace Absolute\SilexApi\Response;

use Symfony\Component\HttpFoundation\Request as HttpRequest;

class JsonApiResponse implements ResponseInterface
{
    const ACCEPT = 'application/vnd.api+json';

    /**
     * @inheritdoc
     */
    public function prepareResponse(HttpRequest $request, $model)
    {
        throw new \Exception('Not yet implemented...');
    }
}
