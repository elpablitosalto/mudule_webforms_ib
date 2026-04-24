<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}
?>

<?php if (!empty($arResult['errors'])): ?>
    <div class="wpg-webform-errors">
        <?php foreach ($arResult['errors'] as $err): ?>
            <div class="wpg-webform-error"><?= htmlspecialcharsbx($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($arResult['ok'])): ?>
    <div class="wpg-webform-success">
        <?= htmlspecialcharsbx($arResult['successMessage'] ?: 'OK') ?>
    </div>
<?php else: ?>
    <?php
    $hasFile = false;
    foreach ((array)($arResult['fields'] ?? []) as $f)
    {
        if (($f['type'] ?? '') === 'F')
        {
            $hasFile = true;
            break;
        }
    }
    ?>
    <form method="post" id="<?= htmlspecialcharsbx($arResult['formId']) ?>"<?= $hasFile ? ' enctype="multipart/form-data"' : '' ?>>
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="WPG_WEBFORM_ID" value="<?= htmlspecialcharsbx($arResult['formId']) ?>">
        <input type="hidden" name="WPG_WEBFORM_SIGNED" value="<?= htmlspecialcharsbx((string)($arResult['signed'] ?? '')) ?>">

        <?php foreach ((array)($arResult['fields'] ?? []) as $field): ?>
            <?php
            $code = (string)($field['code'] ?? '');
            $label = (string)($field['name'] ?? $code);
            $type = (string)($field['type'] ?? 'S');
            $multiple = !empty($field['multiple']);
            $required = !empty($field['required']);
            $val = $arResult['values'][$code] ?? ($multiple ? [] : '');
            ?>
            <div class="wpg-webform-row">
                <label>
                    <span><?= htmlspecialcharsbx($label) ?><?= $required ? ' *' : '' ?></span>

                    <?php if ($type === 'L'): ?>
                        <select name="PROP[<?= htmlspecialcharsbx($code) ?>]<?= $multiple ? '[]' : '' ?>"<?= $multiple ? ' multiple' : '' ?>>
                            <?php if (!$multiple): ?>
                                <option value="">—</option>
                            <?php endif; ?>
                            <?php foreach ((array)($field['enum'] ?? []) as $e): ?>
                                <?php
                                $eid = (int)($e['ID'] ?? 0);
                                $selected = $multiple ? in_array((string)$eid, array_map('strval', (array)$val), true) : ((string)$val === (string)$eid);
                                ?>
                                <option value="<?= $eid ?>"<?= $selected ? ' selected' : '' ?>><?= htmlspecialcharsbx((string)($e['VALUE'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($type === 'N'): ?>
                        <input type="number" name="PROP[<?= htmlspecialcharsbx($code) ?>]<?= $multiple ? '[]' : '' ?>" value="<?= htmlspecialcharsbx(is_array($val) ? '' : (string)$val) ?>">
                    <?php elseif ($type === 'F'): ?>
                        <input type="file" name="PROP[<?= htmlspecialcharsbx($code) ?>]<?= $multiple ? '[]' : '' ?>"<?= $multiple ? ' multiple' : '' ?>>
                    <?php else: ?>
                        <?php
                        $isTextarea = ($code === 'MESSAGE');
                        $inputType = ($code === 'EMAIL') ? 'email' : 'text';
                        ?>
                        <?php if ($isTextarea): ?>
                            <textarea name="PROP[<?= htmlspecialcharsbx($code) ?>]" rows="4"><?= htmlspecialcharsbx(is_array($val) ? '' : (string)$val) ?></textarea>
                        <?php else: ?>
                            <input type="<?= $inputType ?>" name="PROP[<?= htmlspecialcharsbx($code) ?>]<?= $multiple ? '[]' : '' ?>" value="<?= htmlspecialcharsbx(is_array($val) ? '' : (string)$val) ?>">
                        <?php endif; ?>
                    <?php endif; ?>
                </label>
            </div>
        <?php endforeach; ?>

        <div class="wpg-webform-actions">
            <button type="submit">Отправить</button>
        </div>
    </form>
<?php endif; ?>

