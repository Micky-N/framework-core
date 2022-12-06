<?php

namespace MkyCore;

use App\Providers\AppServiceProvider;
use Exception;
use ReflectionException;
use MkyCore\Exceptions\ViewSystemException;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Interfaces\ViewCompileInterface;

class View implements ResponseHandlerInterface
{
    private ?string $renderedView = null;

    /**
     * @throws Exception
     */
    public function render(string $view, array $params = []): View
    {
        if(method_exists(AppServiceProvider::class, 'viewCompile')){
            $compile = app()->get(AppServiceProvider::class)->viewCompile();
        }else{
            $compile = app()->get(\MkyCore\Providers\AppServiceProvider::class)->viewCompile();
        }
        if(!($compile instanceof ViewCompileInterface)){
            throw new ViewSystemException("Class must implement ViewCompileInterface");
        }
        $this->renderedView = $compile->compile($view, $params);
        return $this;
    }

    public function handle(): Response
    {
        return new Response(200, ['content-type' => 'text/html'], $this->renderedView);
    }
}
