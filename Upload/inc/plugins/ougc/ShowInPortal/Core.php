<?php

/***************************************************************************
 *
 *    ougc Show in Portal plugin (/inc/plugins/ougc/ShowInPortal/Core.php)
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

namespace ougc\ShowInPortal\Core;

use AbstractPdoDbDriver;
use DB_SQLite;
use MybbStuff_MyAlerts_AlertManager;
use MybbStuff_MyAlerts_AlertTypeManager;
use MybbStuff_MyAlerts_Entity_Alert;
use PluginLibrary;
use PMDataHandler;

use ReflectionProperty;

use function ougc\ShowInPortal\Admin\pluginInfo;

use function ougc\ShowInPortal\Hooks\Forum\myalerts_register_client_alert_formatters;

use const MYBB_ROOT;
use const ougc\ShowInPortal\ROOT;
use const PLUGINLIBRARY;

const STATUS_SHOW = 1;

const STATUS_HIDE = 0;

const INPUT_NOCHANGE = 0;

const INPUT_SHOW = 1;

const INPUT_HIDE = 2;

const INPUT_TOGGLE = 3;

const DEBUG = false;

function loadLanguage(): bool
{
    global $lang;

    if (!isset($lang->ougcShowInPortal)) {
        $lang->load('ougc_showinportal');
    }

    return true;
}

function pluginLibraryRequirements(): object
{
    return (object)pluginInfo()['pl'];
}

function loadPluginLibrary(): bool
{
    global $PL, $lang;

    loadLanguage();

    $fileExists = file_exists(PLUGINLIBRARY);

    if ($fileExists && !($PL instanceof PluginLibrary)) {
        require_once PLUGINLIBRARY;
    }

    if (!$fileExists || $PL->version < pluginLibraryRequirements()->version) {
        flash_message(
            $lang->sprintf(
                $lang->ougc_showinportal_pluginlibrary_required,
                pluginLibraryRequirements()->url,
                pluginLibraryRequirements()->version
            ),
            'error'
        );

        admin_redirect('index.php?module=config-plugins');
    }

    return true;
}

function addHooks(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, '', 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

function getSetting(string $settingKey = '')
{
    global $mybb;

    return SETTINGS[$settingKey] ?? ($mybb->settings['ougc_showinportal_' . $settingKey] ?? false);
}

function getTemplate(string $templateName, bool $enableHTMLComments = true): string
{
    global $templates;

    if (DEBUG) {
        $filePath = ROOT . "/Templates/{$templateName}.html";

        $templateContents = file_get_contents($filePath);

        $templates->cache["ougcshowinportal_{$templateName}"] = $templateContents;
    }

    return $templates->render("ougcshowinportal_{$templateName}", true, $enableHTMLComments);
}

function isModerator(int $forumID): bool
{
    global $settings;

    if (
        !is_moderator($forumID) ||
        !is_member(getSetting('allowedGroups')) ||
        !is_member($settings['portal_announcementsfid'], ['usergroup' => $forumID, 'additionalgroups' => '']) ||
        !is_member(getSetting('enabledForums'), ['usergroup' => $forumID, 'additionalgroups' => ''])
    ) {
        return false;
    }

    return true;
}

function cutOffMessage(string &$message, int $fid, int $tid)
{
    global $settings;

    if (!$message || !getSetting('enableReadMore') || !getSetting('readMoreTag')) {
        return;
    }

    if (!preg_match('#' . ($tag = preg_quote(getSetting('readMoreTag'))) . '#', $message)) {
        return;
    }

    $msg = preg_split('#' . $tag . '#', $message);
    if (!(isset($msg[0]) && my_strlen($msg[0]) >= (int)$settings['minmessagelength'])) {
        return;
    }

    global $lang, $forum_cache;

    loadLanguage();

    $forum_cache || cache_forums();

    // Find out what langguage variable to use
    $lang_var = 'ougc_showinportal_readmore';
    if ((bool)$forum_cache[$fid]['allowmycode']) {
        $lang_var .= '_mycode';
    } elseif ((bool)$forum_cache[$fid]['allowhtml']) {
        $lang_var .= '_html';
    }

    $message = $msg[0] . $lang->sprintf($lang->{$lang_var}, $settings['bburl'], get_thread_link($tid));
}

function moderationControlExecute(array $threadIDs, int $inputValue = INPUT_SHOW): bool
{
    switch ($inputValue) {
        case INPUT_SHOW:
            updateThreadStatus($threadIDs);
            break;
        case INPUT_HIDE:
            updateThreadStatus($threadIDs, STATUS_HIDE);
            break;
        case INPUT_TOGGLE:
            global $db;

            $threadIDs = implode(
                "','",
                array_map('intval', $threadIDs)
            );

            $dbQuery = $db->simple_select('threads', 'tid, showinportal', "tid IN ('{$threadIDs}')");

            $showIDs = $hideIDs = [];

            while ($threadData = $db->fetch_array($dbQuery)) {
                if ((int)$threadData['showinportal'] === STATUS_SHOW) {
                    $hideIDs[] = (int)$threadData['tid'];
                } else {
                    $showIDs[] = (int)$threadData['tid'];
                }
            }

            if (!empty($showIDs)) {
                updateThreadStatus($showIDs);
            }

            if (!empty($hideIDs)) {
                updateThreadStatus($hideIDs, STATUS_HIDE);
            }

            break;
    }

    return true;
}

function updateThreadStatus(array $threadIDs, int $newStatus = STATUS_SHOW): bool
{
    global $db, $lang;

    loadLanguage();

    $threadIDs = implode(
        "','",
        array_map('intval', $threadIDs)
    );

    $dbQuery = $db->simple_select('threads', 'tid, uid', "tid IN ('{$threadIDs}') AND showinportal!='{$newStatus}'");

    $updateData = [];

    while ($threadData = $db->fetch_array($dbQuery)) {
        $updateData[(int)$threadData['tid']] = (int)$threadData['uid'];
    }

    if ($updateData) {
        $updateThreadIDs = implode(
            "','",
            array_keys($updateData)
        );

        $db->update_query('threads', ['showinportal' => $newStatus], "tid IN ('{$updateThreadIDs}')");

        switch ($newStatus) {
            case STATUS_SHOW;
                $privateMessageSubject = $lang->ougc_showinportal_pm_subject;

                $privateMessageContent = $lang->ougc_showinportal_pm_message;
                break;
            default;
                $privateMessageSubject = $lang->ougc_showinportal_pm_subject_removed;

                $privateMessageContent = $lang->ougc_showinportal_pm_message_removed;
        }

        sendPrivateMessage([
            'subject' => $privateMessageSubject,
            'message' => $privateMessageContent,
        ], array_values($updateData));

        sendAlert($newStatus, array_keys($updateData));
    }

    return true;
}

function sendPrivateMessage(array $privateMessageData, array $userIDs): bool
{
    global $mybb;

    if (
        !$mybb->settings['enablepms'] ||
        !getSetting('notifyByPM') ||
        empty($privateMessageData['subject']) ||
        empty($privateMessageData['message'])
    ) {
        return false;
    }

    global $lang, $db, $session;

    $lang->load('messages');

    // Build our final PM array
    $privateMessageData = [
        'subject' => $privateMessageData['subject'],
        'message' => $lang->sprintf($privateMessageData['message'], $mybb->settings['bbname']),
        'icon' => -1,
        'fromid' => -1,
        'toid' => $userIDs,
        'bccid' => [],
        'do' => '',
        'pmid' => '',
        'saveasdraft' => 0,
        'options' => [
            'signature' => 0,
            'disablesmilies' => 0,
            'savecopy' => 0,
            'readreceipt' => 0
        ]
    ];

    if (isset($mybb->session)) {
        $privateMessageData['ipaddress'] = $mybb->session->packedip;
    }

    require_once MYBB_ROOT . 'inc/datahandlers/pm.php';

    $pmDataHandler = new PMDataHandler();

    // Admin override
    $pmDataHandler->admin_override = true;

    $pmDataHandler->set_data($privateMessageData);

    if ($pmDataHandler->validate_pm()) {
        $pmDataHandler->insert_pm();

        return true;
    }

    return false;
}

function myAlertsInitiate(): bool
{
    if (!function_exists('myalerts_info')) {
        return false;
    }

    if (class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter')) {
        require_once ROOT . '/class_alerts.php';
    }

    if (version_compare(myalerts_info()['version'], getSetting('myAlertsVersion')) <= 0) {
        myalerts_register_client_alert_formatters();
    }

    return true;
}

function sendAlert(int $newStatus, array $threadIDs)
{
    if (!class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        return false;
    }

    global $lang, $mybb, $alertType, $db;

    loadLanguage();

    if (
        !getSetting('notifyByAlert') ||
        empty($mybb->cache->cache['plugins']['active']['myalerts'])
    ) {
        return false;
    }

    $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('ougc_showinportal');

    if (empty($alertType) || !$alertType->getEnabled()) {
        return false;
    }

    $threadIDs = implode(
        "','",
        array_map('intval', $threadIDs)
    );

    $threadsData = [];

    $dbQuery = $db->simple_select(
        'threads',
        'tid, uid',
        "tid IN ('{$threadIDs}')"
    ); // visibility checks?  AND visible='1'

    while ($threadData = $db->fetch_array($dbQuery)) {
        $threadsData[(int)$threadData['tid']] = (int)$threadData['uid'];
    }

    foreach ($threadsData as $threadID => $userID) {
        // Check if already alerted
        $dbQuery = $db->simple_select(
            'alerts',
            '*',
            "object_id='{$threadID}' AND uid='{$userID}' AND unread=1 AND alert_type_id='{$alertType->getId()}'"
        );

        if ($db->num_rows($dbQuery)) {
            continue;
        }

        $alert = new MybbStuff_MyAlerts_Entity_Alert($userID, $alertType, $threadID);

        $alert->setExtraDetails(
            [
                'type' => $newStatus === STATUS_SHOW ? 'display' : 'remove'
            ]
        );

        MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
    }
}

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com )
function control_object(&$obj, string $code)
{
    static $cnt = 0;
    $newname = '_objcont_newpoints_' . (++$cnt);
    $objserial = serialize($obj);
    $classname = get_class($obj);
    $checkstr = 'O:' . strlen($classname) . ':"' . $classname . '":';
    $checkstr_len = strlen($checkstr);
    if (substr($objserial, 0, $checkstr_len) == $checkstr) {
        $vars = [];
        // grab resources/object etc, stripping scope info from keys
        foreach ((array)$obj as $k => $v) {
            if ($p = strrpos($k, "\0")) {
                $k = substr($k, $p + 1);
            }
            $vars[$k] = $v;
        }
        if (!empty($vars)) {
            $code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
        }
        eval('class ' . $newname . ' extends ' . $classname . ' {' . $code . '}');
        $obj = unserialize('O:' . strlen($newname) . ':"' . $newname . '":' . substr($objserial, $checkstr_len));
        if (!empty($vars)) {
            $obj->___setvars($vars);
        }
    }
    // else not a valid object or PHP serialize has changed
}

// explicit workaround for PDO, as trying to serialize it causes a fatal error (even though PHP doesn't complain over serializing other resources)
if ($GLOBALS['db'] instanceof AbstractPdoDbDriver) {
    $GLOBALS['AbstractPdoDbDriver_lastResult_prop'] = new ReflectionProperty('AbstractPdoDbDriver', 'lastResult');
    $GLOBALS['AbstractPdoDbDriver_lastResult_prop']->setAccessible(true);
    function control_db(string $code)
    {
        global $db;
        $linkvars = [
            'read_link' => $db->read_link,
            'write_link' => $db->write_link,
            'current_link' => $db->current_link,
        ];
        unset($db->read_link, $db->write_link, $db->current_link);
        $lastResult = $GLOBALS['AbstractPdoDbDriver_lastResult_prop']->getValue($db);
        $GLOBALS['AbstractPdoDbDriver_lastResult_prop']->setValue($db, null); // don't let this block serialization
        control_object($db, $code);
        foreach ($linkvars as $k => $v) {
            $db->$k = $v;
        }
        $GLOBALS['AbstractPdoDbDriver_lastResult_prop']->setValue($db, $lastResult);
    }
} elseif ($GLOBALS['db'] instanceof DB_SQLite) {
    function control_db(string $code)
    {
        global $db;
        $oldLink = $db->db;
        unset($db->db);
        control_object($db, $code);
        $db->db = $oldLink;
    }
} else {
    function control_db(string $code)
    {
        control_object($GLOBALS['db'], $code);
    }
}