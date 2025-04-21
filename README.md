<p align="center">
    <a href="" rel="noopener">
        <img width="700" height="400" src="https://github.com/OUGC-Network/OUGC-Show-in-Portal/assets/1786584/43e1a95c-ffa2-4c7f-9174-65db36913565" alt="Project logo">
    </a>
</p>

<h3 align="center">ougc Show In Portal</h3>

<div align="center">

[![Status](https://img.shields.io/badge/status-active-success.svg)]()
[![GitHub Issues](https://img.shields.io/github/issues/OUGC-Network/OUGC-Show-in-Portal.svg)](./issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/OUGC-Network/OUGC-Show-in-Portal.svg)](./pulls)
[![License](https://img.shields.io/badge/license-GPL-blue)](/LICENSE)

</div>

---

<p align="center"> Allow moderators to choose what threads to display in the portal.
    <br> 
</p>

## 📜 Table of Contents <a name = "table_of_contents"></a>

- [About](#about)
- [Getting Started](#getting_started)
    - [Dependencies](#dependencies)
    - [File Structure](#file_structure)
    - [Install](#install)
    - [Update](#update)
    - [Template Modifications](#template_modifications)
- [Settings](#settings)
- [Usage](#usage)
- [Built Using](#built_using)
- [Authors](#authors)
- [Acknowledgments](#acknowledgement)
- [Support & Feedback](#support)

## 🚀 About <a name = "about"></a>

An essential tool empowering forum moderators with enhanced control over thread visibility in the portal landing page.
This intuitive plugin allows specific moderator groups to easily mark threads for display in the portal. Users benefit
from a 'read more' tag that links to the complete full thread to continue reading and participate in the discussion.
Additionally, you will have the option to send private messages or MyAlerts notifications to thread authors, informing
them about their thread's status on the portal. With added functionality to filter portal threads by forum and custom
moderation tools, this plugin ensures efficient and effective thread management for a dynamic forum experience.

[Go up to Table of Contents](#table_of_contents)

## 📍 Getting Started <a name = "getting_started"></a>

The following information will assist you into getting a copy of this plugin up and running on your forum.

### Dependencies <a name = "dependencies"></a>

A setup that meets the following requirements is necessary to use this plugin.

- [MyBB](https://mybb.com/) >= 1.8
- PHP >= 7.0
- [PluginLibrary for MyBB](https://github.com/frostschutz/MyBB-PluginLibrary) >= 13
- [MyAlerts](https://community.mybb.com/thread-171301.html) >= 2.0.4 (Optional)

### File structure <a name = "file_structure"></a>

  ```
   .
   ├── inc
   │ ├── plugins
   │ │ ├── ougc
   │ │ │ ├── ShowInPortal
   │ │ │ │ ├── Hooks
   │ │ │ │ │ ├── Admin.php
   │ │ │ │ │ ├── Forum.php
   │ │ │ │ ├── Templates
   │ │ │ │ │ ├── editPost.html
   │ │ │ │ │ ├── newReply.html
   │ │ │ │ │ ├── newThread.html
   │ │ │ │ │ ├── quickReply.html
   │ │ │ │ ├── settings.json
   │ │ │ │ ├── Admin.php
   │ │ │ │ ├── Core.php
   │ │ ├── ougc_showinportal.php
   │ ├── languages
   │ │ ├── espanol
   │ │ │ ├── admin
   │ │ │ │ ├── ougc_showinportal.lang.php
   │ │ │ ├── ougc_showinportal.lang.php
   │ │ ├── english
   │ │ │ ├── admin
   │ │ │ │ ├── ougc_showinportal.lang.php
   │ │ │ ├── ougc_showinportal.lang.php
   ```

### Installing <a name = "install"></a>

Follow the next steps in order to install a copy of this plugin on your forum.

1. Download the latest package from the [MyBB Extend](https://community.mybb.com/mods.php?action=view&pid=399) site or
   from
   the [repository releases](https://github.com/OUGC-Network/OUGC-Show-in-Portal/releases/latest).
2. Upload the contents of the _Upload_ folder to your MyBB root directory.
3. Browse to _Configuration » Plugins_ and install this plugin by clicking _Install & Activate_.

### Updating <a name = "update"></a>

Follow the next steps in order to update your copy of this plugin.

1. Browse to _Configuration » Plugins_ and deactivate this plugin by clicking _Deactivate_.
2. Follow step 1 and 2 from the [Install](#install) section.
3. Browse to _Configuration » Plugins_ and activate this plugin by clicking _Activate_.

**If you are updating to <ins>1.8.36</ins> from any previous version:**

1. After following the above steps, make sure to review your settings and templates as they were updated for this
   version.

### Template Modifications <a name = "template_modifications"></a>

To display the moderation option it is required that you edit the following templates for each of
your themes.

1. Open the `editpost_postoptions` template for editing.
2. Add `<!--OUGC_SHOWINPORTAL-->` after `{$signature}`.
3. Save the template.
4. Open the `newreply_modoptions` template for editing.
5. Add `<!--OUGC_SHOWINPORTAL-->` after `{$stickoption}`.
6. Save the template.
7. Open the `newthread_postoptions` template for editing.
8. Add `<!--OUGC_SHOWINPORTAL-->` after `{$disablesmilies}`.
9. Save the template.
10. Open the `showthread_quickreply` template for editing.
11. Add `<!--OUGC_SHOWINPORTAL-->` after `{$closeoption}`.
12. Save the template.

[Go up to Table of Contents](#table_of_contents)

## 🛠 Settings <a name = "settings"></a>

Below you can find a description of the plugin settings.

### Global Settings

- **Allowed Groups** `select`
    - _Select the groups allowed to use this feature._
- **Enabled Forums** `select`
    - _Select the forums where this feature should be enabled in._
- **Enable Read More Tag** `yesNo` Default: `yes`
    - _Enable users using a tag to cut portal messages._
- **Read More Tag String** `text` Default: `[!--more--]`
    - _Insert the string used to build the read more feature._
- **Send PM Notification** `yesNo` Default: `no`
    - _Send a PM to authors when moderators add or remove their threads from the portal._
- **Send MyAlerts Notification** `yesNo` Default: `no`
    - _Send an alert to authors when moderators add or remove their threads from the portal._
- **Enable Forum Filtering** `yesNo` Default: `no`
    - _Enable this to allow portal threads to be filtered by forum. Query parameter is `forumID`,
      example: `./portal.php?forumID=2`._

[Go up to Table of Contents](#table_of_contents)

## 📖 Usage <a name="usage"></a>

### Custom Moderation Tools

It is possible to change the status of threads using custom moderation tools. For this follow the next steps.

1. Go to the Administrator Control Panel, add a new custom moderation tool from _Home » Moderator Tools » Add New Thread
   Tool_.
2. Fill down the form, go down below to the _Show in Portal_ row under the _Thread Moderation_ table.
3. Select one of the presented options.
    - **No Change** _Default_
    - **Show** _Adds threads to the portal._
    - **Hide** _Removes threads from the portal._
    - **Toggle** _Toggles the status of threads._

**Forum display thread list and Search results page**

You will now be able to change or toggle the status of multiple threads using the _Inline Thread Moderation_ dropdown
menu.

**Show thread page**

You will now be able to change or toggle the status of a threads using the _Moderation Options_ dropdown
menu.

[Go up to Table of Contents](#table_of_contents)

## ⛏ Built Using <a name = "built_using"></a>

- [MyBB](https://mybb.com/) - Web Framework
- [MyBB PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) - A collection of useful functions for MyBB
- [PHP](https://www.php.net/) - Server Environment

[Go up to Table of Contents](#table_of_contents)

## ✍️ Authors <a name = "authors"></a>

- [@Omar G](https://github.com/Sama34) - Idea & Initial work

See also the list of [contributors](https://github.com/OUGC-Network/OUGC-Show-in-Portal/contributors)
who participated
in this project.

[Go up to Table of Contents](#table_of_contents)

## 🎉 Acknowledgements <a name = "acknowledgement"></a>

- [The Documentation Compendium](https://github.com/kylelobo/The-Documentation-Compendium)

[Go up to Table of Contents](#table_of_contents)

## 🎈 Support & Feedback <a name="support"></a>

This is free development and any contribution is welcome. Get support or leave feedback at the
official [MyBB Community](https://community.mybb.com/thread-221815.html).

Thanks for downloading and using our plugins!

[Go up to Table of Contents](#table_of_contents)