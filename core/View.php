<?php

namespace MkyCore;

use MkyCore\Facades\Session;
use MkyCore\MkyDirectives\BaseDirective;
use Exception;
use MkyEngine\MkyEngine;

class View
{
    /**
     * Displays the rendering of the view on the site
     *
     * @param string $view
     * @param array $params
     * @param bool $isModuleView
     * @return string|bool
     */
    public function render(string $view, array $params = [], bool $isModuleView = true)
    {
        try {
            $moduleBaseConfig = include ROOT . '/config/module.php';
            $mkyServiceProvider = include ROOT . '/app/Providers/MkyServiceProvider.php';
            $config = array_merge(config('*', 'mkyEngine'), ($isModuleView ? config('*', 'module') : $moduleBaseConfig));
            $mkyEngine = new MkyEngine($config);
            echo $mkyEngine->addDirectives(new BaseDirective())
                ->addDirectives($mkyServiceProvider['directives'])
                ->addFormatters($mkyServiceProvider['formatters'])
                ->addGlobalVariable('_ENV', $_ENV)
                ->addGlobalVariable('errors', Session::getFlashMessagesByType(Session::getConstant('FLASH_ERROR')))
                ->addGlobalVariable('success', Session::getFlashMessagesByType(Session::getConstant('FLASH_SUCCESS')))
                ->addGlobalVariable('flashMessage', Session::getFlashMessagesByType(Session::getConstant('FLASH_MESSAGE')))
                ->view($view, $params);

        } catch (Exception $ex) {
            dd($ex);
        }
    }
}
