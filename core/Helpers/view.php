<?php


if(!function_exists('auth')){
    function auth()
    {
        return (new \MkyCore\AuthManager())->getAuth();
    }
}