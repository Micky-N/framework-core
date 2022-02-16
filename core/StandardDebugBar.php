<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MkyCore;

use MkyCore\DebugBarCollections\RoutesCollector;
use MkyCore\DebugBarCollections\VotersCollector;
use MkyCore\DebugBar\DataCollector\MemoryCollector;
use MkyCore\DebugBar\DataCollector\PDO\PDOCollector;
use MkyCore\DebugBar\DataCollector\PhpInfoCollector;
use MkyCore\DebugBar\DataCollector\RequestDataCollector;
use MkyCore\DebugBar\DebugBar;
use MkyCore\DebugBar\JavascriptRenderer;

/**
 * Debug bar subclass which adds all included collectors
 */
class StandardDebugBar
{
    /**
     * @var DebugBar
     */
    private DebugBar $debugbar;

    public function __construct()
    {
        $this->debugbar = new DebugBar();
        $this->debugbar->addCollector(new RequestDataCollector());
        $this->debugbar->addCollector(new RoutesCollector());
        $this->debugbar->addCollector(new PDOCollector(MysqlDatabase::getConnection()));
        $this->debugbar->addCollector(new VotersCollector());
        $this->debugbar->addCollector(new PhpInfoCollector());
        $this->debugbar->addCollector(new MemoryCollector());
        return $this->debugbar;
    }

    private function getJavascriptRenderer(): JavascriptRenderer
    {
        return $this->debugbar->getJavascriptRenderer();
    }

    public function addMessage(string $collector, $message, $type = 'info')
    {
        call_user_func([$this->debugbar[$collector], $type], $message);
        return $this;
    }

    public function stackData()
    {
        return $this->debugbar->stackData();
    }

    /**
     * @return string|null
     */
    public function render()
    {
        if($this->getJavascriptRenderer()->renderHead() && $this->getJavascriptRenderer()->render()){
            return $this->getJavascriptRenderer()->renderHead() . $this->getJavascriptRenderer()->render();
        }
        return null;
    }
}
