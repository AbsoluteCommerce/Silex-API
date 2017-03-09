<?php
namespace Absolute\SilexApi\Request;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Model\ModelInterface;

class JsonRequest implements RequestInterface
{
    const CONTENT_TYPE = 'application/json';

    /**
     * @inheritdoc
     */
    public function getQuery(HttpRequest $httpRequest, string $field)
    {
        return $httpRequest->get($field);
    }

    /**
     * @inheritdoc
     */
    public function hydrateModel(HttpRequest $httpRequest, ModelInterface $model)
    {
        $data = json_decode($httpRequest->getContent(), true);
        
        if (!is_array($data)) {
            $data = [];
        }
        
        $model->setData($data);
    }
}
