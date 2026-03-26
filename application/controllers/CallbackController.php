<?php
/* Icinga Web 2 Authentik Module | GPLv2+ */

namespace Icinga\Module\Authentik\Controllers;

use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Logger;
use Icinga\Module\Authentik\OidcClient;
use Icinga\User;
use Icinga\Web\Controller;
use Icinga\Web\Session;
use Icinga\Web\Url;

class CallbackController extends Controller
{
    protected $requiresAuthentication = false;

    /**
     * OAuth2 callback — exchanges the authorization code and logs the user in
     */
    public function indexAction(): void
    {
        $session = Session::getSession();

        $error = $this->params->get('error');
        if ($error !== null) {
            Logger::error('Authentik OAuth error: %s — %s', $error, $this->params->get('error_description', ''));
            $this->redirectToLogin('SSO login failed: ' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8'));
            return;
        }

        $code  = $this->params->get('code');
        $state = $this->params->get('state');

        if (! $code || ! $state) {
            $this->redirectToLogin('Invalid SSO callback: missing code or state.');
            return;
        }

        $expectedState = $session->get('authentik_oauth_state');
        $codeVerifier  = $session->get('authentik_code_verifier');

        $session->delete('authentik_oauth_state');
        $session->delete('authentik_code_verifier');
        $session->write();

        if (! $expectedState || ! hash_equals($expectedState, $state)) {
            Logger::warning('Authentik SSO: state mismatch — possible CSRF attempt.');
            $this->redirectToLogin('SSO login failed: invalid state parameter.');
            return;
        }

        if (! $codeVerifier) {
            $this->redirectToLogin('SSO login failed: missing PKCE code verifier.');
            return;
        }

        try {
            $client   = new OidcClient();
            $userInfo = $client->fetchUserInfo($code, $codeVerifier);
        } catch (\Exception $e) {
            Logger::error('Authentik SSO token exchange failed: %s', $e->getMessage());
            $this->redirectToLogin('SSO login failed. Please try again.');
            return;
        }

        $username = $userInfo['preferred_username'] ?? $userInfo['email'] ?? $userInfo['sub'] ?? null;

        if (! $username) {
            Logger::error('Authentik SSO: could not determine username from userinfo claims.');
            $this->redirectToLogin('SSO login failed: could not determine username.');
            return;
        }

        $user = new User($username);

        if (isset($userInfo['email'])) {
            $user->setEmail($userInfo['email']);
        }

        if (isset($userInfo['groups']) && is_array($userInfo['groups'])) {
            $user->setGroups($userInfo['groups']);
        }

        $this->Auth()->setAuthenticated($user);
        AuthenticationHook::triggerLogin($user);

        $this->redirectNow(Url::fromPath('dashboard'));
    }

    protected function redirectToLogin($message = ''): void
    {
        if ($message) {
            Logger::warning('Authentik SSO redirect to login: %s', $message);
        }

        $this->redirectNow(Url::fromPath('authentication/login'));
    }
}
