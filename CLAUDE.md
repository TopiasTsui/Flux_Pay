# CLAUDE.md

## 项目

FluxPay — 第四方代收代付聚合系统（Deposit & Withdraw）。

## 技术栈

- Laravel 11、PHP 8.2+、Orchid Platform 14、MariaDB、Redis
- 队列：Redis + Laravel Horizon

## 架构

```
Controller → Service → Repository → Model
```

- **Controller**：接收请求、用 FormRequest 校验、调用 Service、返回 Resource。**不写业务逻辑**。
- **Service**：业务逻辑、流程编排、`DB::transaction`、事件派发。**不直接写查询**。
- **Repository**：数据访问、查询组合、`lockForUpdate`、批量操作。**不写跨领域业务流程**。
- **Model**：ORM 映射、关系、accessor/mutator、scope。**不写业务逻辑**。

## API 响应格式

```json
{"code": 0, "message": "Success", "data": {}, "timestamp": 1713350400}
```

## 代码约定

- 状态值使用 PHP Enum，存库为字符串或整数
- 金额使用整数或 `DECIMAL(20,6)`，**绝不使用 float**
- 时间使用 Carbon
- 查询必须指定列，避免 `select *`
- 避免魔法字符串
- Commit message 使用英文
- 除非用户明确要求，**不自动 `git push`**

## 工作规则

涉及以下任一项时，先输出完整方案并等待确认：

- 新增或修改 DB 表（migration）
- 新增或修改 API 端点 / 响应结构
- 跨多文件修改

小范围单文件修复可直接动手。

## 回复风格

- 默认使用简体中文回复；代码、变量、类、函数名保留英文
- 先给结论，再给推理
- 多文件改动时列出各文件的变更说明
- 直接指出问题，不绕弯

## 关键路径

- 后台路由：`routes/platform.php`
- API 路由：`routes/api.php`
- Orchid 屏幕：`app/Orchid/Screens/`
- Service 层：`app/Services/`
- Repository 层：`app/Repositories/`
- Model 层：`app/Models/`
- 迁移：`database/migrations/`
- 架构规划文件：`docs/FluxPay.md`
- 安装手册：`docs/installation.md`
- 架构说明：`docs/architecture.md`
- 使用手册：`docs/user-guide.md`
- 开发者手册：`docs/developer-guide.md`

## 测试

```
php artisan test
php vendor/bin/phpunit
```
