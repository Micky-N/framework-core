<?php

namespace MkyCore\Middlewares;

use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Response;

class ResponseHandlerNotFound extends Response implements ResponseHandlerInterface
{

    /**
     * @inheritDoc
     */
    public function handle(): Response
    {
        return $this;
    }
}