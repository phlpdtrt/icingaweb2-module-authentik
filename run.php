<?php
/* Icinga Web 2 Authentik Module | GPLv2+ */

/** @var \Icinga\Application\Modules\Module $this */

// Resolves to Icinga\Module\Authentik\ProvidedHook\LoginButton
// alwaysRun = true so the hook fires before authentication
$this->provideHook('LoginButton', null, true);
