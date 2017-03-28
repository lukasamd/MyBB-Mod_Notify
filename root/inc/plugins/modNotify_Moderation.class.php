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

if (!class_exists("Moderation"))
{
    require_once MYBB_ROOT . "inc/class_moderation.php";
}

/**
 * Mod Notify Moderation Plugin Class 
 */
class modNotify_Moderation extends Moderation
{
    /**
     * Action for approve posts function
     * 
     * @param array $pids List with posts id to approve
     */
    public function approve_posts($pids)
    {
        global $plugins;
        
        $plugins->run_hooks("class_modnotify_moderation_approve_posts", $pids);
        parent::approve_posts($pids);
    }
    
    /**
     * Action for unapprove posts function
     * 
     * @param array $pids List with posts id to unapprove
     */
    public function unapprove_posts($pids)
    {
        global $plugins;
        
        $plugins->run_hooks("class_modnotify_moderation_unapprove_posts", $pids);
        parent::unapprove_posts($pids);
    }
}
