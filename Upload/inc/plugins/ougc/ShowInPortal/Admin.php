<?php

/***************************************************************************
 *
 *    ougc Show in Portal plugin (/inc/plugins/ougc/ShowInPortal/Admin.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Allow moderators to choose what threads to display in the portal.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

declare(strict_types=1);

namespace ougc\ShowInPortal\Admin;

use DirectoryIterator;
use MybbStuff_MyAlerts_AlertTypeManager;
use MybbStuff_MyAlerts_Entity_AlertType;

use function ougc\ShowInPortal\Core\loadLanguage;
use function ougc\ShowInPortal\Core\loadPluginLibrary;

use const ougc\ShowInPortal\ROOT;

const FIELDS_DATA = [
    'threads' => [
        'showinportal' => "INT(1) NOT NULL DEFAULT '0'"
    ]
];

function pluginInfo(): array
{
    global $lang;

    loadLanguage();

    return [
        'name' => 'ougc Show in Portal',
        'description' => $lang->ougcShowInPortalDesc,
        'website' => 'https://ougc.network',
        'author' => 'Omar G.',
        'authorsite' => 'https://ougc.network',
        'version' => '1.8.37',
        'versioncode' => 1837,
        'compatibility' => '1827,1828,1829,183*,19*',
        'codename' => 'ougc_showinportal',
        'myalerts' => '2.1.0',
        'pl' => [
            'version' => 13,
            'url' => 'https://community.mybb.com/mods.php?action=view&pid=573'
        ],
    ];
}

function pluginActivate(): bool
{
    global $PL, $cache, $lang;

    loadLanguage();

    $pluginInfo = pluginInfo();

    loadPluginLibrary();

    // Add settings group
    $settingsContents = file_get_contents(ROOT . '/settings.json');

    $settingsData = json_decode($settingsContents, true);

    foreach ($settingsData as $settingKey => &$settingData) {
        if (empty($lang->{"setting_ougc_showinportal_{$settingKey}"})) {
            continue;
        }

        if ($settingData['optionscode'] == 'select') {
            foreach ($settingData['options'] as $optionKey) {
                $settingData['optionscode'] .= "\n{$optionKey}={$lang->{"setting_ougc_showinportal_{$settingKey}_{$optionKey}"}}";
            }
        }

        $settingData['title'] = $lang->{"setting_ougc_showinportal_{$settingKey}"};
        $settingData['description'] = $lang->{"setting_ougc_showinportal_{$settingKey}_desc"};
    }

    $PL->settings(
        'ougc_showinportal',
        $lang->setting_group_ougc_showinportal,
        $lang->setting_group_ougc_showinportal_desc,
        $settingsData
    );

    // Add templates
    $templatesDirIterator = new DirectoryIterator(
        ROOT . '/Templates'
    );

    $templates = [];

    foreach ($templatesDirIterator as $template) {
        if (!$template->isFile()) {
            continue;
        }

        $pathName = $template->getPathname();

        $pathInfo = (object)pathinfo($pathName);

        if ($pathInfo->extension === 'html') {
            $templates[$pathInfo->filename] = file_get_contents($pathName);
        }
    }

    if ($templates) {
        $PL->templates('ougcshowinportal', 'ougc Show In Portal', $templates);
    }

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        global $db;
        global $alertTypeManager;

        $alertTypeManager || $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);

        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();

        $alertType->setCode('ougc_showinportal');
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);
    }

    // Insert/update version into cache
    $plugins = (array)$cache->read('ougc_plugins');

    if (!isset($plugins['showinportal'])) {
        $plugins['showinportal'] = $pluginInfo['versioncode'];
    }

    /*~*~* RUN UPDATES START *~*~*/

    dbVerifyColumns();

    /*~*~* RUN UPDATES END *~*~*/

    $plugins['showinportal'] = $pluginInfo['versioncode'];

    $cache->update('ougc_plugins', $plugins);

    return true;
}

function pluginIsInstalled(): bool
{
    static $isInstalled = null;

    if ($isInstalled === null) {
        global $db;

        foreach (FIELDS_DATA as $tableName => $fieldsData) {
            foreach ($fieldsData as $fieldName => $fieldDefinition) {
                $isInstalled = $db->field_exists($fieldName, $tableName);

                break;
            }

            break;
        }
    }

    return $isInstalled;
}

function pluginUninstall(): bool
{
    global $db, $PL, $cache;

    loadPluginLibrary();

    $PL->settings_delete('ougc_showinportal');

    $PL->templates_delete('ougcshowinportal');

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        global $db;
        global $alertTypeManager;

        $alertTypeManager || $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);

        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        $alertTypeManager->deleteByCode('ougc_showinportal');
    }

    foreach (FIELDS_DATA as $table => $columns) {
        foreach ($columns as $field => $definition) {
            if ($db->field_exists($field, $table)) {
                $db->drop_column($table, $field);
            }
        }
    }

    // Delete version from cache
    $plugins = (array)$cache->read('ougc_plugins');

    if (isset($plugins['showinportal'])) {
        unset($plugins['showinportal']);
    }

    if (!empty($plugins)) {
        $cache->update('ougc_plugins', $plugins);
    } else {
        $PL->cache_delete('ougc_plugins');
    }

    return true;
}


function dbVerifyColumns(): bool
{
    global $db;

    foreach (FIELDS_DATA as $tableName => $fieldsData) {
        foreach ($fieldsData as $fieldName => $fieldDefinition) {
            if ($db->field_exists($fieldName, $tableName)) {
                $db->modify_column($tableName, "`{$fieldName}`", $fieldDefinition);
            } else {
                $db->add_column($tableName, $fieldName, $fieldDefinition);
            }
        }
    }

    return true;
}