<?php
/* Icinga Web 2 Authentik Module | GPLv2+ */

namespace Icinga\Module\Authentik\Forms;

use Icinga\Forms\ConfigForm as IcingaConfigForm;

/**
 * Configuration form for the Authentik SSO module
 *
 * Field names follow the {section}_{property} convention so that
 * the parent ConfigForm::onSuccess() can automatically persist them
 * into the [authentik] section of config.ini.
 */
class ConfigForm extends IcingaConfigForm
{
    public function init(): void
    {
        $this->setName('form_config_authentik');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    public function createElements(array $formData): void
    {
        $this->addElement('text', 'authentik_base_url', [
            'required'    => true,
            'label'       => $this->translate('Authentik Base URL'),
            'description' => $this->translate(
                'The base URL of your Authentik instance, e.g. https://auth.example.com'
            ),
            'placeholder' => 'https://auth.example.com',
        ]);

        $this->addElement('text', 'authentik_client_id', [
            'required'    => true,
            'label'       => $this->translate('Client ID'),
            'description' => $this->translate('The OAuth2 client ID configured in Authentik.'),
        ]);

        $this->addElement('password', 'authentik_client_secret', [
            'required'    => false,
            'label'       => $this->translate('Client Secret'),
            'description' => $this->translate(
                'The OAuth2 client secret. Leave empty if using a public client (PKCE only).'
            ),
            'renderPassword' => true,
        ]);

        $this->addElement('text', 'authentik_redirect_uri', [
            'required'    => true,
            'label'       => $this->translate('Redirect URI'),
            'description' => $this->translate(
                'The callback URL registered in Authentik, e.g. https://icinga.example.com/authentik/callback'
            ),
            'placeholder' => 'https://icinga.example.com/authentik/callback',
        ]);

        $this->addElement('text', 'authentik_scope', [
            'required'    => false,
            'label'       => $this->translate('Scopes'),
            'description' => $this->translate(
                'Space-separated list of OIDC scopes to request. Defaults to "openid profile email".'
            ),
            'placeholder' => 'openid profile email',
        ]);
    }
}
