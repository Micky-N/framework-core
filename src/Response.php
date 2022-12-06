<?php

namespace MkyCore;

use Psr\Http\Message\ResponseInterface;
use MkyCore\Interfaces\ResponseHandlerInterface;
use function Http\Response\send;

class Response extends \GuzzleHttp\Psr7\Response implements \Psr\Http\Message\ResponseFactoryInterface
{
    public static function getFromHandler(mixed $response): static
    {
        if($response instanceof ResponseHandlerInterface){
            return $response->handle();
        }
        return new static(200, [], $response);
    }

    /**
     * @return void
     */
    public function send(): void
    {
        $code = $this->getStatusCode();
        if ($code < 400) {
            send($this);
            die;
        }else{
            http_response_code($code);
            $message = $this->getReasonPhrase();
            $homeUrl = \MkyCore\Facades\Url::make(\MkyCore\Facades\Config::get('app.home', '/'));
            $backUrl = \MkyCore\Facades\Request::backUrl() ?? $homeUrl;
            die(require_once __DIR__.'/views/error_page.php');
        }
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->withStatus($code, $reasonPhrase);
    }
}