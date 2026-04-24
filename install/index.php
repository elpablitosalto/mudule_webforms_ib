<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class wpg_webforms extends CModule
{
    public $MODULE_ID = 'wpg.webforms';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'WPG';
    public $PARTNER_URI = '';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/../version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '0.0.0';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? date('Y-m-d');

        $this->MODULE_NAME = Loc::getMessage('WPG_WEBFORMS_MODULE_NAME') ?: 'WPG Webforms';
        $this->MODULE_DESCRIPTION = Loc::getMessage('WPG_WEBFORMS_MODULE_DESC') ?: '';
    }

    public function DoInstall()
    {
        global $APPLICATION;

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallFiles();
        $this->InstallEvents();

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('WPG_WEBFORMS_INSTALL_TITLE') ?: 'Install',
            __DIR__ . '/step.php'
        );
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $this->UnInstallEvents();
        $this->UnInstallFiles();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('WPG_WEBFORMS_UNINSTALL_TITLE') ?: 'Uninstall',
            __DIR__ . '/unstep.php'
        );
    }

    public function InstallFiles()
    {
        $from = __DIR__ . '/components';
        $to = $_SERVER['DOCUMENT_ROOT'] . '/local/components';
        if (is_dir($from))
        {
            CopyDirFiles($from, $to, true, true);
        }

        return true;
    }

    public function UnInstallFiles()
    {
        $base = $_SERVER['DOCUMENT_ROOT'] . '/local/components';
        $componentDir = $base . '/wpg/webform';
        if (is_dir($componentDir))
        {
            DeleteDirFilesEx('/local/components/wpg/webform');
        }
        return true;
    }

    public function InstallEvents()
    {
        return true;
    }

    public function UnInstallEvents()
    {
        return true;
    }
}

