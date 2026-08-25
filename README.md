# 初高学生作业统计Project

一个基于 PHP 8.0 + MySQL 5.6 的作业画廊网站，用户可注册、上传作业图片，浏览公开画廊，管理员可进行用户与作业管理。

## 功能列表

- 用户注册（Geetest4 滑动验证 + 邮箱验证）
- 邮箱验证邮件发送（PHPMailer + SMTP），未验证邮箱也可登录，个人中心可重新发送验证邮件
- 用户登录（用户名/邮箱 + 密码 + Geetest4 验证）
- 忘记密码：通过邮箱发送重置链接（24 小时有效），设置新密码后重新登录
- 上传作业（图片校验 + GD 缩略图生成，需管理员审核通过后公开展示）
- 个人中心：作业管理、编辑（每周限一次）、隐藏/显示、软删除
- 公开画廊：卡片网格、懒加载、Fancybox 大图预览、分页
- 管理员后台：统计仪表盘、用户管理（禁用/启用/删除）、作业管理（审核通过 / 审核拒绝 / 隐藏/显示 / 删除）
- 公开 API：随机作业、全站统计

## 环境要求

- PHP 8.0+（需启用 pdo_mysql、gd、fileinfo、openssl、mbstring、curl 扩展）
- MySQL 5.6+（InnoDB，utf8mb4）
- Composer
- Apache（.htaccess 生效）或 Nginx

## 安装步骤

1. 将项目代码上传到服务器或本地 Web 环境。
2. 创建数据库（如 `homework_gallery`），字符集选择 `utf8mb4`：
   ```sql
   CREATE DATABASE homework_gallery DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. 配置 `config/config.php`：填写数据库连接、`BASE_URL`、SMTP、Geetest4 参数。
4. 设置目录可写：`uploads/` 与 `thumbnails/`（Linux 下 `chmod -R 775`）。
5. 安装依赖：
   ```bash
   composer require phpmailer/phpmailer
   composer require geetest/gt4-php-sdk
   ```
6. 两种初始化方式任选其一：
   - 浏览器访问 `install.php`，点击安装（自动建表、创建管理员、创建目录）；
   - 或手动导入 `database.sql` 并参照文件末尾注释创建管理员。
7. 安装完成后删除 `install.php`。

## 旧版本数据库升级

若你的 `users` 表由旧版本创建（缺少密码重置字段），请先执行以下 SQL 后再使用“忘记密码”功能：

```sql
ALTER TABLE `users`
  ADD COLUMN `reset_token` VARCHAR(64) DEFAULT NULL AFTER `email_token_expires`,
  ADD COLUMN `reset_token_expires` DATETIME DEFAULT NULL AFTER `reset_token`;
```

未升级时，“忘记密码”页面会提示需要执行该升级 SQL。

## 默认管理员账号

- 用户名：`admin`
- 密码：`admin123`
- 角色：`admin`（已激活）

登录后台后请尽快修改密码。管理员可在后台禁用/删除用户、隐藏/删除作业。

## Geetest4 配置说明

1. 到 [极验官网](https://www.geetest.com) 注册并创建验证应用，获取 `captchaId` 与 `captchaKey`。
2. 填入 `config/config.php` 的 `GEETEST_ID` 与 `GEETEST_KEY`。
3. 前端已内置初始化脚本（`assets/js/geetest-init.js`），后端在登录/注册时进行服务端二次校验。
4. 若未安装官方 SDK，`classes/Geetest.php` 会使用内置 HTTP 实现（需 curl 或 allow_url_fopen）。

## 邮件配置说明

- 使用 PHPMailer 通过 SMTP 发送验证邮件与密码重置邮件。
- 在 `config/config.php` 中配置 `SMTP_HOST`、`SMTP_PORT`、`SMTP_USERNAME`、`SMTP_PASSWORD`、`SMTP_ENCRYPTION`、`SMTP_FROM_EMAIL`、`SMTP_FROM_NAME`。
- 常见服务商参考：
  - QQ 邮箱：`smtp.qq.com`，端口 465（ssl）或 587（tls），密码填授权码。
  - 163 邮箱：`smtp.163.com`，端口 465（ssl）或 587（tls），密码填授权码。
  - Gmail：`smtp.gmail.com`，端口 587（tls），需开启应用专用密码。

## 目录结构

```
├── config/config.php        全局配置
├── includes/                初始化、函数库、公共头尾
├── classes/                 Database / User / Homework / Geetest / Mailer / Validator
├── assets/                  样式与脚本
├── uploads/                 原图目录（可写）
├── thumbnails/              缩略图目录（可写）
├── user/                    个人中心相关页面
├── admin/                   管理员后台
├── api/                     公开 JSON 接口
├── index.php                画廊首页
├── login.php / register.php / verify.php / logout.php
├── forgot_password.php      忘记密码（发送重置邮件）
├── reset_password.php       重置密码（设置新密码）
├── install.php              安装程序
├── database.sql             建表 SQL
└── .htaccess                安全配置
```

## 常见问题

**邮件发送失败**
- 检查 SMTP 配置是否填写正确，端口与加密方式是否匹配。
- QQ/163 邮箱需使用授权码而非登录密码。
- 服务器防火墙需放行 SMTP 端口。

**验证码不显示**
- 确认 `config/config.php` 中 `GEETEST_ID` 与 `GEETEST_KEY` 已填写。
- 确认页面能正常加载 `https://static.geetest.com/v4/gt4.js`。
- 服务器需能访问 `https://gcaptcha4.geetest.com`。

**注册后收不到验证邮件**
- 注册后可直接登录（未验证邮箱也允许登录），在个人中心点击“发送验证邮件”卡片重新发送。
- 检查 SMTP 配置与防火墙放行情况。

**忘记密码提示数据库缺少字段**
- 执行“旧版本数据库升级”小节中的 ALTER TABLE 语句后重试。

**上传失败 / 提示目录不可写**
- 确认 `uploads/` 与 `thumbnails/` 目录存在且可写。
- 确认图片格式为 JPG/PNG/GIF/WEBP 且不超过 5MB。
- 确认 PHP 已启用 `gd`、`fileinfo` 扩展。

**页面提示数据库连接失败**
- 检查 `config/config.php` 中数据库配置。
- 确认数据库已创建且 MySQL 服务已启动。

**上传后画廊不显示**
- 新上传或编辑后的作业需管理员在后台「作业管理」中点击「审核通过」后才会在画廊公开展示。
- 审核拒绝会直接删除图片文件与记录，操作前请确认。

**画廊打开白屏**
- 打开 `config/config.php` 将 `DEV_MODE` 临时改为 `true` 查看错误信息，排查后改回 `false`。
