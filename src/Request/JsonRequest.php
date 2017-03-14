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
        #todo need unit tests for this
        
        // handle nested[para][meters]
        parse_str($field, $result);
        
        $field = key($result);
        $value = reset($result);
        $data = $httpRequest->get($field);
        
        if ($data === null) {
            return null;
        } elseif (!is_array($value)) {
            return $data;
        } else {
            $nestedFields = reset($result);
            $nestedValue = $this->_getNestedQueryData($nestedFields, $data);
            return $nestedValue;
        }
    }

    /**
     * @param array $nestedFields
     * @param array $data
     * @return mixed
     */
    private function _getNestedQueryData($nestedFields, $data)
    {
        $dataKey = key($nestedFields);
        $childFields = reset($nestedFields);
        
        if (empty($childFields)) {
            return array_key_exists($dataKey, $data)
                ? $data[$dataKey]
                : null;
        } else {
            
            return $this->_getNestedQueryData($childFields, $data[$dataKey]);
        }
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
