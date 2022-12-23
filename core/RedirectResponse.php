<?php

namespace MkyCore;

use MkyCore\Facades\{Request, Router, Session};
use MkyCore\Interfaces\ResponseHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class RedirectResponse implements ResponseHandlerInterface
{

    private Response|ResponseInterface|null $response = null;

    /**
     * Send error response
     *
     * @param int $code
     * @param string $reasonPhrase
     * @return $this
     */
    public function error(int $code = 404, string $reasonPhrase = ''): static
    {
        return $this->to('', $code, $reasonPhrase);
    }

    /**
     * Redirect to url
     *
     * @param string $to
     * @param int $status
     * @param string $reasonPhrase
     * @return $this
     */
    public function to(string $to, int $status = 302, string $reasonPhrase = ''): static
    {
        $response = new Response();
        $this->response = $response->withStatus($status, $reasonPhrase)->withHeader('Location', $to);
        return $this;
    }

    /**
     * Redirect to route url from name
     *
     * @param string $name
     * @param array $params
     * @param int $status
     * @return $this
     */
    public function route(string $name, array $params = [], int $status = 302): static
    {
        return $this->to(Router::getUrlFromName($name, $params), $status);
    }

    /**
     * Redirect to back
     *
     * @param int $status
     * @return $this
     */
    public function back(int $status = 302): static
    {
        return $this->to(Request::backUrl(), $status);
    }

    public function handle(): Response
    {
        return $this->response;
    }

    /**
     * Add session value
     *
     * @param string $type
     * @param string|array $message
     * @return $this
     */
    public function session(string $type, string|array $message): static
    {
        Session::set('_flash:' . $type, $message);
        return $this;
    }
}