<?php
namespace Absolute\SilexApi\Request;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Absolute\SilexApi\Request\Adapter\JsonAdapter;
use Absolute\SilexApi\Request\Adapter\JsonApiAdapter;
use Absolute\SilexApi\Request\Adapter\AdapterInterface;
use Absolute\SilexApi\Generation\Model\ModelInterface;

class RequestFactory
{
    /** @var AdapterInterface[] */
    private static $adapterCache;

    /**
     * @param HttpRequest $request
     * @param string $field
     * @return mixed
     */
    public static function getQuery(HttpRequest $request, string $field)
    {
        return self::getAdapter($request)->getQuery($request, $field);
    }

    /**
     * @param HttpRequest $request
     * @param ModelInterface $model
     */
    public static function hydrateModel(HttpRequest $request, ModelInterface $model)
    {
        self::getAdapter($request)->hydrateModel($request, $model);
    }

    /**
     * @param HttpRequest $request
     * @return AdapterInterface
     */
    public static function getAdapter(HttpRequest $request)
    {
        $accept = $request->headers->get('content-type');
        
        if (!isset(self::$adapterCache[$accept])) {
            switch ($accept) {
                case JsonApiAdapter::ACCEPT:
                    self::$adapterCache[$accept] = new JsonApiAdapter;
                    break;
                
                default:
                case JsonAdapter::ACCEPT:
                    $accept = JsonAdapter::ACCEPT;
                    self::$adapterCache[$accept] = new JsonAdapter;
                    break;
            }
        }

        return self::$adapterCache[$accept];
    }
}
