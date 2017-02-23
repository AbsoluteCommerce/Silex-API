<?php
namespace Absolute\SilexApi\Response;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class JsonResponse implements ResponseInterface
{
    const ACCEPT = 'application/json';
    
    /** @var HttpResponse */
    private $httpResponse;

    /**
     * @param HttpResponse $httpResponse
     */
    public function __construct(HttpResponse $httpResponse)
    {
        $this->httpResponse = $httpResponse;
    }

    /**
     * @inheritdoc
     */
    public function prepareResponse(HttpRequest $httpRequest, $model)
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
        
        $this->httpResponse->setContent($responseData);
        
        return $this->httpResponse;
    }
}
