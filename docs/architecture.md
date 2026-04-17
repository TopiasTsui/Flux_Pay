# FluxPay 系统架构说明

本文件介绍 FluxPay 整体架构、模块职责、核心流程与数据模型，帮助开发者与运维人员理解系统全貌。

> 完整的技术决策与未来规划见 `docs/FluxPay.md`。本文件聚焦已落地的实现。

---

## 1. 系统定位

FluxPay 是一个 **第四方支付聚合平台**，提供：

- **代收（Deposit）**：商户通过 FluxPay 汇总多家第三方支付通道，统一收款
- **代付（Withdraw）**：商户通过 FluxPay 进行统一付款到银行账户或电子钱包
- **三层代理体系**：平台 → 一级代理 → 二级代理 → 三级代理，各级分润自动计算
- **多租户数据隔离**：管理员、经理、商户、代理各自只能看到授权范围内的数据
- **统一后台**：Orchid Platform 提供完整的运营管理界面

---

## 2. 技术栈总览

| 层面 | 选型 |
|------|------|
| 后端框架 | Laravel 11 |
| 运行时 | PHP 8.2+（CLI 固定 `php8.2`） |
| 后台管理 | Orchid Platform 14 |
| 数据库 | MariaDB 10.6+ |
| 缓存 / 队列 / 会话 | Redis 7+ |
| 队列调度器 | Laravel Horizon |
| 任务守护 | Supervisor |
| Web 服务器 | Nginx + PHP-FPM |
| 金额精度 | `DECIMAL(20,6)` + `bcmath` |
| 测试 | PHPUnit 11 |

---

## 3. 分层架构

```
┌──────────────────────────────────────────────────────────┐
│                       HTTP / CLI                          │
└───────────────┬──────────────────────────────┬────────────┘
                │                              │
        ┌───────▼────────┐             ┌───────▼────────┐
        │  Controller    │             │  Orchid Screen │
        │ (FormRequest → │             │  (后台 UI)     │
        │   Service)     │             └───────┬────────┘
        └───────┬────────┘                     │
                │                              │
                └──────────────┬───────────────┘
                               │
                       ┌───────▼────────┐
                       │    Service     │  业务编排 / 事务 / 事件分发
                       └───────┬────────┘
                               │
                       ┌───────▼────────┐
                       │   Repository   │  数据访问 / 锁 / 批量操作
                       └───────┬────────┘
                               │
                       ┌───────▼────────┐
                       │     Model      │  ORM / 关系 / Scope
                       └───────┬────────┘
                               │
                       ┌───────▼────────┐
                       │   MariaDB      │
                       └────────────────┘
```

职责边界：

| 层 | 做什么 | 不做什么 |
|----|--------|----------|
| Controller | 接收请求、参数校验、调用 Service、返回 `Resource` + 统一 JSON | 业务逻辑、直接操作 Model |
| Service | 业务规则、`DB::transaction`、事件派发 | 直接写查询、返回 HTTP |
| Repository | 查询组合、`lockForUpdate`、批量 upsert | 业务流程、跨领域事务 |
| Model | 关系、accessor/mutator、scope | 业务规则、HTTP 细节 |

---

## 4. 目录结构

```
app/
├── Contracts/              # 接口（Gateway、Repository）
├── DTOs/                   # 数据传输对象（Gateway 响应、佣金计算结果）
├── Enums/                  # 状态枚举（OrderStatus、CallbackStatus、FundStatus…）
├── Events/Order/           # 订单相关领域事件
├── Exceptions/             # 自定义异常
├── Helpers/                # MoneyHelper / SignatureHelper / OrderNumberGenerator
├── Http/
│   ├── Controllers/Api/    # MerchantApi / ProviderCallback / FrontendPay
│   ├── Middleware/         # MerchantAuth / ProviderCallback / RequestLogging / SetLocale / CheckUserActive
│   ├── Requests/           # FormRequest 校验
│   └── Resources/          # 统一输出格式
├── Jobs/                   # 异步作业（MerchantNotificationJob、OrderQueryPollingJob）
├── Listeners/Order/        # 事件监听器（结算、通知、反转、日志）
├── Models/                 # Eloquent 模型
├── Orchid/
│   ├── PlatformProvider.php
│   ├── Screens/            # 后台屏幕
│   ├── Layouts/
│   └── Filters/
├── Repositories/           # Repository 实现
├── Scopes/                 # TenantScope（多租户隔离）
└── Services/
    ├── Order/              # DepositService / WithdrawService
    ├── Wallet/             # MerchantWalletService / AgentWalletService / ProviderWalletService
    ├── Agent/              # CommissionCalculator（佣金计算）
    ├── Provider/           # ChannelSelector（通道选择）
    ├── Gateway/
    │   ├── Vendors/        # TestpayGateway（扩展槽）
    │   └── PaymentGatewayFactory.php
    ├── Security/           # SignatureService / IpWhitelistService
    └── CacheService.php

config/
├── fluxpay.php             # 业务参数
├── gateways.php            # 网关驱动映射
└── platform.php            # Orchid 配置

database/
├── migrations/             # 核心表结构（见第 6 节）
├── seeders/                # RolePermission / PaymentType / Bank / TestData
└── factories/

routes/
├── api.php                 # 商户 API + 供应商回调
├── platform.php            # Orchid 后台
└── web.php                 # 前台收银台

tests/
├── Unit/
├── Feature/
└── Stubs/
```

---

## 5. 业务主体与关系

```
agents (自关联, 最多 3 层)
   │
   ├── merchants     （类型=merchant 的代理所属）
   │     ├── merchant_payment_types          (每个商户的支付方式费率)
   │     ├── merchant_provider_payment_types (商户被授权使用的供应商通道)
   │     ├── deposit_orders
   │     ├── withdraw_orders
   │     └── merchant_wallet_records
   │
   └── providers     （类型=provider 的代理所属）
         ├── provider_payment_types          (供应商通道 + 费率 + 限额)
         └── provider_wallet_records

payment_types                                 (全局支付方式字典)
agent_wallet_records                          (各级代理钱包流水)
order_logs                                    (多态：deposit/withdraw 统一)
banks / provider_bank_codes                   (银行字典 + 映射)
```

### 5.1 代理（Agent）

- `parent_id` 自关联，层级 `level` 取值 1/2/3
- `types` 标记代理类型：`merchant`（管商户）/ `provider`（管供应商）
- 钱包三栏：`total_balance` / `available_balance` / `hold_balance`

### 5.2 商户（Merchant）

- `code` 商户对外编号
- `md5key` API 签名密钥（加密存储）
- `white_ips` JSON 白名单数组
- 钱包同样三栏
- 归属某个 `merchant` 类型的代理

### 5.3 供应商（Provider）

- `vendor_id` 指向网关实现类的 key（对应 `config/gateways.php`）
- `vendor_meta` JSON，存 API key、URL、签名密钥等驱动级配置
- `call_back_ips` 供应商回调来源 IP 白名单
- `api_available_balance` 最近一次查询到的对端 API 余额

---

## 6. 核心数据表

### 6.1 订单表

#### `deposit_orders`（代收）

关键字段：

| 字段 | 说明 |
|------|------|
| `merchant_order_no` | 商户订单号 |
| `system_order_no` | 系统订单号（唯一） |
| `provider_payment_type_id` | 实际使用的供应商通道 |
| `order_amount` / `actual_amount` | 订单金额 / 到账金额 |
| `merchant_fee` / `provider_fee` / `agent_fee` | 各角色费用 |
| `agent_fee_map` / `provider_agent_fee_map` | JSON：`{agent_id: amount}` 多级分润明细 |
| `status` | 0=待处理 1=已送出 3=失败 4=成功 5=取消 |
| `callback_status` | 0=待回调 1=供应商回调成功 2=商户通知成功 3=商户通知失败 |
| `fund_status` | 0=待结算 1=已结算 |

#### `withdraw_orders`（代付）

结构同代收，额外字段：

- `bank_account_name` / `bank_account_no` / `bank_branch`
- `total_debit` 冻结总额（金额 + 手续费）

### 6.2 钱包流水表

三张表：`merchant_wallet_records` / `agent_wallet_records` / `provider_wallet_records`，结构一致：

| 字段 | 说明 |
|------|------|
| `sn` | 流水号（唯一） |
| `type_code` | 操作类型（充值/冻结/扣款/解冻/分润…） |
| `amount` | 金额 |
| `pre_*` / 当前 `total/available/hold` | 记录操作前后三栏余额快照 |
| `system_order_no` | 关联订单 |

### 6.3 `order_logs`（多态）

所有订单的关键动作（`apply`、`callback`、`query`、`settle`、手动操作）统一写入，可按订单查看完整时间线。

### 6.4 其他

- `banks` / `provider_bank_codes`：银行字典及每家供应商的银行代码映射
- `blacklists`：黑名单（IP / 银行账户 / 证件号）
- `system_configs`：系统参数
- `admin_menus`：可配置的后台菜单（见第 11 节）
- `admin_user_ip_whitelists`：后台用户 IP 白名单
- `daily_transaction_stats` / `daily_revenue_stats` 等每日统计表

---

## 7. 支付网关抽象

### 7.1 接口（`App\Contracts\Gateway\PaymentGatewayInterface`）

```php
depositApply(array $data): GatewayResponse;
depositQuery(array $data): GatewayResponse;
depositCallback(array $data, array $options): GatewayResponse;
withdrawApply(array $data): GatewayResponse;
withdrawQuery(array $data): GatewayResponse;
withdrawCallback(array $data, array $options): GatewayResponse;
balanceQuery(): GatewayResponse;
```

### 7.2 配置三层合并

```
config/gateways.php 默认值
    └── providers.vendor_meta (数据库)
            └── 系统固定字段 (provider_no, provider_id…)
```

### 7.3 新增供应商

1. 在 `app/Services/Gateway/Vendors/` 新建 `XxxGateway.php`，继承 `AbstractPaymentGateway`
2. 在 `config/gateways.php` 添加入口：
   ```php
   'xxx' => [
       'classname' => App\Services\Gateway\Vendors\XxxGateway::class,
       'timeout' => 30,
       'retry' => 2,
   ],
   ```
3. 在后台 **主体管理 → 供应商** 创建 Provider，`vendor_id = xxx`
4. 在 **通道配置 → 供应商通道** 为其创建 `provider_payment_types`
5. 在商户费率及通道分配中授权

> `PaymentGatewayFactory` 会根据 `vendor_id` 自动实例化，无需改动框架代码。

---

## 8. 核心业务流程

### 8.1 代收（Deposit）

```
商户                FluxPay                                     供应商
 │ POST /api/deposit/apply                                        │
 │ ──────────────────────▶  MerchantAuth                           │
 │                          (IP + 签名 + merchant 状态)             │
 │                              │                                  │
 │                              ▼                                  │
 │                          DepositService::apply                  │
 │                          ├── ChannelSelector 选通道              │
 │                          ├── CommissionCalculator 算费           │
 │                          ├── 创建 DepositOrder (status=0)        │
 │                          └── Gateway::depositApply ────────────▶ │
 │                              │                     返回支付链接  │
 │◀────────────────────────     │ 更新 provider_order_no, status=1 │
 │  返回 {code:0, data:{...}}                                       │
 │                                                                  │
 │                                                  异步回调        │
 │                          ◀──────────────── POST /api/deposit/{v}/callback
 │                          ProviderCallback (IP 白名单)            │
 │                          DepositService::handleCallback          │
 │                          ├── status=4 成功 / 3 失败              │
 │                          └── dispatch DepositCallbackReceived    │
 │                              │                                  │
 │                    ┌─────────┴─────────┐                        │
 │                    ▼                   ▼                        │
 │            SettleDepositFunds   NotifyMerchantOnDepositSuccess  │
 │            (钱包结算 + 分润)     (异步回调商户 notify_url)        │
 │◀───────────────────────────────────────────────────────────────  │
 │ 收到 FluxPay 的异步通知
```

### 8.2 代付（Withdraw）

```
商户 → POST /api/withdraw/apply
  ├─ 校验余额：available_balance >= amount + fee
  ├─ 创建 WithdrawOrder
  ├─ 冻结商户资金：available → hold (total_debit)
  └─ Gateway::withdrawApply → 供应商

供应商回调 POST /api/withdraw/{vendor}/callback
  ├─ 成功：解冻 hold → 扣减 total, 分配代理 / 供应商佣金
  └─ 失败：解冻 hold → 返还 available, 写入反转流水
```

关键点：

- **所有钱包变更都在 `DB::transaction` + `lockForUpdate` 内完成**
- **代收的钱包操作在回调成功后异步执行**（进入 `fluxpay-wallet` 队列）
- **代付的冻结在下单时同步执行**，确保余额不会被并发请求超卖

---

## 9. 代理佣金计算

`merchant_payment_types` 表以 JSON 形式存储各级代理分润：

```json
{
  "deposit_agents_fee": { "1": "1.5", "3": "0.8", "5": "0.3" },
  "withdraw_agents_fee": { "1": "2.0", "3": "1.0" }
}
```

流程：

1. 订单创建时，`CommissionCalculator` 根据商户 × 支付方式配置，生成 `agent_fee_map`
2. 写入订单（同时计算总 `agent_fee`）
3. 结算时（`SettleDepositFunds` / `SettleWithdrawFunds`），遍历 map 逐级调用 `AgentWalletService` 入账

所有金额使用 `bcmath` 保留 6 位小数，入库后再根据币种约定展示。

---

## 10. 事件与队列

### 10.1 领域事件

| 事件 | 触发时机 |
|------|----------|
| `DepositCallbackReceived` | 供应商代收回调处理完毕 |
| `DepositFundSettled` | 代收结算完成 |
| `WithdrawCallbackReceived` | 供应商代付回调处理完毕 |
| `WithdrawFundSettled` | 代付结算完成 |
| `WithdrawFundReversed` | 代付失败资金已反转 |

### 10.2 监听器（异步进入 Redis 队列）

| Listener | 说明 |
|----------|------|
| `SettleDepositFunds` | 代收资金结算（商户 / 代理 / 供应商三方同步） |
| `SettleWithdrawFunds` | 代付结算 |
| `ReverseWithdrawFunds` | 代付失败反转 |
| `NotifyMerchantOnDepositSuccess` | 异步推送商户回调 |
| `NotifyMerchantOnWithdrawResult` | 异步推送商户代付结果 |
| `LogOrderStatusChange` | 写入 `order_logs` |

### 10.3 队列命名

| 队列 | 处理内容 | 备注 |
|------|----------|------|
| `fluxpay-wallet` | 钱包结算 | 建议串行，防止并发写入冲突 |
| `fluxpay-notification` | 商户回调通知 | 失败按 `FLUXPAY_CALLBACK_*` 配置重试 |
| `fluxpay-gateway` | 网关查询 / 轮询 | |
| `fluxpay-stats` | 每日统计聚合 | |

通过 `php8.2 artisan horizon` 统一管理。

---

## 11. 后台与权限

### 11.1 菜单

默认在 `PlatformProvider::defaultMenu()` 中定义；一旦 `admin_menus` 表有数据，则从 DB 动态加载，支持 **系统 → 菜单管理** 屏幕在线编辑。

主菜单：

- **Dashboard**：仪表板
- **支付管理**：代收订单、代付订单
- **主体管理**：商户、代理、供应商
- **通道配置**：支付方式、供应商通道、商户费率
- **财务**：商户 / 代理 / 供应商钱包流水
- **报表**：交易统计、营收统计
- **银行**：银行列表、供应商银行代码映射
- **系统**：系统配置、黑名单、菜单管理
- **权限控制**：用户、角色

### 11.2 预设角色

| Slug | 名称 | 默认权限 |
|------|------|----------|
| `administrator` | 超级管理员 | 全部 |
| `manager` | 经理 | 全部，除 `platform.systems.roles` |
| `merchant` | 商户 | 仪表板、订单、钱包 |
| `agent` | 代理 | 仪表板、订单、钱包、商户管理 |

（见 `database/seeders/RolePermissionSeeder.php`）

### 11.3 多租户隔离（`TenantScope`）

| 登录身份 | 可见数据 |
|----------|----------|
| administrator | 全部 |
| manager | 排除超管账号 |
| merchant 类型用户 | 仅自己商户的订单、钱包 |
| agent 类型用户 | 自己及下级代理 + 其商户/供应商 |

Scope 在 Model `booted()` 中挂载，Controller / Service 无需额外过滤即可保证数据隔离。

---

## 12. 安全层

| 层面 | 措施 |
|------|------|
| 商户 API | MD5 签名（`SignatureService`） + IP 白名单 |
| 供应商回调 | 每家供应商独立的 IP 白名单 |
| 后台 | Orchid 登录 + 可选 IP 白名单 |
| 敏感字段 | `md5key` 等字段使用 Laravel `encrypted` cast |
| 钱包 | `DB::transaction` + `SELECT ... FOR UPDATE` |
| 其他 | CSRF、XSS、Rate Limit、`APP_DEBUG=false`、HTTPS |

### 12.1 签名算法

```
1. 移除 signature、sign、callbackUrl、extend 字段
2. 过滤空值（'', null）
3. 按 key 字典序排序
4. 拼接 key1=value1&key2=value2&...
5. 末尾追加 &md5key
6. MD5 小写
```

（实现：`app/Helpers/SignatureHelper.php`）

---

## 13. 对外 API 概览

### 13.1 商户 API（需签名）

| 方法 | 端点 | 说明 |
|------|------|------|
| POST | `/api/deposit/apply` | 发起代收 |
| POST | `/api/deposit/query` | 查询代收订单 |
| POST | `/api/withdraw/apply` | 发起代付 |
| POST | `/api/withdraw/query` | 查询代付订单 |
| POST | `/api/balance/query` | 查询商户余额 |

### 13.2 供应商回调

| 方法 | 端点 |
|------|------|
| POST | `/api/deposit/{vendor}/callback` |
| POST | `/api/withdraw/{vendor}/callback` |

### 13.3 统一响应

```json
{
  "code": 0,
  "message": "Success",
  "data": { ... },
  "timestamp": 1713350400
}
```

常见错误码：

| code | 含义 |
|------|------|
| 0 | 成功 |
| 1001 | 缺少 merchantNo |
| 1002 | 商户不存在 |
| 1003 | 商户停用 |
| 1004 | IP 不在白名单 |
| 1005 | 缺少签名 |
| 1006 | 签名错误 |
| 2001 | 参数校验失败 |
| 3001 | 订单不存在 |
| 5000 | 系统异常 |

---

## 14. 扩展与演进路径

当前实现对应 `docs/FluxPay.md` **Phase 1**（标准 PHP-FPM + Redis + 行锁 + 统一后台）。未来可按需升级：

- **Phase 2**：Octane（Swoole）、Redis 分布式锁、Nginx 限流、读写分离
- **Phase 3**：Go 微服务处理高频路径（签名、通道选择、第三方调用），PHP 继续承担业务编排与后台
- **分表**：`deposit_orders` / `withdraw_orders` / 钱包流水按月分表

架构已预留扩展点（Repository 接口、缓存层、队列分区），可渐进升级而不重写业务。

---

## 15. 参考

- `docs/FluxPay.md` — 完整技术规划（含高并发方案）
- `docs/installation.md` — 部署步骤
- `docs/user-guide.md` — 后台与 API 操作指南
