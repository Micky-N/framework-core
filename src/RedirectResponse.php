<?php

namespace MkyCore;

use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Facades\{
    Router, Session, Request
};

class RedirectResponse implements ResponseHandlerInterface
{

    private ?Response $response = null;

    public function to(string $to, int $status = 302, string $reasonPhrase = ''): static
    {
        $response = new Response();
        $this->response = $response->withStatus($status, $reasonPhrase)->withHeader('Location', $to);
        return $this;
    }

    public function error(int $code = 404, string $reasonPhrase = ''): static
    {
        return $this->to('', $code, $reasonPhrase);
    }

    public function route(string $name, array $params = [], int $status = 302): static
    {
        return $this->to(Router::getUrlFromName($name, $params), $status);
    }

    public function back(int $status = 302): static
    {
        return $this->to(Request::backUrl(), $status);
    }

    public function handle(): Response
    {
        return $this->response;
    }

    public function session(string $type, string|array $message): static
    {
        Session::set($type, $message);
        return $this;
    }
}