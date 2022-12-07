<?php

namespace MkyCore;

use ReflectionException;
use MkyCore\Exceptions\Config\ConfigNotFoundException;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;

class Config
{

    public function __construct(private readonly string $configPath)
    {
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @throws ConfigNotFoundException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $configArray = explode('.', $key);
        $directory = array_shift($configArray);
        $configFile = $this->configPath. "/$directory.php";
        if(!file_exists($configFile)){
            if(!is_null($default)){
                return $default;
            }
            throw new ConfigNotFoundException("Config file {$directory} does not exists");
        }
        $config = include($configFile);
        if($configArray){
            for ($i = 0; $i < count($configArray); $i++)
            {
                if(isset($config[$configArray[$i]])){
                    $config = $config[$configArray[$i]];
                }else{
                    if(!is_null($default)){
                        return $default;
                    }
                    throw new ConfigNotFoundException("config {$configArray[$i]} do not exists");
                }
            }
        }
        return $config;
    }
}