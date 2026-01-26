# zrobot/flarum-gitea-org-oauth

A minimal Flarum extension that adds a **FoF OAuth** provider named `gitea_org`.

It lets users log in/register via **Gitea** and **restricts** access to users who belong to one (or more) allowed Gitea organizations.

## Features

- **Gitea OAuth Login**: Allow users to login/register via Gitea
- **Organization Restriction**: Only allow users from specific Gitea organizations
- **FoF Doorman Integration**: Automatically bypass invite codes for OAuth registration (when Doorman is installed)

## Requirements

- Flarum `^1.8`
- FriendsOfFlarum OAuth `^1.7`
- PHP 8.1+ (tested with PHP 8.3)

## Install

### Option A: Path repository (offline-friendly)
1. Copy this repo into your Flarum folder, e.g. `extensions/zrobot-gitea-org-oauth`
2. Add a path repository to `composer.json`:

```json
"repositories": [
  { "type": "path", "url": "extensions/zrobot-gitea-org-oauth" }
]
```

3. Install:

```bash
cd /opt/flarum
composer require zrobot/flarum-gitea-org-oauth:"*"
php flarum cache:clear
```

### Option B: VCS repository (GitHub/Gitea)
Add a VCS repository entry and require `dev-main`, or tag a release and require `^0.1`.

## Gitea setup

Create an **OAuth2 application** in Gitea:

- Redirect URI: `https://YOUR_FORUM_DOMAIN/auth/gitea_org`

## Flarum admin setup

In **Admin → FoF OAuth → Providers → Gitea Org OAuth**:

- **Base URL**: `https://gitea.example.com`
- **Client ID / Client Secret**: from your Gitea OAuth app
- **Allowed organizations**: `orgA,orgB` (comma-separated, leave empty to allow all users)

## Integration with FoF Doorman

If you have FoF Doorman installed, this extension automatically bypasses invite code requirements for Gitea OAuth registration:

- ✅ Normal registration requires an invite code (if Doorman is enabled)
- ✅ Gitea OAuth registration does NOT require an invite code
- ✅ Works automatically when both extensions are enabled

## Notes

- This provider checks membership using `GET /api/v1/user/orgs`
- If a user is not in the allowed org list, login/registration is denied
- Leave "Allowed organizations" empty to allow all Gitea users (no org restriction)
- Doorman bypass is automatic and always enabled when Doorman is installed
