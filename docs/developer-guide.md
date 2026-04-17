# FluxPay 开发者技术手册

本手册面向 **二次开发人员**，涵盖五大块：

1. [如何对接第三方支付（网关抽象化）](#一第三方对接与网关抽象)
2. [如何进行完整测试](#二完整测试指南)
3. [除错注意事项与方法](#三除错指南)
4. [多语言（i18n）管理](#四多语言i18n管理)
5. [列表筛选规范（FilterPanel）](#五列表筛选规范filterpanel)

阅读前请先熟悉 `docs/architecture.md` 的分层约定与 `CLAUDE.md` 的代码规范。

---

## 一、第三方对接与网关抽象

FluxPay 通过 **接口 + 抽象基类 + 工厂 + 3 层配置合并** 的方式，让新增一家供应商只需写一个 Gateway 类即可，**无需改动任何业务流程代码**。

### 1.1 抽象层整体

```
┌───────────────────────────────────────────────────────────────┐
│  PaymentGatewayInterface (App\Contracts\Gateway)              │
│    depositApply / depositQuery / depositCallback              │
│    withdrawApply / withdrawQuery / withdrawCallback           │
│    balanceQuery                                               │
│    supportsDeposit / supportsWithdraw                         │
└──────────────────────────┬────────────────────────────────────┘
                           │ implements
                           ▼
┌───────────────────────────────────────────────────────────────┐
│  AbstractPaymentGateway (App\Services\Gateway)                │
│    setConfig / setVendorId                                    │
│    makeHttpRequest()     ← 统一 HTTP 客户端，自动重试、日志   │
│    generateSignature()   ← 默认 MD5，可覆盖                   │
│    logInfo / logError                                         │
└──────────────────────────┬────────────────────────────────────┘
                           │ extends
      ┌────────────────────┼────────────────────┐
      ▼                    ▼                    ▼
TestpayGateway       <YourGateway>        <AnotherGateway>
(app/Services/Gateway/Vendors/)
```

请求时，由 `PaymentGatewayFactory` 根据 `providers.vendor_id` 找到对应类，合并三层配置后实例化。业务代码（`DepositService` / `WithdrawService`）只依赖接口，不关心具体供应商。

### 1.2 接口与 DTO

#### 接口（`app/Contracts/Gateway/PaymentGatewayInterface.php`）

```php
interface PaymentGatewayInterface
{
    public function depositApply(array $data): DepositApplyResult;
    public function depositQuery(array $data): DepositCallbackResult;
    public function depositCallback(array $data, array $options = []): DepositCallbackResult;
    public function withdrawApply(array $data): WithdrawApplyResult;
    public function withdrawQuery(array $data): WithdrawCallbackResult;
    public function withdrawCallback(array $data, array $options = []): WithdrawCallbackResult;
    public function balanceQuery(): BalanceQueryResult;
    public function supportsDeposit(): bool;
    public function supportsWithdraw(): bool;
}
```

#### DTO（`app/DTOs/Gateway/`）

所有网关方法返回强类型的只读 DTO，业务层据此决策：

| DTO | 关键字段 |
|-----|----------|
| `DepositApplyResult` | `success`, `providerOrderNo`, `payUrl`, `qrContent`, `rawData` |
| `DepositCallbackResult` | `success`, `systemOrderNo`, `providerOrderNo`, `status` (`OrderStatus` 枚举), `actualAmount`, `rawData` |
| `WithdrawApplyResult` | `success`, `providerOrderNo`, `rawData` |
| `WithdrawCallbackResult` | `success`, `systemOrderNo`, `providerOrderNo`, `status`, `rawData` |
| `BalanceQueryResult` | `success`, `availableBalance`, `holdBalance`, `rawData` |

**一个核心要求**：回调解析必须能识别出 **我方的** `systemOrderNo`，因为业务层据此定位订单。通常做法：

- 下单时将 `system_order_no` 作为 `out_trade_no` / `merchantOrderNo` 透传给供应商
- 回调时从供应商报文里取出该字段回写 `DepositCallbackResult::systemOrderNo`

### 1.3 工厂的配置合并

`PaymentGatewayFactory::createFromProvider()` 按以下优先级合并配置（后者覆盖前者）：

```
config/gateways.php 的默认值
  └── providers.vendor_meta (JSON，后台可编辑)
        └── 系统固定字段 (provider_id, provider_no, vendor_id)
```

这样：

- **代码不变，参数可调**：API key 换了只需在后台改 `vendor_meta`
- **类名与默认超时写在代码里**：`config/gateways.php` 控制
- **同一驱动支持多家商户号**：每条 `providers` 记录有独立 `provider_no`

### 1.4 新增一家供应商的标准步骤

假设要接入 **Acmepay**，其文档规定：

- 代收下单：`POST {host}/api/v1/pay/create`，参数 `appId`、`outTradeNo`、`amount`、`notifyUrl`、`sign`
- 签名：`appId + outTradeNo + amount + appSecret` → MD5 大写
- 回调：表单 POST，字段 `outTradeNo`、`tradeStatus`、`sign`

#### 步骤 1：建立 Gateway 类

`app/Services/Gateway/Vendors/AcmepayGateway.php`：

```php
<?php

namespace App\Services\Gateway\Vendors;

use App\DTOs\Gateway\BalanceQueryResult;
use App\DTOs\Gateway\DepositApplyResult;
use App\DTOs\Gateway\DepositCallbackResult;
use App\DTOs\Gateway\WithdrawApplyResult;
use App\DTOs\Gateway\WithdrawCallbackResult;
use App\Enums\OrderStatus;
use App\Services\Gateway\AbstractPaymentGateway;

class AcmepayGateway extends AbstractPaymentGateway
{
    public function depositApply(array $data): DepositApplyResult
    {
        $payload = [
            'appId'      => $this->getConfigValue('app_id'),
            'outTradeNo' => $data['system_order_no'],
            'amount'     => $data['amount'],
            'notifyUrl'  => $data['notify_url'],
        ];
        $payload['sign'] = $this->sign($payload);

        $host = rtrim($this->getConfigValue('host'), '/');
        $resp = $this->makeHttpRequest("{$host}/api/v1/pay/create", $payload);

        $ok = ($resp['code'] ?? -1) === 0;

        return new DepositApplyResult(
            success:         $ok,
            providerOrderNo: $resp['data']['tradeNo'] ?? null,
            payUrl:          $resp['data']['payUrl'] ?? null,
            rawData:         $resp,
        );
    }

    public function depositCallback(array $data, array $options = []): DepositCallbackResult
    {
        if (! $this->verifyCallback($data)) {
            $this->logError('depositCallback', 'signature mismatch', $data);
            return new DepositCallbackResult(success: false, rawData: $data);
        }

        $status = match ($data['tradeStatus'] ?? '') {
            'SUCCESS' => OrderStatus::SUCCESS,
            'FAILED'  => OrderStatus::FAILED,
            default   => OrderStatus::PENDING,
        };

        return new DepositCallbackResult(
            success:         true,
            systemOrderNo:   $data['outTradeNo']   ?? null,
            providerOrderNo: $data['tradeNo']      ?? null,
            status:          $status,
            actualAmount:    (string) ($data['amount'] ?? '0'),
            rawData:         $data,
        );
    }

    // depositQuery / withdrawApply / withdrawQuery / withdrawCallback / balanceQuery 同理…

    private function sign(array $params): string
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            if ($k === 'sign' || $v === '' || $v === null) continue;
            $str .= $k . $v;
        }
        $str .= $this->getConfigValue('app_secret');
        return strtoupper(md5($str));
    }

    private function verifyCallback(array $data): bool
    {
        $expected = $this->sign(collect($data)->except('sign')->toArray());
        return hash_equals($expected, $data['sign'] ?? '');
    }
}
```

#### 步骤 2：在 `config/gateways.php` 注册

```php
return [
    'testpay' => [
        'classname' => App\Services\Gateway\Vendors\TestpayGateway::class,
        'timeout'   => 30,
        'retry'     => 2,
    ],

    // 新增
    'acmepay' => [
        'classname' => App\Services\Gateway\Vendors\AcmepayGateway::class,
        'timeout'   => 30,
        'retry'     => 2,
    ],
];
```

> 该文件只登记 **默认值与类名**。`app_id` / `app_secret` / `host` 放到后台。

#### 步骤 3：后台建立 Provider 记录

**主体管理 → 供应商 → 新增**：

- `vendor_id = acmepay`（与 `config/gateways.php` 的 key 完全一致）
- `provider_no`：Acmepay 分配给我们的商户号
- `vendor_meta`（JSON）：
  ```json
  {
    "host": "https://api.acmepay.com",
    "app_id": "20250101XXXXX",
    "app_secret": "xxxxxxxxxxxxxxxx"
  }
  ```
- `call_back_ips`：Acmepay 文档提供的回调来源 IP
- `bank_config_key`：若银行代码需映射，填入对应 key

#### 步骤 4：创建通道（ProviderPaymentType）

**通道配置 → 供应商通道 → 新增**：为该 provider 绑定 `deposit` / `withdraw` 的支付方式，配置费率、权重、单笔 / 日限额。

#### 步骤 5：分配给商户

**通道配置 → 商户费率 / 通道分配**：把新通道授权给目标商户。

#### 步骤 6：自测

使用 Laravel Tinker 直接调用：

```bash
php8.2 artisan tinker
```

```php
$factory = app(\App\Services\Gateway\PaymentGatewayFactory::class);
$gateway = $factory->createByVendorId('acmepay');

$result = $gateway->depositApply([
    'system_order_no' => 'FP_TEST_' . time(),
    'amount'          => '100.00',
    'notify_url'      => url('/api/deposit/acmepay/callback'),
]);

dd($result);
```

若成功拿到 `payUrl`，抽象层已经接通；否则看下一节的除错。

### 1.5 Gateway 编写守则

| 守则 | 说明 |
|------|------|
| **只做协议翻译** | Gateway 不写业务逻辑、不读数据库、不发事件，只负责把上层入参翻译成供应商报文，并把响应翻译回 DTO |
| **返回 DTO，不抛业务异常** | 网络 / 解析失败可抛异常（由 `makeHttpRequest` 自动记日志），但业务失败（如余额不足）用 `success=false` + `rawData` 反馈，由 Service 决定 |
| **无状态** | 不持有订单、商户等状态，所有数据从参数入；实例会被多请求复用 |
| **签名集中在私有方法** | 不要把签名逻辑散在 apply/query/callback |
| **回调幂等靠 Controller 层** | Gateway 只解析，幂等由 `ProviderCallbackController` 检查 `order->status->isFinal()` |
| **所有 HTTP 走 `makeHttpRequest`** | 自动使用配置的 `timeout`、`retry` 并写日志 |
| **错误日志用 `logError`** | 带上 `vendorId` 前缀，便于日志过滤 |

### 1.6 回调接入要点

路由已经统一：

```
POST /api/deposit/{vendor}/callback
POST /api/withdraw/{vendor}/callback
```

`{vendor}` 对应 `providers.provider_no`（见 `ProviderCallbackMiddleware`）。注册供应商时把此 URL 给到对方后台即可，**无需再写新路由**。

回调处理顺序：

1. `ProviderCallbackMiddleware` 按 `provider_no` 查库，校验 IP 白名单
2. `ProviderCallbackController` 调 `Gateway::depositCallback/withdrawCallback` 解析
3. 解析失败 → 写 warning log，返回 `fail`
4. 找到对应 `DepositOrder` / `WithdrawOrder`
5. 若订单已处于终态（`isFinal`），直接返回 `success`（天然幂等）
6. 否则派发 `DepositCallbackReceived` / `WithdrawCallbackReceived` 事件，异步结算 + 通知商户

---

## 二、完整测试指南

### 2.1 测试栈

- **PHPUnit 11**（`phpunit.xml` 已配置）
- **RefreshDatabase** Trait：每个测试自动跑 migration 并回滚
- **Factories + Seeders**：构造测试数据
- **FakeGateway**（`tests/Stubs/FakeGateway.php`）：替身网关，无外部 HTTP
- **测试环境**：`phpunit.xml` 中已设 `QUEUE_CONNECTION=sync`、`CACHE_STORE=array`、`SESSION_DRIVER=array`，事件与队列同步执行，断言更好写

### 2.2 目录结构

```
tests/
├── TestCase.php                 # 全局基类
├── Stubs/
│   └── FakeGateway.php          # 可注入任意结果的假网关
├── Unit/
│   ├── Helpers/
│   │   ├── SignatureHelperTest.php
│   │   └── MoneyHelperTest.php
│   └── Services/
│       └── CommissionCalculatorTest.php
└── Feature/
    ├── Api/
    │   ├── DepositApplyTest.php
    │   └── BalanceQueryTest.php
    └── Admin/
        └── ScreenSmokeTest.php   # 后台全屏幕冒烟
```

### 2.3 跑测试

```bash
# 全部测试
php8.2 artisan test

# 只跑 Unit / Feature
php8.2 artisan test --testsuite=Unit
php8.2 artisan test --testsuite=Feature

# 跑单一文件 / 单一方法
php8.2 artisan test tests/Feature/Api/DepositApplyTest.php
php8.2 artisan test --filter=deposit_apply_with_valid_signature_returns_success

# 覆盖率（需 xdebug 或 pcov）
php8.2 vendor/bin/phpunit --coverage-html storage/coverage
```

### 2.4 测试数据库

目前 `phpunit.xml` **未** 启用 `sqlite :memory:`，会使用 `.env.testing`（或默认 `.env`）的 MySQL 连接。建议：

1. 复制：`cp .env.example .env.testing`
2. 修改 `.env.testing`：
   ```dotenv
   DB_DATABASE=fluxpay_testing
   QUEUE_CONNECTION=sync
   CACHE_STORE=array
   ```
3. 建库：`CREATE DATABASE fluxpay_testing CHARACTER SET utf8mb4;`
4. 跑 `php8.2 artisan test --env=testing`

`RefreshDatabase` 会自动管理 migration。

### 2.5 单元测试（Unit）

针对纯函数 / 单一服务，不碰 HTTP / DB。

示例：`tests/Unit/Services/CommissionCalculatorTest.php`

```php
#[Test]
public function calculates_percentage_commission_for_multiple_agents(): void
{
    $mpt = MerchantPaymentType::factory()->make([
        'deposit_fee_type'    => FeeType::PERCENTAGE->value,
        'deposit_fee'         => '2.0',
        'deposit_agents_fee'  => ['1' => '0.5', '3' => '0.3'],
    ]);

    $result = app(CommissionCalculator::class)
        ->calculate($mpt, PaymentDirection::DEPOSIT, '1000');

    $this->assertSame('8.000000', $result->agentTotalFee);
    $this->assertSame('5.000000', $result->perAgentFees[1]);
    $this->assertSame('3.000000', $result->perAgentFees[3]);
}
```

适合 Unit 测试的模块：

- `Helpers/SignatureHelper`：签名生成 / 验证
- `Helpers/MoneyHelper`：bcmath 运算、舍入
- `Services/Agent/CommissionCalculator`：佣金计算
- `Services/Provider/ChannelSelector`：权重选择（可注入 `fake()` 随机数）
- `Enums/*`：`label()` / `isFinal()` / `options()`

### 2.6 功能测试（Feature）

完整 HTTP 请求 → Middleware → Controller → Service → DB。

#### 替换网关为 FakeGateway

`FakeGateway` 实现了 `PaymentGatewayInterface`，可暴露 `$lastDepositApplyData` 供断言。

```php
protected function setUp(): void
{
    parent::setUp();

    $this->seed([
        PaymentTypeSeeder::class,
        TestDataSeeder::class,
    ]);

    $fake = new FakeGateway();
    $fake->depositApplyResult = new DepositApplyResult(
        success:         true,
        providerOrderNo: 'FAKE_123',
        payUrl:          'https://fake/pay/xxx',
    );

    $mock = $this->createMock(PaymentGatewayFactory::class);
    $mock->method('createFromProvider')->willReturn($fake);
    $mock->method('createByVendorId')->willReturn($fake);
    $mock->method('createByProviderPaymentTypeId')->willReturn($fake);

    $this->app->instance(PaymentGatewayFactory::class, $mock);
    $this->fakeGateway = $fake;
}
```

#### 测试代收完整链路

```php
#[Test]
public function deposit_callback_triggers_settlement_and_merchant_notification(): void
{
    Event::fake([DepositCallbackReceived::class]);  // 只假装这个事件

    // 1. 下单
    $params = [ /* ... */ ];
    $params['signature'] = SignatureHelper::generate($params, $this->md5key);
    $this->postJson('/api/deposit/apply', $params)->assertJsonPath('code', 0);

    $order = DepositOrder::firstWhere('merchant_order_no', $params['orderNo']);

    // 2. 模拟供应商回调
    $this->postJson('/api/deposit/testpay/callback', [
        'system_order_no' => $order->system_order_no,
        'status'          => 'success',
        'amount'          => $order->order_amount,
    ])->assertSee('success');

    Event::assertDispatched(DepositCallbackReceived::class);

    // 3. 跑完 listener（事件如果真的要跑结算，就不要 fake）
    //    phpunit.xml 的 QUEUE_CONNECTION=sync 保证同步执行
}
```

#### 断言钱包

```php
$merchant->refresh();
$this->assertSame('10000.000000', $merchant->available_balance);
$this->assertDatabaseHas('merchant_wallet_records', [
    'merchant_id'     => $merchant->id,
    'system_order_no' => $order->system_order_no,
    'type_code'       => WalletOperationType::DEPOSIT_SETTLE->value,
]);
```

### 2.7 后台屏幕冒烟测试

`ScreenSmokeTest` 以管理员身份访问所有列表路由，断言 `status=200`。新增屏幕时把路由加进 `listScreenRoutes()` 即可同步覆盖。

运行：

```bash
php8.2 artisan test tests/Feature/Admin/ScreenSmokeTest.php
```

### 2.8 端到端手动测试清单

提交 PR 前，除了自动化测试，建议手动跑一遍：

- [ ] `TestpayGateway` 下单 → `/pay/{token}` 能打开收银台
- [ ] 模拟回调（可用 Tinker 或 curl）→ 订单状态 = 4 / fund_status = 1
- [ ] 商户 / 代理 / 供应商钱包流水都有新增
- [ ] 商户 `callbackUrl` 收到通知（可用 https://webhook.site 接收）
- [ ] 余额不足时代付返回错误码，无冻结发生
- [ ] 代付失败回调 → `hold_balance` 归零且 `available_balance` 恢复

---

## 三、除错指南

### 3.1 日志入口

| 位置 | 内容 |
|------|------|
| `storage/logs/laravel.log` | 默认通道，含异常堆栈 |
| `storage/logs/horizon.log` | Horizon 进程输出（Supervisor 捕获） |
| `order_logs` 表 | 每笔订单的 apply / callback / query / settle 全时间线（多态） |
| Horizon 仪表板 `/horizon` | 队列 Job 实时状态、失败 Job 的 payload + trace |
| 表 `failed_jobs` | 队列重试耗尽后沉淀在此 |

排障时先看 `order_logs`：

```php
$order = DepositOrder::where('system_order_no', $sn)->first();
$order->logs()->orderBy('created_at')->get();
// 每条 log 有 action、request_data、response_data、ip_address
```

### 3.2 快速实时看日志

```bash
# Laravel 自带的 pail，漂亮输出
php8.2 artisan pail --timeout=0

# 传统方式
tail -f storage/logs/laravel.log
```

### 3.3 队列与事件

- **事件没触发 Listener？** 检查 `EventServiceProvider::$listen` 映射 + `app/Providers/EventServiceProvider.php` 是否注册。Laravel 11 自动发现，也需确认 class 命名 / 命名空间正确。
- **Listener 一直在队列里？** `fluxpay-wallet` 默认串行，一条卡住会堵后面。去 Horizon 看最前面的 job 是否一直 retry。
- **钱包结算分毛钱误差？** 一律用 `MoneyHelper::add/sub/mul/div`，禁止 `+ - * /` 操作 `DECIMAL` 字符串。
- **同一笔订单被结算两次？** 检查 `fund_status` 写回时机：必须在 `DB::transaction` 内与钱包流水 **一起** 更新。

### 3.4 回调常见坑

| 现象 | 排查 |
|------|------|
| 回调返回 `fail`，供应商不停重试 | 看 `laravel.log`：多半是 `Deposit callback: order not found`（`systemOrderNo` 解析错）或 `failed to parse`（签名错） |
| `ProviderCallbackMiddleware` 一直拒绝 | `call_back_ips` 留空会默认拒绝；务必填入供应商文档的 IP 或 CIDR |
| 回调 `signature mismatch` | 确认：签名字段排除规则、是否区分大小写、是否先 URL 解码、`amount` 带 `.00` 还是整数 |
| 同一订单被处理两次 | 应由 `if ($order->status->isFinal()) return 'success';` 拦截，检查该分支是否被误删 |
| 事件触发但钱包没动 | Horizon 是否在跑；`QUEUE_CONNECTION` 是否意外被改回 `sync` 以外的值；Listener 有没有实现 `ShouldQueue` |

### 3.5 API 签名调试

```php
use App\Helpers\SignatureHelper;

$params = ['merchantNo' => 'MCH001', 'orderNo' => 'X1', 'amount' => '100.00'];
$md5key = 'your_md5key_here';

echo SignatureHelper::generate($params, $md5key);
```

常见错误：

- `callbackUrl` / `extend` / `signature` / `sign` 参与了签名 → 按规则应排除
- 金额传了 `100` 却签名时用 `100.00`（或反过来），两边对不上
- `null` 与 `''` 没有过滤掉
- JSON 请求时，空数组被序列化成 `[]` 参与签名

### 3.6 数据库锁与并发

- 所有钱包变更必须 `DB::transaction(fn() => $repo->lockForUpdate()->find($id))`
- 若出现 `Deadlock found when trying to get lock`：
  - 确认加锁顺序一致（例如同时锁商户与代理时，都按 ID 升序）
  - 缩短事务体（不要在事务里做 HTTP 请求、发 email）
- 若出现 `Lock wait timeout`：通常是上面的大事务 + 外部调用组合；把外部调用挪到事务外

### 3.7 TenantScope 导致 "数据不见了"

Model 自动挂了 `TenantScope`，普通查询会被当前登录身份过滤。如需绕过：

```php
DepositOrder::withoutGlobalScope(TenantScope::class)->find($id);
```

Artisan 命令 / Job 里没登录用户，Scope 会对空用户进行最严格过滤（通常等于 "看不到任何数据"）。Job 里取数据要么：

- 序列化整个 Model 到 Job 属性（推荐）
- 或在 Job 内临时 `Auth::login($systemUser)`
- 或 `withoutGlobalScope`

### 3.8 Gateway 开发调试

1. **先用 Tinker 试通**：
   ```php
   $g = app(PaymentGatewayFactory::class)->createByVendorId('acmepay');
   $g->depositApply([...]);
   ```
2. **打开 Log 里的 `[acmepay] makeHttpRequest` 条目**：看请求 URL、响应状态、响应长度
3. **把原始响应打印出来**：临时在 Gateway 内加 `$this->logInfo('raw', json_encode($resp))`，跑完记得删掉
4. **对供应商 Demo 签名**：官方多半有签名测试工具或示例，交叉比对

### 3.9 常用诊断命令

```bash
# 列出已注册路由
php8.2 artisan route:list

# 列出调度任务
php8.2 artisan schedule:list

# 单次跑队列（不守护）
php8.2 artisan queue:work --once

# 重跑所有失败队列
php8.2 artisan queue:retry all

# 清理各种缓存（怀疑配置没更新）
php8.2 artisan optimize:clear

# DB 里的迁移状态
php8.2 artisan migrate:status
```

### 3.10 生产环境注意事项

- **`APP_DEBUG=true` 绝对不能进生产**：会泄露栈 trace 与 `.env`
- **Horizon 必须被 Supervisor 守护**：否则 `queue:work` 一崩溃所有异步结算、商户通知全部停摆
- **定时任务须加入 crontab**：否则 `OrderQueryPollingJob` / 每日统计不会运行
- **`storage/logs/` 定期轮转**：默认 `daily` channel；若改为 `single`，会无限增长
- **磁盘空间告警**：`order_logs`、`failed_jobs` 增长速度可观，必要时定期归档
- **回调域名必须 HTTPS**：部分供应商不接受 HTTP 回调；自签证书也不行
- **所有金额比较必须用 `bccomp`**：`"100.00" === "100"` 是 `false`，别用 `==` / `===`

---

## 四、多语言（i18n）管理

FluxPay 的多语言采用 **「lang 文件 + DB 覆盖 + Redis 缓存」** 三层结构。后台运营人员可在线编辑译文，无需改代码。

### 4.1 加载链路

```
Laravel __() / trans()
   │
   ▼
Translator->$loaded  ─── 本请求内存缓存，同请求内不重复查
   │
   ▼ (未命中)
App\Translation\DbOverrideLoader->load()
   │
   ▼
TranslationService->mergedForLocale($locale)
   │
   ├── Cache::remember("i18n:json:{$locale}", 3600)  ← Redis 缓存
   │
   └── 未命中时：
         ├── lang/{locale}.json            (FileLoader 默认加载)
         └── translations 表 (where locale = ?)  ← DB 覆盖
         合并：array_merge(file, db)
```

**特点**：

- DB 中同一 `(locale, key)` 的 `value` 覆盖文件里的值
- 合并结果以 locale 为单位放 Redis，TTL 1 小时
- 译文保存 / 删除 / 导入时，Service 主动 `Cache::forget("i18n:json:{$locale}")`
- DB 不可用时（例如迁移未跑）自动回退到纯文件加载，不会炸

### 4.2 数据表

| 表 | 字段 | 说明 |
|----|------|------|
| `locales` | `code`, `name`, `is_default`, `is_active`, `sort_order` | 后台可编辑的语言清单 |
| `translations` | `locale`, `key`, `value`, `group`, `updated_by` | 译文条目；`(locale, key)` 唯一 |

> 遵守「单字段」原则：译文只有 `value` 一栏，每种语言独立一行 —— 绝不会出现 `value` + `value_i18n` 并列。

### 4.3 后台界面

路径：**系统 → Locales / Translations**

**Locales 屏**：CRUD 语言；设定默认；启用/停用。

**Translations 屏**：
- 按 locale + group + 搜索词 + 「仅缺失译文」筛选
- inline 编辑
- 4 个顶部按钮：
  - **Scan Code**：触发 `TranslationService::scanCodeKeys()`，扫 `app/` + `resources/` + `routes/` 中的 `__()` / `trans()` / `@lang` 调用，对各语言插入缺失 key
  - **Import from Files**：把 `lang/*.json` 内容刷到 DB（默认只插入不覆盖；Artisan 命令可 `--overwrite`）
  - **Export to Files**：把 DB 当前内容写回 `lang/*.json`（方便提交 Git 版本化）
  - **Clear Cache**：清 Redis 缓存

权限：`platform.system.i18n`（已加入 `administrator` / `manager` 角色）。

### 4.4 Artisan 命令

```bash
# 扫描代码里所有 __() / trans() / @lang key，缺失的插入 translations 表
php8.2 artisan i18n:scan

# 导入 lang/*.json 到 DB（所有 locale）
php8.2 artisan i18n:import

# 只导入一个 locale，且覆盖已有 DB 值
php8.2 artisan i18n:import zh-CN --overwrite

# 导出 DB 到 lang/*.json
php8.2 artisan i18n:export
php8.2 artisan i18n:export zh-CN

# 清缓存
php8.2 artisan i18n:cache:clear
```

### 4.5 新增一种语言

1. **系统 → Locales → Create**：填 `code`、`name`、勾选 `Active`
2. 点 **Translations → Scan Code**：系统会为新语言插入所有 key（`value` 空）
3. 按 locale 筛选 + 「Missing only」模式，批量补译文
4. 需要版本化 → 点 **Export to Files**，`lang/{newcode}.json` 会被生成，提交 Git

### 4.6 编写翻译时的规范

- **key 用英文原文**（和 Laravel JSON translation 一致），这样源码里 `__('Dashboard')` 本身就是兜底
- **避免在 key 里放变量**；用 `:placeholder` 形式，如 `__('Imported :count entries', ['count' => $n])`
- **不要拼接 key**：`__('Status: ' . $status)` 无法被扫到；改成 `__('Status') . ': ' . $statusLabel`
- **新增 UI 文案后**，提交前跑一次 `php8.2 artisan i18n:scan`，确保 DB 中各语言都已登记对应 key

### 4.7 部署注意

- 首次部署 / 升级时跑 `php artisan i18n:import` 把语言包灌入 DB
- 多机部署时 **必须** 共享同一 Redis（`CACHE_STORE=redis`）；否则每台机子缓存独立，编辑后部分请求看不到新译文
- Octane / `config:cache` 不影响翻译缓存，无需额外处理
- 若出现「后台改了译文前台没生效」：`php artisan i18n:cache:clear`

### 4.8 与 `admin_menus.title` 的关系

`admin_menus` 的 `title` 列存英文 key（例如 `"Dashboard"`），`PlatformProvider::buildMenuFromDb()` 渲染时走 `Menu::make(__($root->title))`，翻译来自本套系统。如果管理员在 Menu Management 里把 `title` 改成中文，`__()` 找不到匹配 key 会原样显示；恢复的方式是改回英文原文 + 在 Translations 屏里补译文。

---

## 五、列表筛选规范（FilterPanel）

所有列表屏（List Screen）统一使用 **可收合的筛选面板**，保证后台 UI 一致。

### 5.1 组件与约定

- **`App\Orchid\Layouts\Shared\FilterPanel`**：包装 `Layout::accordion`，默认收起。展开体内容是筛选字段 + `Apply` / `Clear` 两个按钮。
- **`App\Orchid\Concerns\HasFilters`**：提供 `applyFilter(Request)` 与 `clearFilter()` 两个方法。前者把表单里的 `filter.*` 值 redirect 成 query string（递归去掉空值），后者 redirect 到同一路由不带参数。
- **字段命名**：全部用 `filter.xxx` 的 dot 点号路径，提交后经表单生成 `?filter[xxx]=...`。
- **收起态标题**：`筛选 [N] 字段1: 值1 字段2: 值2` —— 数字徽标显示应用数，小字摘要截断在 160 字。

### 5.2 在一个新屏幕上接入

1. `use HasFilters;`
2. `query(Request $request)`：从 `$request->input('filter', [])` 读取并应用
3. `layout()`：
   ```php
   FilterPanel::make(
       fields: [
           Input::make('filter.foo')->title(...)->value($filter['foo'] ?? ''),
           Select::make('filter.bar')->options(...)->empty('-- Any --', '')->value($filter['bar'] ?? ''),
           DateRange::make('filter.date')->value($filter['date'] ?? []),
       ],
       summary: $this->buildFilterSummary($filter),
   )
   ```
4. 实现 `filterRoute(): string` 返回自己的 route 名
5. 实现 `buildFilterSummary(array $f): array` 把激活的筛选翻译成「标签 => 显示值」对，FilterPanel 会拿去拼收起态标题

### 5.3 关键约束

- **禁止同时使用 Orchid 列级 `TD::make()->filter(...)` 与 FilterPanel**。FilterPanel 是唯一入口，列级 filter 已从所有列表屏移除。
- **Enum 过滤值**：遵循 `"0"` 与 `""` 不同的原则 —— `isset($f['status']) && $f['status'] !== ''` 才当作"已选"，这样 `0` 代表禁用不会被吞掉
- **Select.empty()**：第二参数要传 `''`，否则空选项的 value 会变成空字符串以外的东西
- **关联字段**：`buildFilterSummary()` 里展示关联对象的人类可读名（商户 code、代理 name 等），不是 id

### 5.4 回归测试

`tests/Feature/Admin/FilterSmokeTest.php` 用 DataProvider 覆盖每个屏幕的若干筛选组合，以及 `clearFilter` 动作的 302 重定向。新屏幕加入 FilterPanel 后请把对应路由 + 几组典型参数补到该数据提供者。

---

## 附录：项目常量速查

| 常量 | 位置 | 说明 |
|------|------|------|
| `FLUXPAY_ORDER_PREFIX` | `config/fluxpay.php` | 系统订单号前缀，默认 `FP` |
| `FLUXPAY_CALLBACK_MAX_RETRIES` | 同上 | 商户回调最多重试次数，默认 5 |
| `FLUXPAY_CALLBACK_RETRY_INTERVAL` | 同上 | 商户回调重试间隔（秒），默认 60 |
| `FLUXPAY_DEFAULT_CURRENCY` | 同上 | 默认币种，`PHP` |
| 队列名前缀 | `fluxpay-*` | `wallet` / `notification` / `gateway` / `stats` |
| 订单状态枚举 | `App\Enums\OrderStatus` | `0=PENDING 1=SENT 3=FAILED 4=SUCCESS 5=CANCELLED` |
| 回调状态枚举 | `App\Enums\CallbackStatus` | `0=待 1=供应商 2=商户成功 3=商户失败` |
| 资金状态枚举 | `App\Enums\FundStatus` | `0=待结算 1=已结算` |
| i18n 缓存 key | `i18n:json:{locale}` | Redis，TTL 3600s，由 `TranslationService` 管理 |
| i18n 权限 | `platform.system.i18n` | 管理 Locales / Translations 屏 |

---

如有新的通用模式（例如接入的第 N 家供应商出现了可抽取的共性签名算法），请在 `AbstractPaymentGateway` 增加 protected 方法并在本文档补一个小节，避免各 Gateway 复制黏贴。
