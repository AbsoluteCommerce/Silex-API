<?php
namespace Absolute\SilexApi\Response\Adapter;

use Symfony\Component\HttpFoundation\Request as HttpRequest;

class JsonApiAdapter implements AdapterInterface
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
