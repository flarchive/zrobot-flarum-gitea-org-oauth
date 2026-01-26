<?php

use Flarum\Extend;
use FoF\OAuth\Extend as OAuthExtend;
use Zrobot\GiteaOrgOAuth\Providers\GiteaOrg;

$extenders = [
    new Extend\Locales(__DIR__.'/locale'),

    // Register a new FoF OAuth provider: "gitea_org"
    (new OAuthExtend\RegisterProvider(GiteaOrg::class)),
];

// Add Doorman bypass if FoF Doorman is installed
if (class_exists(\FoF\Doorman\Extend\BypassDoorkey::class)) {
    $extenders[] = (new \FoF\Doorman\Extend\BypassDoorkey())
        ->forProvider('gitea_org');
}

return $extenders;
