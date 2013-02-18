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
 
/**
 * Disallow direct access to this file for security reasons
 * 
 */
if (!defined("IN_MYBB")) exit;

/**
 * Create plugin object
 * 
 */
$plugins->objects['modNotify'] = new modNotify();

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
        'website' => 'http://lukasztkacz.com',
        'author' => 'Lukasz "LukasAMD" Tkacz',
        'authorsite' => 'http://lukasztkacz.com',
        'version' => '1.6',
        'guid' => '8366943ead7ed8203af17742fb941e71',
        'compatibility' => '16*'
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

    rebuildsettings();
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

    rebuildsettings();
}

 /**
 * Plugin Class 
 * 
 */
class modNotify
{

    // Store information where we should send pm
    private $to = 0;
    // Store information about pm subject
    private $subject = '';
    // Store information about pm message
    private $message = '';
    // Store lang data (reference to $lang)
    private $lang;
    // Store user data (reference to $mybb->user)
    private $user;
    // Store all messages to send
    private $quote = array();

    /**
     * Constructor - add plugin hooks
     */
    public function __construct()
    {
        global $plugins;

        // Add all hooks
        $plugins->hooks["moderation_start"][10]["modNotify_init"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->init($arg);')); 
        $plugins->hooks["class_moderation_close_threads"][10]["modNotify_closeThreads"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->closeThreads($arg);')); 
        $plugins->hooks["class_moderation_open_threads"][10]["modNotify_openThreads"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->openThreads($arg);')); 
        $plugins->hooks["class_moderation_delete_thread_start"][10]["modNotify_deleteThread"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->deleteThread($arg);')); 
        $plugins->hooks["class_moderation_stick_threads"][10]["modNotify_stickyThreads"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->stickyThreads($arg);')); 
        $plugins->hooks["class_moderation_unstick_threads"][10]["modNotify_unstickyThreads"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->unstickyThreads($arg);')); 
        $plugins->hooks["class_moderation_approve_threads"][10]["modNotify_approveThreads"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->approveThreads($arg);')); 
        $plugins->hooks["class_moderation_unapprove_threads"][10]["modNotify_unapproveThreads"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->unapproveThreads($arg);')); 
        $plugins->hooks["class_moderation_change_thread_subject"][10]["modNotify_threadSubject"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->threadSubject($arg);')); 
        $plugins->hooks["class_moderation_delete_poll"][10]["modNotify_deletePoll"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->deletePoll($arg);')); 
        $plugins->hooks["class_moderation_delete_post_start"][10]["modNotify_deletePost"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->deletePost($arg);')); 
        $plugins->hooks["class_moderation_copy_thread"][10]["modNotify_copyThread"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->copyThread($arg);')); 
        $plugins->hooks["class_moderation_move_simple"][10]["modNotify_moveRedirect"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->moveRedirect($arg);')); 
        $plugins->hooks["class_moderation_move_thread_redirect"][10]["modNotify_moveRedirect"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->moveRedirect($arg);')); 
        $plugins->hooks["class_moderation_move_threads"][10]["modNotify_moveThreads"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->moveThreads($arg);')); 
        $plugins->hooks["class_moderation_merge_threads"][10]["modNotify_mergeThreads"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->mergeThreads($arg);')); 
        $plugins->hooks["class_moderation_split_posts"][10]["modNotify_splitPosts"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->splitPosts($arg);')); 
        $plugins->hooks["class_modnotify_moderation_approve_posts"][10]["modNotify_approvePosts"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->approvePosts($arg);')); 
        $plugins->hooks["class_modnotify_moderation_unapprove_posts"][10]["modNotify_unapprovePosts"] = array("function" => create_function('$arg','global $plugins; $plugins->objects[\'modNotify\']->unapprovePosts($arg);'));   
        $plugins->hooks["pre_output_page"][10]["modNotify_pluginThanks"] = array("function" => create_function('&$arg', 'global $plugins; $plugins->objects[\'modNotify\']->pluginThanks($arg);'));
    }
    
    /**
     * Helper function to load and grab user data
     */
    public function init()
    {
        global $lang, $mybb, $moderation;

        require_once MYBB_ROOT . '/inc/datahandlers/pm.php';
        require_once MYBB_ROOT . '/inc/functions_user.php';
        require_once MYBB_ROOT . '/inc/functions.php';
        require_once MYBB_ROOT . '/inc/plugins/modNotify_Moderation.class.php';
        
        // Load all language data
        $lang->load('modNotify');
        $lang->load('modNotify', true);
        $this->lang = $lang;

        // Add user and DBAL data
        $this->user = $mybb->user;
        
        // Overwrite Moderation class to get new hooks 
        $moderation = new modNotify_Moderation();
    }

    /**
     * Action for close thread function
     * 
     * @param array $tids List threads id to close
     */
    public function closeThreads($tids)
    {
        if ($this->getConfig('modNotifyCloseThreads'))
        {
            $this->subject = $this->lang->modNotifyInfoCloseThreads;

            foreach ($tids as $tid)
            {
                $thread = $this->getData($tid, 'thread');

                if ($this->checkUserID($thread['uid']))
                {
                    $link = $this->buildUrl('thread', $tid);
                    $this->message = sprintf($this->lang->modNotifyInfoCloseThreadsMessage, $thread['subject'], $link);

                    $this->addToQuote($thread['uid']);
                }
            }
        }
    }

    /**
     * Action for open thread function
     * 
     * @param array $tids List threads id to open
     */
    public function openThreads($tids)
    {
        if ($this->getConfig('modNotifyOpenThreads'))
        {
            $this->subject = $this->lang->modNotifyInfoOpenThreads;

            foreach ($tids as $tid)
            {
                $thread = $this->getData($tid, 'thread');

                if ($this->checkUserID($thread['uid']))
                {
                    $link = $this->buildUrl('thread', $tid);
                    $this->message = sprintf($this->lang->modNotifyInfoOpenThreadsMessage, $thread['subject'], $link);

                    $this->addToQuote($thread['uid']);
                }
            }
        }
    }

    /**
     * Action for delete thread function
     * 
     * @param int $tid Thread id to delete
     */
    public function deleteThread($tid)
    {
        if ($this->getConfig('modNotifyDeleteThread'))
        {
            $this->subject = $this->lang->modNotifyInfoDeleteThread;

            $thread = $this->getData($tid, 'thread');

            if ($this->checkUserID($thread['uid']))
            {
                $this->message = sprintf($this->lang->modNotifyInfoDeleteThreadMessage, $thread['subject']);

                $this->addToQuote($thread['uid']);
            }
        }
    }

    /**
     * Action for sticky threads function
     * 
     * @param array $tids List with threads id to sticky
     */
    public function stickyThreads($tids)
    {
        if ($this->getConfig('modNotifyStickyThreads'))
        {
            $this->subject = $this->lang->modNotifyInfoStickyThreads;

            foreach ($tids as $tid)
            {
                $thread = $this->getData($tid, 'thread');

                if ($this->checkUserID($thread['uid']))
                {
                    $link = $this->buildUrl('thread', $tid);
                    $this->message = sprintf($this->lang->modNotifyInfoStickyThreadsMessage, $thread['subject'], $link);

                    $this->addToQuote($thread['uid']);
                }
            }
        }
    }

    /**
     * Action for unsticky threads function
     * 
     * @param array $tids List with threads id to unsticky
     */
    public function unstickyThreads($tids)
    {
        if ($this->getConfig('modNotifyUnstickyThreads'))
        {
            $this->subject = $this->lang->modNotifyInfoUnstickyThreads;

            foreach ($tids as $tid)
            {
                $thread = $this->getData($tid, 'thread');

                if ($this->checkUserID($thread['uid']))
                {
                    $link = $this->buildUrl('thread', $tid);
                    $this->message = sprintf($this->lang->modNotifyInfoUnstickyThreadsMessage, $thread['subject'], $link);

                    $this->addToQuote($thread['uid']);
                }
            }
        }
    }
    
    /**
     * Action for approve posts function
     * 
     * @param array $pids List with posts id to approve
     */
    public function approvePosts($pids)
    {
        if ($this->getConfig('modNotifyApprovePosts'))
        {
            $this->subject = $this->lang->modNotifyInfoApprovePosts;

            foreach ($pids as $pid)
            {
                $post = $this->getData($pid, 'post');

                if ($this->checkUserID($post['uid']))
                {
                    $link = $this->buildUrl('post', $pid);
                    $this->message = sprintf($this->lang->modNotifyInfoApprovePostsMessage, $post['subject'], $link);

                    $this->addToQuote($post['uid']);
                }
            }
        }
    }
    
    /**
     * Action for unapprove posts function
     * 
     * @param array $pids List with posts id to unapprove
     */
    public function unapprovePosts($pids)
    {
        if ($this->getConfig('modNotifyUnapprovePosts'))
        {
            $this->subject = $this->lang->modNotifyInfoUnapprovePosts;

            foreach ($pids as $pid)
            {
                $post = $this->getData($pid, 'post');

                if ($this->checkUserID($post['uid']))
                {
                    $link = $this->buildUrl('post', $pid);
                    $this->message = sprintf($this->lang->modNotifyInfoUnapprovePostsMessage, $post['subject'], $link);

                    $this->addToQuote($post['uid']);
                }
            }
        }
    }
    

    /**
     * Action for approve threads function
     * 
     * @param array $tids List with threads id to approve
     */
    public function approveThreads($tids)
    {
        if ($this->getConfig('modNotifyApproveThreads'))
        {
            $this->subject = $this->lang->modNotifyInfoApproveThreads;

            foreach ($tids as $tid)
            {
                $thread = $this->getData($tid, 'thread');

                if ($this->checkUserID($thread['uid']))
                {
                    $link = $this->buildUrl('thread', $tid);
                    $this->message = sprintf($this->lang->modNotifyInfoApproveThreadsMessage, $thread['subject'], $link);

                    $this->addToQuote($thread['uid']);
                }
            }
        }
    }

    /**
     * Action for unapprove threads function
     * 
     * @param array $tids List with threads id to unapprove
     */
    public function unapproveThreads($tids)
    {
        if ($this->getConfig('modNotifyUnapproveThreads'))
        {
            $this->subject = $this->lang->modNotifyInfoUnapproveThreads;

            foreach ($tids as $tid)
            {
                $thread = $this->getData($tid, 'thread');

                if ($this->checkUserID($thread['uid']))
                {
                    $link = $this->buildUrl('thread', $tid);
                    $this->message = sprintf($this->lang->modNotifyInfoUnapproveThreadsMessage, $thread['subject'], $link);

                    $this->addToQuote($thread['uid']);
                }
            }
        }
    }

    /**
     * Action for change thread subject function
     * 
     * @param array $arguments Data with threads tids
     */
    public function threadSubject($arguments)
    {
        if ($this->getConfig('modNotifyThreadSubject'))
        {
            $this->subject = $this->lang->modNotifyInfoThreadSubject;

            // Get all data from arguments and database
            $tids = $arguments['tids'];

            foreach ($tids as $tid)
            {
                $thread = $this->getData($tid, 'thread');

                if ($this->checkUserID($thread['uid']))
                {
                    $link = $this->buildUrl('thread', $tid);
                    $this->message = sprintf($this->lang->modNotifyInfoThreadSubjectMessage, $thread['subject'], $link);

                    $this->addToQuote($thread['uid']);
                }
            }
        }
    }

    /**
     * Action for delete poll function
     * 
     * @param int $pid Poll id to delete
     */
    public function deletePoll($pid)
    {
        if ($this->getConfig('modNotifyDeletePoll'))
        {
            $this->subject = $this->lang->modNotifyInfoDeletePoll;

            // Get all data from arguments and database
            $poll = $this->getData($pid, 'poll');
            $thread = $this->getData($poll['tid'], 'thread');

            if ($this->checkUserID($thread['uid']))
            {
                $link = $this->buildUrl('thread', $thread['tid']);
                $this->message = sprintf($this->lang->modNotifyInfoDeletePollMessage, $thread['subject'], $link);

                $this->addToQuote($thread['uid']);
            }
        }
    }

    /**
     * Action for delete post function
     * 
     * @param int $pid Post id to delete
     */
    public function deletePost($pid)
    {
        if ($this->getConfig('modNotifyDeletePost'))
        {
            $this->init(); 
            $this->subject = $this->lang->modNotifyInfoDeletePost;

            // Get all data from arguments and database
            $post = $this->getData($pid, 'post');
            $thread = $this->getData($post['tid'], 'thread');

            if ($this->checkUserID($post['uid']))
            {
                $link = $this->buildUrl('thread', $thread['tid']);
                $this->message = sprintf($this->lang->modNotifyInfoDeletePostMessage, $thread['subject'], $link);

                $this->addToQuote($post['uid']);
            }
        }
    }

    /**
     * Action for copy thread function
     * 
     * @param array $arguments Data with thread id and forum id
     */
    public function copyThread($arguments)
    {
        if ($this->getConfig('modNotifyCopyThread'))
        {
            $this->subject = $this->lang->modNotifyInfoCopyThread;

            // Get all data from arguments and database
            $thread = $this->getData($arguments['tid'], 'thread');
            $forum = $this->getData($arguments['new_fid'], 'forum');

            if ($this->checkUserID($thread['uid']))
            {
                $link_thread = $this->buildUrl('thread', $thread['tid']);
                $link_forum = $this->buildUrl('forum', $forum['fid'], $forum['name']);
                ;
                $this->message = sprintf($this->lang->modNotifyInfoCopyThreadMessage, $thread['subject'], $link_forum, $link_thread);

                $this->addToQuote($thread['uid']);
            }
        }
    }

    /**
     * Action for move and redirect thread function
     * 
     * @param array $arguments Data with thread id and new forum id
     */
    public function moveRedirect($arguments)
    {
        if ($this->getConfig('modNotifyMoveRedirect'))
        {
            $this->subject = $this->lang->modNotifyInfoMoveRedirect;

            // Get all data from arguments and database
            $thread = $this->getData($arguments['tid'], 'thread');
            $forum = $this->getData($arguments['new_fid'], 'forum');

            if ($this->checkUserID($thread['uid']))
            {
                $link_thread = $this->buildUrl('thread', $thread['tid']);
                $link_forum = $this->buildUrl('forum', $forum['fid'], $forum['name']);
                ;
                $this->message = sprintf($this->lang->modNotifyInfoMoveRedirectMessage, $thread['subject'], $link_forum, $link_thread);

                $this->addToQuote($thread['uid']);
            }
        }
    }

    /**
     * Action for move threads function
     * 
     * @param array $arguments Data with threads ids list and new forum id
     */
    public function moveThreads($arguments)
    {
        if ($this->getConfig('modNotifyMoveThreads'))
        {
            $this->subject = $this->lang->modNotifyInfoMoveThreads;

            // Get all data from arguments and database
            $tids = $arguments['tids'];
            $forum = $this->getData($arguments['moveto'], 'forum');
            $link_forum = $this->buildUrl('forum', $forum['fid'], $forum['name']);
            ;

            foreach ($tids as $tid)
            {
                $thread = $this->getData($tid, 'thread');

                if ($this->checkUserID($thread['uid']))
                {
                    $link_thread = $this->buildUrl('thread', $thread['tid']);
                    $this->message = sprintf($this->lang->modNotifyInfoMoveThreadsMessage, $thread['subject'], $link_forum, $link_thread);

                    $this->addToQuote($thread['uid']);
                }
            }
        }
    }

    /**
     * Action for merge threads function
     * 
     * @param array $arguments Data with old and new thread id
     */
    public function mergeThreads($arguments)
    {
        if ($this->getConfig('modNotifyMergeThreads'))
        {
            $this->subject = $this->lang->modNotifyInfoMergeThreads;

            // Get all data from arguments and database
            $merge = $this->getData($arguments['mergetid'], 'thread');
            $thread = $this->getData($arguments['tid'], 'thread');

            if ($this->checkUserID($merge['uid']) && $this->checkUserID($thread['uid']))
            {
                $link = $this->buildUrl('thread', $thread['tid']);
                $this->message = sprintf($this->lang->modNotifyInfoMergeThreadsMessage, $merge['subject'], $thread['subject'], $link);

                $this->addToQuote($merge['uid']);
            }
        }
    }

    /**
     * Action for split posts function
     * 
     * @param array $arguments Data with new thread id and list of posts pids
     */
    public function splitPosts($arguments)
    {
        if ($this->getConfig('modNotifySplitPosts'))
        {
            $this->subject = $this->lang->modNotifyInfoSplitPosts;

            // Get all data from arguments and database
            $tid = $arguments['destination_tid'];
            $pids = $arguments['pids'];
            $forum = $this->getData($arguments['moveto'], 'forum');

            // Get old thread data for old subject
            $thread_old = $this->getData($arguments['tid'], 'thread');

            // If destination is 0, get new thread id
            if ($tid == 0)
            {
                $post_temp = $this->getData($pids[0], 'post');
                $tid = $post_temp['tid'];
                unset($post_temp);
            }

            // Build thread and forum links
            $link_thread = $this->buildUrl('thread', $tid);
            $link_forum = $this->buildUrl('forum', $forum['fid'], $forum['name']);
            ;

            foreach ($pids as $pid)
            {
                $post = $this->getData($pid, 'post');

                if ($this->checkUserID($post['uid']))
                {
                    $this->message = sprintf($this->lang->modNotifyInfoSplitPostsMessage, $thread_old['subject'], $arguments['newsubject'], $link_forum, $link_thread);

                    $this->addToQuote($post['uid']);
                }
            }
        }
    }

    /**
     * Add message to quote
     * 
     * @param int $id Recipient id
     */
    public function addToQuote($id = 0)
    {
        global $db;
        
        if ($id < 1)
        {
            return;
        }

        $data = array(
            'uid' => (int) $id,
            'mod_id' => (int) $this->user['uid'],
            'from_id' => (int) $this->getFromID(),
            'subject' => $db->escape_string($this->subject),
            'message' => $db->escape_string($this->message),
        );

        $db->insert_query('mod_notify', $data);
    }

    /**
     * Helper function to choose from user id
     * 
     * @return int "From" user id
     */
    public function getFromID()
    {
        static $fromid;

        if (empty($fromid))
        {
            $fromid = (int) $this->getConfig('modNotifyFromID');
            if ($fromid <= 0)
            {
                $fromid = $this->user['uid'];
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
    public function getData($id = 0, $type = '')
    {
        global $db;
        
        $data = array();
        if ($id > 0)
        {
            switch ($type)
            {
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

                    if (isset($data['username']))
                    {
                        $data['link'] = $this->buildUrl('profile', $id, $data['username']);
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
    public function checkUserID($uid)
    {
        return ($uid > 0 && $uid != $this->user['uid'] && $uid != $this->getFromID());
    }

    /**
     * Helper function to check user id before send pm
     * 
     * @param string $type Type of link - thread, forum or user
     * @param int $id Element id in database
     * @param string $anchor Optional anchor for bbcode
     * @return string Link to element in MyBB Engine
     */
    public function buildUrl($type, $id = 0, $anchor = '')
    {
        $link = '';
        if ($id > 0)
        {
            $function = "get_{$type}_link";
            if (!function_exists($function))
            {
                return;
            }
            
            if (!isset($anchor[0]))
            {
                $link = $function($id) . ']' . $function($id); 
            }
            else
            {
                $link = $function($id) . ']' . $anchor; 
            }
        }

        $link = '[url=' . $this->getConfig('bburl') . '/' . $link . '[/url]'; 
        return $link;
    }

    /**
     * Helper function to get variable from config
     * 
     * @param string $name Name of config to get
     * @return string Data config from MyBB Settings
     */
    public function getConfig($name)
    {
        global $mybb;

        return $mybb->settings[$name];
    }
    
    /**
     * Say thanks to plugin author - paste link to author website.
     * Please don't remove this code if you didn't make donate
     * It's the only way to say thanks without donate :)     
     */
    public function pluginThanks(&$content)
    {
        global $session, $lukasamd_thanks;
        
        if (!isset($lukasamd_thanks) && $session->is_spider)
        {
            $thx = '<div style="margin:auto; text-align:center;">This forum uses <a href="http://lukasztkacz.com">Lukasz Tkacz</a> MyBB addons.</div></body>';
            $content = str_replace('</body>', $thx, $content);
            $lukasamd_thanks = true;
        }
    }

}
