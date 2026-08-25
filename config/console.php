<?php


// 控制台配置
return [
    // 指令定义
    'commands' => [
        // 定时任务
        'timer' => 'app\command\Timer',
        // 渐进式重构只读事实基线
        'refactor:baseline' => 'app\command\RefactorBaselineAudit',
        // SQL 快照只读结构与业务基准
        'refactor:snapshot' => 'app\command\RefactorSnapshotAudit',
        // 每日订单、核销与财务只读对账
        'refactor:reconcile' => 'app\command\RefactorDailyReconciliation',
    ],
];
