<?php

declare(strict_types=1);

namespace app\command;

use app\common\service\report\DailyOrderReconciliationService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class RefactorDailyReconciliation extends Command
{
    protected function configure()
    {
        $this->setName('refactor:reconcile')
            ->addOption('date', null, Option::VALUE_OPTIONAL, '对账日期 YYYY-MM-DD，默认昨天')
            ->addOption('output', null, Option::VALUE_OPTIONAL, 'JSON 输出路径，默认 runtime/refactor-reconciliation/日期.json')
            ->setDescription('生成订单、核销、采购流水和支付网关每日只读对账报告');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $date = trim(strval($input->getOption('date'))) ?: date('Y-m-d', strtotime('-1 day'));
            $report = DailyOrderReconciliationService::report($date);
            $json = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            if ($json === false) {
                throw new \RuntimeException('对账报告 JSON 生成失败');
            }
            $target = trim(strval($input->getOption('output')));
            if ($target === '') {
                $target = root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'refactor-reconciliation' . DIRECTORY_SEPARATOR . $date . '.json';
            }
            $directory = dirname($target);
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException('无法创建对账报告目录：' . $directory);
            }
            if (file_put_contents($target, $json . PHP_EOL, LOCK_EX) === false) {
                throw new \RuntimeException('无法写入对账报告：' . $target);
            }
            $output->writeln(sprintf(
                'Reconciliation %s: status=%s paid=%d amount=%.2f writeoff=%d anomalies=%d',
                $date,
                $report['status'],
                $report['order_summary']['paid_count'],
                $report['order_summary']['paid_accounting_amount'],
                $report['writeoff_summary']['event_count'] + $report['writeoff_summary']['legacy_candidate_count'],
                $report['anomaly_count']
            ));
            $output->writeln('Report written: ' . $target);
            return 0;
        } catch (\Throwable $throwable) {
            $output->writeln('<error>' . $throwable->getMessage() . '</error>');
            return 1;
        }
    }
}
