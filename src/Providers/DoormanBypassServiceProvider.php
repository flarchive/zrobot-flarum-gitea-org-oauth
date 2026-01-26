<?php

namespace Zrobot\GiteaOrgOAuth\Providers;

use Flarum\Foundation\AbstractServiceProvider;
use FoF\Doorman\DoorkeyBypassRegistry;

class DoormanBypassServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        $provider = 'gitea_org';

        if (!class_exists(DoorkeyBypassRegistry::class)) {
            return;
        }

        // Register for when the bypass list is resolved (avoids boot-time binding errors).
        $this->container->afterResolving('fof-doorman.bypass_providers', function ($bypass) use ($provider) {
            $this->updateBypassProviders($bypass, $provider);
        });

        // Also register directly with Doorman's registry when it resolves.
        $this->container->afterResolving(DoorkeyBypassRegistry::class, function ($registry) use ($provider) {
            if (method_exists($registry, 'registerProvider')) {
                $registry->registerProvider($provider);
            }
        });

        // If the bindings already exist, update immediately.
        $this->tryUpdateBypassProviders($provider);
        $this->tryRegisterWithRegistry($provider);
    }

    private function tryUpdateBypassProviders(string $provider): void
    {
        if (!$this->container->bound('fof-doorman.bypass_providers')) {
            return;
        }

        try {
            $bypass = $this->container->make('fof-doorman.bypass_providers');
            $this->updateBypassProviders($bypass, $provider);
        } catch (\Throwable $e) {
            // Fail safe: do not break the app if Doorman internals change.
        }
    }

    private function tryRegisterWithRegistry(string $provider): void
    {
        if (!$this->container->bound(DoorkeyBypassRegistry::class)) {
            return;
        }

        try {
            $registry = $this->container->make(DoorkeyBypassRegistry::class);
            if (method_exists($registry, 'registerProvider')) {
                $registry->registerProvider($provider);
            }
        } catch (\Throwable $e) {
            // Fail safe: do not break the app if Doorman internals change.
        }
    }

    private function updateBypassProviders(mixed $bypass, string $provider): void
    {
        if (is_object($bypass)) {
            if (method_exists($bypass, 'add')) {
                $bypass->add($provider);
                return;
            }

            if (method_exists($bypass, 'push')) {
                $bypass->push($provider);
                return;
            }

            if ($bypass instanceof \ArrayAccess) {
                $bypass[] = $provider;
                return;
            }
        }

        if (is_array($bypass)) {
            $bypass[] = $provider;
            $this->container->instance('fof-doorman.bypass_providers', $bypass);
        }
    }
}
