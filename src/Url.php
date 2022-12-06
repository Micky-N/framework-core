<?php

namespace MkyCore;

use MkyCore\Exceptions\UrlParamNotFoundException;

class Url
{

    private readonly string $baseUrl;

    public function __construct(Request $request)
    {
        $this->baseUrl = $request->baseUri();
    }

    public function make(string|array $url, array $query = []): string
    {
        if(is_string($url)){
            $url = explode('/', trim($url, '/'));
        }
        $url = implode('/', $url);
        $url = explode('?', $url);
        $url = array_shift($url);
        if($query){
            $url .= http_build_query($query);
        }
        return $this->baseUrl.'/'.$url;
    }

    /**
     * @param string|array $url
     * @param array $params
     * @param array $query
     * @return string
     * @throws UrlParamNotFoundException
     */
    public function makeWithParams(string|array $url, array $params, array $query = []): string
    {
        return preg_replace_callback('/\{(.*?)}/', function($e) use ($params){
            if(isset($e[1])){
                if(!isset($params[$e[1]])){
                    throw new UrlParamNotFoundException("Url param $e[1] not found");
                }
                return $params[$e[1]];
            }
            return $e[0];
        }, $this->make($url, $query));
    }
}