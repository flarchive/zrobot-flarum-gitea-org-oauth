<?php

/*
 * This file is part of acme/flarum-gitea-org-oauth.
 *
 * MIT License
 */

use Flarum\Extend;
use FoF\OAuth\Extend as OAuthExtend;
use Acme\GiteaOrgOAuth\Providers\GiteaOrg;

return [
    new Extend\Locales(__DIR__.'/locale'),

    // Register a new FoF OAuth provider: "gitea_org"
    (new OAuthExtend\RegisterProvider(GiteaOrg::class)),
];
