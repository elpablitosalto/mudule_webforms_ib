<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'wpg.webforms',
    [
        \Wpg\Webforms\Submitter::class => 'lib/Submitter.php',
        \Wpg\Webforms\Util::class => 'lib/Util.php',
    ]
);

