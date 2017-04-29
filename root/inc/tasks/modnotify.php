<?php
/**
 * This file is part of Mod Notify plugin for MyBB.
 * Copyright (C) 2010-2013 Lukasz Tkacz <lukasamd@gmail.com>
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

function task_modnotify($task)
{
    global $db, $lang;

    // Check table, class and settings
    if (!$db->table_exists("mod_notify")) {
        return;
    }

    require_once MYBB_ROOT . '/inc/plugins/modNotify.php';

    // Initialize all Mod Notify data
    modNotify::init();
    $quote = [];
    $ids = [];

    $result = $db->simple_select('mod_notify', '*');
    while ($row = $db->fetch_array($result)) {
        $quote[$row['uid']][$row['mod_id']][] = array(
            'subject' => $row['subject'],
            'message' => $row['message'],
            'from_id' => $row['from_id'],
        );

        $ids[] = $row['id'];
    }


    // Is there any pms to send? 
    if (!sizeof($ids)) {
        add_task_log($task, $lang->modNotifyTaskLog);
        return;
    }

    // First loop - user data 
    foreach ($quote as $uid => $data) {
        $user = modNotify::getData($uid, 'user');

        if (!$user['allownotices']) {
            continue;
        }

        if (modNotify::isIgnored($user)) {
            continue;
        }

        // Second loop - moderator data
        foreach ($data as $mod_id => $messages) {
            $moderator = modNotify::getData($mod_id, 'user');

            $message = $user['username'] . ",\n";
            $countMessages = sizeof($messages);
            $countMessages--;

            if ($countMessages > 0) {
                $subject = $lang->modNotifyMultiSubject;
            } else {
                $subject = $messages[0]['subject'];
            }

            // Third loop - messages data
            for ($i = 0; $i <= $countMessages; $i++) {
                $message .= str_replace('{USERNAME}', htmlspecialchars_decode($moderator['link']), $messages[$i]['message']);

                if ($i < $countMessages) {
                    $message .= "\n\n[hr]\n\n\n";
                }
            }

            // Add global signature
            $message .= "\n" . modNotify::getConfig('modNotifySignature');
            // Get "from" user id
            $from_id = $messages[0]['from_id'];

            // Send pm using build-in pm datahandler
            $pmhandler = new PMDataHandler();
            $pm = array(
                'subject' => $subject,
                'message' => $message,
                'icon' => 0,
                'fromid' => $from_id,
                'toid' => array($uid),
                'do' => '',
                'pmid' => ''
            );

            $pm['options'] = array(
                'savecopy' => 'no',
                'signature' => 'no',
                'disablesmilies' => 'no',
                'readreceipt' => 'no',
            );

            $pmhandler->admin_override = true;
            $pmhandler->set_data($pm);

            // Now let the pm handler do all the hard work.
            if ($pmhandler->validate_pm()) {
                $pmhandler->insert_pm();
            }
        }

        update_pm_count($uid);
    }

    $db->delete_query('mod_notify', 'id IN (' . implode(',', $ids) . ')');
    add_task_log($task, $lang->modNotifyTaskLog);
}
