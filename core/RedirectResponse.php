<?php

namespace MkyCore;

use MkyCore\Facades\{Request, Router, Session};
use MkyCore\Interfaces\ResponseHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class RedirectResponse implements ResponseHandlerInterface
{

    private string $url = '';
    private int $status = 300;
    private string $reasonPhrase = '';

    /**
     * Send error response
     *
     * @param int $code
     * @param string $reasonPhrase
     * @return $this
     */
    public function error(int $code = 404, string $reasonPhrase = ''): static
    {
        $this->to('', $code, $reasonPhrase);
        return $this;
    }

    /**
     * Redirect to url
     *
     * @param string $url
     * @param int $status
     * @param string $reasonPhrase
     * @return $this
     */
    public function to(string $url = '', int $status = 302, string $reasonPhrase = ''): static
    {
        $this->url = $url;
        $this->status = $status;
        $this->reasonPhrase = $reasonPhrase;
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
        $response = new Response();
        $response = $response->withStatus($this->status, $this->reasonPhrase);
        if($this->url){
            $response = $response->withHeader('Location', $this->url);
        }
        return $response;
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

    /**
     * Add session value
     *
     * @param string $type
     * @param mixed $data
     * @return $this
     */
    public function oldInput(string $type, mixed $data): static
    {
        Session::set('_input:' . $type, $data);
        return $this;
    }

    public function queries(array $array): static
    {
        $queries = http_build_query($array);
        $this->url = rtrim($this->url, '/').'?'.$queries;
        return $this;
    }
}