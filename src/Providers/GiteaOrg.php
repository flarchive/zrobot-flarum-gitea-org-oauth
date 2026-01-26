<?php

/*
 * This file is part of acme/flarum-gitea-org-oauth.
 *
 * MIT License
 */

namespace Zrobot\GiteaOrgOAuth\Providers;

use Flarum\Forum\Auth\Registration;
use Flarum\User\Exception\PermissionDeniedException;
use FoF\OAuth\Provider;
use Illuminate\Support\Arr;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Log\LoggerInterface;

class GiteaOrg extends Provider
{
    /**
     * @var GenericProvider
     */
    protected $provider;

    public function name(): string
    {
        return 'gitea_org';
    }

    public function link(): string
    {
        // A sensible default; admins can navigate to their Gitea instance and create an OAuth2 application.
        $base = rtrim((string) $this->getSetting('base_url'), '/');
        return $base ? $base.'/user/settings/applications' : 'https://gitea.com';
    }

    public function fields(): array
    {
        return [
            'base_url'       => 'required', // e.g. https://gitea.example.com
            'client_id'      => 'required',
            'client_secret'  => 'required',
            'allowed_orgs'   => 'optional', // comma separated
        ];
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        $base = rtrim((string) $this->getSetting('base_url'), '/');

        return $this->provider = new GenericProvider([
            'clientId'                => (string) $this->getSetting('client_id'),
            'clientSecret'            => (string) $this->getSetting('client_secret'),
            'redirectUri'             => $redirectUri,

            // Gitea OAuth2 endpoints
            'urlAuthorize'            => $base.'/login/oauth/authorize',
            'urlAccessToken'          => $base.'/login/oauth/access_token',

            // Resource owner (user) endpoint (Gitea API)
            'urlResourceOwnerDetails' => $base.'/api/v1/user',
        ]);
    }

    public function options(): array
    {
        // 基础权限：拿用户信息 + 邮箱
        $scopes = ['read:user', 'user:email'];
    
        // 只有启用组织限制时才要读组织列表
        $allowedRaw = trim((string) $this->getSetting('allowed_orgs'));
        if ($allowedRaw !== '') {
            $scopes[] = 'read:org';
        }
    
        return ['scope' => $scopes];
    }


    public function suggestions(Registration $registration, mixed $user, string $token): void
    {
        // Reject early if not in allowed orgs (prevents spinning UX)
        $allowedRaw = trim((string) $this->getSetting('allowed_orgs'));
        if ($allowedRaw !== '') {
            $this->enforceOrgMembership($token);
        }


        // Email: prefer user->email; otherwise call /user/emails
        $email = Arr::get($user->toArray(), 'email') ?: $this->getPrimaryEmailFromApi($token);
        $this->verifyEmail($email);

        $registration
            ->provideTrustedEmail($email)
            ->suggestUsername(
                Arr::get($user->toArray(), 'login')
                ?: Arr::get($user->toArray(), 'username')
                ?: ''
            )
            ->setPayload($user->toArray());

        $this->provideAvatar($registration, Arr::get($user->toArray(), 'avatar_url'));
    }

    protected function enforceOrgMembership(string $token): void
    {
        $base = rtrim((string) $this->getSetting('base_url'), '/');
        $allowed = $this->parseAllowedOrgs((string) $this->getSetting('allowed_orgs'));

        if (empty($allowed)) {
            // Misconfigured: deny by default
            return;
        }

        $url = $base.'/api/v1/user/orgs';

        try {
            $response = $this->provider->getResponse(
                $this->provider->getAuthenticatedRequest('GET', $url, $token)
            );

            $orgs = json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (\Throwable $e) {
            // If Gitea is unreachable or the token lacks scope, deny (and log for admins)
            $this->logProviderError('Org membership check failed', $e);
            throw new PermissionDeniedException();
        }

        // Gitea org object usually includes "username" (org handle). Fall back to "name" just in case.
        $userOrgHandles = array_map(static function ($o) {
            return strtolower((string) ($o['username'] ?? $o['name'] ?? ''));
        }, $orgs);

        foreach ($allowed as $org) {
            if (in_array(strtolower($org), $userOrgHandles, true)) {
                return;
            }
        }

        throw new PermissionDeniedException();
    }

    protected function getPrimaryEmailFromApi(string $token): ?string
    {
        $base = rtrim((string) $this->getSetting('base_url'), '/');
        $url = $base.'/api/v1/user/emails';

        try {
            $response = $this->provider->getResponse(
                $this->provider->getAuthenticatedRequest('GET', $url, $token)
            );

            $emails = json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (\Throwable $e) {
            $this->logProviderError('Email fetch failed', $e);
            return null;
        }

        // Try to pick the primary verified email
        foreach ($emails as $e) {
            if (($e['primary'] ?? false) && ($e['verified'] ?? true) && !empty($e['email'])) {
                return (string) $e['email'];
            }
        }

        // Otherwise pick the first available
        return !empty($emails[0]['email']) ? (string) $emails[0]['email'] : null;
    }

    protected function parseAllowedOrgs(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return [];

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    protected function logProviderError(string $message, \Throwable $e): void
    {
        try {
            /** @var LoggerInterface|null $logger */
            $logger = resolve(LoggerInterface::class);
            $logger?->error('[OAuth][gitea_org] '.$message.': '.$e->getMessage(), [
                'exception' => $e,
            ]);
        } catch (\Throwable $ignored) {
            // ignore logging failures
        }
    }
}
