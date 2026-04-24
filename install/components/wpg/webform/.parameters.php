<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'PARAMETERS' => [
        'IBLOCK_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('WPG_WEBFORMS_PARAM_IBLOCK_ID') ?: 'ИБ для сохранения заявки (ID или CODE)',
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'PROPERTY_CODES' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('WPG_WEBFORMS_PARAM_PROPERTY_CODES') ?: 'Коды свойств (через запятую)',
            'TYPE' => 'STRING',
            'DEFAULT' => 'NAME,PHONE,EMAIL,MESSAGE',
        ],
        'REQUIRED_PROPERTY_CODES' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('WPG_WEBFORMS_PARAM_REQUIRED_PROPERTY_CODES') ?: 'Обязательные коды (через запятую)',
            'TYPE' => 'STRING',
            'DEFAULT' => 'PHONE,EMAIL',
        ],
        'ELEMENT_NAME_TEMPLATE' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('WPG_WEBFORMS_PARAM_ELEMENT_NAME_TEMPLATE') ?: 'Шаблон имени элемента',
            'TYPE' => 'STRING',
            'DEFAULT' => 'Заявка от #DATETIME#',
        ],
        'ACTIVE_AFTER_SUBMIT' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('WPG_WEBFORMS_PARAM_ACTIVE_AFTER_SUBMIT') ?: 'Активировать элемент после отправки',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
        'FORM_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('WPG_WEBFORMS_PARAM_FORM_ID') ?: 'HTML id формы',
            'TYPE' => 'STRING',
            'DEFAULT' => 'wpg-webform',
        ],
        'SUCCESS_MESSAGE' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('WPG_WEBFORMS_PARAM_SUCCESS_MESSAGE') ?: 'Сообщение об успехе',
            'TYPE' => 'STRING',
            'DEFAULT' => Loc::getMessage('WPG_WEBFORMS_PARAM_SUCCESS_MESSAGE_DEFAULT') ?: 'Спасибо! Заявка отправлена.',
        ],
        'CACHE_TIME' => ['DEFAULT' => 0],
    ],
];

