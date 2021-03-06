<?php
namespace Absolute\SilexApi\Factory;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Absolute\SilexApi\Response\JsonResponse;
use Absolute\SilexApi\Response\JsonApiResponse;
use Absolute\SilexApi\Response\ResponseInterface;

class ResponseFactory
{
    const DEFAULT = JsonResponse::ACCEPT;

    /** @var array */
    private static $allowed = [
        JsonResponse::ACCEPT,
        JsonApiResponse::ACCEPT,
    ];

    /** @var array */
    private static $cache = [];

    /**
     * @param HttpRequest $httpRequest
     * @param HttpResponse $httpResponse
     * @return ResponseInterface
     */
    public static function get(
        HttpRequest $httpRequest,
        HttpResponse $httpResponse
    ) {
        $accept = $httpRequest->headers->get('accept');
        if (!in_array($accept, self::$allowed)) {
            $accept = self::DEFAULT;
        }

        if (empty(self::$cache[$accept])) {
            switch ($accept) {
                case JsonApiResponse::ACCEPT:
                    self::$cache[$accept] = new JsonApiResponse($httpResponse);
                    break;

                default:
                case JsonResponse::ACCEPT:
                    self::$cache[$accept] = new JsonResponse($httpResponse);
                    break;
            }
        }

        return self::$cache[$accept];
    }
}
