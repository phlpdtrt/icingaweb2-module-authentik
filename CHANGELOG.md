# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [0.1.0] - 2026-03-26

### Added
- OIDC Authorization Code flow with PKCE (S256) via Authentik identity provider
- Login button on the Icinga Web 2 login page (`LoginButtonHook`)
- Optional client secret support for confidential clients
- Configuration UI via **Configuration → Modules → authentik**
- Configurable scope (default: `openid profile email`)
- Group membership propagation from Authentik userinfo claims to Icinga Web 2
