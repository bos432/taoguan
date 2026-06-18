<?php

namespace app\common\service\setting;

use app\common\cache\setting\AccordCache;
use app\common\model\setting\AccordModel;

class AccordService
{
    private static $builtin_sync_checked = false;

    private static $broken_text_fragments = [
        '骞冲彴',
        '鍗忚',
        '闅愮',
        '鍏嶈矗',
        '鍞悗',
        '鐢ㄦ埛',
        '璇存槑',
        '鏁版嵁',
        '鏃堕棿',
        '绠＄悊',
    ];

    private static $placeholder_text_fragments = [
        '当前后台尚未配置',
        '请在后台协议管理中补充正式',
        '建议上线前在后台“协议管理”里补充完整正式文案',
        '系统初始化协议',
        '系统内置协议占位内容',
    ];

    public static $edit_field = [
        'accord_id/d' => '',
        'unique/s' => '',
        'name/s' => '',
        'desc/s' => '',
        'content/s' => '',
        'remark/s' => '',
        'sort/d' => 250,
    ];

    public static function list($where = [], $page = 1, $limit = 10, $order = [], $field = '')
    {
        self::ensureBuiltinAccordsHealthy();

        $model = new AccordModel();
        $pk = $model->getPk();

        if (empty($field)) {
            $field = $pk . ',unique,name,desc,remark,sort,is_disable,create_time,update_time';
        }
        if (empty($order)) {
            $order = ['sort' => 'desc', $pk => 'desc'];
        }

        $count = $model->where($where)->count();
        $pages = 0;
        if ($page > 0) {
            $model = $model->page($page);
        }
        if ($limit > 0) {
            $model = $model->limit($limit);
            $pages = ceil($count / $limit);
        }
        $list = $model->field($field)->where($where)->order($order)->select()->toArray();
        $list = self::sanitizeAccordList($list);

        return compact('count', 'pages', 'page', 'limit', 'list');
    }

    public static function info($id, $exce = true)
    {
        self::ensureBuiltinAccordsHealthy();

        $info = AccordCache::get($id);
        if (empty($info)) {
            $model = new AccordModel();
            $pk = $model->getPk();

            if (is_numeric($id)) {
                $where[] = [$pk, '=', $id];
            } else {
                $where[] = ['unique', '=', $id];
                $where[] = where_delete();
            }

            $info = $model->where($where)->find();
            if (empty($info)) {
                if ($exce) {
                    exception('协议不存在：' . $id);
                }
                return [];
            }
            $info = $info->toArray();

            AccordCache::set($id, $info);
        }

        $default = self::defaultAccordByUnique($info['unique'] ?? '');
        if (!empty($default)) {
            $info = self::sanitizeAccordItem($info, $default);
        }

        return $info;
    }

    public static function frontendList($name = '', $unique = '')
    {
        $where[] = ['accord_id', '>', 0];
        if ($name !== '') {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        if ($unique !== '') {
            $where[] = ['unique', 'in', $unique];
        }
        $where[] = where_disable();
        $where[] = where_delete();

        $data = self::list($where, 1, 0, ['sort' => 'desc', 'accord_id' => 'desc'], 'accord_id,unique,name,desc,content,remark,sort,is_disable,is_delete,create_time,update_time');
        $list = $data['list'] ?? [];

        $defaultAccords = self::defaultAccords();
        $uniqueList = self::parseUniqueList($unique);
        $listMap = [];
        foreach ($list as $item) {
            $defaults = self::defaultAccords();
            $default = $defaults[$item['unique']] ?? [];
            $listMap[$item['unique']] = self::sanitizeAccordItem($item, $default);
        }

        foreach ($defaultAccords as $default) {
            if (!empty($uniqueList) && !in_array($default['unique'], $uniqueList, true)) {
                continue;
            }
            if ($name !== '' && mb_strpos(($default['name'] ?? '') . ($default['desc'] ?? ''), $name) === false) {
                continue;
            }
            if (!isset($listMap[$default['unique']])) {
                $listMap[$default['unique']] = $default;
            }
        }

        $merged = array_values($listMap);
        usort($merged, function ($left, $right) {
            $leftSort = intval($left['sort'] ?? 0);
            $rightSort = intval($right['sort'] ?? 0);
            if ($leftSort === $rightSort) {
                return intval($right['accord_id'] ?? 0) <=> intval($left['accord_id'] ?? 0);
            }
            return $rightSort <=> $leftSort;
        });

        return [
            'count' => count($merged),
            'pages' => 1,
            'page' => 1,
            'limit' => count($merged),
            'list' => $merged,
        ];
    }

    public static function frontendInfo($id)
    {
        $info = self::info($id, false);
        if (!empty($info) && empty($info['is_disable']) && empty($info['is_delete'])) {
            $defaults = !is_numeric($id) ? self::defaultAccords() : [];
            $default = !is_numeric($id) && isset($defaults[$id]) ? $defaults[$id] : [];
            return self::sanitizeAccordItem($info, $default);
        }

        if (!is_numeric($id)) {
            $defaults = self::defaultAccords();
            if (isset($defaults[$id])) {
                return $defaults[$id];
            }
        }

        return [];
    }

    public static function frontendResolveByUniques($accordUniques = [])
    {
        self::ensureBuiltinAccordsHealthy();

        $accordUniques = array_values(array_unique(array_filter(array_map(function ($item) {
            return trim((string) $item);
        }, (array) $accordUniques))));

        if (empty($accordUniques)) {
            return [];
        }

        $rows = (new AccordModel())
            ->whereIn('unique', $accordUniques)
            ->where('is_disable', 0)
            ->where('is_delete', 0)
            ->field('accord_id,unique,name,desc,content,sort,is_disable,is_delete')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['unique']] = $row;
        }

        $defaults = self::defaultAccords();
        $resolved = [];
        foreach ($accordUniques as $unique) {
            if (isset($map[$unique])) {
                $resolved[] = self::sanitizeAccordItem($map[$unique], $defaults[$unique] ?? []);
                continue;
            }
            if (isset($defaults[$unique])) {
                $resolved[] = $defaults[$unique];
            }
        }

        return $resolved;
    }

    public static function add($param)
    {
        $model = new AccordModel();
        $pk = $model->getPk();

        unset($param[$pk]);

        $param['create_uid'] = user_id();
        $param['create_time'] = datetime();
        if (empty($param['unique'] ?? '')) {
            $param['unique'] = uniqids();
        }

        $model->save($param);
        $id = $model->$pk;
        if (empty($id)) {
            exception();
        }

        $param[$pk] = $id;

        return $param;
    }

    public static function edit($ids, $param = [])
    {
        $model = new AccordModel();
        $pk = $model->getPk();

        unset($param[$pk], $param['ids']);

        $param['update_uid'] = user_id();
        $param['update_time'] = datetime();

        $unique = $model->where($pk, 'in', $ids)->column('unique');

        $res = $model->where($pk, 'in', $ids)->update($param);
        if (empty($res)) {
            exception();
        }

        $param['ids'] = $ids;

        AccordCache::del($ids);
        AccordCache::del($unique);

        return $param;
    }

    public static function dele($ids, $real = false)
    {
        $model = new AccordModel();
        $pk = $model->getPk();

        $unique = $model->where($pk, 'in', $ids)->column('unique');

        if ($real) {
            $res = $model->where($pk, 'in', $ids)->delete();
        } else {
            $update = delete_update();
            $res = $model->where($pk, 'in', $ids)->update($update);
        }

        if (empty($res)) {
            exception();
        }

        $update['ids'] = $ids;

        AccordCache::del($ids);
        AccordCache::del($unique);

        return $update;
    }

    private static function parseUniqueList($unique = '')
    {
        if ($unique === '') {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $unique)))));
    }

    private static function sanitizeAccordList($list = [])
    {
        foreach ($list as $index => $item) {
            $default = self::defaultAccordByUnique($item['unique'] ?? '');
            if (empty($default)) {
                continue;
            }
            $list[$index] = self::sanitizeAccordItem($item, $default);
        }

        return $list;
    }

    private static function defaultAccordByUnique($unique = '')
    {
        $unique = trim((string) $unique);
        if ($unique === '') {
            return [];
        }

        $defaults = self::defaultAccords();

        return $defaults[$unique] ?? [];
    }

    private static function ensureBuiltinAccordsHealthy()
    {
        if (self::$builtin_sync_checked) {
            return;
        }

        self::$builtin_sync_checked = true;

        try {
            $defaults = self::defaultAccords();
            $defaultUniques = array_keys($defaults);
            if (empty($defaultUniques)) {
                return;
            }

            $rows = (new AccordModel())
                ->whereIn('unique', $defaultUniques)
                ->where('is_delete', 0)
                ->order(['accord_id' => 'desc'])
                ->select()
                ->toArray();

            $currentMap = [];
            foreach ($rows as $row) {
                $unique = trim((string) ($row['unique'] ?? ''));
                if ($unique === '' || isset($currentMap[$unique])) {
                    continue;
                }
                $currentMap[$unique] = $row;
            }

            $cacheKeys = [];
            foreach ($defaults as $unique => $default) {
                if (isset($currentMap[$unique])) {
                    $current = $currentMap[$unique];
                    $repair = self::buildAccordRepairPayload($current, $default);
                    if (empty($repair)) {
                        continue;
                    }

                    $repair['update_uid'] = 0;
                    $repair['update_time'] = datetime();

                    (new AccordModel())
                        ->where('accord_id', intval($current['accord_id'] ?? 0))
                        ->update($repair);

                    $cacheKeys[] = intval($current['accord_id'] ?? 0);
                    $cacheKeys[] = $unique;
                    continue;
                }

                $payload = self::buildDefaultAccordInsertPayload($default);
                $model = new AccordModel();
                $model->save($payload);

                $cacheKeys[] = intval($model->getAttr($model->getPk()) ?? 0);
                $cacheKeys[] = $unique;
            }

            $cacheKeys = array_values(array_unique(array_filter($cacheKeys, function ($item) {
                return $item !== '' && $item !== 0;
            })));
            if (!empty($cacheKeys)) {
                AccordCache::del($cacheKeys);
            }
        } catch (\Throwable $e) {
        }
    }

    private static function buildAccordRepairPayload($item = [], $default = [])
    {
        $sanitized = self::sanitizeAccordItem($item, $default);
        $repair = [];

        foreach (['name', 'desc', 'content', 'remark'] as $field) {
            if (!array_key_exists($field, $sanitized)) {
                continue;
            }
            if (($item[$field] ?? null) === $sanitized[$field]) {
                continue;
            }
            $repair[$field] = $sanitized[$field];
        }

        return $repair;
    }

    private static function buildDefaultAccordInsertPayload($default = [])
    {
        return [
            'unique' => trim((string) ($default['unique'] ?? '')),
            'name' => (string) ($default['name'] ?? ''),
            'desc' => (string) ($default['desc'] ?? ''),
            'content' => (string) ($default['content'] ?? ''),
            'remark' => (string) ($default['remark'] ?? ''),
            'sort' => intval($default['sort'] ?? 250),
            'is_disable' => 0,
            'is_delete' => 0,
            'create_uid' => 0,
            'update_uid' => 0,
            'create_time' => datetime(),
            'update_time' => datetime(),
        ];
    }

    private static function defaultAccords()
    {
        return [
            'disclaimer' => self::defaultAccordItem(
                -101,
                'disclaimer',
                '免责声明',
                '查看平台免责声明与使用边界说明。',
                self::renderAccordContent(
                    '免责声明',
                    [
                        '涛冠优选平台（以下简称“平台”）依据本声明向用户、商家及访问者说明平台服务边界、信息展示规则及风险承担原则。',
                        '您继续访问、注册、登录、下单、发布信息或使用平台任一功能，即视为已经阅读、理解并同意本声明的全部内容。',
                    ],
                    [
                        [
                            'title' => '一、平台服务定位',
                            'paragraphs' => [
                                '平台主要提供商品展示、信息发布、交易撮合、订单协同、客户服务及相关技术支持。除平台明确承诺的事项外，平台并非所有商品或服务的实际生产者、发货方或最终履约方。',
                                '商家作为独立经营主体，应当对其发布的商品信息、价格、库存、资质、售后承诺及履约行为依法独立承担责任。',
                            ],
                        ],
                        [
                            'title' => '二、信息展示说明',
                            'paragraphs' => [
                                '平台会尽合理商业努力对页面信息、商家资质和交易数据进行维护，但不对所有展示内容的实时性、完整性、绝对准确性或持续可得性作任何明示或默示保证。',
                                '商品图片、参数、产地、规格、物流时效、价格优惠、活动说明等信息以订单提交时页面展示及商家最终确认结果为准。',
                            ],
                        ],
                        [
                            'title' => '三、交易风险提示',
                            'paragraphs' => [
                                '用户应当结合自身需求、预算、用途和风险承受能力，自主判断是否下单或继续交易，并妥善核验订单、付款、签收及售后信息。',
                                '因用户操作失误、信息填写错误、未及时签收、未按流程举证、未及时处理售后申请等原因造成的损失，由相应责任方依法承担。',
                            ],
                        ],
                        [
                            'title' => '四、第三方服务说明',
                            'paragraphs' => [
                                '平台服务过程中可能接入支付、物流、短信、地图、云存储等第三方服务。第三方服务的稳定性、时效性、安全性及使用规则由相应服务提供方负责。',
                                '因第三方系统故障、网络拥塞、设备异常、接口调整或其他非平台可控因素导致的服务中断、延迟或数据偏差，平台将在合理范围内协助处理，但不承担超出法定范围的责任。',
                            ],
                        ],
                        [
                            'title' => '五、责任限制',
                            'paragraphs' => [
                                '在法律法规允许的范围内，平台对因不可抗力、政府行为、网络攻击、通信故障、系统维护升级或其他非因平台故意或重大过失造成的服务中断、信息缺失、交易延误或其他损失，不承担赔偿责任。',
                                '任何情况下，平台均不对用户的间接损失、预期利益损失、商誉损失、机会损失或其他扩大的连带损失承担责任；法律法规另有强制性规定的除外。',
                            ],
                        ],
                        [
                            'title' => '六、其他说明',
                            'paragraphs' => [
                                '如本声明部分条款被认定无效或不可执行，不影响其他条款的效力。',
                                '平台有权根据业务调整、规则变化和法律法规要求对本声明进行更新，并通过页面公告、站内消息或其他合理方式提示用户。',
                            ],
                        ],
                    ],
                    [
                        '如您对本声明存在疑问，可通过平台公示的客服渠道与我们联系。',
                        '如法律法规对消费者权益保护另有强制性规定的，从其规定。',
                    ]
                ),
                '系统内置正式协议文案',
                999
            ),
            'user_agreement' => self::defaultAccordItem(
                -102,
                'user_agreement',
                '用户协议',
                '查看平台用户协议与服务使用规则。',
                self::renderAccordContent(
                    '用户协议',
                    [
                        '本协议是您与涛冠优选平台之间就平台服务使用、账号管理、交易规则、权利义务及责任承担所订立的有效约定。',
                        '您勾选同意、注册账号、登录平台、浏览商品、提交订单、发布信息或以其他方式实际使用平台服务，即视为已阅读并接受本协议全部条款。',
                    ],
                    [
                        [
                            'title' => '一、账号注册与使用',
                            'paragraphs' => [
                                '您应当使用真实、合法、有效且可联系的身份信息完成注册、认证或资料补充，并保证资料持续真实、准确、完整。',
                                '账号仅限您本人或依法授权主体使用，不得出租、出借、买卖、转让、赠与或以其他方式提供给第三方使用。因账号保管不善造成的风险和损失，由账号持有人自行承担。',
                            ],
                        ],
                        [
                            'title' => '二、平台服务内容',
                            'paragraphs' => [
                                '平台向您提供商品浏览、搜索筛选、下单支付、订单跟踪、售后申请、通知提醒、数据展示、客户服务及相关技术支持等服务，具体功能以平台实际开放内容为准。',
                                '平台有权根据经营安排、系统升级、风控要求或法律法规变化，对服务内容、页面布局、功能权限和业务规则进行调整。',
                            ],
                        ],
                        [
                            'title' => '三、用户行为规范',
                            'paragraphs' => [
                                '您在使用平台服务时，应遵守中华人民共和国法律法规、行业规范和平台规则，不得发布违法、侵权、虚假、欺诈、误导、侮辱、骚扰、恶意攻击或损害平台秩序的信息。',
                                '您不得利用平台从事刷单炒信、套现洗钱、批量注册、技术攻击、数据爬取、恶意占用系统资源、绕过风控或其他影响平台安全和正常交易秩序的行为。',
                            ],
                        ],
                        [
                            'title' => '四、订单与交易规则',
                            'paragraphs' => [
                                '您提交订单前，应认真核对商品名称、规格、数量、价格、配送信息、开票信息、优惠明细及适用协议。订单提交成功后，仍应以商家确认、支付结果和履约情况为准。',
                                '如出现库存异常、价格明显错误、支付失败、风控拦截、收货信息异常或其他影响履约的情形，平台或商家有权与您协商调整、取消订单或采取其他合理处置措施。',
                            ],
                        ],
                        [
                            'title' => '五、知识产权与数据使用',
                            'paragraphs' => [
                                '平台页面、系统界面、程序代码、图文内容、数据整理、标识设计及相关知识产权依法归平台或权利人所有。未经书面许可，任何主体不得擅自复制、传播、修改、反编译、镜像或商业化使用。',
                                '对于您依法上传、发布或提交的内容，您应保证拥有合法权利，并授权平台在提供服务、处理投诉、留存凭证、合规审计和纠纷处理所必要的范围内使用。',
                            ],
                        ],
                        [
                            'title' => '六、违约处理与责任承担',
                            'paragraphs' => [
                                '如您违反本协议、平台规则或法律法规要求，平台有权视情节采取警示、限权、下架内容、冻结订单、暂停服务、注销账号、保留证据并向有关部门报告等措施。',
                                '因您的违约、侵权或违法行为导致平台、商家、其他用户或第三方遭受损失的，您应依法承担赔偿责任；平台因此先行赔付或被追责的，有权向您追偿。',
                            ],
                        ],
                        [
                            'title' => '七、协议变更与争议处理',
                            'paragraphs' => [
                                '平台有权根据业务发展和监管要求对本协议进行修订，并通过合理方式向您公示。修订后的协议自公示之日起生效；您继续使用平台服务的，视为接受修订内容。',
                                '因本协议或平台服务产生的争议，双方应先友好协商；协商不成的，任一方可向有管辖权的人民法院提起诉讼。',
                            ],
                        ],
                    ],
                    [
                        '如您不同意本协议任一条款，应立即停止注册、登录或继续使用平台服务。',
                    ]
                ),
                '系统内置正式协议文案',
                998
            ),
            'privacy_policy' => self::defaultAccordItem(
                -103,
                'privacy_policy',
                '隐私政策',
                '查看个人信息收集、使用和保护说明。',
                self::renderAccordContent(
                    '隐私政策',
                    [
                        '涛冠优选平台重视您的个人信息与隐私保护。本政策用于说明我们如何收集、存储、使用、共享和保护您的个人信息，以及您如何行使相关权利。',
                        '在您使用平台服务前，请认真阅读本政策。您注册、登录、下单、咨询、授权登录或继续使用平台服务，即表示您理解并同意本政策所述处理规则。',
                    ],
                    [
                        [
                            'title' => '一、我们收集的信息类型',
                            'paragraphs' => [
                                '为向您提供基础服务，我们可能收集账号信息、联系人信息、收货地址、订单信息、支付状态、售后记录、发票信息、客服沟通记录及您主动提交的其他资料。',
                                '为保障平台安全与服务稳定，我们可能收集设备信息、日志信息、访问记录、IP 地址、浏览操作、异常行为识别信息及相关安全风控信息。',
                            ],
                        ],
                        [
                            'title' => '二、信息使用目的',
                            'paragraphs' => [
                                '我们将基于账号注册登录、身份核验、交易履约、订单管理、物流协同、售后处理、风险控制、通知触达、运营分析、客户服务和合规管理等必要目的使用您的信息。',
                                '如需将您的信息用于超出前述必要范围的新用途，我们会依法通过页面提示、单独授权或其他适当方式再次征求您的同意。',
                            ],
                        ],
                        [
                            'title' => '三、信息共享与委托处理',
                            'paragraphs' => [
                                '在交易履约必需范围内，我们可能与商家、物流服务商、支付机构、短信服务商、技术服务商或客服合作方共享必要信息，但仅限完成服务所必需的最小范围。',
                                '如因监管要求、司法机关调查、行政执法、企业并购重组或保障平台、用户及公众合法权益所必须时，我们可能依法披露或转移相关信息。',
                            ],
                        ],
                        [
                            'title' => '四、信息存储与安全',
                            'paragraphs' => [
                                '我们会采取访问控制、权限隔离、传输加密、日志审计、风险识别等合理安全措施保护您的个人信息，努力防止信息被未经授权访问、披露、篡改或丢失。',
                                '我们仅在实现服务目的所必需的期限内保存您的信息；法律法规另有规定或争议处理、审计取证确有需要的，我们会依法延长保存期限。',
                            ],
                        ],
                        [
                            'title' => '五、您的权利',
                            'paragraphs' => [
                                '您有权依法访问、更正、补充、更新或删除您的个人信息，并可在符合平台规则和法律法规要求的情况下申请注销账号、撤回授权或要求解释说明。',
                                '当您撤回授权或申请删除信息后，部分依赖相关信息的服务功能可能无法继续使用；但在法律法规要求保存、争议处理或交易留痕场景下，我们仍可能继续保留必要信息。',
                            ],
                        ],
                        [
                            'title' => '六、未成年人保护',
                            'paragraphs' => [
                                '如您为未成年人，请在监护人陪同下阅读本政策，并在取得监护人同意后使用平台服务。',
                                '如我们发现未成年人在未取得监护人同意的情况下提供个人信息，将依法采取删除、屏蔽或停止服务等合理措施。',
                            ],
                        ],
                        [
                            'title' => '七、政策更新',
                            'paragraphs' => [
                                '我们可能根据业务变化、服务调整和法律法规要求对本政策进行更新，并通过官网、站内公告、消息提醒或其他适当方式向您告知。',
                                '更新后的政策一经发布生效；如更新内容可能对您的权利义务产生重大影响，我们会以更显著的方式提示您。',
                            ],
                        ],
                    ],
                    [
                        '如您对本政策有疑问、意见或投诉建议，可通过平台公示客服渠道与我们联系。',
                    ]
                ),
                '系统内置正式协议文案',
                997
            ),
            'after_sales_policy' => self::defaultAccordItem(
                -104,
                'after_sales_policy',
                '售后/退货说明',
                '查看下单、售后、退款和退货规则。',
                self::renderAccordContent(
                    '售后/退货说明',
                    [
                        '本说明适用于您在涛冠优选平台购买商品或使用相关服务时，就售后申请、退货退款、责任划分和处理流程所应遵循的规则。',
                        '商品详情页、活动规则页、商家单独承诺或法律法规另有规定的，从其特别约定或强制性规定。',
                    ],
                    [
                        [
                            'title' => '一、售后适用范围',
                            'paragraphs' => [
                                '如出现商品错发、漏发、质量异常、运输破损、与页面描述严重不符、商家无法履约或法律法规规定应支持退款退货的情形，您可依平台流程发起售后申请。',
                                '对于生鲜易腐、定制作业、临期处理、已拆封影响二次销售、在线下载类商品或页面已依法明示不适用无理由退货的商品，售后范围将依法律法规和商品页面说明执行。',
                            ],
                        ],
                        [
                            'title' => '二、申请时效与举证要求',
                            'paragraphs' => [
                                '您应在发现问题后尽快通过订单页面、客服渠道或平台指定入口提交售后申请，并提供订单号、问题描述、图片视频、签收情况等必要证明材料。',
                                '如因您未在合理期间内提交申请、拒不配合核验、无法提供基本举证材料或自行处理导致问题无法核实，平台和商家有权根据现有事实作出处理判断。',
                            ],
                        ],
                        [
                            'title' => '三、处理流程',
                            'paragraphs' => [
                                '平台或商家在收到申请后，会结合订单信息、物流记录、沟通凭证、商品页面说明及您提交的证据进行审核，并在合理期限内给出处理意见。',
                                '处理结果可能包括补发、换货、维修、部分退款、全额退款、协商补偿、驳回申请或其他依法合规的解决方案。',
                            ],
                        ],
                        [
                            'title' => '四、退货与退款规则',
                            'paragraphs' => [
                                '需要退货的，您应按照平台或商家提供的退回地址、包装要求和时限安排寄回，并确保商品、配件、赠品、票据及相关资料保持可核验状态。',
                                '退款原则上按原支付路径退回；如受支付渠道限制或法律法规要求影响，平台或商家可在与您协商后采用其他合法方式退款。',
                            ],
                        ],
                        [
                            'title' => '五、运费与责任划分',
                            'paragraphs' => [
                                '因商品质量问题、错发漏发、履约错误或商家责任导致退换货的，退回运费及再次发货费用原则上由责任方承担。',
                                '因用户个人原因发起且依法可以退货的，相关运费承担方式以商品页面说明、活动规则、商家承诺及法律法规规定为准。',
                            ],
                        ],
                        [
                            'title' => '六、特别说明',
                            'paragraphs' => [
                                '如订单存在异常支付、恶意索赔、虚假举证、频繁不当售后、影响商品二次销售或其他违背诚实信用原则的行为，平台有权要求补充材料、暂停处理、驳回申请或采取风控措施。',
                                '售后处理过程中，如平台介入协调，不视为平台对商品质量或商家责任作出实质性担保。平台将基于现有证据、公平原则和平台规则协助处理争议。',
                            ],
                        ],
                    ],
                    [
                        '如法律法规对消费者退货退款权利另有更有利规定的，从其规定。',
                    ]
                ),
                '系统内置正式协议文案',
                996
            ),
        ];
    }

    private static function defaultAccordItem($accordId, $unique, $name, $desc, $contentHtml, $remark = '系统内置正式协议文案', $sort = 999)
    {
        return [
            'accord_id' => $accordId,
            'unique' => $unique,
            'name' => $name,
            'desc' => $desc,
            'content' => $contentHtml,
            'remark' => $remark,
            'sort' => $sort,
            'is_disable' => 0,
            'is_delete' => 0,
            'create_time' => '',
            'update_time' => '',
        ];
    }

    private static function renderAccordContent($title, $lead = [], $sections = [], $footer = [])
    {
        $parts = ['<article class="tg-accord">'];
        $parts[] = '<h1>' . self::escapeAccordHtml($title) . '</h1>';

        foreach ((array) $lead as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph === '') {
                continue;
            }
            $parts[] = '<p>' . self::escapeAccordHtml($paragraph) . '</p>';
        }

        foreach ((array) $sections as $section) {
            $sectionTitle = trim((string) ($section['title'] ?? ''));
            $paragraphs = (array) ($section['paragraphs'] ?? []);
            $items = (array) ($section['items'] ?? []);

            if ($sectionTitle === '' && empty($paragraphs) && empty($items)) {
                continue;
            }

            $parts[] = '<section>';
            if ($sectionTitle !== '') {
                $parts[] = '<h2>' . self::escapeAccordHtml($sectionTitle) . '</h2>';
            }

            foreach ($paragraphs as $paragraph) {
                $paragraph = trim((string) $paragraph);
                if ($paragraph === '') {
                    continue;
                }
                $parts[] = '<p>' . self::escapeAccordHtml($paragraph) . '</p>';
            }

            if (!empty($items)) {
                $parts[] = '<ul>';
                foreach ($items as $item) {
                    $item = trim((string) $item);
                    if ($item === '') {
                        continue;
                    }
                    $parts[] = '<li>' . self::escapeAccordHtml($item) . '</li>';
                }
                $parts[] = '</ul>';
            }

            $parts[] = '</section>';
        }

        foreach ((array) $footer as $paragraph) {
            $paragraph = trim((string) $paragraph);
            if ($paragraph === '') {
                continue;
            }
            $parts[] = '<p>' . self::escapeAccordHtml($paragraph) . '</p>';
        }

        $parts[] = '</article>';

        return implode('', $parts);
    }

    private static function escapeAccordHtml($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }

    private static function sanitizeAccordItem($item = [], $default = [])
    {
        foreach (['name', 'desc', 'content', 'remark'] as $field) {
            $value = (string) ($item[$field] ?? '');
            if ((self::isBrokenText($value) || self::isPlaceholderText($value)) && isset($default[$field])) {
                $item[$field] = $default[$field];
            }
        }

        if (empty($item['name']) && !empty($default['name'])) {
            $item['name'] = $default['name'];
        }
        if (empty($item['desc']) && !empty($default['desc'])) {
            $item['desc'] = $default['desc'];
        }
        if (empty($item['content']) && !empty($default['content'])) {
            $item['content'] = $default['content'];
        }

        return $item;
    }

    private static function isPlaceholderText($value = '')
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        foreach (self::$placeholder_text_fragments as $fragment) {
            if (mb_strpos($value, $fragment) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function isBrokenText($value = '')
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        $normalized = strip_tags($value);
        $normalized = preg_replace('/[\s\/\\\\|,，。；;：:（）()\[\]\-—_]+/u', '', $normalized);
        if ($normalized !== '' && preg_match('/^\?+$/', $normalized) === 1) {
            return true;
        }

        if ($normalized !== '') {
            $questionOnly = preg_replace('/[^\?？]/u', '', $normalized);
            $questionCount = mb_strlen((string) $questionOnly);
            $totalCount = mb_strlen((string) $normalized);
            if ($questionCount >= 6 && $totalCount > 0 && ($questionCount / $totalCount) >= 0.2) {
                return true;
            }
        }

        foreach (self::$broken_text_fragments as $fragment) {
            if (mb_strpos($value, $fragment) !== false) {
                return true;
            }
        }

        return false;
    }
}
