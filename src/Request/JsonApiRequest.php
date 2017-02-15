<?php
namespace Absolute\SilexApi\Request;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Model\ModelInterface;

class JsonApiRequest implements RequestInterface
{
    const CONTENT_TYPE = 'application/vnd.api+json';

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
