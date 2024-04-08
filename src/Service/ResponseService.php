<?php

namespace CybozuHttp\Service;

use CybozuHttp\Exception\UnknownClientException;
use CybozuHttp\Exception\UnknownRequestException;
use CybozuHttp\Exception\UnknownServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * @author ochi51 <ochiai07@gmail.com>
 */
class ResponseService
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var Throwable|null
     */
    private $previousThrowable;

    /**
     * ResponseService constructor.
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(RequestInterface $request, ResponseInterface $response, ?Throwable $previousThrowable = null)
    {
        $this->request = $request;
        $this->response = $response;
        $this->previousThrowable = $previousThrowable;
    }

    /**
     * @return bool
     */
    public function isJsonResponse(): bool
    {
        $contentType = $this->response->getHeader('Content-Type');
        $contentType = is_array($contentType) && isset($contentType[0]) ? $contentType[0] : $contentType;

        return is_string($contentType) && strpos($contentType, 'application/json') === 0;
    }

    /**
     * @return bool
     */
    public function isHtmlResponse(): bool
    {
        $contentType = $this->response->getHeader('Content-Type');
        $contentType = is_array($contentType) && isset($contentType[0]) ? $contentType[0] : $contentType;

        return is_string($contentType) && strpos($contentType, 'text/html') === 0;
    }


    /**
     * @throws RequestException
     */
    public function handleDomError(): void
    {
        $body = (string)$this->response->getBody()->getContents();
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if ($dom->loadHTML($body)) {
            $title = $dom->getElementsByTagName('title');
            if (is_object($title)) {
                $title = $title->item(0)->nodeValue;
            }
            if ($title === 'Error') {
                $message = $dom->getElementsByTagName('h3')->item(0)->nodeValue;
                throw $this->createException($message);
            }
            if ($title === 'Unauthorized') {
                $message = $dom->getElementsByTagName('h2')->item(0)->nodeValue;
                throw $this->createException($message);
            }

            throw $this->createException('Invalid auth.');
        }

        throw new \InvalidArgumentException('Body is not DOM.');
    }

    /**
     * @throws RequestException
     */
    public function handleJsonError(): void
    {
        try {
            $body = (string)$this->response->getBody()->getContents();
            $json = \GuzzleHttp\json_decode($body, true);
        } catch (\InvalidArgumentException $e) {
            return;
        } catch (\RuntimeException $e) {
            return;
        }

        $message = $json['message'];
        if (isset($json['errors']) && is_array($json['errors'])) {
            $message .= $this->addErrorMessages($json['errors']);
        }

        throw $this->createException($message);
    }

    /**
     * @param array $errors
     * @return string
     */
    private function addErrorMessages(array $errors): string
    {
        $message = ' (';
        foreach ($errors as $k => $err) {
            $message .= $k . ' : ';
            if (is_array($err['messages'])) {
                foreach ($err['messages'] as $m) {
                    $message .= $m . ' ';
                }
            } else {
                $message .= $err['messages'];
            }
        }
        $message .= ')';

        return $message;
    }

    /**
     * @param string|null $message
     * @return RequestException
     */
    public function createException(?string $message): RequestException
    {
        if (is_null($message)) {
            return $this->createUnknownException();
        }
        return $this->createKnownException($message);
    }

    /**
     * @param string $message
     * @return RequestException
     */
    private function createKnownException(string $message): RequestException
    {
        $level = (int) floor($this->response->getStatusCode() / 100);
        $className = RequestException::class;

        if ($level === 4) {
            $className = ClientException::class;
        } elseif ($level === 5) {
            $className = ServerException::class;
        }

        return new $className($message, $this->request, $this->response);
    }

    /**
     * @return RequestException
     */
    private function createUnknownException(): RequestException
    {
        $level = (int) floor($this->response->getStatusCode() / 100);
        $className = UnknownRequestException::class;

        if ($level === 4) {
            $className = UnknownClientException::class;
        } elseif ($level === 5) {
            $className = UnknownServerException::class;
        }

        return new $className('Unknown error.', $this->request, $this->response, $this->previousThrowable);
    }
}
