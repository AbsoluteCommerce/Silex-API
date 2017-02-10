<?php
namespace Absolute\SilexApi\Request\Adapter;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Model\ModelInterface;

class JsonApiAdapter implements AdapterInterface
{
    const ACCEPT = 'application/vnd.api+json';

    /**
     * @inheritdoc
     */
    public function getQuery(HttpRequest $request, string $field)
    {
        throw new \Exception('Not yet implemented...');
    }

    /**
     * @inheritdoc
     */
    public function hydrateModel(HttpRequest $request, ModelInterface $model)
    {
        throw new \Exception('Not yet implemented...');
    }
}
