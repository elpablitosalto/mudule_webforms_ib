<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('WPG_WEBFORMS_COMPONENT_NAME') ?: 'Веб-форма',
    'DESCRIPTION' => Loc::getMessage('WPG_WEBFORMS_COMPONENT_DESC') ?: 'Форма с сохранением заявки в инфоблок.',
    'PATH' => [
        'ID' => 'wpg',
        'NAME' => 'WPG',
        'CHILD' => [
            'ID' => 'webforms',
            'NAME' => Loc::getMessage('WPG_WEBFORMS_COMPONENT_SECTION') ?: 'Веб-формы',
        ],
    ],
];

