<?php

namespace MkyCore\Interfaces;

use Exception;
use MkyCore\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface NodeRequestHandlerInterface
{
    /**
     * Handle response
     *
     * @param Request $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;
}