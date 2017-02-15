<?php
namespace Absolute\SilexApi\Response;

use Symfony\Component\HttpFoundation\Request as HttpRequest;

class JsonResponse implements ResponseInterface
{
    const ACCEPT = 'application/json';

    /**
     * @inheritdoc
     */
    public function prepareResponse(HttpRequest $request, $model)
    {
        if ($model === null) {
            return '';
        } elseif (is_array($model)) {
            $responseData = [];
            foreach ($model as $_model) {
                $responseData[] = $_model->getData();
            }
        } else {
            $responseData = $model->getData();
        }

        $responseData = json_encode($responseData, JSON_PRETTY_PRINT);
        
        return $responseData;
    }
}
