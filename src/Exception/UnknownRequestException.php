<?php

namespace CybozuHttp\Exception;

use GuzzleHttp\Exception\RequestException;

class UnknownRequestException extends RequestException implements UnknownExceptionInterface
{
}
