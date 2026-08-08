# PHP OIDC Auth Starter

Dependency-light PHP authentication starter for Tuurio ID with secure sessions and OpenID Connect Authorization Code flow.

[![Verify template](https://github.com/Tuurio/php-oidc-auth-starter/actions/workflows/verify.yml/badge.svg)](https://github.com/Tuurio/php-oidc-auth-starter/actions/workflows/verify.yml)

![PHP OIDC Auth Starter social preview](.github/social-preview.png)

> Generated from [`Tuurio/auth_samples/auth_samples_php`](https://github.com/Tuurio/auth_samples/tree/main/auth_samples_php). Submit implementation fixes upstream so they are not replaced by the next synchronized release.

## What you get

- Standards-based OpenID Connect authentication with framework-native integration.
- Exact redirect and post-logout redirect handling.
- Protected-route and logout examples.
- A reviewed, pinned Tuurio provisioning workflow.

## Quickstart

1. Create a repository with **Use this template** or clone this repository.
2. Follow the framework-specific prerequisites below.
3. Review and run this pinned provisioning command:

```bash
npx manage-tuurio-id@1.1.6 init --framework php --project-dir . --auth browser --yes --output json --campaign github_php --no-open --no-wait
```

4. Approve the exact command, then complete the secure browser handoff yourself.
5. Run the build and verify one real sign-in and sign-out.

Never paste credentials, client secrets, authorization codes, tokens, session cookies, or environment-file contents into an agent chat. Browser and native applications are public clients and must not contain a client secret.

## Runtime and verification

- Runtime: PHP 8.2+
- Package manager: None
- Verification: `find . -name '*.php' -print0 | xargs -0 -n1 php -l`

## Security model

This starter uses OpenID Connect Authorization Code flow. Browser and native clients use PKCE S256 and contain no client secret. Redirect and post-logout redirect URIs must match exactly. Identity comes from the established OIDC integration or an authenticated UserInfo request; decoded JWT payloads are never treated as validation. Keep generated local environment files ignored and never commit tokens or credentials.

## Framework instructions

# Tuurio Auth PHP Server Demo

A server-rendered PHP demo that signs in with OAuth 2.0 / OpenID Connect, keeps tokens server-side, and supports logout.

## Integration guide

- Detailed integration guide: [PHP example page](https://id.tuurio.com/public/developers/examples/php)
- General developer docs: [Tuurio ID developers](https://id.tuurio.com/public/developers)

## Setup

```bash
cd auth_samples_php
cp .env.example .env
# edit .env with your tenant/client values
php -S localhost:8080 -t public router.php
```

Open `http://localhost:8080`.

For Apache/Plesk deployments, `public/` is still the preferred document root. If the whole package is web-accessible instead, the bundled root `.htaccess` forwards requests into `public/` and blocks direct access to internal files.

## Required client URLs

Configure your Tuurio client with these redirect URLs (matching your `.env` values):

```text
Redirect URI: http://localhost:8080/auth/callback
Post-logout Redirect URI: http://localhost:8080/logout/callback
```

## `.env` keys

```env
TUURIO_ISSUER=https://YOUR_TENANT.id.tuurio.com
TUURIO_CLIENT_ID=YOUR_CLIENT_ID
TUURIO_CLIENT_SECRET=
TUURIO_REDIRECT_URI=http://localhost:8080/auth/callback
TUURIO_POST_LOGOUT_REDIRECT_URI=http://localhost:8080/logout/callback
TUURIO_SCOPE=openid profile email
```

Values come from your Tuurio **Connect** page:

```text
https://<tenantId>.id.tuurio.com/admin/clients
```


## License

Licensed under the Apache License, Version 2.0. See [`LICENSE`](./LICENSE).
