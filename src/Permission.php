<?php


namespace MkyCore;


use Closure;
use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Exceptions\Permission\PermissionAliasNotFoundException;
use MkyCore\Facades\Auth;
use MkyCore\Facades\Redirect;

class Permission
{
    private array $callbacks = [];

    public function define(string $name, Closure $callback): static
    {
        $this->callbacks[$name] = $callback;
        return $this;
    }

    /**
     * @throws PermissionAliasNotFoundException
     */
    public function authorize(string $name, Entity $user, Entity|string $entity, array $options = []): bool
    {
        $callback = $this->getCallback($name);
        if(!$callback){
            throw new PermissionAliasNotFoundException("No permission found with name $name");
        }
        return $callback($user, $entity, $options);
    }

    /**
     * @throws Exception
     */
    public function routeAuthorize(string $name, Entity|string $entity, array $options = []): \MkyCore\RedirectResponse|bool
    {
        $user = Auth::user();
        if(!$user){
            return Redirect::to404();
        }
        return $this->authorize($name, $user, $entity, $options);
    }

    /**
     * @return array
     */
    public function getCallbacks(): array
    {
        return $this->callbacks;
    }

    /**
     * @param string $name
     * @return ?Closure
     */
    public function getCallback(string $name): ?Closure
    {
        return $this->callbacks[$name] ?? null;
    }
}