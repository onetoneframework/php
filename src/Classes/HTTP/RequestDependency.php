<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\HTTP;

use Clover\Classes\BaseClass;
use Clover\Classes\HTTP\Request as RequestHandler;
use Clover\Classes\HTTP\Request as HTTPRequest;

class RequestDependency extends BaseClass
{
    private $queryParameters;

    private $postParameters;

    private $requestMethod;

    private $acceptLanguage;

    public function __construct()
    {
        $this->queryParameters = RequestHandler::getExtractedQueryParameters();
        $this->postParameters = RequestHandler::getExtractedPostParameters();
        $this->requestMethod = HTTPRequest::getMethod();
        $this->acceptLanguage = HTTPRequest::parseAcceptLanguage(HTTPRequest::getAcceptLanguage());
    }

    public function queryParameter($key): mixed
    {
        return $this->queryParameters[$key];
    }

    public function postParameter($key): mixed
    {
        return $this->postParameters[$key];
    }
}
