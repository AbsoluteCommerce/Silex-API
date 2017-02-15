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
    public function getQuery(HttpRequest $request, string $field)
    {
        return $field;
    }

    /**
     * @inheritdoc
     */
    public function hydrateModel(HttpRequest $request, ModelInterface $model)
    {
        $data = json_decode($request->getContent(), true);
        
        if (!is_array($data)) {
            $data = [];
        }
        
        $model->setData($data);
    }
}
