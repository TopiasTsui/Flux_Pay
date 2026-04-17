# FluxPay 使用手册

本手册面向 **后台运营人员** 与 **商户技术对接人员**，说明如何使用 FluxPay 的后台功能与对外 API。

---

## 目录

- [A. 后台使用指南](#a-后台使用指南)
  - A1. 登录与个人资料
  - A2. 仪表板
  - A3. 代理管理
  - A4. 商户管理
  - A5. 供应商管理
  - A6. 支付方式与通道配置
  - A7. 商户费率配置
  - A8. 订单管理
  - A9. 钱包与手动调账
  - A10. 报表
  - A11. 银行管理
  - A12. 系统模块
  - A13. 用户与角色
  - A14. 菜单管理
- [B. 商户对接 API](#b-商户对接-api)
  - B1. 通用约定
  - B2. 签名规则
  - B3. 代收申请
  - B4. 代收查询
  - B5. 代付申请
  - B6. 代付查询
  - B7. 余额查询
  - B8. 商户回调（FluxPay → 商户）
- [C. 供应商回调](#c-供应商回调)
- [D. 常见运营操作流程](#d-常见运营操作流程)

---

## A. 后台使用指南

### A1. 登录与个人资料

- 后台地址：`https://<your-domain>/admin`
- 输入邮箱 + 密码登录
- 右上角头像 → **Profile**：修改姓名、邮箱、密码、界面语言（支持 `zh-CN` / `zh-TW` / `en`）

> 若账号登录后无任何菜单，请联系管理员检查 **用户 → 角色** 是否已分配。

---

### A2. 仪表板

路径：**Dashboard**

- 今日代收 / 代付金额与笔数
- 各状态订单数量
- 各主体（商户 / 代理 / 供应商）钱包汇总
- 最近操作记录

根据登录身份自动套用租户隔离：商户只看自己、代理看下级、管理员看全部。

---

### A3. 代理管理

路径：**主体管理 → 代理**

功能：

- **列表**：按名称、类型、层级、状态筛选，支持导出
- **新建**：
  - `parent_id` 留空即为一级代理
  - `types` 选择 `merchant`（管商户）或 `provider`（管供应商）
  - 初始 `currency`（币种）、`status`
- **编辑**：
  - 可调整归属代理（若未产生交易）
  - 钱包调账：在代理详情页底部的「钱包调整」区块，选择增减金额、填写备注后提交，系统会自动写入 `agent_wallet_records`
- **层级限制**：最多 3 层，新建子代理时会校验父代理 `level`

> ⚠ 代理一旦已有商户或供应商关联，不建议更改 `types`。

---

### A4. 商户管理

路径：**主体管理 → 商户**

功能：

- **新建商户**
  - `code`：商户号（对接 API 时使用）
  - `name`：显示名称
  - `md5key`：签名密钥，建议使用后台自动生成；保存后对商户技术方提供
  - `agent_id`：归属代理（必填，决定分润链）
  - `currency_code`：币种（如 `PHP`、`CNY`）
  - `white_ips`：逗号分隔的 IP 白名单，API 请求必须来自这些 IP
  - `status`：`1` 启用 / `0` 停用
- **钱包调账**：在商户编辑页底部的「钱包调整」直接操作；自动写入 `merchant_wallet_records`，带操作人与备注
- **删除**：若已存在订单则不可删除

> 建议上线前先在测试环境用 `TestpayGateway` 演练完整闭环，再启用真实 IP 白名单。

---

### A5. 供应商管理

路径：**主体管理 → 供应商**

功能：

- **新建**
  - `name`：对内显示名称
  - `vendor_id`：**必须** 与 `config/gateways.php` 中的 key 一致（例如 `testpay`）
  - `provider_no`：供应商分配给我方的商户号
  - `vendor_meta`：JSON 格式的驱动级配置（API key、URL、签名密钥等），字段名由对应 Gateway 类决定
  - `bank_config_key`：与 `provider_bank_codes.bank_config_key` 对应，用于银行代码映射
  - `call_back_ips`：供应商回调来源 IP，逗号分隔
  - `agent_id`：可选，供应商侧代理
- **钱包调账**：同商户，页面底部「钱包调整」
- **api_available_balance**：只读字段，由定时 `balanceQuery` 任务更新

---

### A6. 支付方式与通道配置

#### A6.1 支付方式（Payment Types）

路径：**通道配置 → 支付方式**

字典表，列出系统支持的所有支付方式（例如 `BANK_TRANSFER`、`GCASH`、`USDT`）。

- `payment_type_code`：对外代码，商户调用 API 时传入
- `status`：停用后所有相关通道会同步失效

#### A6.2 供应商通道（Provider Channels）

路径：**通道配置 → 供应商通道**

每条记录 = 一个供应商支持的具体通道（供应商 × 支付方式 × 方向）。

关键字段：

| 字段 | 说明 |
|------|------|
| `provider_id` | 归属供应商 |
| `payment_type_id` | 支付方式 |
| `type` | `deposit`（代收） / `withdraw`（代付） |
| `alias` | 别名，便于区分同供应商的不同包装 |
| `weight` | 1–100，`ChannelSelector` 按权重抽取 |
| `single_min_amount` / `single_max_amount` | 单笔限额 |
| `daily_amount_limit` / `daily_count_limit` | 日限额 |
| `current_daily_amount` | 只读，系统自动累计 |
| `reset_time` | `HH:MM`，每日清零时间 |
| `deposit_fee_type` / `deposit_fee` | 成本费率（百分比 `1` / 固定 `2`） |
| `agent_fee` | 供应商侧代理费率 |

权重示例：A=70 / B=20 / C=10 时，按该比例随机分流；若某通道日限额用尽，自动跳过选下一个。

---

### A7. 商户费率配置

路径：**通道配置 → 商户费率**

每条记录 = 商户 × 支付方式的一套费率配置。

核心字段：

- `deposit_fee_type` / `deposit_fee`：商户代收手续费
- `withdraw_fee_type` / `withdraw_fee`：商户代付手续费
- `single_min_amount` / `single_max_amount`：商户级单笔限额（覆盖通道默认）
- `deposit_agents_fee`（JSON）：逐级代理分润
  ```json
  { "1": "1.5", "3": "0.8" }
  ```
  含义：代理 ID `1` 每笔抽 1.5%（或 1.5，视 `fee_type` 而定），代理 ID `3` 抽 0.8
- `withdraw_agents_fee`：同理

> JSON 键是 `agents.id`，非层级，务必先在代理列表确认 ID。

#### 通道分配

同屏或独立屏幕（视后台菜单）：**商户 × 供应商通道** 的授权关系（`merchant_provider_payment_types`）。只有被授权的通道，才会进入 `ChannelSelector` 的候选池。

---

### A8. 订单管理

#### A8.1 代收订单

路径：**支付管理 → 代收订单**

功能：

- **筛选**：系统订单号、商户订单号、商户、供应商、状态、创建时间范围
- **详情**：
  - 订单基本信息（金额、手续费、代理分润明细、供应商成本）
  - 完整时间线（来自 `order_logs`）：申请 → 送出 → 回调 → 结算 → 商户通知
  - 回调原始报文（JSON）
- **操作**（需 `platform.orders.actions` 权限）：
  - **手动查单**：向供应商重新拉取订单状态
  - **手动回调**：用于供应商回调丢失时，管理员可手动触发结算
  - **标记状态**：谨慎使用，仅异常情况下修正

#### A8.2 代付订单

路径：**支付管理 → 代付订单**

同代收，额外显示：

- 收款银行名称、户名、账号、支行
- `total_debit`：冻结总额
- 失败时的反转流水引用

---

### A9. 钱包与手动调账

路径：**财务 → 商户 / 代理 / 供应商钱包**

- 列表为钱包流水（不是余额列表），按 `sn`、`type_code`、创建时间筛选
- 点击任一流水可查看操作前后三栏余额快照，方便对账
- **手动调账** 在 **主体编辑屏（商户 / 代理 / 供应商）** 页面底部完成；提交后即时生成流水并更新余额

调账典型场景：

- 线下打款入账
- 退款 / 补偿
- 误操作修正（建议附备注与工单编号）

---

### A10. 报表

路径：**报表 → 交易统计 / 营收统计**

- **交易统计**：按日期 × 商户 / 供应商展示代收代付笔数、金额、成功率
- **营收统计**：平台收入 = 商户手续费 − 供应商成本 − 代理分润，按日聚合

数据由 `AggregateDailyStatsJob` 每日 00:05 自动聚合，支持日期范围筛选与导出。

---

### A11. 银行管理

路径：**银行 → 银行列表 / 供应商银行代码**

- **银行列表**：全局银行字典（`bank_code`、`name`、`status`）
- **供应商银行代码**：为每家供应商维护 `bank_config_key + bank_code → provider_bank_code` 的映射。代付下单时，系统按供应商的 `bank_config_key` 查出对应的 `provider_bank_code`，以兼容不同供应商的银行代码规则。

---

### A12. 系统模块

路径：**系统 → ...**

- **系统配置**：通用 key-value 配置（`system_configs`），可按 `group` 分组
- **黑名单**：按类型（IP / 证件号 / 银行账户）维护，命中后相关订单会被拒绝
- **菜单管理**：见 A14

---

### A13. 用户与角色

#### A13.1 用户

路径：**权限控制 → 用户**

- 新建后台账号，选择 **角色** 并指定 **所属代理 / 商户**（决定 TenantScope 的可见范围）
- 可设置 `locale`（界面语言）、启用/停用

#### A13.2 角色

路径：**权限控制 → 角色**

- 内置角色：`administrator`、`manager`、`merchant`、`agent`
- 可自行新增角色并勾选权限；权限由 `PlatformProvider::permissions()` 定义

> 最小权限原则：尽量为不同岗位建立专属角色，避免直接使用超管账号。

---

### A14. 菜单管理

路径：**系统 → 菜单管理**

- 一旦 `admin_menus` 表有数据，`PlatformProvider` 会优先读取 DB 菜单
- 支持拖拽排序、层级嵌套、按权限过滤
- 可临时隐藏某菜单（`is_active=false`）而不删除记录
- 清空 `admin_menus` 后系统回退到代码默认菜单

---

## B. 商户对接 API

### B1. 通用约定

- 请求方式：**POST**
- Content-Type：`application/x-www-form-urlencoded` 或 `application/json` 均可
- 字符集：`UTF-8`
- 金额：字符串或数字，保留最多 6 位小数
- 时区：FluxPay 内部 `Asia/Taipei`；返回时间戳为 Unix 秒
- 所有响应统一为：

```json
{
  "code": 0,
  "message": "Success",
  "data": { ... },
  "timestamp": 1713350400
}
```

常见 `code`：

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

### B2. 签名规则（MD5）

```
1. 取请求参数，移除 signature、sign、callbackUrl、extend
2. 过滤值为空字符串或 null 的字段
3. 按参数名 (key) 字典序 A-Z 排序
4. 按 key1=value1&key2=value2 拼接
5. 末尾追加 &<md5key>
6. 对最终字符串计算 MD5，结果转小写
```

PHP 示例：

```php
$params = [
    'merchantNo' => 'MCH001',
    'orderNo'    => 'ORDER20260417001',
    'amount'     => '100.00',
    'paymentTypeCode' => 'BANK_TRANSFER',
];

ksort($params);
$pairs = [];
foreach ($params as $k => $v) {
    if ($v === '' || $v === null) continue;
    $pairs[] = "{$k}={$v}";
}
$str = implode('&', $pairs) . '&' . $md5key;
$signature = md5($str);
```

Python 示例：

```python
import hashlib

def sign(params: dict, md5key: str) -> str:
    exclude = {'signature', 'sign', 'callbackUrl', 'extend'}
    filtered = {k: v for k, v in params.items()
                if k not in exclude and v not in (None, '')}
    items = sorted(filtered.items())
    s = '&'.join(f'{k}={v}' for k, v in items) + f'&{md5key}'
    return hashlib.md5(s.encode('utf-8')).hexdigest()
```

---

### B3. 代收申请 `POST /api/deposit/apply`

请求参数：

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| merchantNo | string | ✓ | 商户号 |
| orderNo | string(≤64) | ✓ | 商户订单号（在商户侧唯一） |
| amount | number ≥ 0.01 | ✓ | 金额 |
| currency | string | | 币种，默认商户币种 |
| paymentTypeCode | string | | 指定支付方式；不传则由 `ChannelSelector` 决定 |
| callbackUrl | url | | 异步通知地址（不参与签名） |
| bankCode | string | | 银行代码，部分支付方式必填 |
| payerName | string | | 付款人姓名 |
| extend | string | | 透传字段，原样在异步通知里返回（不参与签名） |
| signature | string | ✓ | MD5 签名 |

成功响应：

```json
{
  "code": 0,
  "message": "Success",
  "data": {
    "merchantOrderNo": "ORDER20260417001",
    "systemOrderNo": "FP20260417000001",
    "amount": "100.000000",
    "status": 1,
    "payUrl": "https://pay.example.com/xxx",
    "qrCode": null
  },
  "timestamp": 1713350400
}
```

收银台链接（`payUrl`）或 `qrCode` 由供应商决定，至少返回其一。商户可直接跳转或生成二维码。

---

### B4. 代收查询 `POST /api/deposit/query`

请求：

| 字段 | 必填 | 说明 |
|------|------|------|
| merchantNo | ✓ | |
| orderNo | ✓ | 商户订单号 |
| signature | ✓ | |

响应 data 与 B3 类似，额外包含最终 `status` 与回调时间。

---

### B5. 代付申请 `POST /api/withdraw/apply`

请求：

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| merchantNo | string | ✓ | |
| orderNo | string(≤64) | ✓ | |
| amount | number ≥ 0.01 | ✓ | |
| currency | string | | |
| bankCode | string | ✓ | |
| bankAccountName | string | ✓ | 收款人姓名 |
| bankAccountNo | string | ✓ | 收款账号 |
| bankBranch | string | | 支行 |
| callbackUrl | url | | 异步通知地址 |
| extend | string | | 透传字段 |
| signature | string | ✓ | |

下单时系统会 **立即冻结** 商户可用余额（金额 + 手续费）。若余额不足直接返回错误。

---

### B6. 代付查询 `POST /api/withdraw/query`

同 B4。

---

### B7. 余额查询 `POST /api/balance/query`

请求：`merchantNo`、`signature`

响应：

```json
{
  "code": 0,
  "message": "Success",
  "data": {
    "merchantNo": "MCH001",
    "currency": "PHP",
    "totalBalance": "10000.000000",
    "availableBalance": "9500.000000",
    "holdBalance": "500.000000"
  },
  "timestamp": 1713350400
}
```

---

### B8. 商户回调（FluxPay → 商户）

当代收 / 代付终态确定后，FluxPay 会向商户 `callbackUrl` 推送 POST 通知。

- Content-Type：`application/x-www-form-urlencoded`
- 重试策略：最多 `FLUXPAY_CALLBACK_MAX_RETRIES`（默认 5 次），间隔 `FLUXPAY_CALLBACK_RETRY_INTERVAL`（默认 60 秒）
- 商户需 **返回字符串 `SUCCESS`**（或 HTTP 200 且 body 为 `SUCCESS`）才视为通知成功；否则按策略重试

回调参数（代收示例）：

| 字段 | 说明 |
|------|------|
| merchantNo | |
| orderNo | 商户订单号 |
| systemOrderNo | 系统订单号 |
| amount | 订单金额 |
| actualAmount | 实际到账 |
| status | `4`=成功 `3`=失败 |
| paymentTypeCode | |
| providerOrderNo | |
| extend | 透传字段 |
| timestamp | Unix 秒 |
| signature | MD5 签名（与请求签名规则一致） |

**请务必验签** 后再更新商户侧订单。

---

## C. 供应商回调

FluxPay 接收供应商的异步通知：

- 代收：`POST /api/deposit/{vendor}/callback`
- 代付：`POST /api/withdraw/{vendor}/callback`

流程：

1. `ProviderCallbackMiddleware` 校验来源 IP 是否在 `providers.call_back_ips` 白名单
2. 路由中的 `{vendor}` 对应 `vendor_id`，系统据此实例化网关类
3. 网关 `depositCallback` / `withdrawCallback` 解析并验签，返回统一 DTO
4. `DepositService` / `WithdrawService` 更新订单、派发事件、异步结算与通知

> 对供应商的回应内容由具体 Gateway 决定（通常为 `SUCCESS` 文本或 JSON）。

---

## D. 常见运营操作流程

### D1. 新接入一家商户

1. **主体管理 → 代理**：确认或新建商户所属代理
2. **主体管理 → 商户**：新建商户，生成 `md5key`，填写白名单 IP
3. **通道配置 → 商户费率**：为商户配置各支付方式的费率与代理分润
4. **通道配置 → 通道分配**：授权商户可使用的供应商通道
5. **权限控制 → 用户**：可选，为商户新建后台登录账号，角色 `merchant`
6. 将商户号 + `md5key` + API 文档（本手册 B 节）交给商户技术
7. 在测试环境用 `TestpayGateway` 跑通代收 / 代付闭环

### D2. 接入新供应商

1. 开发 `app/Services/Gateway/Vendors/<Xxx>Gateway.php`（参考 `TestpayGateway`）
2. 在 `config/gateways.php` 注册 `vendor_id → classname`
3. **主体管理 → 供应商**：新建 Provider，填 `vendor_id` 与 `vendor_meta`、回调 IP 白名单
4. **通道配置 → 供应商通道**：创建代收 / 代付通道，设置费率、权重、限额
5. **银行 → 供应商银行代码**：如需银行映射，补齐
6. 在商户费率与通道分配中授权给相应商户
7. 开启小额灰度，观察订单与钱包流水无误后放开限额

### D3. 对账

- **报表 → 交易统计 / 营收统计**：对账基线
- **财务 → 钱包流水**：逐笔核对
- **供应商侧**：通过后台「手动查单」与供应商后台对比
- 对不一致订单：查看详情页的 `order_logs` 时间线 → 决定是否手动调账

### D4. 处理异常订单

- **长时间未收到供应商回调**：
  - 订单详情 → **手动查单**
  - 或等待 `OrderQueryPollingJob` 自动轮询
- **供应商回调丢失**：
  - 订单详情 → **手动回调** 由管理员触发结算
- **代付失败但钱未退回**：
  - 检查 `withdraw_orders.fund_status`
  - 若确实未反转，可手动触发反转事件或提交工单

### D5. 紧急停服一家供应商

1. **主体管理 → 供应商**：将供应商 `status` 置为 `0`（停用）
2. 或 **通道配置 → 供应商通道**：单独停用某类型（如只停代付保留代收）
3. 已送出的订单继续按原流程回调结算，`ChannelSelector` 不再派单到停用通道

---

如需进一步了解系统内部实现，请阅读 `docs/architecture.md` 与 `docs/FluxPay.md`。
