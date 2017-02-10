<?php
namespace Absolute\SilexApi\Request\Adapter;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Model\ModelInterface;

class JsonAdapter implements AdapterInterface
{
    const ACCEPT = 'application/json';

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
        $data = json_decode($request->getContent());
        
        if (!is_array($data)) {
            $data = [];
        }
        
        $model->setData($data);
    }
}
