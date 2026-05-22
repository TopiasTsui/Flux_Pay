# FluxPay

第四方代收代付聚合系统，支持 **代收（Deposit）** 与 **代付（Withdraw）**，具备最多三层代理体系与多家第三方支付通道的统一对接能力。

**当前版本：v0.1.0**（2026-04-17）

---

## 技术栈

- **后端**：Laravel 11、PHP 8.2+
- **后台管理**：Orchid Platform 14
- **数据库**：MariaDB 10.6+
- **缓存 / 队列**：Redis 7+
- **队列监控**：Laravel Horizon

---

## 系统需求

- PHP 8.2+，必备扩展：`mbstring`、`xml`、`ctype`、`json`、`bcmath`、`pdo_mysql`、`redis`
- MariaDB 10.6+
- Redis 7+
- Composer 2.x

---

## 快速开始

完整部署步骤请参考 [`docs/installation.md`](docs/installation.md)。

### 1. 拉取源码与安装依赖

```bash
git clone https://github.com/TopiasTsui/Flux_Pay.git fluxpay
cd fluxpay
php $(which composer) install
```

### 2. 环境配置

```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env`，填写数据库与 Redis：

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fluxpay
DB_USERNAME=root
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### 3. 建库与迁移

```sql
CREATE DATABASE fluxpay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate
php artisan db:seed
```

### 4. 建立后台管理员

```bash
php artisan orchid:admin admin admin@fluxpay.com your_password
```

### 5. 发布前端资源

```bash
php artisan vendor:publish --tag=laravel-assets --force
php artisan storage:link
```

### 6. 启动服务

```bash
# 开发用内置服务器
php artisan serve

# 另一终端启动队列
php artisan horizon
```

### 7. 访问

- **后台面板**：`http://localhost:8000/admin`
- **Horizon 面板**：`http://localhost:8000/horizon`

---

## 架构

```
Controller → Service → Repository → Model
```

详细说明见 [`docs/architecture.md`](docs/architecture.md) 与 [`docs/FluxPay.md`](docs/FluxPay.md)。

---

## 文档

| 文件 | 内容 |
|------|------|
| [`docs/installation.md`](docs/installation.md) | 从零部署到生产环境的完整步骤 |
| [`docs/architecture.md`](docs/architecture.md) | 模块划分、数据模型、核心流程 |
| [`docs/user-guide.md`](docs/user-guide.md) | 后台操作 + 商户 API 对接指南 |
| [`docs/developer-guide.md`](docs/developer-guide.md) | 二次开发技术手册：网关对接、测试、除错 |
| [`docs/FluxPay.md`](docs/FluxPay.md) | 架构规划与未来演进路线 |
| [`CLAUDE.md`](CLAUDE.md) | AI 协作开发约定 |

---

## 测试

```bash
php artisan test
```

---

## 捐赠

<a id="donate"></a>

如果 FluxPay 对你有帮助，欢迎请作者喝杯咖啡 ☕

通过 **Polygon PoS 网络** 转账 **USDT / USDC** 到以下地址：

```
0x5072b3d05f1550f30bd22b0175ca55bb27294bca
```

[在 Polygonscan 上查看该地址 →](https://polygonscan.com/address/0x5072b3d05f1550f30bd22b0175ca55bb27294bca)

> 仅支持 Polygon PoS 网络的 USDT / USDC，请勿从其它链转入，以免资产丢失。

---

## 版本历史

本节记录每次较大更新的内容。新增版本时请 **在顶部插入新条目**，并同步更新上方的「当前版本」。

### v0.1.0 — 2026-04-17

首个功能完整的初版，涵盖以下能力：

- 完整代收 / 代付 API 与供应商回调处理
- 支付网关抽象层（Gateway Factory + `config/gateways.php`）
- 三层代理体系与分润计算（`CommissionCalculator`）
- 通道选择器（`ChannelSelector`）支持权重、单笔 / 日限额
- 商户 / 代理 / 供应商三方钱包与流水
- Orchid 后台 26 个屏幕（仪表板、代理、商户、供应商、支付方式、供应商通道、商户费率、订单、钱包、报表、银行、系统、用户、角色）
- 租户数据隔离（`TenantScope`）
- 多语言支持（`en` / `zh-CN` / `zh-TW`），每用户独立语言
- 可配置后台菜单（`admin_menus` 表 + 菜单管理屏）
- 异步事件驱动结算与商户回调通知（Horizon）
- 全量 Seeder + Factory + Feature 冒烟测试
- 签名机制（MD5）+ IP 白名单 + 黑名单
