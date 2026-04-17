# FluxPay 安装手册

本手册描述如何在全新服务器上完成 FluxPay 的部署，涵盖环境准备、依赖安装、数据库初始化、后台账号建立、排程与队列服务启动。

---

## 1. 系统需求

| 组件 | 最低版本 | 说明 |
|------|----------|------|
| 操作系统 | Ubuntu 22.04 / Debian 12 / CentOS Stream 9 | 其他 Linux 发行版亦可，需自行调整包管理命令 |
| PHP | 8.2+ | 必须是 8.2 以上，本项目 CLI 固定使用 `php8.2` |
| Composer | 2.x | PHP 包管理器 |
| MariaDB | 10.6+ | 或兼容的 MySQL 8.0+ |
| Redis | 7+ | 缓存、队列、会话 |
| Nginx | 1.22+ | Web 服务器 |
| Supervisor | 任意 | 守护 Horizon 队列进程 |
| Git | 任意 | 源码拉取 |

### PHP 必备扩展

```
mbstring, xml, ctype, json, bcmath, pdo_mysql, redis, openssl, tokenizer, fileinfo, curl
```

---

## 2. 服务器基础环境准备

### 2.1 安装 PHP 8.2（Ubuntu 示例）

```bash
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

sudo apt install -y php8.2 php8.2-fpm php8.2-cli \
  php8.2-mbstring php8.2-xml php8.2-bcmath \
  php8.2-mysql php8.2-redis php8.2-curl \
  php8.2-zip php8.2-gd php8.2-intl
```

验证：

```bash
php8.2 -v
php8.2 -m | grep -E 'bcmath|redis|pdo_mysql'
```

### 2.2 安装 Composer

```bash
curl -sS https://getcomposer.org/installer | sudo php8.2 -- \
  --install-dir=/usr/local/bin --filename=composer
composer --version
```

### 2.3 安装 MariaDB

```bash
sudo apt install -y mariadb-server
sudo systemctl enable --now mariadb
sudo mysql_secure_installation
```

### 2.4 安装 Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable --now redis-server
redis-cli ping   # 应回 PONG
```

### 2.5 安装 Nginx 与 Supervisor

```bash
sudo apt install -y nginx supervisor
sudo systemctl enable --now nginx supervisor
```

---

## 3. 获取源码

```bash
sudo mkdir -p /var/www/html
sudo chown -R $USER:www-data /var/www/html
cd /var/www/html

git clone <your-repo-url> fluxpay
cd fluxpay
```

设置目录权限：

```bash
sudo chown -R $USER:www-data /var/www/html/fluxpay
sudo chmod -R 775 storage bootstrap/cache
```

---

## 4. 安装依赖

本项目 **统一使用 `php8.2`**，不可用裸 `php` 命令。

```bash
php8.2 $(which composer) install --no-dev --optimize-autoloader
```

开发环境若要安装 dev 依赖：

```bash
php8.2 $(which composer) install
```

---

## 5. 环境配置

### 5.1 复制 `.env`

```bash
cp .env.example .env
php8.2 artisan key:generate
```

### 5.2 编辑 `.env`

关键配置项：

```dotenv
APP_NAME=FluxPay
APP_ENV=production          # 生产环境改为 production
APP_DEBUG=false             # 生产环境必须关闭
APP_URL=https://your-domain.com
APP_TIMEZONE=Asia/Taipei
APP_LOCALE=zh-CN            # 可选 zh-CN / zh-TW / en

# 数据库
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fluxpay
DB_USERNAME=fluxpay
DB_PASSWORD=your_secure_password

# Redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# 会话、缓存、队列均走 Redis
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
CACHE_PREFIX=fluxpay_

# FluxPay 业务参数
FLUXPAY_ORDER_PREFIX=FP
FLUXPAY_CALLBACK_MAX_RETRIES=5
FLUXPAY_CALLBACK_RETRY_INTERVAL=60
FLUXPAY_DEFAULT_CURRENCY=PHP
```

---

## 6. 数据库初始化

### 6.1 创建数据库与用户

```sql
CREATE DATABASE fluxpay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'fluxpay'@'127.0.0.1' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON fluxpay.* TO 'fluxpay'@'127.0.0.1';
FLUSH PRIVILEGES;
```

### 6.2 执行迁移

```bash
php8.2 artisan migrate --force
```

### 6.3 执行 Seeder（首次部署必需）

```bash
php8.2 artisan db:seed --force
```

Seeder 会初始化：

- 角色与权限（Administrator / Manager / Merchant / Agent）
- 预置支付方式（`PaymentTypeSeeder`）
- 预置银行列表（`BankSeeder`）
- 测试数据（`TestDataSeeder`，生产环境可单独跳过）

仅执行指定 Seeder（生产环境建议跳过测试数据）：

```bash
php8.2 artisan db:seed --class=RolePermissionSeeder --force
php8.2 artisan db:seed --class=PaymentTypeSeeder --force
php8.2 artisan db:seed --class=BankSeeder --force
```

---

## 7. 建立首个超级管理员

```bash
php8.2 artisan orchid:admin <用户名> <邮箱> <密码>
# 例：
php8.2 artisan orchid:admin admin admin@fluxpay.com StrongPass123!
```

创建完毕后，登录后台需在 `users` 表中为该账号附加 `administrator` 角色（Seeder 已自动授予默认账号，如手动新建可在后台 `系统 → 角色` 中关联）。

---

## 8. 发布前端资源

```bash
php8.2 artisan vendor:publish --tag=laravel-assets --force
php8.2 artisan vendor:publish --tag=orchid-migrations --force
php8.2 artisan storage:link
```

---

## 9. 缓存与优化（生产环境）

```bash
php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache
php8.2 artisan event:cache
```

如需清除：

```bash
php8.2 artisan optimize:clear
```

---

## 10. Nginx 配置示例

`/etc/nginx/sites-available/fluxpay.conf`：

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/fluxpay/public;

    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

启用：

```bash
sudo ln -s /etc/nginx/sites-available/fluxpay.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### HTTPS（强烈建议）

使用 Certbot：

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

---

## 11. 队列与 Horizon

FluxPay 的商户回调、钱包结算、订单轮询均走 Redis 队列，**必须** 保持 Horizon 持续运行。

### 11.1 Supervisor 守护配置

`/etc/supervisor/conf.d/fluxpay-horizon.conf`：

```ini
[program:fluxpay-horizon]
process_name=%(program_name)s
command=/usr/bin/php8.2 /var/www/html/fluxpay/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/html/fluxpay/storage/logs/horizon.log
stopwaitsecs=3600
```

启动：

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start fluxpay-horizon
sudo supervisorctl status
```

### 11.2 Horizon 仪表板

访问 `https://your-domain.com/horizon`（仅 `administrator` / `manager` 可见）。

---

## 12. 排程任务（Cron）

FluxPay 存在多个周期任务（订单轮询、每日统计聚合、通道日限额重置）。在系统 crontab 中加入 Laravel 调度：

```bash
sudo crontab -e -u www-data
```

加入：

```
* * * * * cd /var/www/html/fluxpay && /usr/bin/php8.2 artisan schedule:run >> /dev/null 2>&1
```

---

## 13. 验证部署

| 检查项 | 命令 / 动作 |
|--------|-------------|
| Web 可达 | 浏览器打开 `https://your-domain.com/admin`，出现登录页 |
| 登录 | 用步骤 7 的账号登录 |
| 数据库 | 后台首页应能看到仪表板，无报错 |
| Redis | `redis-cli ping` 返回 `PONG` |
| Horizon | `https://your-domain.com/horizon` 显示绿色 Active |
| 队列 | 在后台触发一次测试订单，查看 Horizon `fluxpay-*` 队列是否有执行记录 |
| 排程 | `php8.2 artisan schedule:list` 能列出已注册任务 |
| 日志 | `tail -f storage/logs/laravel.log` 无致命错误 |

---

## 14. 常见问题

### 14.1 `SQLSTATE[HY000] [1045] Access denied`

检查 `.env` 中的 `DB_USERNAME` / `DB_PASSWORD`，并确认该用户对 `fluxpay` 库具备权限。

### 14.2 `Permission denied` 写入日志或缓存

```bash
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 14.3 Orchid 后台静态资源 404

```bash
php8.2 artisan vendor:publish --tag=laravel-assets --force
php8.2 artisan optimize:clear
```

### 14.4 Horizon 启动后立即退出

查看 `storage/logs/horizon.log`，最常见为 Redis 不可达。确认 `.env` 中的 Redis 配置与 `redis-cli ping` 一致。

### 14.5 回调通知不触发

* 确认 Horizon 正在运行
* 确认 `QUEUE_CONNECTION=redis`
* 查看 `order_logs` 表与 `storage/logs/laravel.log`

---

## 15. 升级流程（同版本小版本更新）

```bash
cd /var/www/html/fluxpay
git pull origin main

php8.2 $(which composer) install --no-dev --optimize-autoloader
php8.2 artisan migrate --force
php8.2 artisan optimize:clear
php8.2 artisan config:cache
php8.2 artisan route:cache
php8.2 artisan view:cache

sudo supervisorctl restart fluxpay-horizon
```

---

至此，FluxPay 已完成基础部署。后续的业务配置（代理层级、商户、供应商、通道、费率）请参考 **docs/user-guide.md**。
