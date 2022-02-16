<?php


namespace MkyCore;

use MkyCore\Facades\Session;
use App\Models\User;
use MkyCore\Facades\Route;
use Exception;

class AuthManager
{
    /**
     * @var string|null
     */
    private ?string $auth = null;

    public function __construct()
    {
        $this->auth = Session::get('auth');
    }

    /**
     * Get logged user
     *
     * @return User|null|Router
     * @throws Exception
     */
    public function getAuth()
    {
        if($this->isLogin()){
            return !is_null($this->auth) ? (User::find($this->auth) ?? $this->logout()) : $this->logout();
        }
        return null;
    }

    /**
     * Log user to session
     *
     * @param mixed $logId
     * @return void
     */
    public function login($logId)
    {
        if(!$this->isLogin()){
            Session::set('auth', $logId);
        }
    }

    /**
     * logout user from session
     *
     * @return Router
     */
    public function logout()
    {
        Session::delete('auth');
        $this->auth = null;
        if(!currentRoute(route('home.index'))){
            return Route::redirectName('auth.signin');
        }
        return Route::back();
    }

    /**
     * Check if logged
     *
     * @return bool
     */
    public function isLogin()
    {
        return $this->auth !== null;
    }
}