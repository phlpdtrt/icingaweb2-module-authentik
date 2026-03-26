<?php
/* Icinga Web 2 Authentik Module | GPLv2+ */

namespace Icinga\Module\Authentik\Controllers;

use Icinga\Application\Config;
use Icinga\Module\Authentik\Forms\ConfigForm;
use Icinga\Web\Controller;

class ConfigController extends Controller
{
    public function indexAction(): void
    {
        $config = Config::module('authentik');

        $form = new ConfigForm();
        $form->setIniConfig($config);
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->title = $this->translate('Authentik SSO Configuration');
    }
}
