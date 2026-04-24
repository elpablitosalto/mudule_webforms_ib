<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<div>
    <p><?= Loc::getMessage('WPG_WEBFORMS_UNINSTALL_OK') ?: 'Модуль удалён.'; ?></p>
</div>

