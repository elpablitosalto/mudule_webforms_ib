<?php

namespace Wpg\Webforms;

use Bitrix\Main\Loader;

final class Util
{
    public static function resolveIblockId(?string $idOrCode): int
    {
        if (!$idOrCode)
        {
            return 0;
        }

        if (preg_match('/^\d+$/', $idOrCode))
        {
            return (int)$idOrCode;
        }

        if (!Loader::includeModule('iblock'))
        {
            return 0;
        }

        $res = \CIBlock::GetList([], ['=CODE' => $idOrCode, 'ACTIVE' => 'Y']);
        if ($row = $res->Fetch())
        {
            return (int)$row['ID'];
        }

        return 0;
    }

    public static function normalizeFields(array $fields): array
    {
        $out = [];
        foreach ($fields as $field)
        {
            if (!is_array($field))
            {
                continue;
            }

            $name = (string)($field['name'] ?? '');
            if ($name === '')
            {
                continue;
            }

            $type = (string)($field['type'] ?? 'text');
            $label = (string)($field['label'] ?? $name);

            $out[] = [
                'name' => $name,
                'type' => $type,
                'label' => $label,
                'placeholder' => (string)($field['placeholder'] ?? ''),
                'default' => (string)($field['default'] ?? ''),
            ];
        }

        return $out;
    }

    public static function safeString(?string $value): string
    {
        $value = (string)$value;
        $value = trim($value);
        $value = str_replace("\0", '', $value);
        return $value;
    }

    public static function buildElementName(string $tpl): string
    {
        if ($tpl === '')
        {
            $tpl = 'Заявка от #DATE#';
        }

        return str_replace(
            ['#DATE#', '#DATETIME#'],
            [date('d.m.Y'), date('d.m.Y H:i:s')],
            $tpl
        );
    }
}

