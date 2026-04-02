# Icinga Web 2 – Authentik Module

[![PHP](https://github.com/phlpdtrt/icingaweb2-module-authentik/actions/workflows/php.yml/badge.svg)](https://github.com/phlpdtrt/icingaweb2-module-authentik/actions/workflows/php.yml)

An [Icinga Web 2](https://icinga.com/docs/icinga-web/) module that adds SSO login via an [Authentik](https://goauthentik.io/) identity provider.
Authentication uses the **OpenID Connect (OIDC) Authorization Code flow with PKCE** — no client secret is strictly required.

## Features

- Login button on the Icinga Web 2 login page
- OIDC Authorization Code flow with PKCE (S256)
- Optional client secret support
- Minimal dependencies: only IcingaWeb2 core and PHP `curl`

## Requirements

| Requirement | Version |
|---|---|
| Icinga Web 2 | ≥ 2.13.0 |
| PHP | ≥ 8.2 |
| PHP extension | `curl` |

## Installation

```bash
# Clone into the Icinga Web 2 modules directory
git clone https://github.com/phlpdtrt/icingaweb2-module-authentik \
    /usr/share/icingaweb2/modules/authentik

# Enable the module
icingacli module enable authentik
```

## Configuration

Create or edit `/etc/icingaweb2/modules/authentik/config.ini`:

```ini
[authentik]
base_url      = "https://authentik.example.com"
client_id     = "your-client-id"
client_secret = ""                              ; optional, leave empty for public clients
redirect_uri  = "https://icinga.example.com/icingaweb2/authentik/callback"
scope         = "openid profile email"          ; optional, this is the default
```

The settings can also be managed via **Configuration → Modules → authentik** in the Icinga Web 2 UI.

### Authentik application setup

1. Create a new **OAuth2 / OpenID Connect Provider** in Authentik.
2. Set the **Redirect URI** to match `redirect_uri` above.
3. Choose **Authorization Code** as the grant type and enable **PKCE**.
4. Copy the **Client ID** (and optionally the **Client Secret**) into the config.

## Usage

After configuration a **"Login with Authentik"** button appears on the Icinga Web 2 login page.
Clicking it redirects the user to Authentik, authenticates them, and returns them to Icinga Web 2.

## Security

- Both Icinga Web 2 and Authentik **must be served over HTTPS**. The OIDC redirect URI and the Authentik base URL must use `https://`. Sending authorization codes or tokens over plain HTTP exposes them to interception.
- The module uses **PKCE (S256)** to protect the authorization code exchange, so no client secret is required for public clients. If you do configure a `client_secret`, treat it like a password and restrict file permissions on `config.ini` accordingly (`chmod 640`).

## License

This project is licensed under the **GNU General Public License v2.0 or later** – see [LICENSE](LICENSE) for details.