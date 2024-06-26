<?php
// 事件定义文件
return [
    'bind'      => [],
    'listen'    => [
        'MemberUpdate' => [
            'app\addons\member\admin\event\MemberUpdate'
        ],
        'MemberRegister' => [
            'app\addons\member\admin\event\MemberRegister'
        ],
        'OrderComplete' => [
            'app\addons\member\admin\event\OrderComplete'
        ],
    ],
    'subscribe' => [],
];
