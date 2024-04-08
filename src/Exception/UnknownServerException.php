<?php

namespace CybozuHttp\Exception;

use GuzzleHttp\Exception\ServerException;

class UnknownServerException extends ServerException implements UnknownExceptionInterface
{
}
