<?php

namespace MkyCore\Tests\App\Middleware;

use MkyCore\Response;

class ResponseHandlerTest extends Response implements \MkyCore\Interfaces\ResponseHandlerInterface
{

    /**
     * @inheritDoc
     */
    public function handle(): Response
    {
        return new Response(400, ['content-type' => 'text/html'], 'test');
    }
}