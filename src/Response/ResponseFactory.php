<?php
namespace Absolute\SilexApi\Response;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Response\Adapter\JsonAdapter;
use Absolute\SilexApi\Response\Adapter\JsonApiAdapter;
use Absolute\SilexApi\Response\Adapter\AdapterInterface;
use Absolute\SilexApi\Model\ModelInterface;

class ResponseFactory
{
    /** @var AdapterInterface */
    private static $adapter;

    /**
     * @param HttpRequest $request
     * @param ModelInterface|ModelInterface[] $model
     * @return ModelInterface
     */
    public static function prepareResponse(HttpRequest $request, $model)
    {
        return self::getAdapter($request)->prepareResponse($request, $model);
    }

    /**
     * @param HttpRequest $request
     * @return AdapterInterface
     */
    public static function getAdapter(HttpRequest $request)
    {
        if (self::$adapter === null) {
            switch ($request->headers->get('accept')) {
                #todo correct, this should use ACCEPT header
                # Content-Types that are acceptable for the response. See Content negotiation.
                case JsonApiAdapter::ACCEPT:
                    self::$adapter = new JsonApiAdapter;
                    break;
                
                default:
                case JsonAdapter::ACCEPT:
                    self::$adapter = new JsonAdapter;
                    break;
            }
        }

        return self::$adapter;
    }
}
