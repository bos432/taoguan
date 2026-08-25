<?php

declare(strict_types=1);

namespace app\command;

use app\common\support\refactor\SqlSnapshotAudit;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class RefactorSnapshotAudit extends Command
{
    protected function configure()
    {
        $this->setName('refactor:snapshot')
            ->addOption('snapshot', null, Option::VALUE_REQUIRED, 'SQL 快照文件路径')
            ->addOption('output', null, Option::VALUE_OPTIONAL, 'JSON 报告输出路径')
            ->setDescription('只读解析 SQL 快照并输出核心表结构与脱敏业务基准');
    }

    protected function execute(Input $input, Output $output)
    {
        $snapshot = trim((string) $input->getOption('snapshot'));
        if ($snapshot === '') {
            $output->writeln('<error>必须提供 --snapshot SQL 快照路径</error>');
            return 2;
        }

        try {
            $report = (new SqlSnapshotAudit())->audit($snapshot);
            $json = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            if ($json === false) {
                throw new \RuntimeException('Snapshot report JSON encode failed');
            }
            $json .= PHP_EOL;

            $target = trim((string) $input->getOption('output'));
            if ($target !== '') {
                $directory = dirname($target);
                if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                    throw new \RuntimeException("Cannot create output directory: {$directory}");
                }
                if (file_put_contents($target, $json) === false) {
                    throw new \RuntimeException("Cannot write snapshot report: {$target}");
                }
                $output->writeln("Snapshot baseline written: {$target}");
            } else {
                $output->write($json);
            }
            return 0;
        } catch (\Throwable $throwable) {
            $output->writeln('<error>' . $throwable->getMessage() . '</error>');
            return 1;
        }
    }
}
