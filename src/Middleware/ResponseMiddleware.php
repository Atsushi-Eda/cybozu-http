<?php

namespace CybozuHttp\Middleware;

use CybozuHttp\Service\ResponseService;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author ochi51 <ochiai07@gmail.com>
 */
class ResponseMiddleware
{

    /**
     * Called when the middleware is handled by the client.
     *
     * @param callable $handler
     *
     * @return \Closure
     * @throws RequestException
     * @throws \InvalidArgumentException
     */
    public function __invoke(callable $handler)
    {
        return function ($request, array $options) use ($handler) {

            return $handler($request, $options)->then(
                $this->onFulfilled($request),
            );
        };
    }

    /**
     * @param RequestInterface $request
     * @return \Closure
     * @throws \InvalidArgumentException
     */
    private function onFulfilled(RequestInterface $request): callable
    {
        return static function (ResponseInterface $response) use ($request) {
            $service = new ResponseService($request, $response);
            if ($service->isJsonResponse()) {
                return $response->withBody(new JsonStream($response->getBody()));
            }
            return $response;
        };
    }
}
