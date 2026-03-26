<?php
/* Icinga Web 2 Authentik Module | GPLv2+ */

/** @var \Icinga\Application\Modules\Module $this */

$this->provideConfigTab('authentik', [
    'title' => $this->translate('Authentik SSO'),
    'label' => $this->translate('Authentik SSO'),
    'url'   => 'config/index',
]);
