<?php
// 事件定义文件
return [
    'bind'      => [],
    'listen'    => [
        'AgentUpgradeCondition' => [
            'app\addons\agent\admin\event\AgentUpgradeCondition'
        ]
    ],
    'subscribe' => [],
];
