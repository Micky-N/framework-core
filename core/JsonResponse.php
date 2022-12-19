<?php

namespace MkyCore;

class JsonResponse implements Interfaces\ResponseHandlerInterface
{

    private array $headers;
    private Response $response;

    public function __construct(private readonly Request $request)
    {
        $headers = [];
        $headers = $this->getContentType($headers);
        $headers = $this->getCacheControl($headers);
        $headers = $this->getAllowOrigin($headers);
        $headers = $this->getAllowMethods($headers);
        $headers = $this->getAllowHeaders($headers);
        $headers = $this->getAllowCredentials($headers);
        $this->headers = $headers;
    }

    private function getContentType(array $headers = []): array
    {
        $headers['Content-Type'] = 'application/json; charset=' . config('api.charset');
        return $headers;
    }

    private function getCacheControl(array $headers = []): array
    {
        $headers['Cache-Control'] = 'max-age=' . config('api.max_age');
        return $headers;
    }

    private function getAllowOrigin(array $headers = []): array
    {
        $allowedOrigins = config('api.allowed_origins');
        $origin = $this->request->header('Origin')[0] ?? '';
        for ($i = 0; $i < count($allowedOrigins); $i++) {
            $allowedOrigin = $allowedOrigins[$i];
            if (in_array($allowedOrigin, ['*', $origin])) {
                $headers['Access-Control-Allow-Origin'] = $origin;
                break;
            }
        }
        return $headers;
    }

    private function getAllowMethods(array $headers = []): array
    {
        $allowedMethods = config('api.allowed_methods', ['GET']);
        $method = $this->request->method() ?? 'GET';
        for ($i = 0; $i < count($allowedMethods); $i++) {
            $allowedMethod = $allowedMethods[$i];
            if (in_array($allowedMethod, ['*', $method])) {
                $headers['Access-Control-Allow-Methods'] = $method;
            }
        }
        return $headers;
    }

    private function getAllowHeaders(array $headers = []): array
    {
        $allowedHeaders = config('api.allowed_headers');
        if (!in_array('*', $allowedHeaders)) {
            $headers['Access-Control-Allow-Headers'] = join(', ', $allowedHeaders) == '*' ? '' : join(', ', $allowedHeaders);
        }
        return $headers;
    }

    private function getAllowCredentials(array $headers = []): array
    {
        $headers['Access-Control-Allow-Credentials'] = config('api.allowed_credentials') ? 'true' : 'false';
        return $headers;
    }

    public function make(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers = array_merge($this->headers, $headers);
        $res = [
            'data' => $data,
            'status' => $status,
            'statusText' => Response::getErrorMessage($status),
            'url' => $this->request->backUrl()
        ];
        $this->response = new Response($status, $headers, json_encode($res));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function handle(): Response
    {
        return $this->response;
    }
}