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
     * @throws ReflectionException
     * @throws Exception
     */
    public function attempt(array $credentials): bool
    {
        if(!empty($this->provider)){
            $manager = $this->app->get($this->provider['manager']);
            if (!($manager instanceof AuthSystemInterface)) {
                $class = get_class($manager);
                throw new Exception("The class $class must be an instance of MkyCore\Interface\AuthSystemInterface");
            }
            $credentials = array_filter($credentials, fn($key) => in_array($key, $this->provider['properties']), ARRAY_FILTER_USE_KEY);
            if ($entity = $manager->passwordCheck($credentials)) {
                $primaryKey = $entity->getPrimaryKey();
                $this->session->set('auth', $entity->{$primaryKey}());
                return true;
            }
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function use(string $provider): static
    {
        $this->providerName = $provider;
        $this->provider = $this->config->get('auth.providers.'.$provider);
        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function user(): bool|Entity|null
    {
        if(!empty($this->provider)){
            if ($this->isLogin()) {
                $manager = $this->app->get($this->provider['manager']);
                $id = $this->session->get('auth');
                return $manager->find($id);
            }
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
     * @return array
     */
    public function getProvider(): array
    {
        return $this->provider;
    }

    /**
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->providerName;
    }
}