<?php

namespace CybozuHttp\Exception;

use GuzzleHttp\Exception\ClientException;

class UnknownClientException extends ClientException implements UnknownExceptionInterface
{
}
