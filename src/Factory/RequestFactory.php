<?php
namespace Absolute\SilexApi\Factory;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Request\JsonRequest;
use Absolute\SilexApi\Request\JsonApiRequest;
use Absolute\SilexApi\Request\RequestInterface;

class RequestFactory
{
    const DEFAULT = JsonRequest::CONTENT_TYPE;
    
    /** @var array */
    private static $allowed = [
        JsonRequest::CONTENT_TYPE,
        JsonApiRequest::CONTENT_TYPE,
    ];
    
    /** @var array */
    private static $cache = [];
    
    /**
     * @param HttpRequest $httpRequest
     * @return RequestInterface
     */
    public static function get(HttpRequest $httpRequest)
    {
        $contentType = $httpRequest->headers->get('content-type');
        if (!in_array($contentType, self::$allowed)) {
            $contentType = self::DEFAULT;
        }
        
        if (empty(self::$cache[$contentType])) {
            switch ($contentType) {
                case JsonApiRequest::CONTENT_TYPE:
                    self::$cache[$contentType] = new JsonApiRequest;
                    break;

                default:
                case JsonRequest::CONTENT_TYPE:
                    self::$cache[$contentType] = new JsonRequest;
                    break;
            }
        }
        
        return self::$cache[$contentType];
    }
}
