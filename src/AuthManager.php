<?php

namespace MkyCore;

use App\PersonnageModule\Managers\PersonnageManager;
use Exception;
use ReflectionException;
use MkyCore\Abstracts\Entity;
use MkyCore\Interfaces\AuthSystemInterface;

class AuthManager
{

    private array $provider;


    /**
     * @throws Exception
     */
    public function __construct(private readonly Config $config, private readonly Session $session, private readonly PersonnageManager $personnagesManager, private readonly Container $app)
    {
        $defaultProvider = $config->get('auth.default.provider');
        $this->provider = $config->get('auth.providers.' . $defaultProvider);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function attempt(array $credentials): bool
    {
        $entity = $this->app->get($this->provider['entity']);
        if (!($entity instanceof AuthSystemInterface)) {
            $class = get_class($entity);
            throw new Exception("The class $class must be an instance of MkyCore\Interface\AuthSystemInterface");
        }
        $credentials = array_filter($credentials, fn($key) => in_array($key, $this->provider['properties']), ARRAY_FILTER_USE_KEY);
        if ($entity = $entity->passwordCheck($credentials)) {
            $primaryKey = $entity->getPrimaryKey();
            $this->session->set('auth', $entity->{$primaryKey}());
            return true;
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function use(string $provider): static
    {
        $provider = $this->config->get('auth.providers.'.$provider);
        $this->provider = $provider;
        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function user(): bool|Entity|null
    {
        if ($this->isLogin()) {
            $entity = $this->app->get($this->provider['entity']);
            $id = $this->session->get('auth');
            return $entity->getManager()->find($id);
        }
        return null;
    }

    public function isLogin(): bool
    {
        return !empty($this->session->get('auth'));
    }

    public function logout(): void
    {
        $this->session->remove('auth');
    }

    /**
     * @return Config
     * @throws Exception
     */
    public function getConfig(): Config
    {
        return $this->config->get('auth.providers.' . $this->provider);
    }
}