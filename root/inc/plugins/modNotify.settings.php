<?php
/**
 * This file is part of Mod Notify plugin for MyBB.
 * Copyright (C) Lukasz Tkacz <lukasamd@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Disallow direct access to this file for security reasons
 *
 */
if (!defined("IN_MYBB")) exit;

/**
 * Plugin Installator Class
 *
 */
class modNotifyInstaller
{

    public static function install()
    {
        global $db, $lang, $mybb;
        self::uninstall();

        $result = $db->simple_select('settinggroups', 'MAX(disporder) AS max_disporder');
        $max_disporder = $db->fetch_field($result, 'max_disporder');
        $disporder = 1;

        $settings_group = array(
            'name' => 'modNotify',
            'title' => $db->escape_string($lang->modNotifyName),
            'description' => $db->escape_string($lang->modNotifyGroupDesc),
            'disporder' => $max_disporder + 1,
            'isdefault' => '0'
        );
        $db->insert_query('settinggroups', $settings_group);
        $gid = (int)$db->insert_id();


        $setting_array = array(
            'modNotifyFromID' => array(
                'title' => $db->escape_string($lang->modNotifyFromID),
                'description' => $db->escape_string($lang->modNotifyFromIDDesc),
                'optionscode' => 'text',
                'value' => $mybb->user['uid'],
                'disporder' => $disporder++,
            ),

            'modNotifyCloseThreads' => array(
                'name' => 'modNotifyCloseThreads',
                'title' => $db->escape_string($lang->modNotifyCloseThreads),
                'description' => $db->escape_string($lang->modNotifyCloseThreadsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyOpenThreads' => array(
                'name' => 'modNotifyOpenThreads',
                'title' => $db->escape_string($lang->modNotifyOpenThreads),
                'description' => $db->escape_string($lang->modNotifyOpenThreadsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyDeleteThread' => array(
                'name' => 'modNotifyDeleteThread',
                'title' => $db->escape_string($lang->modNotifyDeleteThread),
                'description' => $db->escape_string($lang->modNotifyDeleteThreadDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyStickyThreads' => array(
                'name' => 'modNotifyStickyThreads',
                'title' => $db->escape_string($lang->modNotifyStickyThreads),
                'description' => $db->escape_string($lang->modNotifyStickyThreadsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyUnstickyThreads' => array(
                'name' => 'modNotifyUnstickyThreads',
                'title' => $db->escape_string($lang->modNotifyUnstickyThreads),
                'description' => $db->escape_string($lang->modNotifyUnstickyThreadsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyApprovePosts' => array(
                'name' => 'modNotifyApprovePosts',
                'title' => $db->escape_string($lang->modNotifyApprovePosts),
                'description' => $db->escape_string($lang->modNotifyApprovePostsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyUnapprovePosts' => array(
                'name' => 'modNotifyUnapprovePosts',
                'title' => $db->escape_string($lang->modNotifyUnapprovePosts),
                'description' => $db->escape_string($lang->modNotifyUnapprovePostsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyApproveThreads' => array(
                'name' => 'modNotifyApproveThreads',
                'title' => $db->escape_string($lang->modNotifyApproveThreads),
                'description' => $db->escape_string($lang->modNotifyApproveThreadsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyUnapproveThreads' => array(
                'name' => 'modNotifyUnapproveThreads',
                'title' => $db->escape_string($lang->modNotifyUnapproveThreads),
                'description' => $db->escape_string($lang->modNotifyUnapproveThreadsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyThreadSubject' => array(
                'name' => 'modNotifyThreadSubject',
                'title' => $db->escape_string($lang->modNotifyThreadSubject),
                'description' => $db->escape_string($lang->modNotifyThreadSubjectDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyDeletePoll' => array(
                'name' => 'modNotifyDeletePoll',
                'title' => $db->escape_string($lang->modNotifyDeletePoll),
                'description' => $db->escape_string($lang->modNotifyDeletePollDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyDeletePost' => array(
                'name' => 'modNotifyDeletePost',
                'title' => $db->escape_string($lang->modNotifyDeletePost),
                'description' => $db->escape_string($lang->modNotifyDeletePostDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyCopyThread' => array(
                'name' => 'modNotifyCopyThread',
                'title' => $db->escape_string($lang->modNotifyCopyThread),
                'description' => $db->escape_string($lang->modNotifyCopyThreadDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyMoveRedirect' => array(
                'name' => 'modNotifyMoveRedirect',
                'title' => $db->escape_string($lang->modNotifyMoveRedirect),
                'description' => $db->escape_string($lang->modNotifyMoveRedirectDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyMoveThreads' => array(
                'name' => 'modNotifyMoveThreads',
                'title' => $db->escape_string($lang->modNotifyMoveThreads),
                'description' => $db->escape_string($lang->modNotifyMoveThreadsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyMergeThreads' => array(
                'name' => 'modNotifyMergeThreads',
                'title' => $db->escape_string($lang->modNotifyMergeThreads),
                'description' => $db->escape_string($lang->modNotifyMergeThreadsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifySplitPosts' => array(
                'name' => 'modNotifySplitPosts',
                'title' => $db->escape_string($lang->modNotifySplitPosts),
                'description' => $db->escape_string($lang->modNotifySplitPostsDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifyReportRead' => array(
                'name' => 'modNotifyReportRead',
                'title' => $db->escape_string($lang->modNotifyReportRead),
                'description' => $db->escape_string($lang->modNotifyReportReadDesc),
                'optionscode' => 'onoff',
                'value' => '1',
                'disporder' => $disporder++,
            ),

            'modNotifySignature' => array(
                'name' => 'modNotifySignature',
                'title' => $db->escape_string($lang->modNotifySignature),
                'description' => $db->escape_string($lang->modNotifySignatureDesc),
                'optionscode' => 'textarea',
                'value' => $db->escape_string($lang->modNotifySignatureDefault),
                'disporder' => $disporder,
            ),
        );

        foreach ($setting_array as $name => $setting) {
            $setting['name'] = $name;
            $setting['gid'] = $gid;

            $db->insert_query('settings', $setting);
        }

        $task = array(
            'title' => $db->escape_string($lang->modNotifyTask),
            'description' => $db->escape_string($lang->modNotifyTaskDesc),
            'file' => 'modnotify',
            'minute' => '0,5,10,15,20,25,30,35,40,45,50,55',
            'hour' => '*',
            'day' => '*',
            'month' => '*',
            'weekday' => '*',
            'nextrun' => (time() + 300),
            'lastrun' => '0',
            'enabled' => '1',
            'logging' => '1',
            'locked' => '0'
        );
        $db->insert_query('tasks', $task);

        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "mod_notify (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            uid int(10) UNSIGNED NOT NULL,
            mod_id int(10) UNSIGNED NOT NULL,
            from_id int(10) UNSIGNED NOT NULL,
            subject text NOT NULL DEFAULT '',
            message text NOT NULL DEFAULT '',
            PRIMARY KEY (id)
        ) DEFAULT CHARSET=utf8;";
        $db->query($sql);

        rebuild_settings();
    }


    public static function uninstall()
    {
        global $db;

        $result = $db->simple_select('settinggroups', 'gid', "name = 'modNotify'");
        $gid = (int)$db->fetch_field($result, "gid");

        if ($gid > 0) {
            $db->delete_query('settings', "gid = '{$gid}'");
        }
        $db->delete_query('settinggroups', "gid = '{$gid}'");
        $db->delete_query('tasks', "file = 'modnotify'");

        if ($db->table_exists('mod_notify')) {
            $db->drop_table('mod_notify');
        }

        rebuild_settings();
    }

}
