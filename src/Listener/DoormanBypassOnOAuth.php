<?php

namespace Zrobot\GiteaOrgOAuth\Listener;

use Flarum\User\Event\RegisteringFromProvider;

class DoormanBypassOnOAuth
{
    public function handle(RegisteringFromProvider $event): void
    {
        if (!class_exists(\FoF\Doorman\DoorkeyBypassRegistry::class)) {
            return;
        }

        $provider = (string) ($event->provider ?? '');
        if (!$this->isGiteaProvider($provider)) {
            return;
        }

        try {
            /** @var \FoF\Doorman\DoorkeyBypassRegistry $registry */
            $registry = resolve(\FoF\Doorman\DoorkeyBypassRegistry::class);
            $registry->registerProvider($provider);

            // Mark this user as exempt from doorkey validation
            $identifier = spl_object_hash($event->user);
            $registry->exemptUser($identifier);
            $event->user->doorkey_identifier = $identifier;
        } catch (\Throwable $e) {
            // Fail safe: do not break the app if Doorman internals change.
        }
    }

    private function isGiteaProvider(string $provider): bool
    {
        $normalized = strtolower($provider);

        return in_array($normalized, ['gitea_org', 'gitea-org', 'gitea'], true);
    }
}
