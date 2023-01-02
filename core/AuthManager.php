<?php

namespace MkyCore;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Manager;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\RememberMeException;
use MkyCore\Facades\Cookie;
use MkyCore\Interfaces\AuthSystemInterface;
use MkyCore\Remember\RememberMe;
use MkyCore\Traits\HasRememberToken;
use ReflectionException;

class AuthManager
{

    private static ?string $BASE_PROVIDER = null;
    private array $providerConfig;
    private string $providerName;

    /**
     * @throws Exception
     */
    public function __construct(private readonly Config $config, private readonly Session $session, private readonly Container $app)
    {
        $defaultProvider = self::$BASE_PROVIDER ?? $config->get('auth.default.provider');
        $this->providerName = $defaultProvider;
        $this->providerConfig = $config->get('auth.providers.' . $defaultProvider, []);
    }

    /**
     * @return string|null
     */
    public static function getBaseProvider(): ?string
    {
        return self::$BASE_PROVIDER;
    }

    /**
     * Test credentials for authentication
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function attempt(array $credentials, bool $rememberMe = false): bool|Entity
    {
        if (empty($this->providerConfig['manager'])) {
            throw new Exception("No config set for \"$this->providerName\" provider");
        }
        $manager = $this->getManager();
        $credentials = array_filter($credentials, fn($key) => in_array($key, $this->providerConfig['properties']), ARRAY_FILTER_USE_KEY);
        if ($entity = $manager->passwordCheck($credentials)) {
            return $this->login($entity, $rememberMe);
        }
        return false;
    }

    /**
     * @return AuthSystemInterface
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws Exception
     */
    public function getManager(): AuthSystemInterface
    {
        $manager = $this->app->get($this->providerConfig['manager']);
        if (!($manager instanceof AuthSystemInterface)) {
            $class = get_class($manager);
            throw new Exception("The class $class must be an instance of MkyCore\Interface\AuthSystemInterface");
        }
        return $manager;
    }

    /**
     * @param Entity $entity
     * @param bool $rememberMe
     * @return Entity
     * @throws ReflectionException
     * @throws RememberMeException
     */
    public function login(Entity $entity, bool $rememberMe = false): Entity
    {
        $primaryKey = $entity->getPrimaryKey();
        $this->session->set('auth', $entity->{$primaryKey}());
        if ($rememberMe) {
            if (in_array(HasRememberToken::class, class_uses($entity), true)) {
                $entity->rememberMe($this->providerName);
            } else {
                throw new RememberMeException(sprintf('Entity %s must uses HasRememberToken trait', get_class($entity)));
            }
        }
        return $entity;
    }

    /**
     * Change authentication provider
     *
     * @throws Exception
     */
    public function use(string $provider, bool $replace = false): static
    {
        $this->providerName = $provider;
        $this->providerConfig = $this->config->get('auth.providers.' . $provider);
        if($replace){
            self::$BASE_PROVIDER = $provider;
        }
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
        if (!empty($this->providerConfig)) {
            if ($this->isLogin()) {
                /** @var Manager $manager */
                $manager = $this->app->get($this->providerConfig['manager']);
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
        Cookie::remove(RememberMe::PREFIX_ID);
        $this->session->remove('auth');
        $this->session->destroy();
    }

    /**
     * Get authenticate provider config
     *
     * @param string|null $key
     * @return array|mixed
     */
    public function getProviderConfig(?string $key = null): mixed
    {
        if ($key && $this->hasProviderConfig($key)) {
            return $this->providerConfig[$key] ?? null;
        }
        return $this->providerConfig;
    }

    /**
     * @param string $key
     * @return bool
     */
    private function hasProviderConfig(string $key): bool
    {
        return isset($this->providerConfig[$key]);
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