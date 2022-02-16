<?php


namespace MkyCore;


class Module
{
    const CONFIG = null;

    public function getRoot()
    {
        $reflector = new \ReflectionClass($this);
        return dirname($reflector->getFileName());
    }
}