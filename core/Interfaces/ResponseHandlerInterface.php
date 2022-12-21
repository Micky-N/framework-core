<?php

namespace MkyCore\Interfaces;

use MkyCore\Response;

interface ResponseHandlerInterface
{
    /**
     * Create a new Response
     *
     * @return Response
     */
    public function handle(): Response;
}