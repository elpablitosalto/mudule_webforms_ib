<?php

namespace Wpg\Webforms;

use Bitrix\Main\Loader;

final class Submitter
{
    /**
     * @return array{ok:bool, elementId?:int, error?:string}
     */
    public static function submitToIblock(array $params): array
    {
        $iblockId = (int)($params['IBLOCK_ID'] ?? 0);
        if ($iblockId <= 0)
        {
            return ['ok' => false, 'error' => 'Не задан ИБ для сохранения заявки'];
        }

        if (!Loader::includeModule('iblock'))
        {
            return ['ok' => false, 'error' => 'Модуль iblock не подключен'];
        }

        $name = (string)($params['NAME'] ?? '');
        $active = (($params['ACTIVE'] ?? 'N') === 'Y') ? 'Y' : 'N';

        $fields = [
            'IBLOCK_ID' => $iblockId,
            'NAME' => $name !== '' ? $name : Util::buildElementName(''),
            'ACTIVE' => $active,
        ];

        $previewText = (string)($params['PREVIEW_TEXT'] ?? '');
        if ($previewText !== '')
        {
            $fields['PREVIEW_TEXT'] = $previewText;
            $fields['PREVIEW_TEXT_TYPE'] = 'text';
        }

        $propertyValues = $params['PROPERTY_VALUES'] ?? null;
        if (is_array($propertyValues) && $propertyValues)
        {
            $fields['PROPERTY_VALUES'] = $propertyValues;
        }

        $el = new \CIBlockElement();
        $elementId = (int)$el->Add($fields);
        if ($elementId <= 0)
        {
            $err = $el->LAST_ERROR ?: 'Ошибка сохранения заявки';
            return ['ok' => false, 'error' => $err];
        }

        return ['ok' => true, 'elementId' => $elementId];
    }
}

