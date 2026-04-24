<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<div>
    <p><?= Loc::getMessage('WPG_WEBFORMS_INSTALL_OK') ?: 'Модуль установлен.'; ?></p>
</div>

