<?php

namespace CybozuHttp\Middleware;

use CybozuHttp\Service\ResponseService;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author ochi51 <ochiai07@gmail.com>
 */
class ErrorMiddleware
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
                fn (ResponseInterface $response) => $response,
                $this->onRejected($request)
            );
        };
    }

    /**
     * @param RequestInterface $request
     * @return \Closure
     * @throws RequestException
     */
    private function onRejected(RequestInterface $request): callable
    {
        return static function ($reason) use ($request) {
            if (!($reason instanceof RequestException)) {
                throw $reason;
            }
            $response = $reason->getResponse();
            if ($response === null || $response->getStatusCode() < 300) {
                throw $reason;
            }
            $service = new ResponseService($request, $response);
            if ($service->isJsonResponse()) {
                $service->handleJsonError();
            } else if ($service->isHtmlResponse()) {
                $service->handleDomError();
            }

            throw $reason;
        };
    }
}
