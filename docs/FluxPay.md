# FluxPay - 第四方代收代付系统架构计划书

## Context

建立一个全新的第四方支付系统，支持代收（Collection/Deposit）与代付（Payout/Withdraw），最多三层代理体系，高度弹性的第三方支付对接能力。

---

## 1. 技术栈

| 组件 | 选型 |
|------|------|
| 后端框架 | Laravel 11 |
| PHP 版本 | 8.2+ |
| 后台管理 | Orchid Platform |
| 数据库 | MariaDB 10.6+ |
| 缓存/队列 | Redis 7+ |
| 队列监控 | Laravel Horizon |
| 2FA | pragmarx/google2fa-laravel |
| 测试 | PHPUnit + Factories |
| 负载均衡 | Nginx (upstream) |
| 连接池 | Swoole / Laravel Octane (可选) |

---

## 2. 目录结构

```
fluxpay/
├── app/
│   ├── Contracts/                          # 接口定义
│   │   ├── Gateway/
│   │   │   └── PaymentGatewayInterface.php
│   │   └── Repositories/                   # Repository 接口
│   │       ├── MerchantRepositoryInterface.php
│   │       ├── AgentRepositoryInterface.php
│   │       ├── ProviderRepositoryInterface.php
│   │       ├── DepositOrderRepositoryInterface.php
│   │       ├── WithdrawOrderRepositoryInterface.php
│   │       ├── MerchantWalletRecordRepositoryInterface.php
│   │       ├── AgentWalletRecordRepositoryInterface.php
│   │       └── ProviderWalletRecordRepositoryInterface.php
│   ├── DTOs/                               # 数据传输对象
│   │   ├── Gateway/
│   │   │   ├── DepositApplyResult.php
│   │   │   ├── DepositCallbackResult.php
│   │   │   ├── WithdrawApplyResult.php
│   │   │   ├── WithdrawCallbackResult.php
│   │   │   └── BalanceQueryResult.php
│   │   ├── FeeCalculationResult.php
│   │   └── AgentCommissionResult.php
│   ├── Enums/
│   │   ├── OrderStatus.php                 # 0=pending,1=sent,3=failed,4=success,5=cancelled
│   │   ├── CallbackStatus.php
│   │   ├── FundStatus.php
│   │   ├── FeeType.php                     # PERCENTAGE, FIXED
│   │   ├── Currency.php
│   │   ├── PaymentDirection.php            # DEPOSIT, WITHDRAW
│   │   ├── WalletOperationType.php
│   │   ├── AgentType.php                   # MERCHANT, PROVIDER
│   │   └── EntityStatus.php
│   ├── Events/                             # 事件驱动
│   │   └── Order/
│   │       ├── DepositOrderCreated.php
│   │       ├── DepositCallbackReceived.php
│   │       ├── DepositFundSettled.php
│   │       ├── WithdrawOrderCreated.php
│   │       ├── WithdrawCallbackReceived.php
│   │       ├── WithdrawFundSettled.php
│   │       └── WithdrawFundReversed.php
│   ├── Exceptions/
│   │   ├── PaymentProviderNotFoundException.php
│   │   ├── InvalidPaymentConfigException.php
│   │   ├── WalletException.php
│   │   ├── InsufficientBalanceException.php
│   │   └── ChannelUnavailableException.php
│   ├── Helpers/
│   │   ├── SignatureHelper.php
│   │   ├── OrderNumberGenerator.php
│   │   └── MoneyHelper.php                 # bcmath 封装
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── MerchantApiController.php
│   │   │   ├── ProviderCallbackController.php
│   │   │   └── FrontendPayController.php
│   │   ├── Middleware/
│   │   │   ├── MerchantAuthMiddleware.php
│   │   │   ├── ProviderCallbackMiddleware.php
│   │   │   └── RequestLoggingMiddleware.php
│   │   ├── Requests/
│   │   │   ├── DepositApplyRequest.php
│   │   │   ├── WithdrawApplyRequest.php
│   │   │   ├── DepositQueryRequest.php
│   │   │   ├── WithdrawQueryRequest.php
│   │   │   └── BalanceQueryRequest.php
│   │   └── Resources/
│   │       ├── DepositOrderResource.php
│   │       └── WithdrawOrderResource.php
│   ├── Jobs/
│   │   ├── ProcessDepositWalletJob.php
│   │   ├── ProcessWithdrawWalletJob.php
│   │   ├── MerchantNotificationJob.php     # 统一通知（合并 deposit/withdraw）
│   │   ├── OrderQueryPollingJob.php
│   │   └── AggregateDailyStatsJob.php
│   ├── Listeners/Order/
│   │   ├── SettleDepositFunds.php
│   │   ├── SettleWithdrawFunds.php
│   │   ├── ReverseWithdrawFunds.php
│   │   ├── NotifyMerchantOnDepositSuccess.php
│   │   ├── NotifyMerchantOnWithdrawResult.php
│   │   └── LogOrderStatusChange.php
│   ├── Models/
│   │   ├── Agent.php
│   │   ├── Merchant.php
│   │   ├── Provider.php
│   │   ├── PaymentType.php
│   │   ├── ProviderPaymentType.php
│   │   ├── MerchantPaymentType.php
│   │   ├── MerchantProviderPaymentType.php
│   │   ├── DepositOrder.php
│   │   ├── WithdrawOrder.php
│   │   ├── OrderLog.php                    # 多态日志（统一 deposit/withdraw）
│   │   ├── MerchantWalletRecord.php
│   │   ├── AgentWalletRecord.php
│   │   ├── ProviderWalletRecord.php
│   │   ├── Bank.php
│   │   ├── ProviderBankCode.php
│   │   ├── Blacklist.php
│   │   ├── Proxy.php
│   │   ├── SystemConfig.php
│   │   ├── AdminUserIpWhitelist.php
│   │   └── Concerns/                       # Model Traits
│   │       ├── HasWallet.php
│   │       ├── HasTenantScope.php
│   │       └── HasStatusAttribute.php
│   ├── Orchid/                             # 后台管理
│   │   ├── PlatformProvider.php
│   │   ├── Screens/
│   │   │   ├── Dashboard/
│   │   │   │   ├── AdminDashboardScreen.php
│   │   │   │   ├── MerchantDashboardScreen.php
│   │   │   │   └── AgentDashboardScreen.php
│   │   │   ├── Agent/
│   │   │   ├── Merchant/
│   │   │   ├── Provider/
│   │   │   ├── PaymentConfig/
│   │   │   ├── Order/
│   │   │   ├── Wallet/
│   │   │   ├── Report/
│   │   │   ├── Bank/
│   │   │   └── System/
│   │   ├── Layouts/
│   │   └── Filters/
│   ├── Repositories/                       # Repository 实现
│   │   ├── MerchantRepository.php
│   │   ├── AgentRepository.php
│   │   ├── ProviderRepository.php
│   │   ├── DepositOrderRepository.php
│   │   ├── WithdrawOrderRepository.php
│   │   ├── MerchantWalletRecordRepository.php
│   │   ├── AgentWalletRecordRepository.php
│   │   └── ProviderWalletRecordRepository.php
│   ├── Scopes/
│   │   └── TenantScope.php
│   └── Services/
│       ├── Order/
│       │   ├── DepositService.php
│       │   └── WithdrawService.php
│       ├── Wallet/
│       │   ├── MerchantWalletService.php
│       │   ├── AgentWalletService.php
│       │   └── ProviderWalletService.php
│       ├── Agent/
│       │   ├── AgentService.php
│       │   └── CommissionCalculator.php    # 独立佣金计算
│       ├── Merchant/
│       │   ├── MerchantService.php
│       │   └── MerchantNotificationService.php
│       ├── Provider/
│       │   ├── ProviderService.php
│       │   └── ChannelSelector.php         # 通道选择策略
│       ├── Gateway/
│       │   ├── PaymentGatewayFactory.php
│       │   ├── AbstractPaymentGateway.php
│       │   ├── GatewayResponse.php
│       │   └── Vendors/
│       │       ├── TestpayGateway.php
│       │       ├── TraxionpayGateway.php
│       │       └── CoinsGateway.php
│       ├── Report/
│       ├── Security/
│       │   ├── SignatureService.php
│       │   ├── IpWhitelistService.php
│       │   └── TwoFactorAuthService.php
│       └── CacheService.php
├── config/
│   ├── fluxpay.php                         # 主配置
│   ├── gateways.php                        # 支付网关默认配置（取代 PHP 常量）
│   └── platform.php                        # Orchid 配置
├── database/
│   ├── migrations/
│   ├── seeders/
│   │   ├── RolePermissionSeeder.php
│   │   ├── PaymentTypeSeeder.php
│   │   ├── BankSeeder.php
│   │   └── TestDataSeeder.php
│   └── factories/
├── resources/views/
│   ├── payment/
│   │   ├── cashier.blade.php               # 收银台（QR / 跳转）
│   │   ├── error.blade.php
│   │   └── success.blade.php
├── routes/
│   ├── api.php
│   ├── platform.php                        # Orchid 后台路由
│   └── web.php
└── tests/
    ├── Unit/
    │   ├── Services/
    │   ├── Gateway/
    │   └── Helpers/
    ├── Feature/
    │   ├── Api/
    │   ├── Wallet/
    │   └── Admin/
    └── Stubs/
        └── FakeGateway.php
```

---

## 3. 数据库设计

### 3.1 ER 关系总览

```
agents (1) --< agents             (self-ref, parent_id, 最多3层)
agents (1) --< merchants          (merchant-type agent)
agents (1) --< providers          (provider-type agent)
merchants (1) --< deposit_orders
merchants (1) --< withdraw_orders
providers (1) --< provider_payment_types
payment_types (1) --< provider_payment_types
payment_types (1) --< merchant_payment_types
provider_payment_types (1) --< merchant_provider_payment_types
merchants (1) --< merchant_wallet_records
agents (1) --< agent_wallet_records
providers (1) --< provider_wallet_records
```

### 3.2 核心表

#### `agents` - 代理
| 字段 | 类型 | 说明 |
|------|------|------|
| id | BIGINT PK | |
| parent_id | BIGINT NULL FK(agents) | 上级代理 |
| types | ENUM(merchant, provider) | 代理类型 |
| name | VARCHAR(100) | |
| level | TINYINT (1/2/3) | 层级 |
| status | TINYINT | 0=停用, 1=启用 |
| currency | VARCHAR(10) | |
| total_balance | DECIMAL(20,6) | |
| available_balance | DECIMAL(20,6) | |
| hold_balance | DECIMAL(20,6) | |

#### `merchants` - 商户
| 字段 | 类型 | 说明 |
|------|------|------|
| id | BIGINT PK | |
| agent_id | BIGINT FK(agents) | 所属代理 |
| code | VARCHAR(50) UNIQUE | 商户编号（对外标识） |
| name | VARCHAR(100) | |
| md5key | VARCHAR(64) | API 签名密钥 |
| currency_code | VARCHAR(10) | |
| status | TINYINT | |
| total_balance | DECIMAL(20,6) | |
| available_balance | DECIMAL(20,6) | |
| hold_balance | DECIMAL(20,6) | |
| white_ips | JSON | IP 白名单 |
| options | JSON | 扩展配置 |

#### `providers` - 支付供应商
| 字段 | 类型 | 说明 |
|------|------|------|
| id | BIGINT PK | |
| agent_id | BIGINT NULL FK(agents) | 供应商侧代理 |
| name | VARCHAR(100) | |
| provider_no | VARCHAR(100) | 供应商商户号 |
| vendor_id | VARCHAR(50) | 对应网关类名 |
| vendor_meta | JSON | 供应商配置（API key/URL 等） |
| bank_config_key | VARCHAR(50) | 银行代码映射 key |
| status | TINYINT | |
| total_balance | DECIMAL(20,6) | |
| available_balance | DECIMAL(20,6) | |
| hold_balance | DECIMAL(20,6) | |
| api_available_balance | DECIMAL(20,6) | 最后查询的 API 余额 |
| call_back_ips | TEXT | 回调 IP 白名单 |

#### `payment_types` - 支付方式
| 字段 | 类型 | 说明 |
|------|------|------|
| id | BIGINT PK | |
| payment_type_code | VARCHAR(50) UNIQUE | 如 BANK_TRANSFER, GCASH |
| name | VARCHAR(100) | |
| status | TINYINT | |

#### `provider_payment_types` - 供应商通道
| 字段 | 类型 | 说明 |
|------|------|------|
| id | BIGINT PK | |
| provider_id | BIGINT FK | |
| payment_type_id | BIGINT FK | |
| type | ENUM(deposit, withdraw) | 代收/代付 |
| alias | VARCHAR(100) | 通道别名 |
| status | TINYINT | |
| weight | TINYINT (1-100) | 通道选择权重 |
| single_min_amount | DECIMAL(20,2) | |
| single_max_amount | DECIMAL(20,2) | |
| daily_amount_limit | DECIMAL(20,2) | |
| daily_count_limit | INT | |
| current_daily_amount | DECIMAL(20,2) | |
| reset_time | VARCHAR(5) | 每日重置时间 |
| deposit_fee_type | TINYINT | 1=百分比, 2=固定 |
| deposit_fee | DECIMAL(10,4) | |
| withdraw_fee_type | TINYINT | |
| withdraw_fee | DECIMAL(10,4) | |
| agent_fee | DECIMAL(10,4) | 供应商侧代理费率 |

#### `merchant_payment_types` - 商户费率配置
| 字段 | 类型 | 说明 |
|------|------|------|
| id | BIGINT PK | |
| merchant_id | BIGINT FK | |
| payment_type_id | BIGINT FK | |
| status | TINYINT | |
| single_min_amount | DECIMAL(20,2) | 商户级限额覆盖 |
| single_max_amount | DECIMAL(20,2) | |
| deposit_fee_type | TINYINT | |
| deposit_fee | DECIMAL(10,4) | 商户代收手续费 |
| deposit_agents_fee | JSON | 各级代理分润：`{"agent_id": rate}` |
| withdraw_fee_type | TINYINT | |
| withdraw_fee | DECIMAL(10,4) | 商户代付手续费 |
| withdraw_agents_fee | JSON | |
| UNIQUE | (merchant_id, payment_type_id) | |

#### `merchant_provider_payment_types` - 商户通道分配
| 字段 | 类型 | 说明 |
|------|------|------|
| id | BIGINT PK | |
| merchant_id | BIGINT FK | |
| provider_payment_type_id | BIGINT FK | |
| status | TINYINT | |
| UNIQUE | (merchant_id, provider_payment_type_id) | |

#### `deposit_orders` - 代收订单
| 字段 | 类型 | 说明 |
|------|------|------|
| id | BIGINT PK | |
| merchant_id | BIGINT FK | |
| merchant_order_no | VARCHAR(100) | 商户订单号 |
| system_order_no | VARCHAR(50) UNIQUE | 系统订单号 |
| provider_payment_type_id | BIGINT FK | |
| provider_order_no | VARCHAR(100) | 供应商订单号 |
| order_amount | DECIMAL(20,6) | 订单金额 |
| actual_amount | DECIMAL(20,6) | 实际金额 |
| merchant_fee | DECIMAL(20,6) | 商户手续费 |
| provider_fee | DECIMAL(20,6) | 供应商成本 |
| agent_fee | DECIMAL(20,6) | 代理佣金总计 |
| agent_fee_map | JSON | `{"agent_id": amount}` |
| provider_agent_fee | DECIMAL(20,6) | 供应商侧代理佣金 |
| provider_agent_fee_map | JSON | |
| status | TINYINT | 0=待处理,1=已送出,3=失败,4=成功,5=取消 |
| callback_status | TINYINT | 0=待处理,1=供应商回调成功,2=商户通知成功,3=商户通知失败 |
| fund_status | TINYINT | 0=待结算,1=已结算 |
| fund_at | TIMESTAMP | |
| merchant_notify_url | VARCHAR(500) | |
| bank_code | VARCHAR(50) | |
| payer_name | VARCHAR(100) | |
| currency | VARCHAR(10) | |
| provider_apply_time | TIMESTAMP | |
| provider_callback_time | TIMESTAMP | |
| remark | TEXT | |

#### `withdraw_orders` - 代付订单
与 deposit_orders 结构一致，额外字段：
- `bank_account_name` VARCHAR(100)
- `bank_account_no` VARCHAR(100)
- `bank_branch` VARCHAR(255)
- `total_debit` DECIMAL(20,6) — 冻结总额（金额+手续费）

#### `order_logs` - 订单日志（多态）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | BIGINT PK | |
| orderable_type | VARCHAR(100) | Model 类名 |
| orderable_id | BIGINT | |
| action | VARCHAR(50) | apply/callback/query/settle 等 |
| request_data | JSON | |
| response_data | JSON | |
| ip_address | VARCHAR(45) | |
| remark | TEXT | |

#### 钱包记录表 (`merchant_wallet_records` / `agent_wallet_records` / `provider_wallet_records`)
三张表结构相同：
| 字段 | 类型 | 说明 |
|------|------|------|
| id | BIGINT PK | |
| {entity}_id | BIGINT FK | |
| sn | VARCHAR(50) UNIQUE | 流水号 |
| type_code | VARCHAR(50) | 操作类型 |
| amount | DECIMAL(20,6) | |
| pre_total_balance | DECIMAL(20,6) | 操作前 |
| pre_available_balance | DECIMAL(20,6) | |
| pre_hold_balance | DECIMAL(20,6) | |
| total_balance | DECIMAL(20,6) | 操作后 |
| available_balance | DECIMAL(20,6) | |
| hold_balance | DECIMAL(20,6) | |
| system_order_no | VARCHAR(50) | |
| remark | TEXT | |

#### 辅助表
- `banks` (id, bank_code UNIQUE, name, status)
- `provider_bank_codes` (id, bank_config_key, bank_code, provider_bank_code, status)
- `blacklists` (id, type, value, remark, status)
- `proxies` (id, name, host, port, username, password, protocol, status)
- `system_configs` (id, group, key UNIQUE, value, remark)
- `admin_user_ip_whitelists` (id, admin_user_id, ip_address, remark, status)
- `login_logs` (id, admin_user_id, ip, user_agent, created_at)
- `operation_logs` (id, admin_user_id, module, action, target_type, target_id, payload JSON, created_at)

#### 统计表
- `daily_transaction_stats` (date, deposit_count, deposit_amount, withdraw_count, withdraw_amount, ...)
- `daily_transaction_stats_by_merchant` / `by_provider`
- `daily_revenue_stats` (date, total_revenue, merchant_fees, provider_fees, agent_commissions, net_profit)
- `daily_revenue_stats_by_merchant` / `by_provider`

---

## 4. 架构分层与流程

### 4.1 架构分层（四层）

```
Controller → Service → Repository → Model
```

| 层 | 职责 | 不做 |
|----|------|------|
| **Controller** | 接收请求、FormRequest 校验、调用 Service、返回 Resource | 业务逻辑、直接操作 Model |
| **Service** | 业务逻辑、流程编排、事务控制（DB::transaction）、事件触发 | 直接写查询、返回 HTTP Response |
| **Repository** | 数据访问封装、查询组合、lockForUpdate、批量操作 | 业务流程、跨表事务 |
| **Model** | ORM 映射、关系定义、accessor/mutator、scope | 业务逻辑 |

**Repository 接口绑定**（在 `AppServiceProvider` 或 `RepositoryServiceProvider`）：
```php
$this->app->bind(MerchantRepositoryInterface::class, MerchantRepository::class);
$this->app->bind(DepositOrderRepositoryInterface::class, DepositOrderRepository::class);
// ... 其他绑定
```

### 4.2 请求处理流程

```
HTTP Request
    │
    ▼
[Middleware] ─── MerchantAuthMiddleware (IP + 签名 + 商户验证)
    │            ProviderCallbackMiddleware (IP + 供应商验证)
    │            RequestLoggingMiddleware
    ▼
[FormRequest] ── DepositApplyRequest 等（参数校验）
    │
    ▼
[Controller] ─── 薄层，调用 Service，返回 Resource
    │
    ▼
[Service] ─── 业务编排，事务控制
    ├──→ ChannelSelector        选择通道
    ├──→ CommissionCalculator   计算费用与佣金
    ├──→ Repository             数据读写（含行锁）
    └──→ GatewayFactory → Vendor Gateway  调用第三方 API
    │
    ▼
[Event Dispatch] ── DepositCallbackReceived 等
    │
    ▼
[Listeners] ─── 异步队列处理
    ├──→ SettleDepositFunds（通过 WalletService → Repository 结算）
    ├──→ NotifyMerchantOnDepositSuccess（商户回调）
    └──→ LogOrderStatusChange（订单日志）
```

### 4.3 代收（Deposit）完整流程

1. 商户 POST `/api/deposit/apply`，附带签名
2. Middleware 验证 IP、签名、商户状态
3. `DepositService` 调用 `ChannelSelector` 选择可用通道
4. 创建 `DepositOrder`，status=0
5. `CommissionCalculator` 计算商户手续费、各级代理佣金、供应商费用
6. `PaymentGatewayFactory` 实例化网关，调用 `depositApply()`
7. 更新 provider_order_no，status=1
8. 返回支付链接/QR 给商户
9. 供应商异步回调 POST `/api/deposit/{vendor}/callback`
10. 验证 IP，解析回调，更新 status=4（成功）或 3（失败）
11. 触发 `DepositCallbackReceived` 事件
12. Listener: 结算资金（更新商户/代理/供应商钱包）
13. Listener: 通知商户（队列重试5次，间隔60秒）

### 4.4 代付（Withdraw）完整流程

1. 商户 POST `/api/withdraw/apply`
2. 验证余额 >= 金额 + 手续费
3. 创建 `WithdrawOrder`
4. **冻结**商户余额（available → hold）
5. 调用网关 `withdrawApply()`
6. 供应商回调
7. 成功：解冻 → 永久扣款，分配代理佣金
8. 失败：解冻 → 恢复余额，记录反转流水

---

## 5. 支付网关抽象

### 5.1 接口定义

```php
interface PaymentGatewayInterface
{
    public function depositApply(array $data): GatewayResponse;
    public function depositQuery(array $data): GatewayResponse;
    public function depositCallback(array $data, array $options): GatewayResponse;
    public function withdrawApply(array $data): GatewayResponse;
    public function withdrawQuery(array $data): GatewayResponse;
    public function withdrawCallback(array $data, array $options): GatewayResponse;
    public function balanceQuery(): GatewayResponse;
    public function supportsDeposit(): bool;
    public function supportsWithdraw(): bool;
}
```

### 5.2 配置优先级（3层合并）

```
config/gateways.php 默认值  <  providers.vendor_meta (数据库)  <  系统固定字段 (provider_no, provider_id)
```

### 5.3 新增供应商步骤

1. 创建 `app/Services/Gateway/Vendors/NewVendorGateway.php`，继承 `AbstractPaymentGateway`
2. 实现 7 个接口方法
3. 在 `config/gateways.php` 添加默认配置
4. 后台创建 Provider 记录，设定 `vendor_id`
5. 创建 ProviderPaymentType 并分配给商户

**无需修改框架代码**，Factory 根据 `vendor_id` 自动加载对应类。

---

## 6. 代理系统

### 6.1 层级结构

- **Level 1**：一级代理，直属平台
- **Level 2**：二级代理，归属一级代理
- **Level 3**：三级代理，叶子节点
- **类型**：`merchant`（管理商户）或 `provider`（管理供应商）

### 6.2 佣金计算

`merchant_payment_types.deposit_agents_fee` / `withdraw_agents_fee` 存储 JSON：
```json
{"1": "1.5", "3": "0.8", "5": "0.3"}
```
`CommissionCalculator` 独立计算每级代理分润，返回 `AgentCommissionResult` DTO。

### 6.3 结算时分配

订单结算时，遍历 `agent_fee_map`，逐级调用 `AgentWalletService` 入账。

---

## 7. API 设计

### 7.1 商户 API

| 端点 | 说明 |
|------|------|
| `POST /api/deposit/apply` | 发起代收 |
| `POST /api/deposit/query` | 查询代收订单 |
| `POST /api/withdraw/apply` | 发起代付 |
| `POST /api/withdraw/query` | 查询代付订单 |
| `POST /api/balance/query` | 查询余额 |

### 7.2 供应商回调

| 端点 | 说明 |
|------|------|
| `POST /api/deposit/{vendor}/callback` | 代收回调 |
| `POST /api/withdraw/{vendor}/callback` | 代付回调 |

### 7.3 签名算法

MD5 签名：参数按 key 排序 → 拼接 `key=value&` → 末尾追加 md5key → MD5 哈希

### 7.4 返回格式

```json
{"code": 0, "message": "Success", "data": {}, "timestamp": "2026-04-16 12:00:00"}
```

### 7.5 前台支付页

| 端点 | 说明 |
|------|------|
| `GET /pay/{token}` | 收银台页面（QR 码 / 跳转） |
| `GET /pay/error` | 错误页 |

---

## 8. 后台管理（Orchid）

### 8.1 菜单结构

```
Dashboard
支付管理
  ├── 代收订单
  └── 代付订单
主体管理
  ├── 代理管理
  ├── 商户管理
  └── 供应商管理
通道配置
  ├── 支付方式
  ├── 供应商通道
  ├── 商户费率
  └── 通道分配
财务
  ├── 商户钱包流水
  ├── 代理钱包流水
  └── 供应商钱包流水
报表
  ├── 每日交易统计
  ├── 每日营收统计
  ├── 通道即时状态
  └── 代理利润
银行
  ├── 银行列表
  └── 供应商银行代码
系统
  ├── 系统配置
  ├── 黑名单
  ├── 代理服务器
  ├── 管理员 IP 白名单
  ├── 登录日志
  ├── 角色权限
  └── API 测试工具
```

### 8.2 权限设计

```php
ItemPermission::group('支付管理')
    ->addPermission('platform.orders.deposits', '代收订单')
    ->addPermission('platform.orders.withdrawals', '代付订单')
    ->addPermission('platform.orders.manual_actions', '手动操作'),

ItemPermission::group('主体管理')
    ->addPermission('platform.agents', '代理')
    ->addPermission('platform.merchants', '商户')
    ->addPermission('platform.providers', '供应商'),

ItemPermission::group('财务')
    ->addPermission('platform.wallets.merchant', '商户钱包')
    ->addPermission('platform.wallets.agent', '代理钱包')
    ->addPermission('platform.wallets.provider', '供应商钱包')
    ->addPermission('platform.wallets.adjust', '手动调账'),

ItemPermission::group('报表')
    ->addPermission('platform.reports.*', '报表查看'),

ItemPermission::group('系统')
    ->addPermission('platform.system.*', '系统管理'),
```

### 8.3 多租户数据隔离（TenantScope）

| 角色 | 数据范围 |
|------|----------|
| administrator | 全部数据 |
| manager | 排除超管账号 |
| merchant | 仅自己商户的订单、钱包 |
| agent | 自己及下级代理 + 其商户/供应商的数据 |

### 8.4 订单管理功能

- 筛选：日期区间、商户、供应商、状态、订单号
- 操作：手动查询、手动回调模拟、手动状态更新
- 详情：完整时间线（order_logs）、费用明细

---

## 9. 安全措施

| 层面 | 措施 |
|------|------|
| 商户 API | MD5 签名 + IP 白名单 |
| 供应商回调 | IP 白名单验证 |
| 后台 | IP 白名单 + Google 2FA |
| 敏感数据 | md5key 加密存储（Laravel encrypted cast） |
| 钱包操作 | DB 事务 + 行锁（lockForUpdate） |
| 通用 | Rate limiting、CSRF、XSS 防护、安全 Headers |

---

## 10. 高并发架构设计（10万+ QPS）

### 10.1 整体策略

```
                         ┌─────────────┐
                         │   Nginx LB   │  ← 限流 + 负载均衡
                         └──────┬───────┘
                    ┌───────────┼───────────┐
                    ▼           ▼           ▼
              ┌──────────┐ ┌──────────┐ ┌──────────┐
              │ Octane 1 │ │ Octane 2 │ │ Octane N │  ← 多实例，常驻进程
              └────┬─────┘ └────┬─────┘ └────┬─────┘
                   │            │            │
         ┌─────────────────────────────────────────┐
         │              Redis Cluster               │  ← 缓存 + 分布式锁 + 队列
         └─────────────────────────────────────────┘
                   │            │            │
         ┌─────────────────────────────────────────┐
         │        MariaDB (主从 + 读写分离)          │
         └─────────────────────────────────────────┘
```

### 10.2 应用层 - Laravel Octane (Swoole)

| 项目 | 说明 |
|------|------|
| 常驻进程 | 避免每次请求 bootstrap 框架，QPS 提升 5-10 倍 |
| 协程 HTTP 客户端 | 调用第三方 API 时非阻塞，不占用 worker |
| 连接池 | DB 连接复用，避免高并发下连接耗尽 |
| 注意事项 | Service/Repository 必须无状态，避免单例污染 |

```php
// config/octane.php
'server' => 'swoole',
'workers' => env('OCTANE_WORKERS', 16),        // CPU 核数 × 2
'task_workers' => env('OCTANE_TASK_WORKERS', 8),
'max_requests' => 1000,                         // 防内存泄漏
```

### 10.3 数据库层 - 读写分离 + 连接池

**读写分离**：
```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [env('DB_READ_HOST_1'), env('DB_READ_HOST_2')],
    ],
    'write' => [
        'host' => env('DB_WRITE_HOST'),
    ],
    'sticky' => true,  // 写入后同一请求内读主库，避免主从延迟问题
],
```

**Repository 层读写控制**：
```php
// 查询走从库（默认）
$this->model->newQuery()->where(...)->get();

// 写入/锁定走主库
DB::connection()->useWritePdo();  // 强制主库
$this->model->lockForUpdate()->find($id);
```

**连接池（Swoole）**：
- Octane 自动管理 DB 连接池
- 配置 `OCTANE_DB_CONNECTION_POOL_SIZE=64`
- 避免高并发下 "Too many connections"

### 10.4 缓存层 - Redis 热点数据

**缓存策略**：

| 数据 | 缓存时间 | 说明 |
|------|----------|------|
| 商户信息 (merchant) | 5 min | 含 md5key、white_ips、status |
| 通道配置 (provider_payment_type) | 5 min | 含费率、限额 |
| 商户通道映射 | 5 min | merchant_provider_payment_types |
| 银行列表 | 30 min | 低频变更 |
| 系统配置 | 10 min | system_configs |

**缓存穿透防护**：
- 空值缓存（60s TTL），防止不存在的 merchantCode 反复穿透
- 布隆过滤器可选（Redis Bloom module）

**缓存更新**：后台编辑商户/通道时主动清除对应缓存 key（通过 Model Observer）。

### 10.5 并发控制 - 分布式锁 + 幂等

**1. 商户下单去重（幂等）**：
```php
// DepositService::apply()
$lockKey = "deposit:idempotent:{$merchantId}:{$merchantOrderNo}";
$lock = Cache::lock($lockKey, 30); // 30秒锁

if (!$lock->get()) {
    // 查询已存在订单并返回
    return $this->depositOrderRepo->findByMerchantOrder($merchantId, $merchantOrderNo);
}
try {
    // 创建订单...
} finally {
    $lock->release();
}
```

**2. 钱包操作串行化**：
```php
// MerchantWalletService::freeze()
// 方案 A：数据库行锁（适合中等并发）
DB::transaction(function () use ($merchantId, $amount) {
    $merchant = $this->merchantRepo->lockForUpdate($merchantId);
    // 检查余额 → 扣减 → 写流水
});

// 方案 B：Redis 分布式锁（适合高并发，减少 DB 锁等待）
$lock = Cache::lock("wallet:merchant:{$merchantId}", 10);
$lock->block(5); // 最多等5秒
try {
    DB::transaction(function () { /* ... */ });
} finally {
    $lock->release();
}
```

**3. 通道日限额原子更新**：
```php
// ChannelSelector - 使用 Redis 原子递增
$dailyKey = "channel:daily:{$providerPaymentTypeId}:" . now()->format('Ymd');
$current = Redis::incrByFloat($dailyKey, $amount);
Redis::expire($dailyKey, 86400);

if ($current > $dailyLimit) {
    Redis::incrByFloat($dailyKey, -$amount); // 回退
    throw new ChannelUnavailableException('日限额已满');
}
```

### 10.6 Nginx 层限流

```nginx
# 全局限流
limit_req_zone $binary_remote_addr zone=api:10m rate=1000r/s;

# 按商户限流（通过 header 或 body 中的 merchantNo）
limit_req_zone $http_x_merchant_no zone=merchant:10m rate=100r/s;

server {
    location /api/ {
        limit_req zone=api burst=2000 nodelay;
        limit_req zone=merchant burst=200 nodelay;
        proxy_pass http://octane_upstream;
    }
}

upstream octane_upstream {
    least_conn;
    server 127.0.0.1:8000;
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;
}
```

### 10.7 队列高并发处理

| 队列 | Worker 数 | 策略 |
|------|-----------|------|
| fluxpay-wallet | 4（串行为主） | 按 merchant_id 分区，同一商户串行 |
| fluxpay-notification | 16 | 并行，merchant callback 互不影响 |
| fluxpay-gateway | 8 | 并行，不同供应商独立 |
| fluxpay-stats | 2 | 低优先级 |

**钱包队列分区**（防止同一商户并发写入冲突）：
```php
// ProcessDepositWalletJob
public function viaQueue(): string
{
    // 按 merchant_id 取模分配到不同子队列
    $partition = $this->merchantId % 4;
    return "fluxpay-wallet-{$partition}";
}
```

### 10.8 数据库优化

**索引策略**（高频查询路径）：
```sql
-- 商户下单查重
ALTER TABLE deposit_orders ADD INDEX idx_merchant_order_unique (merchant_id, merchant_order_no);

-- 回调查单
ALTER TABLE deposit_orders ADD INDEX idx_provider_order (provider_payment_type_id, provider_order_no);

-- 钱包流水按时间查询
ALTER TABLE merchant_wallet_records ADD INDEX idx_merchant_created (merchant_id, created_at);

-- 统计查询
ALTER TABLE deposit_orders ADD INDEX idx_status_created (status, created_at);
```

**分表策略**（数据量大时）：
- `deposit_orders` / `withdraw_orders`：按月分表（`deposit_orders_202604`）
- `*_wallet_records`：按月分表
- 使用 Laravel 的 `$table` 动态设置或引入分表包

**慢查询监控**：
```php
// AppServiceProvider::boot()
DB::listen(function ($query) {
    if ($query->time > 500) { // 500ms
        Log::channel('slow_query')->warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time,
        ]);
    }
});
```

### 10.9 分阶段实施

| 阶段 | 预期 QPS | 方案 |
|------|----------|------|
| Phase 1 | 1,000-5,000 | 标准 PHP-FPM + Redis 缓存 + 行锁 + 读写分离 |
| Phase 2 | 5,000-30,000 | Octane (Swoole) + Redis 分布式锁 + Nginx 限流 |
| Phase 3 | 30,000-100,000+ | 引入 Go 微服务 + 分表 + Redis Cluster |

### 10.10 Phase 3：Go 微服务扩展方案

当 PHP + Octane 到达瓶颈（约 3 万 QPS），引入 Go 处理高频路径：

**架构**：
```
                      Nginx / API Gateway
                           │
              ┌────────────┼────────────┐
              ▼            ▼            ▼
      ┌──────────────┐  ┌─────────┐  ┌──────────────┐
      │  PHP/Laravel  │  │   Go    │  │  PHP/Laravel  │
      │  (业务主体)   │  │ (高频层) │  │   (后台)      │
      └──────────────┘  └─────────┘  └──────────────┘
```

**Go 负责的高频路径**：

| 模块 | 说明 |
|------|------|
| API Gateway 层 | 签名验证、IP 白名单、限流 — 纯计算+内存，Go 原生 HTTP 性能 > PHP 10 倍 |
| 通道选择 + 日限额 | Redis 原子操作 + 内存缓存，goroutine 天然适合高并发 |
| 供应商 API 调用代理 | 并发调用第三方，goroutine 比 Swoole 协程更成熟稳定 |
| 回调接收层 | 高频回调接收、签名验证后投入 Redis 队列，PHP 端消费处理 |

**PHP 继续负责**：

| 模块 | 说明 |
|------|------|
| 后台管理 (Orchid) | 所有 CRUD、报表、配置 — 低并发，PHP 完全胜任 |
| 业务逻辑编排 | 订单创建、费用计算、钱包结算 — 复杂业务用 PHP 开发效率更高 |
| 队列消费 | 异步任务不需要超高并发 |

**Go ↔ PHP 通信方式**：
- Go 验证通过后，通过 Redis Queue 或 HTTP 内网调用转发给 PHP
- 共享 MariaDB + Redis，Go 只做读取（商户信息缓存在 Redis）
- PHP 写入订单后，Go 负责调用第三方 API 并回写结果

**引入 Go 的触发信号**：
- PHP Octane worker 持续满载，扩机器成本 > 开发 Go 服务成本
- 第三方供应商调用延迟拖累 PHP worker 释放
- 签名验证 CPU 占用成为瓶颈

**建议**：先以 Phase 1 落地，架构已预留扩展点（Repository 接口、缓存层、队列分区），后续按需升级，避免过度工程。

---

## 11. 队列与异步处理

| Job | Queue | 重试 | 说明 |
|-----|-------|------|------|
| ProcessDepositWalletJob | fluxpay-wallet | 3 | 代收结算 |
| ProcessWithdrawWalletJob | fluxpay-wallet | 3 | 代付结算/反转 |
| MerchantNotificationJob | fluxpay-notification | 5 (60s) | 商户回调通知 |
| OrderQueryPollingJob | fluxpay-gateway | 3 | 轮询滞留订单 |
| AggregateDailyStatsJob | fluxpay-stats | 1 | 日统计聚合 |

使用 Laravel Horizon 监控，wallet 队列串行处理防止竞态。

---

## 12. 测试策略

### Unit Tests
- `CommissionCalculatorTest` — 百分比/固定费用、多级代理、边界值
- `MerchantWalletServiceTest` — 充值/冻结/解冻/扣款、余额不足
- `SignatureHelperTest` — 签名生成与验证
- `MoneyHelperTest` — bcmath 精度
- `PaymentGatewayFactoryTest` — 配置合并、类加载

### Feature Tests
- `DepositApplyTest` — 完整 HTTP 请求 → 订单创建 → 响应格式
- `DepositCallbackTest` — 模拟回调 → 结算 → 钱包更新 → 通知派发
- `WithdrawApplyTest` — 余额检查 → 冻结 → 网关调用
- `WalletIntegrationTest` — 完整存取流程，三方钱包同步验证

### 测试基础设施
- `FakeGateway` — 实现接口，可配置返回，无外部调用
- Database Factories — Merchant/Agent/Provider/Order
- 每个测试用 DB Transaction 回滚

---

## 13. 定时任务

| 任务 | 频率 |
|------|------|
| ResetProviderDailyLimitsCommand | 每日按 reset_time |
| StalledOrderCheckCommand | 每 5 分钟 |
| AggregateDailyStatsJob | 每日 00:05 |

---

## 14. 相较 sifangpay 的改进

| 方面 | sifangpay | FluxPay |
|------|-----------|---------|
| 架构分层 | Controller → Service → Model | Controller → Service → Repository → Model |
| 网关配置 | PHP 常量 | `config/gateways.php` + DB 合并 |
| 后台 | dcat-admin | Orchid Platform |
| 事件系统 | 无（过程式调用） | Laravel Events/Listeners |
| 参数校验 | Controller 内联 | FormRequest 类 |
| 订单日志 | 分表 | 多态统一 order_logs |
| 通知 Job | 2 个独立 Job | 1 个统一 MerchantNotificationJob |
| 佣金计算 | 散落多处 | 独立 CommissionCalculator |
| 通道选择 | 手动 | ChannelSelector（权重+限额感知） |
| 高并发 | 无特殊设计 | Octane + 读写分离 + 分布式锁 + 队列分区 + 分表 |
| 测试 | 极少 | 完整测试套件 + FakeGateway |
| 租户隔离 | dcat-admin 专用 | 框架无关，Orchid 兼容 |

---

## 15. 验证方式

1. **单元测试**：`php artisan test --testsuite=Unit`
2. **功能测试**：`php artisan test --testsuite=Feature`
3. **API 手动测试**：后台 API 测试工具 Screen
4. **代收流程**：用 TestpayGateway 模拟完整 deposit apply → callback → settlement
5. **代付流程**：用 TestpayGateway 模拟完整 withdraw apply → callback → settlement / reversal
6. **多级代理佣金**：创建 3 级代理链 → 下单 → 验证各级钱包流水
7. **后台权限**：以不同角色登录，验证数据隔离与菜单可见性
