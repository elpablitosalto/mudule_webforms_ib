<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Web\Json;
use Wpg\Webforms\Submitter;
use Wpg\Webforms\Util;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

Loc::loadMessages(__FILE__);

final class WpgWebformComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $this->arResult = [
            'errors' => [],
            'ok' => false,
            'successMessage' => (string)($this->arParams['SUCCESS_MESSAGE'] ?? ''),
            'formId' => (string)($this->arParams['FORM_ID'] ?? 'wpg-webform'),
            'fields' => [],
            'values' => [],
        ];

        if (!Loader::includeModule('wpg.webforms'))
        {
            $this->arResult['errors'][] = Loc::getMessage('WPG_WEBFORMS_ERR_MODULE') ?: 'Модуль wpg.webforms не установлен';
            $this->includeComponentTemplate();
            return;
        }

        $iblockId = Util::resolveIblockId((string)($this->arParams['IBLOCK_ID'] ?? ''));
        if ($iblockId <= 0)
        {
            $this->arResult['errors'][] = Loc::getMessage('WPG_WEBFORMS_ERR_IBLOCK') ?: 'Не задан ИБ для сохранения';
            $this->includeComponentTemplate();
            return;
        }

        if (!Loader::includeModule('iblock'))
        {
            $this->arResult['errors'][] = Loc::getMessage('WPG_WEBFORMS_ERR_IBLOCK_MODULE') ?: 'Модуль iblock не подключен';
            $this->includeComponentTemplate();
            return;
        }

        $propertyCodes = $this->parseCodes((string)($this->arParams['PROPERTY_CODES'] ?? ''));
        $requiredCodes = array_fill_keys(
            $this->parseCodes((string)($this->arParams['REQUIRED_PROPERTY_CODES'] ?? '')),
            true
        );

        $props = $this->loadPropertiesByCodes($iblockId, $propertyCodes);
        $this->arResult['fields'] = $this->buildFields($props, $requiredCodes);

        $signer = new Signer();
        $payload = Json::encode([
            'iblockId' => $iblockId,
            'propIds' => array_values(array_map(static fn($p) => (int)$p['ID'], $props)),
        ]);
        $this->arResult['signed'] = $signer->sign($payload, 'wpg.webforms.webform');

        if ($this->isPost($this->arResult['formId']))
        {
            $this->processPost($iblockId, $signer, $props, $requiredCodes);
        }

        $this->includeComponentTemplate();
    }

    private function isPost(string $formId): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            return false;
        }

        return (string)($_POST['WPG_WEBFORM_ID'] ?? '') === $formId;
    }

    private function processPost(int $iblockId, Signer $signer, array $props, array $requiredCodes): void
    {
        if (!check_bitrix_sessid())
        {
            $this->arResult['errors'][] = Loc::getMessage('WPG_WEBFORMS_ERR_SESSID') ?: 'Сессия истекла, обновите страницу';
            return;
        }

        $signed = (string)($_POST['WPG_WEBFORM_SIGNED'] ?? '');
        if ($signed === '')
        {
            $this->arResult['errors'][] = Loc::getMessage('WPG_WEBFORMS_ERR_SIGN') ?: 'Неверные данные формы';
            return;
        }

        try
        {
            $unsigned = $signer->unsign($signed, 'wpg.webforms.webform');
            $data = Json::decode($unsigned);
            if ((int)($data['iblockId'] ?? 0) !== $iblockId)
            {
                $this->arResult['errors'][] = Loc::getMessage('WPG_WEBFORMS_ERR_SIGN') ?: 'Неверные данные формы';
                return;
            }

            $expectedPropIds = array_map(static fn($p) => (int)$p['ID'], $props);
            $signedPropIds = array_map('intval', (array)($data['propIds'] ?? []));
            sort($expectedPropIds);
            sort($signedPropIds);
            if ($expectedPropIds !== $signedPropIds)
            {
                $this->arResult['errors'][] = Loc::getMessage('WPG_WEBFORMS_ERR_SIGN') ?: 'Неверные данные формы';
                return;
            }
        }
        catch (\Throwable $e)
        {
            $this->arResult['errors'][] = Loc::getMessage('WPG_WEBFORMS_ERR_SIGN') ?: 'Неверные данные формы';
            return;
        }

        $propertyValues = [];
        $previewPairs = [];
        $this->arResult['values'] = [];

        foreach ($props as $prop)
        {
            $code = (string)$prop['CODE'];
            $pid = (int)$prop['ID'];

            $val = $this->readPostedValue($prop);
            $this->arResult['values'][$code] = $val;

            $isRequired = isset($requiredCodes[$code]);
            $err = $this->validateValue($prop, $val, $isRequired);
            if ($err !== null)
            {
                $this->arResult['errors'][] = $err;
            }

            $prepared = $this->prepareForSave($prop, $val);
            if ($prepared !== null)
            {
                $propertyValues[$pid] = $prepared;
            }

            $label = (string)($prop['NAME'] ?: $code);
            $previewPairs[] = $label . ': ' . $this->previewValue($prop, $val);
        }

        if (!empty($this->arResult['errors']))
        {
            return;
        }

        $active = (($this->arParams['ACTIVE_AFTER_SUBMIT'] ?? 'N') === 'Y') ? 'Y' : 'N';
        $elementNameTpl = (string)($this->arParams['ELEMENT_NAME_TEMPLATE'] ?? 'Заявка от #DATETIME#');

        $res = Submitter::submitToIblock([
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => $active,
            'NAME' => Util::buildElementName($elementNameTpl),
            'PROPERTY_VALUES' => $propertyValues,
            'PREVIEW_TEXT' => trim(implode("\n", $previewPairs)),
        ]);

        if (!$res['ok'])
        {
            $this->arResult['errors'][] = (string)($res['error'] ?? 'Ошибка отправки');
            return;
        }

        $this->arResult['ok'] = true;
        $this->arResult['elementId'] = (int)$res['elementId'];
    }

    private function parseCodes(string $csv): array
    {
        $parts = preg_split('/[,\s]+/u', $csv, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($parts as $p)
        {
            $p = strtoupper(trim((string)$p));
            if ($p !== '' && !isset($out[$p]))
            {
                $out[$p] = true;
            }
        }
        return array_keys($out);
    }

    private function loadPropertiesByCodes(int $iblockId, array $codes): array
    {
        if (empty($codes))
        {
            return [];
        }

        $props = [];
        foreach ($codes as $code)
        {
            $res = CIBlockProperty::GetList(
                ['SORT' => 'ASC', 'ID' => 'ASC'],
                ['IBLOCK_ID' => $iblockId, '=CODE' => $code, 'ACTIVE' => 'Y']
            );
            if ($row = $res->Fetch())
            {
                $row['ID'] = (int)$row['ID'];
                $row['MULTIPLE'] = ($row['MULTIPLE'] === 'Y') ? 'Y' : 'N';

                if ($row['PROPERTY_TYPE'] === 'L')
                {
                    $row['ENUM'] = [];
                    $er = CIBlockPropertyEnum::GetList(
                        ['SORT' => 'ASC', 'ID' => 'ASC'],
                        ['PROPERTY_ID' => $row['ID']]
                    );
                    while ($e = $er->Fetch())
                    {
                        $row['ENUM'][] = [
                            'ID' => (int)$e['ID'],
                            'VALUE' => (string)$e['VALUE'],
                            'XML_ID' => (string)$e['XML_ID'],
                        ];
                    }
                }

                $props[] = $row;
            }
        }

        return $props;
    }

    private function buildFields(array $props, array $requiredCodes): array
    {
        $out = [];
        foreach ($props as $prop)
        {
            $code = (string)$prop['CODE'];
            $out[] = [
                'code' => $code,
                'id' => (int)$prop['ID'],
                'name' => (string)($prop['NAME'] ?: $code),
                'type' => (string)$prop['PROPERTY_TYPE'],
                'userType' => (string)($prop['USER_TYPE'] ?? ''),
                'multiple' => (($prop['MULTIPLE'] ?? 'N') === 'Y'),
                'required' => isset($requiredCodes[$code]),
                'enum' => (array)($prop['ENUM'] ?? []),
            ];
        }
        return $out;
    }

    private function readPostedValue(array $prop)
    {
        $code = (string)$prop['CODE'];
        $multiple = (($prop['MULTIPLE'] ?? 'N') === 'Y');
        $type = (string)$prop['PROPERTY_TYPE'];

        if ($type === 'F')
        {
            $f = $_FILES['PROP'][$code] ?? null;
            if (!is_array($f))
            {
                return $multiple ? [] : null;
            }
            return $f;
        }

        $v = $_POST['PROP'][$code] ?? ($multiple ? [] : '');
        return $v;
    }

    private function validateValue(array $prop, $val, bool $required): ?string
    {
        $code = (string)$prop['CODE'];
        $label = (string)($prop['NAME'] ?: $code);
        $type = (string)$prop['PROPERTY_TYPE'];
        $userType = (string)($prop['USER_TYPE'] ?? '');
        $multiple = (($prop['MULTIPLE'] ?? 'N') === 'Y');

        $isEmpty = static function ($v) use ($type, $multiple): bool {
            if ($type === 'F')
            {
                if (!is_array($v))
                {
                    return true;
                }
                if ($multiple && isset($v['name']) && is_array($v['name']))
                {
                    foreach ($v['name'] as $n)
                    {
                        if ((string)$n !== '')
                        {
                            return false;
                        }
                    }
                    return true;
                }
                return (string)($v['name'] ?? '') === '';
            }

            if ($multiple)
            {
                if (!is_array($v))
                {
                    return true;
                }
                foreach ($v as $one)
                {
                    if (Util::safeString((string)$one) !== '')
                    {
                        return false;
                    }
                }
                return true;
            }

            return Util::safeString((string)$v) === '';
        };

        if ($required && $isEmpty($val))
        {
            return Loc::getMessage('WPG_WEBFORMS_ERR_REQUIRED') ?: "Поле \"{$label}\" обязательно";
        }

        if ($isEmpty($val))
        {
            return null;
        }

        if ($type === 'N')
        {
            $check = $multiple ? (array)$val : [$val];
            foreach ($check as $one)
            {
                $s = Util::safeString((string)$one);
                if ($s !== '' && !is_numeric($s))
                {
                    return Loc::getMessage('WPG_WEBFORMS_ERR_NUMBER') ?: "Поле \"{$label}\" должно быть числом";
                }
            }
        }

        if ($type === 'S' && ($userType === 'Email' || $code === 'EMAIL'))
        {
            $check = $multiple ? (array)$val : [$val];
            foreach ($check as $one)
            {
                $s = Util::safeString((string)$one);
                if ($s !== '' && !filter_var($s, FILTER_VALIDATE_EMAIL))
                {
                    return Loc::getMessage('WPG_WEBFORMS_ERR_EMAIL') ?: "Поле \"{$label}\" содержит неверный email";
                }
            }
        }

        return null;
    }

    private function prepareForSave(array $prop, $val)
    {
        $type = (string)$prop['PROPERTY_TYPE'];
        $multiple = (($prop['MULTIPLE'] ?? 'N') === 'Y');

        if ($type === 'F')
        {
            if (!is_array($val))
            {
                return null;
            }

            if ($multiple && isset($val['name']) && is_array($val['name']))
            {
                $out = [];
                $cnt = count($val['name']);
                for ($i = 0; $i < $cnt; $i++)
                {
                    if ((string)$val['name'][$i] === '')
                    {
                        continue;
                    }
                    $out[] = [
                        'name' => $val['name'][$i],
                        'type' => $val['type'][$i] ?? '',
                        'tmp_name' => $val['tmp_name'][$i] ?? '',
                        'error' => $val['error'][$i] ?? 0,
                        'size' => $val['size'][$i] ?? 0,
                    ];
                }
                return $out ?: null;
            }

            if ((string)($val['name'] ?? '') === '')
            {
                return null;
            }
            return $val;
        }

        if ($multiple)
        {
            if (!is_array($val))
            {
                return null;
            }
            $out = [];
            foreach ($val as $one)
            {
                $s = Util::safeString((string)$one);
                if ($s !== '')
                {
                    $out[] = $s;
                }
            }
            return $out ?: null;
        }

        $s = Util::safeString((string)$val);
        return $s !== '' ? $s : null;
    }

    private function previewValue(array $prop, $val): string
    {
        $type = (string)$prop['PROPERTY_TYPE'];
        $multiple = (($prop['MULTIPLE'] ?? 'N') === 'Y');

        if ($type === 'F')
        {
            if (!is_array($val))
            {
                return '';
            }
            if ($multiple && isset($val['name']) && is_array($val['name']))
            {
                $names = array_filter(array_map('strval', $val['name']));
                return implode(', ', $names);
            }
            return (string)($val['name'] ?? '');
        }

        if ($multiple)
        {
            return implode(', ', array_filter(array_map(static fn($x) => Util::safeString((string)$x), (array)$val)));
        }

        return Util::safeString((string)$val);
    }
}

