<?php

/* Icinga Web 2 Authentik Module | GPLv2+ */

namespace Icinga\Module\Authentik\ProvidedHook;

use Icinga\Application\Hook\LoginButtonHook;
use Icinga\Application\Logger;
use Icinga\Authentication\LoginButton as LoginButtonModel;
use Icinga\Module\Authentik\OidcClient;
use Icinga\Web\Session;
use ipl\Html\Attributes;
use ipl\Html\Html;

class LoginButton extends LoginButtonHook
{
    public function getButtons(): array
    {
        $content = Html::tag('span', 'Login with Authentik');

        $onClick = function (): void {
            try {
                $client = new OidcClient();
            } catch (\Exception $e) {
                Logger::error('Authentik SSO not configured: %s', $e->getMessage());
                return;
            }

            $request = $client->buildAuthorizationRequest();

            $session = Session::getSession();
            $session->set('authentik_oauth_state', $request['state']);
            $session->set('authentik_code_verifier', $request['code_verifier']);
            $session->write();

            header('Location: ' . $request['url'], true, 302);
            exit;
        };

        return [
            new LoginButtonModel(
                $onClick,
                $content,
                Attributes::create(['class' => 'authentik-sso-button'])
            ),
        ];
    }
}
