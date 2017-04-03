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
 * Create plugin object
 *
 */
$plugins->add_hook("global_start", ['modNotify', 'addHooks']);


/**
 * Standard MyBB info function
 *
 */
function modNotify_info()
{
    global $lang;

    $lang->load('modNotify');

    $lang->modNotifyDesc = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float:right;">' .
        '<input type="hidden" name="cmd" value="_s-xclick">' .
        '<input type="hidden" name="hosted_button_id" value="3BTVZBUG6TMFQ">' .
        '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">' .
        '<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">' .
        '</form>' . $lang->modNotifyDesc;

    return array(
        'name' => $lang->modNotifyName,
        'description' => $lang->modNotifyDesc,
        'website' => 'https://tkacz.pro',
        'author' => 'Lukasz Tkacz',
        'authorsite' => 'https://tkacz.com',
        'version' => '1.8.0',
        'codename' => 'moderator_notifications',
        'compatibility' => '18*'
    );
}


/**
 * Standard MyBB installation functions
 *
 */
function modNotify_install()
{
    require_once('modNotify.settings.php');
    modNotifyInstaller::install();
}


function modNotify_is_installed()
{
    global $mybb;

    return (isset($mybb->settings['modNotifyCloseThreads']));
}


function modNotify_uninstall()
{
    require_once('modNotify.settings.php');
    modNotifyInstaller::uninstall();
}

/**
 * Plugin Class
 *
 */
class modNotify
{
    private static $subject = '';
    private static $message = '';

    // Store lang data (reference to $lang)
    private static $lang;
    // Store user data (reference to $mybb->user)
    private static $user;

    
    /**
     * Constructor - add plugin hooks
     */
    public static function addHooks()
    {
        global $plugins;

        $plugins->add_hook("moderation_start", ['modNotify', 'init']);
        $plugins->add_hook("class_moderation_close_threads", ['modNotify', 'closeThreads']);
        $plugins->add_hook("class_moderation_open_threads", ['modNotify', 'openThreads']);
        $plugins->add_hook("class_moderation_delete_thread_start", ['modNotify', 'deleteThread']);
        $plugins->add_hook("class_moderation_stick_threads", ['modNotify', 'stickyThreads']);
        $plugins->add_hook("class_moderation_unstick_threads", ['modNotify', 'unstickyThreads']);
        $plugins->add_hook("class_moderation_approve_threads", ['modNotify', 'approveThreads']);
        $plugins->add_hook("class_moderation_unapprove_threads", ['modNotify', 'unapproveThreads']);
        $plugins->add_hook("class_moderation_change_thread_subject", ['modNotify', 'threadSubject']);
        $plugins->add_hook("class_moderation_delete_poll", ['modNotify', 'deletePoll']);
        $plugins->add_hook("class_moderation_delete_post_start", ['modNotify', 'deletePost']);
        $plugins->add_hook("class_moderation_copy_thread", ['modNotify', 'copyThread']);
        $plugins->add_hook("class_moderation_move_simple", ['modNotify', 'moveRedirect']);
        $plugins->add_hook("class_moderation_move_thread_redirect", ['modNotify', 'moveRedirect']);
        $plugins->add_hook("class_moderation_move_threads", ['modNotify', 'moveThreads']);
        $plugins->add_hook("class_moderation_merge_threads", ['modNotify', 'mergeThreads']);
        $plugins->add_hook("class_moderation_split_posts", ['modNotify', 'splitPosts']);
        $plugins->add_hook("class_moderation_approve_posts", ['modNotify', 'approvePosts']);
        $plugins->add_hook("class_moderation_unapprove_posts", ['modNotify', 'unapprovePosts']);
        $plugins->add_hook("modcp_do_reports", ['modNotify', 'readReports']);
        $plugins->add_hook("pre_output_page", ['modNotify', 'pluginThanks']);
    }

    
    /**
     * Helper function to load and grab user data
     */
    public static function init()
    {
        global $lang, $mybb;

        require_once MYBB_ROOT . '/inc/datahandlers/pm.php';
        require_once MYBB_ROOT . '/inc/functions_user.php';
        require_once MYBB_ROOT . '/inc/functions.php';

        // Load all language data
        $lang->load('modNotify');
        $lang->load('modNotify', true);
        self::$lang = $lang;

        // Add user and DBAL data
        self::$user = $mybb->user;
    }

    
    /**
     * Action for close thread function
     *
     * @param array $tids List threads id to close
     */
    public static function closeThreads($tids)
    {
        if (self::getConfig('modNotifyCloseThreads')) {
            self::$subject = self::$lang->modNotifyInfoCloseThreads;

            foreach ($tids as $tid) {
                $thread = self::getData($tid, 'thread');

                if (self::checkUserID($thread['uid'])) {
                    $link = self::buildUrl('thread', $tid);
                    self::$message = sprintf(self::$lang->modNotifyInfoCloseThreadsMessage, $thread['subject'], $link);

                    self::addToQuote($thread['uid']);
                }
            }
        }
    }

    
    /**
     * Action for open thread function
     *
     * @param array $tids List threads id to open
     */
    public static function openThreads($tids)
    {
        if (self::getConfig('modNotifyOpenThreads')) {
            self::$subject = self::$lang->modNotifyInfoOpenThreads;

            foreach ($tids as $tid) {
                $thread = self::getData($tid, 'thread');

                if (self::checkUserID($thread['uid'])) {
                    $link = self::buildUrl('thread', $tid);
                    self::$message = sprintf(self::$lang->modNotifyInfoOpenThreadsMessage, $thread['subject'], $link);

                    self::addToQuote($thread['uid']);
                }
            }
        }
    }

    
    /**
     * Action for delete thread function
     *
     * @param int $tid Thread id to delete
     */
    public static function deleteThread($tid)
    {
        if (self::getConfig('modNotifyDeleteThread')) {
            self::$subject = self::$lang->modNotifyInfoDeleteThread;

            $thread = self::getData($tid, 'thread');

            if (self::checkUserID($thread['uid'])) {
                self::$message = sprintf(self::$lang->modNotifyInfoDeleteThreadMessage, $thread['subject']);

                self::addToQuote($thread['uid']);
            }
        }
    }

    
    /**
     * Action for sticky threads function
     *
     * @param array $tids List with threads id to sticky
     */
    public static function stickyThreads($tids)
    {
        if (self::getConfig('modNotifyStickyThreads')) {
            self::$subject = self::$lang->modNotifyInfoStickyThreads;

            foreach ($tids as $tid) {
                $thread = self::getData($tid, 'thread');

                if (self::checkUserID($thread['uid'])) {
                    $link = self::buildUrl('thread', $tid);
                    self::$message = sprintf(self::$lang->modNotifyInfoStickyThreadsMessage, $thread['subject'], $link);

                    self::addToQuote($thread['uid']);
                }
            }
        }
    }

    
    /**
     * Action for unsticky threads function
     *
     * @param array $tids List with threads id to unsticky
     */
    public static function unstickyThreads($tids)
    {
        if (self::getConfig('modNotifyUnstickyThreads')) {
            self::$subject = self::$lang->modNotifyInfoUnstickyThreads;

            foreach ($tids as $tid) {
                $thread = self::getData($tid, 'thread');

                if (self::checkUserID($thread['uid'])) {
                    $link = self::buildUrl('thread', $tid);
                    self::$message = sprintf(self::$lang->modNotifyInfoUnstickyThreadsMessage, $thread['subject'], $link);

                    self::addToQuote($thread['uid']);
                }
            }
        }
    }

    
    /**
     * Action for approve posts function
     *
     * @param array $pids List with posts id to approve
     */
    public static function approvePosts($pids)
    {
        if (self::getConfig('modNotifyApprovePosts')) {
            self::$subject = self::$lang->modNotifyInfoApprovePosts;

            foreach ($pids as $pid) {
                $post = self::getData($pid, 'post');

                if (self::checkUserID($post['uid'])) {
                    $link = self::buildUrl('post', $pid);
                    self::$message = sprintf(self::$lang->modNotifyInfoApprovePostsMessage, $post['subject'], $link);

                    self::addToQuote($post['uid']);
                }
            }
        }
    }

    
    /**
     * Action for unapprove posts function
     *
     * @param array $pids List with posts id to unapprove
     */
    public static function unapprovePosts($pids)
    {
        if (self::getConfig('modNotifyUnapprovePosts')) {
            self::$subject = self::$lang->modNotifyInfoUnapprovePosts;

            foreach ($pids as $pid) {
                $post = self::getData($pid, 'post');

                if (self::checkUserID($post['uid'])) {
                    $link = self::buildUrl('post', $pid);
                    self::$message = sprintf(self::$lang->modNotifyInfoUnapprovePostsMessage, $post['subject'], $link);

                    self::addToQuote($post['uid']);
                }
            }
        }
    }


    /**
     * Action for approve threads function
     *
     * @param array $tids List with threads id to approve
     */
    public static function approveThreads($tids)
    {
        if (self::getConfig('modNotifyApproveThreads')) {
            self::$subject = self::$lang->modNotifyInfoApproveThreads;

            foreach ($tids as $tid) {
                $thread = self::getData($tid, 'thread');

                if (self::checkUserID($thread['uid'])) {
                    $link = self::buildUrl('thread', $tid);
                    self::$message = sprintf(self::$lang->modNotifyInfoApproveThreadsMessage, $thread['subject'], $link);

                    self::addToQuote($thread['uid']);
                }
            }
        }
    }

    
    /**
     * Action for unapprove threads function
     *
     * @param array $tids List with threads id to unapprove
     */
    public static function unapproveThreads($tids)
    {
        if (self::getConfig('modNotifyUnapproveThreads')) {
            self::$subject = self::$lang->modNotifyInfoUnapproveThreads;

            foreach ($tids as $tid) {
                $thread = self::getData($tid, 'thread');

                if (self::checkUserID($thread['uid'])) {
                    $link = self::buildUrl('thread', $tid);
                    self::$message = sprintf(self::$lang->modNotifyInfoUnapproveThreadsMessage, $thread['subject'], $link);

                    self::addToQuote($thread['uid']);
                }
            }
        }
    }

    
    /**
     * Action for change thread subject function
     *
     * @param array $arguments Data with threads tids
     */
    public static function threadSubject($arguments)
    {
        if (self::getConfig('modNotifyThreadSubject')) {
            self::$subject = self::$lang->modNotifyInfoThreadSubject;

            // Get all data from arguments and database
            $tids = $arguments['tids'];

            foreach ($tids as $tid) {
                $thread = self::getData($tid, 'thread');

                if (self::checkUserID($thread['uid'])) {
                    $link = self::buildUrl('thread', $tid);
                    self::$message = sprintf(self::$lang->modNotifyInfoThreadSubjectMessage, $thread['subject'], $link);

                    self::addToQuote($thread['uid']);
                }
            }
        }
    }

    
    /**
     * Action for delete poll function
     *
     * @param int $pid Poll id to delete
     */
    public static function deletePoll($pid)
    {
        if (self::getConfig('modNotifyDeletePoll')) {
            self::$subject = self::$lang->modNotifyInfoDeletePoll;

            // Get all data from arguments and database
            $poll = self::getData($pid, 'poll');
            $thread = self::getData($poll['tid'], 'thread');

            if (self::checkUserID($thread['uid'])) {
                $link = self::buildUrl('thread', $thread['tid']);
                self::$message = sprintf(self::$lang->modNotifyInfoDeletePollMessage, $thread['subject'], $link);

                self::addToQuote($thread['uid']);
            }
        }
    }

    
    /**
     * Action for delete post function
     *
     * @param int $pid Post id to delete
     */
    public static function deletePost($pid)
    {
        if (self::getConfig('modNotifyDeletePost')) {
            self::init();
            self::$subject = self::$lang->modNotifyInfoDeletePost;

            // Get all data from arguments and database
            $post = self::getData($pid, 'post');
            $thread = self::getData($post['tid'], 'thread');

            if (self::checkUserID($post['uid'])) {
                $link = self::buildUrl('thread', $thread['tid']);
                self::$message = sprintf(self::$lang->modNotifyInfoDeletePostMessage, $thread['subject'], $link);

                self::addToQuote($post['uid']);
            }
        }
    }

    
    /**
     * Action for copy thread function
     *
     * @param array $arguments Data with thread id and forum id
     */
    public static function copyThread($arguments)
    {
        if (self::getConfig('modNotifyCopyThread')) {
            self::$subject = self::$lang->modNotifyInfoCopyThread;

            // Get all data from arguments and database
            $thread = self::getData($arguments['tid'], 'thread');
            $forum = self::getData($arguments['new_fid'], 'forum');

            if (self::checkUserID($thread['uid'])) {
                $link_thread = self::buildUrl('thread', $thread['tid']);
                $link_forum = self::buildUrl('forum', $forum['fid'], $forum['name']);;
                self::$message = sprintf(self::$lang->modNotifyInfoCopyThreadMessage, $thread['subject'], $link_forum, $link_thread);

                self::addToQuote($thread['uid']);
            }
        }
    }

    
    /**
     * Action for move and redirect thread function
     *
     * @param array $arguments Data with thread id and new forum id
     */
    public static function moveRedirect($arguments)
    {
        if (self::getConfig('modNotifyMoveRedirect')) {
            self::$subject = self::$lang->modNotifyInfoMoveRedirect;

            // Get all data from arguments and database
            $thread = self::getData($arguments['tid'], 'thread');
            $forum = self::getData($arguments['new_fid'], 'forum');

            if (self::checkUserID($thread['uid'])) {
                $link_thread = self::buildUrl('thread', $thread['tid']);
                $link_forum = self::buildUrl('forum', $forum['fid'], $forum['name']);;
                self::$message = sprintf(self::$lang->modNotifyInfoMoveRedirectMessage, $thread['subject'], $link_forum, $link_thread);

                self::addToQuote($thread['uid']);
            }
        }
    }

    
    /**
     * Action for move threads function
     *
     * @param array $arguments Data with threads ids list and new forum id
     */
    public static function moveThreads($arguments)
    {
        if (self::getConfig('modNotifyMoveThreads')) {
            self::$subject = self::$lang->modNotifyInfoMoveThreads;

            // Get all data from arguments and database
            $tids = $arguments['tids'];
            $forum = self::getData($arguments['moveto'], 'forum');
            $link_forum = self::buildUrl('forum', $forum['fid'], $forum['name']);
            ;

            foreach ($tids as $tid) {
                $thread = self::getData($tid, 'thread');

                if (self::checkUserID($thread['uid'])) {
                    $link_thread = self::buildUrl('thread', $thread['tid']);
                    self::$message = sprintf(self::$lang->modNotifyInfoMoveThreadsMessage, $thread['subject'], $link_forum, $link_thread);

                    self::addToQuote($thread['uid']);
                }
            }
        }
    }

    
    /**
     * Action for merge threads function
     *
     * @param array $arguments Data with old and new thread id
     */
    public static function mergeThreads($arguments)
    {
        if (self::getConfig('modNotifyMergeThreads')) {
            self::$subject = self::$lang->modNotifyInfoMergeThreads;

            // Get all data from arguments and database
            $merge = self::getData($arguments['mergetid'], 'thread');
            $thread = self::getData($arguments['tid'], 'thread');

            if (self::checkUserID($merge['uid']) && self::checkUserID($thread['uid'])) {
                $link = self::buildUrl('thread', $thread['tid']);
                self::$message = sprintf(self::$lang->modNotifyInfoMergeThreadsMessage, $merge['subject'], $thread['subject'], $link);

                self::addToQuote($merge['uid']);
            }
        }
    }

    
    /**
     * Action for split posts function
     *
     * @param array $arguments Data with new thread id and list of posts pids
     */
    public static function splitPosts($arguments)
    {
        if (self::getConfig('modNotifySplitPosts')) {
            self::$subject = self::$lang->modNotifyInfoSplitPosts;

            // Get all data from arguments and database
            $tid = $arguments['destination_tid'];
            $pids = $arguments['pids'];
            $forum = self::getData($arguments['moveto'], 'forum');

            // Get old thread data for old subject
            $thread_old = self::getData($arguments['tid'], 'thread');

            // If destination is 0, get new thread id
            if ($tid == 0) {
                $post_temp = self::getData($pids[0], 'post');
                $tid = $post_temp['tid'];
                unset($post_temp);
            }

            // Build thread and forum links
            $link_thread = self::buildUrl('thread', $tid);
            $link_forum = self::buildUrl('forum', $forum['fid'], $forum['name']);

            foreach ($pids as $pid) {
                $post = self::getData($pid, 'post');

                if (self::checkUserID($post['uid'])) {
                    self::$message = sprintf(self::$lang->modNotifyInfoSplitPostsMessage, $thread_old['subject'], $arguments['newsubject'], $link_forum, $link_thread);

                    self::addToQuote($post['uid']);
                }
            }
        }
    }


    /**
     * Action for read reports by mod
     *
     * @param array $pids List with posts id to unapprove
     */
    public static function readReports()
    {
        if (!self::getConfig('modNotifyReportRead')) {
            return;
        }

        global $mybb, $db;
        self::init();

        if (!empty($mybb->input['reports'])) {
            $reportIds = array_map('intval', $mybb->input['reports']);
            $reportIds = implode(',', $reportIds);

            self::$subject = self::$lang->modNotifyInfoReportRead;

            $result = $db->simple_select('reportedcontent', '*', "rid IN (" . $reportIds . ") AND type = 'post'");
            while ($row = $db->fetch_array($result)) {
                $postObj = self::getData($row['id'], 'post');
                $link_post = self::buildUrl('post', $postObj['pid']);

                self::$message = sprintf(self::$lang->modNotifyInfoReportReadMessage, $postObj['subject'], $postObj['username'], $link_post);
                self::addToQuote($row['uid']);
            }
        }
    }

    
    /**
     * Add message to quote
     *
     * @param int $id Recipient id
     */
    public static function addToQuote($id = 0)
    {
        global $db;

        if ($id < 1) {
            return;
        }

        $data = array(
            'uid' => (int) $id,
            'mod_id' => (int) self::$user['uid'],
            'from_id' => (int) self::getFromID(),
            'subject' => $db->escape_string(self::$subject),
            'message' => $db->escape_string(self::$message),
        );

        $db->insert_query('mod_notify', $data);
    }


    /**
     * Helper function to choose from user id
     *
     * @return int "From" user id
     */
    public static function getFromID()
    {
        static $fromid;

        if (empty($fromid)) {
            $fromid = (int) self::getConfig('modNotifyFromID');
            if ($fromid <= 0) {
                $fromid = self::$user['uid'];
            }
        }

        return $fromid;
    }


    /**
     * Helper function to grab important data from database
     *
     * @param int $id Element id in database
     * @param string $type Element type - post, thread, forum, poll or user
     * @return array Element data from database
     */
    public static function getData($id = 0, $type = '')
    {
        global $db;

        $data = array();
        if ($id > 0) {
            switch ($type) {
                case 'post':
                    $result = $db->simple_select('posts', 'pid, tid, fid, subject, uid, username', "pid = '{$id}'");
                    $data = $db->fetch_array($result);
                    break;

                case 'thread':
                    $result = $db->simple_select('threads', 'tid, fid, subject, uid, username', "tid = '{$id}'");
                    $data = $db->fetch_array($result);
                    break;

                case 'forum':
                    $result = $db->simple_select('forums', 'fid, name', "fid = '{$id}'");
                    $data = $db->fetch_array($result);
                    break;

                case 'poll':
                    $result = $db->simple_select('polls', 'pid, tid', "pid = '{$id}'");
                    $data = $db->fetch_array($result);
                    break;

                case 'user':
                    $result = $db->simple_select('users', 'uid, username', "uid = '{$id}'");
                    $data = $db->fetch_array($result);

                    if (isset($data['username'])) {
                        $data['link'] = self::buildUrl('profile', $id, $data['username']);
                    }
                    break;

                default:
                    break;
            }
        }

        return $data;
    }


    /**
     * Helper function to check user id before send pm
     *
     * @param int $uid User id to check
     * @return bool Information whether it is the sender
     */
    public static function checkUserID($uid)
    {
        return ($uid > 0 && $uid != self::$user['uid'] && $uid != self::getFromID());
    }


    /**
     * Helper function to check user id before send pm
     *
     * @param string $type Type of link - thread, forum or user
     * @param int $id Element id in database
     * @param string $anchor Optional anchor for bbcode
     * @return string Link to element in MyBB Engine
     */
    public static function buildUrl($type, $id = 0, $anchor = '')
    {
        $link = '';
        if ($id > 0) {
            $function = "get_{$type}_link";
            if (!function_exists($function)) {
                return '';
            }

            if (!isset($anchor[0])) {
                $link = $function($id) . ']' . $function($id);
            } else {
                $link = $function($id) . ']' . $anchor;
            }
        }

        $link = '[url=' . self::getConfig('bburl') . '/' . $link . '[/url]';
        return $link;
    }


    /**
     * Helper function to get variable from config
     *
     * @param string $name Name of config to get
     * @return string Data config from MyBB Settings
     */
    public static function getConfig($name)
    {
        global $mybb;
        return $mybb->settings[$name];
    }


    /**
     * Say thanks to plugin author - paste link to author website.
     * Please don't remove this code if you didn't make donate
     * It's the only way to say thanks without donate :)
     */
    public static function pluginThanks(&$content)
    {
        global $session, $lukasamd_thanks;

        if (!isset($lukasamd_thanks) && $session->is_spider) {
            $thx = '<div style="margin:auto; text-align:center;">This forum uses <a href="https://tkacz.pro">Lukasz Tkacz</a> MyBB addons.</div></body>';
            $content = str_replace('</body>', $thx, $content);
            $lukasamd_thanks = true;
        }
    }

}
