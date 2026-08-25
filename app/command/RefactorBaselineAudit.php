<?php

declare(strict_types=1);

namespace app\command;

use app\common\model\goods\GoodsModel;
use app\common\model\member\MemberOrderModel;
use app\common\model\merchant\MerchantModel;
use app\common\service\merchant\MerchantService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class RefactorBaselineAudit extends Command
{
    private const CORE_FILES = [
        'app/common/service/member/MemberOrderService.php',
        'app/common/service/merchant/MerchantService.php',
        'app/common/service/report/MerchantPurchaseLedgerReportService.php',
        'app/common/service/report/PlatformAnalyticsService.php',
        'app/common/service/goods/GoodsService.php',
        'app/common.php',
    ];

    private const LEGACY_PATTERNS = [
        '/-1\.php$/',
        '/-2\.php$/',
        '/-22\.php$/',
        '/-1\.vue$/',
        '/backup\.(js|wxs)$/',
    ];

    private const WRITE_ACTION_PATTERN = '/^(add|edit|dele|delete|disable|auth|pay|refund|cancel|receipt|receive|writeoff|renew|memberSuper|transfer|migrate|upload|bind|unbind|switch|save|submit|confirm)/i';

    protected function configure()
    {
        $this->setName('refactor:baseline')
            ->addOption('database', null, Option::VALUE_NONE, '只读检查关键表字段和索引')
            ->setDescription('输出渐进式重构机器可读事实基线');
    }

    protected function execute(Input $input, Output $output)
    {
        $root = dirname(__DIR__, 2);
        $report = [
            'generated_at' => date('c'),
            'project_root' => str_replace('\\', '/', $root),
            'core_states' => [
                'member_order' => MemberOrderModel::STATUS,
                'member_order_role_type' => MemberOrderModel::ROLE_TYPE,
                'member_order_pay_type' => MemberOrderModel::PAY_TYPE,
                'merchant_auth' => MerchantModel::AUTH_STATE,
                'goods_auth' => GoodsModel::STATUS,
            ],
            'core_files' => $this->coreFileReport($root),
            'write_actions' => $this->writeActionReport($root),
            'legacy_candidates' => $this->legacyCandidateReport($root),
            'checks' => $this->invariantChecks(),
        ];

        if ((bool) $input->getOption('database')) {
            $report['database_schema'] = $this->databaseSchemaReport();
        }

        $report['summary'] = [
            'core_file_count' => count($report['core_files']),
            'write_action_count' => count($report['write_actions']),
            'legacy_candidate_count' => count($report['legacy_candidates']),
            'check_passed' => count(array_filter($report['checks'], static fn(array $check): bool => $check['passed'])),
            'check_failed' => count(array_filter($report['checks'], static fn(array $check): bool => !$check['passed'])),
        ];

        $json = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            $output->writeln('{"error":"baseline report json encode failed"}');
            return 2;
        }

        $output->writeln($json);
        return $report['summary']['check_failed'] > 0 ? 1 : 0;
    }

    private function coreFileReport(string $root): array
    {
        $rows = [];
        foreach (self::CORE_FILES as $relativePath) {
            $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $rows[] = [
                'file' => $relativePath,
                'exists' => is_file($path),
                'lines' => is_file($path) ? count(file($path, FILE_IGNORE_NEW_LINES)) : 0,
                'bytes' => is_file($path) ? filesize($path) : 0,
            ];
        }
        return $rows;
    }

    private function writeActionReport(string $root): array
    {
        $rows = [];
        foreach (['admin', 'api', 'merchant', 'inspection'] as $scope) {
            $directory = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $scope . DIRECTORY_SEPARATOR . 'controller';
            foreach ($this->phpFiles($directory) as $file) {
                $content = file_get_contents($file);
                if ($content === false) {
                    continue;
                }
                preg_match_all('/public\s+function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $content, $matches);
                foreach ($matches[1] ?? [] as $action) {
                    if (!preg_match(self::WRITE_ACTION_PATTERN, $action)) {
                        continue;
                    }
                    $relative = $this->relativePath($root, $file);
                    $controller = preg_replace('/\.php$/', '', substr($relative, strlen("app/{$scope}/controller/")));
                    $rows[] = [
                        'scope' => $scope,
                        'controller' => str_replace('/', '.', (string) $controller),
                        'action' => $action,
                        'file' => $relative,
                    ];
                }
            }
        }
        usort($rows, static fn(array $a, array $b): int => [$a['scope'], $a['controller'], $a['action']] <=> [$b['scope'], $b['controller'], $b['action']]);
        return $rows;
    }

    private function legacyCandidateReport(string $root): array
    {
        $rows = [];
        foreach (['app', 'zflAdminWeb/src', 'zflMerchantWeb/src', 'zflUniApp/zflUniApp/pages', 'zflUniApp/zflUniApp/uni_modules'] as $relativeDirectory) {
            $directory = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
            foreach ($this->allFiles($directory) as $file) {
                $relative = $this->relativePath($root, $file);
                foreach (self::LEGACY_PATTERNS as $pattern) {
                    if (preg_match($pattern, $relative)) {
                        $rows[] = [
                            'file' => $relative,
                            'bytes' => filesize($file),
                        ];
                        break;
                    }
                }
            }
        }
        usort($rows, static fn(array $a, array $b): int => $a['file'] <=> $b['file']);
        return $rows;
    }

    private function invariantChecks(): array
    {
        $merchantEditFields = array_keys(MerchantService::$edit_field);
        return [
            [
                'code' => 'order_cancel_status_defined',
                'passed' => MemberOrderModel::getStatus('cancel', 1) === 7,
                'expected' => 7,
                'actual' => MemberOrderModel::getStatus('cancel', 1),
            ],
            [
                'code' => 'merchant_member_binding_not_generically_editable',
                'passed' => !in_array('member_id', $merchantEditFields, true),
                'expected' => false,
                'actual' => in_array('member_id', $merchantEditFields, true),
            ],
            [
                'code' => 'merchant_super_not_generically_editable',
                'passed' => !in_array('member_is_super', $merchantEditFields, true),
                'expected' => false,
                'actual' => in_array('member_is_super', $merchantEditFields, true),
            ],
        ];
    }

    private function databaseSchemaReport(): array
    {
        $connection = (string) config('database.default', 'mysql');
        $prefix = (string) config("database.connections.{$connection}.prefix", '');
        $rows = [];
        foreach (['member_order', 'member_order_detailed', 'member_order_log', 'merchant', 'goods', 'member'] as $table) {
            $fullTable = $prefix . $table;
            $columns = Db::query('SHOW COLUMNS FROM `' . str_replace('`', '``', $fullTable) . '`');
            $indexes = Db::query('SHOW INDEX FROM `' . str_replace('`', '``', $fullTable) . '`');
            $rows[] = [
                'table' => $fullTable,
                'columns' => array_map(static fn(array $column): array => [
                    'name' => $column['Field'] ?? '',
                    'type' => $column['Type'] ?? '',
                    'nullable' => ($column['Null'] ?? '') === 'YES',
                    'default' => $column['Default'] ?? null,
                ], $columns),
                'indexes' => array_values(array_map(static fn(array $index): array => [
                    'name' => $index['Key_name'] ?? '',
                    'column' => $index['Column_name'] ?? '',
                    'sequence' => intval($index['Seq_in_index'] ?? 0),
                    'unique' => intval($index['Non_unique'] ?? 1) === 0,
                ], $indexes)),
            ];
        }
        return $rows;
    }

    private function phpFiles(string $directory): array
    {
        return array_values(array_filter($this->allFiles($directory), static fn(string $file): bool => str_ends_with($file, '.php')));
    }

    private function allFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $files[] = $item->getPathname();
            }
        }
        return $files;
    }

    private function relativePath(string $root, string $path): string
    {
        return str_replace('\\', '/', substr($path, strlen($root) + 1));
    }
}
