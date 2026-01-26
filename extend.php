<?php

use Flarum\Extend;
use FoF\OAuth\Extend as OAuthExtend;
use Zrobot\GiteaOrgOAuth\Listener\DoormanBypassOnOAuth;
use Zrobot\GiteaOrgOAuth\Providers\GiteaOrg;
use Zrobot\GiteaOrgOAuth\Providers\DoormanBypassServiceProvider;

$extenders = [
    new Extend\Locales(__DIR__.'/locale'),

    // Register a new FoF OAuth provider: "gitea_org"
    (new OAuthExtend\RegisterProvider(GiteaOrg::class)),

    // Ensure Doorman bypass applies during OAuth registration
    (new Extend\Event())
        ->listen(\Flarum\User\Event\RegisteringFromProvider::class, DoormanBypassOnOAuth::class),
];

// Add Doorman bypass after the container is ready (avoids boot-time binding errors)
$extenders[] = (new Extend\ServiceProvider(DoormanBypassServiceProvider::class));

return $extenders;
