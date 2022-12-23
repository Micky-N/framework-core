<?php

namespace MkyCore;

class JsonResponse implements Interfaces\ResponseHandlerInterface
{

    private array $headers;
    private Response $response;

    public function __construct(private readonly Request $request)
    {
        $headers = [];
        $headers = $this->setContentType($headers);
        $headers = $this->setCacheControl($headers);
        $headers = $this->setAllowOrigin($headers);
        $headers = $this->setAllowMethods($headers);
        $headers = $this->setAllowHeaders($headers);
        $headers = $this->setAllowCredentials($headers);
        $this->headers = $headers;
    }

    /**
     * Set content-type
     *
     * @param array $headers
     * @return array
     */
    private function setContentType(array $headers = []): array
    {
        $headers['Content-Type'] = 'application/json; charset=' . config('jwt.charset');
        return $headers;
    }

    /**
     * Set max_age
     *
     * @param array $headers
     * @return array
     */
    private function setCacheControl(array $headers = []): array
    {
        $headers['Cache-Control'] = 'max-age=' . config('jwt.max_age');
        return $headers;
    }

    /**
     * Set allowed origin
     *
     * @param array $headers
     * @return array
     */
    private function setAllowOrigin(array $headers = []): array
    {
        $allowedOrigins = config('jwt.allowed_origins');
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

    /**
     * Set allowed methods
     *
     * @param array $headers
     * @return array
     */
    private function setAllowMethods(array $headers = []): array
    {
        $allowedMethods = config('jwt.allowed_methods', ['GET']);
        $method = $this->request->method() ?? 'GET';
        for ($i = 0; $i < count($allowedMethods); $i++) {
            $allowedMethod = $allowedMethods[$i];
            if (in_array($allowedMethod, ['*', $method])) {
                $headers['Access-Control-Allow-Methods'] = $method;
            }
        }
        return $headers;
    }

    /**
     * Set allowed headers
     *
     * @param array $headers
     * @return array
     */
    private function setAllowHeaders(array $headers = []): array
    {
        $allowedHeaders = config('jwt.allowed_headers');
        if (!in_array('*', $allowedHeaders)) {
            $headers['Access-Control-Allow-Headers'] = join(', ', $allowedHeaders) == '*' ? '' : join(', ', $allowedHeaders);
        }
        return $headers;
    }

    /**
     * Set allowed credentials
     *
     * @param array $headers
     * @return array
     */
    private function setAllowCredentials(array $headers = []): array
    {
        $headers['Access-Control-Allow-Credentials'] = config('jwt.allowed_credentials') ? 'true' : 'false';
        return $headers;
    }

    /**
     * Set response
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return $this
     */
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