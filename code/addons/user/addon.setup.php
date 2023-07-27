<?php

require_once 'autoload.php';
$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

return array(
    'name'              => $addonJson->name,
    'description'       => $addonJson->description,
    'version'           => $addonJson->version,
    'namespace'         => $addonJson->namespace,
    'author'            => 'EEHarbor',
    'author_url'        => 'http://eeharbor.com/user',
    'docs_url'          => 'http://eeharbor.com/user/documentation',
    'settings_exist'    => true,
    'models' => array(
        'Author'                    => 'Model\Author',
        'ActivationGroup'           => 'Model\ActivationGroup',
        'Cache'                     => 'Model\Cache',
        'CategoryPosts'             => 'Model\CategoryPosts',
        'Key'                       => 'Model\Key',
        'Member'                    => 'Model\Member',
        'MemberData'                => 'Model\MemberData',
        'MemberChannelEntry'        => 'Model\MemberChannelEntry',
        'OnlineUser'                => 'Model\OnlineUser',
        'Param'                     => 'Model\Param',
        'Preference'                => 'Model\Preference',
        'ResetPassword'             => 'Model\ResetPassword',
        'Role'                      => 'Model\Role',
        'RoleAssigned'              => 'Model\RoleAssigned',
        'RoleEntryPermission'       => 'Model\RoleEntryPermission',
        'RoleInherit'               => 'Model\RoleInherit',
        'RolePermission'            => 'Model\RolePermission',
        'Search'                    => 'Model\Search',
        'Session'                   => 'Model\Session',
        'WelcomeEmailList'          => 'Model\WelcomeEmailList'
    ),
    'models.dependencies' => array(
        'Author'   => array(
            'ee:Member'
        ),
        'Key'   => array(
            'ee:MemberGroup'
        ),
        'WelcomeEmailList'   => array(
            'ee:Member'
        ),
        'MemberChannelEntry'   => array(
            'ee:Member'
        )
    ),
);
