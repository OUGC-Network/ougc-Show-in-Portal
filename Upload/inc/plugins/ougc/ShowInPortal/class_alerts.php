<?php

/***************************************************************************
 *
 *    ougc Show in Portal plugin (/inc/plugins/ougc/ShowInPortal/class_alerts.php)
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

use MybbStuff_MyAlerts_Entity_Alert;
use MybbStuff_MyAlerts_Formatter_AbstractFormatter;

class MyAlertsFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
    public function init(): bool
    {
        loadLanguage();

        return true;
    }

    /**
     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
     *
     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
     *
     * @return string The formatted alert string.
     */
    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert): string
    {
        global $parser, $mybb;

        $Details = $alert->toArray();
        $ExtraDetails = $alert->getExtraDetails();

        $FromUser = $alert->getFromUser();

        $lang_var = 'ougc_showinportal_myalerts_display';
        if ($ExtraDetails['type'] == 'remove') {
            $lang_var = 'ougc_showinportal_myalerts_removed';
        }

        $subject = '';
        if ($thread = get_thread($Details['object_id'])) {
            require_once MYBB_ROOT . 'inc/class_parser.php';
            ($parser instanceof PostDataHandler) || $parser = new postParser();

            $thread['threadprefix'] = $thread['displayprefix'] = '';
            if ($thread['prefix']) {
                $threadprefix = build_prefixes($thread['prefix']);

                if (!empty($threadprefix['prefix'])) {
                    $thread['threadprefix'] = htmlspecialchars_uni($threadprefix['prefix']) . '&nbsp;';
                    $thread['displayprefix'] = $threadprefix['displaystyle'] . '&nbsp;';
                }
            }

            $subject = $parser->parse_badwords($thread['subject']);
            $subject = $thread['displayprefix'] . htmlspecialchars_uni($thread['subject']);
        }

        $username = format_name($mybb->user['username'], $mybb->user['usergroup'], $mybb->user['displaygroup']);

        return $this->lang->sprintf($this->lang->{$lang_var}, $username, $outputAlert['from_user'], $subject);
    }

    /**
     * Build a link to an alert's content so that the system can redirect to it.
     *
     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
     *
     * @return string The built alert, preferably an absolute link.
     */
    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert): string
    {
        global $settings;

        $Details = $alert->toArray();

        return $settings['bburl'] . '/' . get_thread_link($Details['object_id']);
    }
}