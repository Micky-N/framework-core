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
    private array $provider;
    private string $providerName;

    /**
     * @throws Exception
     */
    public function __construct(private readonly Config $config, private readonly Session $session, private readonly Container $app)
    {
        $defaultProvider = self::$BASE_PROVIDER ?? $config->get('auth.default.provider');
        $this->providerName = $defaultProvider;
        $this->provider = $config->get('auth.providers.' . $defaultProvider, []);
    }

    /**
     * @return string|null
     */
    public static function getBaseProvider(): ?string
    {
        return self::$BASE_PROVIDER;
    }

    /**
     * @param string|null $BaseProvider
     * @return AuthManager
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public static function setBaseProvider(?string $BaseProvider): AuthManager
    {
        self::$BASE_PROVIDER = $BaseProvider ?: null;
        return app()->get(static::class);
    }

    /**
     * Test credentials for authentication
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function attempt(array $credentials, bool $rememberMe = false): bool|Entity
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
            return $this->login($entity, $rememberMe);
        }
        return false;
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
        Cookie::remove(RememberMe::PREFIX_ID);
        $this->session->remove('auth');
        $this->session->destroy();
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