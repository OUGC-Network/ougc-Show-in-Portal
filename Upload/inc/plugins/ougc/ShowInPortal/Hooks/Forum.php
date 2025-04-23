<?php

/***************************************************************************
 *
 *    ougc Show in Portal plugin (/inc/plugins/ougc/ShowInPortal/Hooks/Forums.php)
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

namespace ougc\ShowInPortal\Hooks\Forum;

use MyBB;
use MybbStuff_MyAlerts_AlertFormatterManager;
use ougc\ShowInPortal\Core\MyAlertsFormatter;
use PostDataHandler;

use function ougc\ShowInPortal\Core\control_db;
use function ougc\ShowInPortal\Core\loadLanguage;
use function ougc\ShowInPortal\Core\cutOffMessage;
use function ougc\ShowInPortal\Core\isModerator;
use function ougc\ShowInPortal\Core\getTemplate;
use function ougc\ShowInPortal\Core\getSetting;
use function ougc\ShowInPortal\Core\moderationControlExecute;
use function ougc\ShowInPortal\Core\myAlertsInitiate;
use function ougc\ShowInPortal\Core\updateThreadStatus;

use const ougc\ShowInPortal\Core\STATUS_HIDE;
use const ougc\ShowInPortal\Core\STATUS_SHOW;

function global_start05()
{
    myAlertsInitiate();

    global $templatelist;

    if (!isset($templatelist)) {
        $templatelist = '';
    } else {
        $templatelist .= ',';
    }

    switch (THIS_SCRIPT) {
        case 'showthread.php';
            $templatelist .= 'ougcshowinportal_quickReply';
            break;
        case 'newthread.php';
            $templatelist .= 'ougcshowinportal_newThread';
            break;
        case 'newreply.php';
            $templatelist .= 'ougcshowinportal_newReply';
            break;
        case 'editpost.php';
            $templatelist .= 'ougcshowinportal_editPost';
            break;
    }
}

function newthread_end()
{
    global $plugins, $lang, $mybb;

    $inputName = 'modoptions';

    $templateName = 'newThread';

    if ($plugins->current_hook === 'showthread_end') {
        global $quickreply;

        $modoptions = &$quickreply;

        $templateName = 'quickReply';
    } elseif ($plugins->current_hook === 'editpost_end') {
        global $postoptions;

        $modoptions = &$postoptions;

        $templateName = 'editPost';

        $inputName = 'postoptions';
    } elseif ($plugins->current_hook === 'newthread_end') {
        global $modoptions;

        $forumID = $mybb->get_input('fid', MyBB::INPUT_INT);
    } elseif ($plugins->current_hook === 'newreply_end') {
        global $modoptions;

        $templateName = 'newReply';
    }

    if ($plugins->current_hook !== 'newthread_end') {
        global $thread;

        $forumID = (int)$thread['fid'];
    }

    if (!isModerator($forumID)) {
        return false;
    }

    if ($plugins->current_hook == 'editpost_end' && (int)$thread['firstpost'] !== $mybb->get_input(
            'pid',
            MYBB::INPUT_INT
        )) {
        return false;
    }

    if (!isset($modoptions) || my_strpos($modoptions, '<!--OUGC_SHOWINPORTAL-->') === false) {
        return false;
    }

    loadLanguage();

    $displayStatus = STATUS_HIDE; // newthread_end

    $moderationOptions = $mybb->get_input($inputName, MYBB::INPUT_ARRAY);

    if ($mybb->request_method == 'post') {
        if (
            !isset($inputName) ||
            (isset($moderationOptions['showinportal']) && (int)$moderationOptions['showinportal'] === STATUS_SHOW)
        ) {
            $displayStatus = STATUS_SHOW;
        }
    } elseif ($plugins->current_hook !== 'newthread_end') {
        $displayStatus = (int)$thread['showinportal'];
    }

    $checkAttribute = '';

    if ($displayStatus) {
        $checkAttribute = ' checked="checked"';
    }

    $modoptions = str_replace(
        '<!--OUGC_SHOWINPORTAL-->',
        eval(getTemplate($templateName)),
        $modoptions
    );
}

function showthread_end()
{
    newthread_end();
}

function newreply_end()
{
    newthread_end();
}

function editpost_end()
{
    newthread_end();
}

function datahandler_post_insert_thread(&$dh)
{
    $dh->thread_insert_data['showinportal'] = 0;

    if (is_moderator($dh->data['fid'])) {
        if (isModerator((int)$dh->data['fid']) && !empty($dh->data['modoptions']['showinportal'])) {
            $dh->thread_insert_data['showinportal'] = 1;
        }
    }
}

function datahandler_post_insert_post(PostDataHandler &$dataHandler): PostDataHandler
{
    global $mybb, $plugins;

    if (!isModerator((int)$dataHandler->data['fid'])) {
        return $dataHandler;
    }

    $threadData = get_thread($dataHandler->data['tid']);

    if ($plugins->current_hook === 'datahandler_post_update') {
        if (THIS_SCRIPT === 'xmlhttp.php' || empty($dataHandler->first_post)) {
            return $dataHandler;
        }

        $options = $mybb->get_input('postoptions', MYBB::INPUT_ARRAY);
    } else {
        $options = $dataHandler->data['modoptions'];
    }

    if (!isset($options['showinportal'])) {
        return $dataHandler;
    }

    $inputValue = STATUS_HIDE;

    if ((int)$options['showinportal'] === STATUS_SHOW) {
        $inputValue = STATUS_SHOW;
    }

    updateThreadStatus([$threadData['tid']], $inputValue);

    return $dataHandler;
}

//datahandler_post_insert_merge

function datahandler_post_update(PostDataHandler &$dataHandler): PostDataHandler
{
    return datahandler_post_insert_post($dataHandler);
}

function postbit(&$post): array
{
    global $settings;
    global $thread;

    if (
        (int)$thread['firstpost'] === (int)$post['pid'] &&
        !empty($thread['showinportal']) &&
        getSetting('enableReadMore') && getSetting('readMoreTag')
    ) {
        $post['message'] = preg_replace(
            '#' . preg_quote(getSetting('readMoreTag')) . '#',
            '',
            $post['message']
        );
    }

    return $post;
}

function portal_start()
{
    global $mybb;

    $forumID = $mybb->get_input('forumID', MyBB::INPUT_INT);

    if (getSetting('enableForumFilter') && $forumID) {
        control_db(
            'function query($string, $hide_errors = 0, $write_query = 0)
    {
        if (!$write_query && strpos($string, "ORDER BY t.dateline DESC")) {
            $string = strtr($string, array(
                "t.closed" => "t.fid=\'' . $forumID . '\' AND t.closed"
            ));
        }
        if (!$write_query && strpos($string, "OUNT(t.tid) AS thread")) {
            $string = strtr($string, array(
                "t.visible" => "t.fid=\'' . $forumID . '\' AND t.visible"
            ));
        }
        return parent::query($string, $hide_errors, $write_query);
    }'
        );
    }

    control_db(
        'function query($string, $hide_errors = 0, $write_query = 0)
    {
        if (!$write_query && strpos($string, "ORDER BY t.dateline DESC")) {
            $string = strtr($string, array(
                "t.closed" => "t.showinportal=\'1\' AND t.closed"
            ));
        }
        if (!$write_query && strpos($string, "OUNT(t.tid) AS thread")) {
            $string = strtr($string, array(
                "t.visible" => "t.showinportal=\'1\' AND t.visible"
            ));
        }
        return parent::query($string, $hide_errors, $write_query);
    }'
    );
}

function portal_announcement()
{
    if (getSetting('enableReadMore') && getSetting('readMoreTag')) {
        global $announcement;

        cutOffMessage($announcement['message'], (int)$announcement['fid'], (int)$announcement['tid']);
    }
}

function syndication_start()
{
    global $mybb;

    if (!($mybb->get_input('portal') && $mybb->settings['portal'])) {
        return;
    }

    control_db(
        'function simple_select($table, $fields = "*", $conditions = "", $options = array())
    {
        if ($table == "threads" && strpos($fields, "subject, tid, dateline") !== false) {
            $conditions = strtr($conditions, array(
                "visible" => "showinportal=\'1\' AND visible"
            ));
        }
        
        return parent::simple_select($table, $fields, $conditions, $options);
    }'
    );

    if (getSetting('enableReadMore') && getSetting('readMoreTag')) {
        global $ShowInportalCutOffMessage;

        $ShowInportalCutOffMessage = true;
    }
}

function parse_message_start(&$message)
{
    global $ShowInportalCutOffMessage;

    if (isset($ShowInportalCutOffMessage)) {
        global $post;

        cutOffMessage($message, (int)$post['fid'], (int)$post['tid']);
    }
}

function xmlhttp_update_post()
{
    myAlertsInitiate();

    global $post;

    postbit($post);
}

function myalerts_register_client_alert_formatters(): bool
{
    if (
        class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter') &&
        class_exists('MybbStuff_MyAlerts_AlertFormatterManager') &&
        class_exists('ougc\Awards\Core\MyAlertsFormatter')
    ) {
        global $mybb, $lang;

        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        if ($formatterManager) {
            $formatterManager->registerFormatter(
                new MyAlertsFormatter($mybb, $lang, 'ougc_showinportal')
            );
        }
    }

    return true;
}

function class_custommoderation_execute_thread_moderation_start(array &$hookArguments): array
{
    if (isset($hookArguments['thread_options']['showinportal'])) {
        moderationControlExecute((array)$hookArguments['tids'], (int)$hookArguments['thread_options']['showinportal']);
    }

    return $hookArguments;
}