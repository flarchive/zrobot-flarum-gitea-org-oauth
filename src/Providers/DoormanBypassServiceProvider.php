<?php

namespace Zrobot\GiteaOrgOAuth\Providers;

use Flarum\Foundation\AbstractServiceProvider;

class DoormanBypassServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        if (!class_exists(\FoF\Doorman\Extend\BypassDoorkey::class)) {
            return;
        }

        if (!$this->container->bound('fof-doorman.bypass_providers')) {
            return;
        }

        try {
            $bypass = $this->container->make('fof-doorman.bypass_providers');
            $this->addProvider($bypass, 'gitea_org');
        } catch (\Throwable $e) {
            // Fail safe: do not break the app if Doorman internals change.
        }
    }

    private function addProvider(mixed $bypass, string $provider): void
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
