<?php


namespace MkyCore\Facades;

use MkyCore\Interfaces\VoterInterface;
use MkyCore\Permission as CorePermission;

/**
 * @method static \MkyCore\Permission can(string $permission, $subject = null)
 * @method static \MkyCore\Permission authorize($user, string $permission, $subject = null)
 * @method static \MkyCore\Permission authorizeAuth(string $permission, $subject = null)
 * @method static \MkyCore\Permission addVoter(VoterInterface $voter)
 *
 * @see \MkyCore\Permission
 */
class Permission
{
    /**
     * @var CorePermission|null
     */
    public static ?CorePermission $permission;

    public static function __callStatic($method, $arguments)
    {
        if(empty(self::$permission)){
            self::$permission = new CorePermission();
        }
        return call_user_func_array([self::$permission, $method], $arguments);
    }
}