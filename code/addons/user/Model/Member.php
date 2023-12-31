<?php

namespace EEHarbor\User\Model;

class Member extends BaseModel
{
    protected static $_primary_key  = 'member_id';
    protected static $_table_name   = 'exp_members';

    // What we added to exp_members
    protected $profile_views;

    protected static $_relationships = array(
        'MemberData' => array(
            'model'     => 'MemberData',
            'type'      => 'HasOne',
            'from_key'  => 'member_id',
            'to_key'    => 'member_id',
            'weak'      => false
        )
    );

    // Properties
    protected $member_id;
    protected $group_id;
    protected $username;
    protected $screen_name;
    protected $password;
    protected $salt;
    protected $unique_id;
    protected $crypt_key;
    protected $authcode;
    protected $email;
    protected $signature;
    protected $avatar_filename;
    protected $avatar_width;
    protected $avatar_height;
    protected $photo_filename;
    protected $photo_width;
    protected $photo_height;
    protected $sig_img_filename;
    protected $sig_img_width;
    protected $sig_img_height;
    protected $ignore_list;
    protected $private_messages;
    protected $accept_messages;
    protected $last_view_bulletins;
    protected $last_bulletin_date;
    protected $ip_address;
    protected $join_date;
    protected $last_visit;
    protected $last_activity;
    protected $total_entries;
    protected $total_comments;
    protected $total_forum_topics;
    protected $total_forum_posts;
    protected $last_entry_date;
    protected $last_comment_date;
    protected $last_forum_post_date;
    protected $last_email_date;
    protected $in_authorlist;
    protected $accept_admin_email;
    protected $accept_user_email;
    protected $notify_by_default;
    protected $notify_of_pm;
    protected $display_avatars;
    protected $display_signatures;
    protected $parse_smileys;
    protected $smart_notifications;
    protected $language;
    protected $timezone;
    protected $time_format;
    protected $date_format;
    protected $include_seconds;
    protected $profile_theme;
    protected $forum_theme;
    protected $tracker;
    protected $template_size;
    protected $notepad;
    protected $notepad_size;
    protected $bookmarklets;
    protected $quick_links;
    protected $quick_tabs;
    protected $show_sidebar;
    protected $pmember_id;
    protected $rte_enabled;
    protected $rte_toolset_id;
    protected $cp_homepage;
    protected $cp_homepage_channel;
    protected $cp_homepage_custom;
}
//END Member
