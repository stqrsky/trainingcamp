<?php

/*
|--------------------------------------------------------------------------
| Main navigation
|--------------------------------------------------------------------------
|
| Shared by the bottom navigation (phones, tablets) and the sidebar
| (desktop). "active" lists route name patterns that highlight the item.
|
*/

return [
    'items' => [
        [
            'route'  => 'home',
            'label'  => 'Overview',
            'icon'   => 'format_list_bulleted',
            'active' => ['home', 'notification.*'],
        ],
        [
            'route'  => 'schedules.index',
            'label'  => 'Schedule',
            'icon'   => 'date_range',
            'active' => ['schedules.*'],
        ],
        [
            'route'  => 'tasks.index',
            'label'  => 'Tasks',
            'icon'   => 'checklist',
            'active' => ['tasks.*'],
        ],
        [
            'route'  => 'user.athletes',
            'label'  => 'Athletes',
            'icon'   => 'people_outline',
            'active' => ['user.athletes*', 'teams.*'],
        ],
        [
            'route'  => 'user.profile',
            'label'  => 'Profile',
            'icon'   => 'reorder',
            'active' => ['user.profile*', 'user.account.setting'],
        ],
    ],
];
