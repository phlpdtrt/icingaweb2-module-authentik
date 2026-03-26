<?php

/* Icinga Web 2 Authentik Module | GPLv2+ */

namespace Icinga\Module\Authentik;

use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use RuntimeException;

/**
 * Minimal OIDC client for Authentik using PKCE + Authorization Code flow
 */
class OidcClient
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $scope;

    public function __construct()
    {
        $config = Config::module('authentik')->getSection('authentik');

        $this->baseUrl      = rtrim((string) $config->get('base_url', ''), '/');
        $this->clientId     = (string) $config->get('client_id', '');
        $this->clientSecret = (string) $config->get('client_secret', '');
        $this->redirectUri  = (string) $config->get('redirect_uri', '');
        $this->scope        = (string) $config->get('scope', 'openid profile email');

        if (! $this->baseUrl || ! $this->clientId || ! $this->redirectUri) {
            throw new ConfigurationError(
                'Authentik module is not fully configured. Please set base_url, client_id and redirect_uri.'
            );
        }
    }

    /**
     * Build the authorization URL and generate PKCE + state values
     *
     * @return array{url: string, state: string, code_verifier: string}
     */
    public function buildAuthorizationRequest(): array
    {
        $state        = $this->generateRandomString(32);
        $codeVerifier = $this->generateRandomString(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $params = http_build_query([
            'response_type'         => 'code',
            'client_id'             => $this->clientId,
            'redirect_uri'          => $this->redirectUri,
            'scope'                 => $this->scope,
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return [
            'url'           => $this->baseUrl . '/application/o/authorize/?' . $params,
            'state'         => $state,
            'code_verifier' => $codeVerifier,
        ];
    }

    /**
     * Exchange an authorization code for tokens and return the userinfo claims
     *
     * @param string $code          Authorization code from the callback
     * @param string $codeVerifier  PKCE code verifier stored in session
     * @return array<string, mixed> Userinfo claims
     */
    public function fetchUserInfo(string $code, string $codeVerifier): array
    {
        $tokens = $this->exchangeCode($code, $codeVerifier);

        return $this->requestUserInfo($tokens['access_token']);
    }

    private function exchangeCode(string $code, string $codeVerifier): array
    {
        $params = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'code'          => $code,
            'code_verifier' => $codeVerifier,
        ];

        if ($this->clientSecret !== '') {
            $params['client_secret'] = $this->clientSecret;
        }

        $response = $this->post($this->baseUrl . '/application/o/token/', $params);
        $data = json_decode($response, true);

        if (! isset($data['access_token'])) {
            throw new RuntimeException('Token exchange failed: ' . $response);
        }

        return $data;
    }

    private function requestUserInfo(string $accessToken): array
    {
        $response = $this->get(
            $this->baseUrl . '/application/o/userinfo/',
            ['Authorization: Bearer ' . $accessToken]
        );

        $data = json_decode($response, true);

        if (! is_array($data) || ! isset($data['sub'])) {
            throw new RuntimeException('Userinfo request failed or returned unexpected data.');
        }

        return $data;
    }

    private function post(string $url, array $params): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('HTTP request failed: ' . $error);
        }

        return $response;
    }

    private function get(string $url, array $headers = []): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('HTTP request failed: ' . $error);
        }

        return $response;
    }

    private function generateRandomString(int $length): string
    {
        return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
    }
}
