<?php

/***************************************************************************
 *
 *    ougc Show in Portal plugin (/inc/plugins/ougc_showinportal.php)
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

use function ougc\ShowInPortal\Admin\pluginActivate;
use function ougc\ShowInPortal\Admin\pluginInfo;
use function ougc\ShowInPortal\Admin\pluginIsInstalled;
use function ougc\ShowInPortal\Admin\pluginUninstall;
use function ougc\ShowInPortal\Core\addHooks;
use function ougc\ShowInPortal\Core\loadLanguage;

use const ougc\ShowInPortal\ROOT;

if (!defined('IN_MYBB')) {
    die('This file cannot be accessed directly.');
}

define('ougc\ShowInPortal\ROOT', MYBB_ROOT . 'inc/plugins/ougc/ShowInPortal');

// Plugin Settings
define('ougc\ShowInPortal\Core\SETTINGS', [
    //'allowedGroups' => '-1'
    'myAlertsVersion' => '2.1.0'
]);

// PLUGINLIBRARY
if (!defined('PLUGINLIBRARY')) {
    define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');
}

require_once ROOT . '/Core.php';

if (defined('IN_ADMINCP')) {
    require_once ROOT . '/Admin.php';
    require_once ROOT . '/Hooks/Admin.php';

    addHooks('ougc\ShowInPortal\Hooks\Admin');
} else {
    require_once ROOT . '/Hooks/Forum.php';

    addHooks('ougc\ShowInPortal\Hooks\Forum');
}

require_once ROOT . '/Core.php';

// Plugin API
function ougc_showinportal_info(): array
{
    return pluginInfo();
}

function ougc_showinportal_activate(): bool
{
    return pluginActivate();
}

// _is_installed() routine
function ougc_showinportal_is_installed(): bool
{
    return pluginIsInstalled();
}

// _uninstall() routine
function ougc_showinportal_uninstall(): bool
{
    return pluginUninstall();
}