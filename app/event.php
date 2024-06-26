<?php
// 事件定义文件
return [
    'bind'      => [],
    'listen'    => [
        'AppInit'  => [],
        'HttpRun'  => [],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],
        'MemberRegister' => [],
        'MemberTrash' => [
            'app\event\MemberTrash'
        ],
        'MemberUpdate' => [],
        'MemberAccountChange' => [],
        'OrderPay' => [
            'app\event\OrderPay'
        ],
        'OrderComplete' => [],
    ],
    'subscribe' => [],
];
