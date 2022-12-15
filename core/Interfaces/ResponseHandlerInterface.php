<?php

namespace MkyCore\Interfaces;

use MkyCore\Response;

interface ResponseHandlerInterface
{
    /**
     * @return Response
     */
    public function handle(): Response;
}