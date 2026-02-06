<?php

/***************************************************************************
 *
 *    ougc Show in Portal plugin (/inc/plugins/ougc/ShowInPortal/Hooks/Admin.php)
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

namespace ougc\ShowInPortal\Hooks\Admin;

use MyBB;

use function ougc\ShowInPortal\Core\loadLanguage;

use const ougc\ShowInPortal\Core\INPUT_HIDE;
use const ougc\ShowInPortal\Core\INPUT_NOCHANGE;
use const ougc\ShowInPortal\Core\INPUT_SHOW;
use const ougc\ShowInPortal\Core\INPUT_TOGGLE;

function admin_config_plugins_deactivate(): bool
{
    global $mybb, $page;

    if (
        $mybb->get_input('action') != 'deactivate' ||
        $mybb->get_input('plugin') != 'ougc_showinportal' ||
        !$mybb->get_input('uninstall', MyBB::INPUT_INT)
    ) {
        return false;
    }

    if ($mybb->request_method != 'post') {
        $page->output_confirm_action(
            'index.php?module=config-plugins&amp;action=deactivate&amp;uninstall=1&amp;plugin=ougc_showinportal'
        );
    }

    if ($mybb->get_input('no')) {
        admin_redirect('index.php?module=config-plugins');
    }

    return true;
}

function admin_config_settings_start()
{
    loadLanguage();
}

function admin_style_templates_set()
{
    loadLanguage();
}

function admin_config_settings_change()
{
    loadLanguage();
}

// Moderator Tools
function admin_formcontainer_end(array &$current_hook_arguments): array
{
    global $mybb, $run_module, $form_container, $lang;

    if (!($run_module == 'config' && !empty($form_container->_title) && !empty($lang->thread_moderation) && $form_container->_title == $lang->thread_moderation && $mybb->get_input(
            'action'
        ) != 'add_post_tool' && $mybb->get_input('action') != 'edit_post_tool')) {
        return $current_hook_arguments;
    }

    global $form;

    loadLanguage();

    if ($mybb->get_input('action') !== 'add_thread_tool' && !isset($mybb->input['showinportal'])) {
        global $thread_options;

        $mybb->input['showinportal'] = (int)($thread_options['showinportal'] ?? 0);
    }

    $form_container->output_row(
        $lang->ougc_showinportal_modtool . ' <em>*</em>',
        '',
        $form->generate_select_box('showinportal', [
            INPUT_NOCHANGE => $lang->no_change,
            INPUT_SHOW => $lang->ougc_showinportal_modtool_show,
            INPUT_HIDE => $lang->ougc_showinportal_modtool_remove,
            INPUT_TOGGLE => $lang->toggle
        ], $mybb->get_input('showinportal', MyBB::INPUT_INT), ['id' => 'showinportal']),
        'showinportal'
    );

    return $current_hook_arguments;
}

// Save moderator tools input
function admin_config_mod_tools_add_thread_tool_commit()
{
    global $mybb;

    if ($mybb->request_method == 'post') {
        global $db, $thread_options, $update_tool, $new_tool;

        $showInPortal = $mybb->get_input('showinportal', MyBB::INPUT_INT);

        $thread_options['showinportal'] = ($showInPortal > 3 || $showInPortal < 0 ? 0 : $showInPortal);

        if ($mybb->get_input('action') == 'add_thread_tool') {
            $new_tool['threadoptions'] = $db->escape_string(serialize($thread_options));
        } else {
            $update_tool['threadoptions'] = $db->escape_string(serialize($thread_options));
        }

        if ($mybb->get_input('action') == 'add_thread_tool') {
            global $tid;

            if ($mybb->get_input('action') == 'add_thread_tool') {
                $db->update_query('modtools', $new_tool, 'tid=\'' . $tid . '\'');
            } else {
                $db->update_query('modtools', $update_tool, 'tid=\'' . $tid . '\'');
            }
        }
    }
}

function admin_config_mod_tools_edit_thread_tool_commit()
{
    admin_config_mod_tools_add_thread_tool_commit();
}