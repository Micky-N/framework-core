<?php

namespace MkyCore;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Interfaces\AuthSystemInterface;
use ReflectionException;

class AuthManager
{

    private array $provider;
    private string $providerName;


    /**
     * @throws Exception
     */
    public function __construct(private readonly Config $config, private readonly Session $session, private readonly Container $app)
    {
        $defaultProvider = $config->get('auth.default.provider');
        $this->providerName = $defaultProvider;
        $this->provider = $config->get('auth.providers.' . $defaultProvider, []);
    }

    /**
     * Test credentials for authentication
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function attempt(array $credentials): bool|Entity
    {
        if (empty($this->provider['manager'])) {
            throw new Exception("No config set for \"$this->providerName\" provider");
        }
        $manager = $this->app->get($this->provider['manager']);
        if (!($manager instanceof AuthSystemInterface)) {
            $class = get_class($manager);
            throw new Exception("The class $class must be an instance of MkyCore\Interface\AuthSystemInterface");
        }
        $credentials = array_filter($credentials, fn($key) => in_array($key, $this->provider['properties']), ARRAY_FILTER_USE_KEY);
        if ($entity = $manager->passwordCheck($credentials)) {
            $primaryKey = $entity->getPrimaryKey();
            $this->session->set('auth', $entity->{$primaryKey}());
            return $entity;
        }
        return false;
    }

    /**
     * Change authentication provider
     *
     * @throws Exception
     */
    public function use(string $provider): static
    {
        $this->providerName = $provider;
        $this->provider = $this->config->get('auth.providers.' . $provider);
        return $this;
    }

    /**
     * Get user entity authenticated
     *
     * @return false|Entity|null
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function user(): false|Entity|null
    {
        if (!empty($this->provider)) {
            if ($this->isLogin()) {
                /** @var Manager $manager */
                $manager = $this->app->get($this->provider['manager']);
                $id = $this->session->get('auth');
                return $manager->find($id);
            }
        }
        return null;
    }

    /**
     * Check if login
     *
     * @return bool
     */
    public function isLogin(): bool
    {
        return !empty($this->session->get('auth'));
    }

    public function logout(): void
    {
        $this->session->remove('auth');
    }

    /**
     * Get authenticate provider
     *
     * @return array
     */
    public function getProvider(): array
    {
        return $this->provider;
    }

    /**
     * Get authenticate provider name
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->providerName;
    }
}