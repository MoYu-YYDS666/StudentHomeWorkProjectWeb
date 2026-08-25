<?php
/**
 * ============================================================
 * 初高中作业大赏 - 用户业务逻辑类
 * 文件：classes/User.php
 * 说明：注册、登录、邮箱验证、Token 管理、状态管理、用户列表、
 *       用户删除、密码重置等用户相关业务。所有 SQL 使用 PDO 预处理。
 * ============================================================
 */

class User
{
    /**
     * 获取数据库单例
     */
    private static function db()
    {
        return Database::getInstance();
    }

    /**
     * 注册新用户
     * @param string $username 用户名
     * @param string $email 邮箱
     * @param string $password 明文密码
     * @return int 新用户 ID
     * @throws Exception 用户名/邮箱已存在或参数不合法
     */
    public static function register($username, $email, $password)
    {
        $username = trim((string)$username);
        $email = trim((string)$email);

        if (!Validator::username($username)) {
            throw new Exception('用户名长度为 3-20 位，只能包含字母、数字和下划线');
        }
        if (!Validator::email($email)) {
            throw new Exception('邮箱格式不正确');
        }
        if (!Validator::password($password)) {
            throw new Exception('密码长度至少 6 位');
        }

        $db = self::db();

        $exists = $db->fetch('SELECT id FROM users WHERE username = ? LIMIT 1', [$username]);
        if ($exists) {
            throw new Exception('用户名已存在');
        }

        $exists = $db->fetch('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
        if ($exists) {
            throw new Exception('邮箱已存在');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $db->execute(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)',
            [$username, $email, $passwordHash]
        );

        return (int)$db->lastInsertId();
    }

    /**
     * 登录：按用户名或邮箱查找用户并验证密码
     * 说明：未验证邮箱（status=0）的用户允许登录，可进入个人中心
     *       重新发送验证邮件；禁用账号（status=2）禁止登录。
     * @param string $identifier 用户名或邮箱
     * @param string $password 明文密码
     * @return array 用户数据数组
     * @throws Exception 账号不存在 / 密码错误 / 已禁用
     */
    public static function login($identifier, $password)
    {
        $identifier = trim((string)$identifier);
        if ($identifier === '' || (string)$password === '') {
            throw new Exception('请填写登录标识和密码');
        }

        $user = self::db()->fetch(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$identifier, $identifier]
        );

        if (!$user) {
            throw new Exception('账号不存在');
        }
        if (!password_verify((string)$password, $user['password_hash'])) {
            throw new Exception('密码错误');
        }
        if ((int)$user['status'] === 2) {
            throw new Exception('账号已被禁用，请联系管理员');
        }

        return $user;
    }

    /**
     * 根据邮箱查找用户
     */
    public static function findByEmail($email)
    {
        return self::db()->fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [trim((string)$email)]);
    }

    /**
     * 根据 ID 查找用户
     */
    public static function findById($id)
    {
        return self::db()->fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [(int)$id]);
    }

    /**
     * 邮箱验证：校验 token 是否匹配、是否过期，成功后激活账号
     * @param string $token 验证 token
     * @param string $email 用户邮箱
     * @return bool 验证成功返回 true
     * @throws Exception 链接无效或已过期
     */
    public static function verifyEmail($token, $email)
    {
        $token = trim((string)$token);
        $email = trim((string)$email);
        if ($token === '' || $email === '') {
            throw new Exception('链接无效或已过期');
        }

        $user = self::findByEmail($email);
        if (!$user || empty($user['email_token']) || !hash_equals($user['email_token'], $token)) {
            throw new Exception('链接无效或已过期');
        }
        if (empty($user['email_token_expires']) || strtotime($user['email_token_expires']) < time()) {
            throw new Exception('链接无效或已过期');
        }

        self::db()->execute(
            'UPDATE users SET email_token = NULL, email_token_expires = NULL, status = 1 WHERE id = ?',
            [(int)$user['id']]
        );

        return true;
    }

    /**
     * 为指定用户生成新的邮箱验证 Token（24 小时有效）
     * @param int $userId 用户 ID
     * @return string 新 Token
     */
    public static function createEmailToken($userId)
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 86400);

        self::db()->execute(
            'UPDATE users SET email_token = ?, email_token_expires = ? WHERE id = ?',
            [$token, $expires, (int)$userId]
        );

        return $token;
    }

    /**
     * 重新发送验证邮件前的准备工作
     * @param string $email 用户邮箱
     * @return string 新生成的 Token（供 Mailer 发送）
     * @throws Exception 账号不存在 / 已激活 / 已禁用
     */
    public static function resendVerification($email)
    {
        $user = self::findByEmail($email);
        if (!$user) {
            throw new Exception('账号不存在');
        }
        if ((int)$user['status'] === 1) {
            throw new Exception('该账号已完成邮箱验证，无需重新发送');
        }
        if ((int)$user['status'] === 2) {
            throw new Exception('账号已被禁用，无法发送验证邮件');
        }

        return self::createEmailToken((int)$user['id']);
    }

    /**
     * 更新最后登录时间和 IP
     */
    public static function updateLastLogin($userId, $ip)
    {
        self::db()->execute(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?',
            [(string)$ip, (int)$userId]
        );
    }

    /**
     * 判断指定用户是否为管理员
     * @param int $userId 用户 ID
     * @return bool
     */
    public static function isAdmin($userId)
    {
        $user = self::findById($userId);
        return $user !== false && $user['role'] === 'admin';
    }

    /**
     * 分页获取全部用户列表
     * @param int $page 页码（从 1 开始）
     * @param int $perPage 每页数量
     * @return array ['data' => 用户列表, 'total' => 总数]
     */
    public static function listAllUsers($page, $perPage)
    {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $db = self::db();

        $total = (int)$db->fetch('SELECT COUNT(*) AS total FROM users')['total'];
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare('SELECT * FROM users ORDER BY id DESC LIMIT :offset, :limit');
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'  => $stmt->fetchAll(),
            'total' => $total,
        ];
    }

    /**
     * 更新用户状态（1 正常 / 2 禁用）；$newStatus 为空时自动切换
     * @param int $userId 目标用户 ID
     * @param int|null $newStatus 新状态
     * @return int 更新后的状态
     * @throws Exception 用户不存在或参数不合法
     */
    public static function toggleStatus($userId, $newStatus = null)
    {
        $userId = (int)$userId;
        $user = self::findById($userId);
        if (!$user) {
            throw new Exception('用户不存在');
        }

        if ($newStatus === null) {
            $newStatus = ((int)$user['status'] === 1) ? 2 : 1;
        }
        $newStatus = (int)$newStatus;
        if (!in_array($newStatus, [1, 2], true)) {
            throw new Exception('状态参数不合法');
        }

        self::db()->execute('UPDATE users SET status = ? WHERE id = ?', [$newStatus, $userId]);

        return $newStatus;
    }

    /**
     * 物理删除用户（作业记录通过外键级联删除，图片文件一并清理）
     * @param int $userId 目标用户 ID
     * @throws Exception 用户不存在
     */
    public static function deleteUser($userId)
    {
        $userId = (int)$userId;
        $user = self::findById($userId);
        if (!$user) {
            throw new Exception('用户不存在');
        }

        // 先清理该用户全部作业的图片文件
        $homeworks = self::db()->fetchAll('SELECT image_path, thumb_path FROM homeworks WHERE user_id = ?', [$userId]);
        foreach ($homeworks as $homework) {
            Homework::deleteImageFiles($homework['image_path'], $homework['thumb_path']);
        }

        // 物理删除用户（homeworks 表通过外键 ON DELETE CASCADE 自动级联删除）
        self::db()->execute('DELETE FROM users WHERE id = ?', [$userId]);
    }

    /**
     * 检查 users 表是否已包含重置密码字段（兼容旧版本数据库）
     * @return bool
     */
    private static function hasResetColumns()
    {
        $rows = self::db()->fetchAll("SHOW COLUMNS FROM users LIKE 'reset\\_%'");
        $names = [];
        foreach ($rows as $row) {
            $names[] = $row['Field'] ?? '';
        }
        return in_array('reset_token', $names, true) && in_array('reset_token_expires', $names, true);
    }

    /**
     * 检查重置密码字段，缺失时抛出带升级 SQL 的异常
     * @throws Exception
     */
    private static function ensureResetColumns()
    {
        if (!self::hasResetColumns()) {
            throw new Exception(
                '数据库缺少重置密码字段，请先执行升级 SQL：' .
                'ALTER TABLE `users` ADD COLUMN `reset_token` VARCHAR(64) DEFAULT NULL ' .
                'AFTER `email_token_expires`, ADD COLUMN `reset_token_expires` DATETIME DEFAULT NULL AFTER `reset_token`;'
            );
        }
    }

    /**
     * 生成密码重置 Token（24 小时有效）
     * 账号不存在或已被禁用时返回 null（页面统一提示，不泄露账号状态）
     * @param string $email 用户邮箱
     * @return string|null 成功返回 Token，失败返回 null
     * @throws Exception 邮箱格式不正确 / 数据库缺少字段
     */
    public static function createResetToken($email)
    {
        $email = trim((string)$email);
        if (!Validator::email($email)) {
            throw new Exception('邮箱格式不正确');
        }
        self::ensureResetColumns();

        $user = self::findByEmail($email);
        if (!$user || (int)$user['status'] === 2) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 86400);

        self::db()->execute(
            'UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?',
            [$token, $expires, (int)$user['id']]
        );

        return $token;
    }

    /**
     * 校验密码重置 Token 是否有效（存在、匹配、未过期、账号未被禁用）
     * @param string $token 重置 token
     * @param string $email 用户邮箱
     * @return bool
     */
    public static function validateResetToken($token, $email)
    {
        $token = trim((string)$token);
        $email = trim((string)$email);
        if ($token === '' || $email === '') {
            return false;
        }

        $user = self::findByEmail($email);
        if (!$user || empty($user['reset_token']) || !hash_equals($user['reset_token'], $token)) {
            return false;
        }
        if (empty($user['reset_token_expires']) || strtotime($user['reset_token_expires']) < time()) {
            return false;
        }
        if ((int)$user['status'] === 2) {
            return false;
        }

        return true;
    }

    /**
     * 重置用户密码（校验 Token 与过期时间，成功后清空 Token）
     * @param string $token 重置 token
     * @param string $email 用户邮箱
     * @param string $newPassword 新明文密码
     * @return bool
     * @throws Exception 链接无效或已过期 / 新密码不合法
     */
    public static function resetPassword($token, $email, $newPassword)
    {
        $token = trim((string)$token);
        $email = trim((string)$email);

        if (!self::validateResetToken($token, $email)) {
            throw new Exception('重置链接无效或已过期，请重新申请');
        }
        if (!Validator::password($newPassword)) {
            throw new Exception('新密码长度至少 6 位');
        }

        $user = self::findByEmail($email);

        self::db()->execute(
            'UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?',
            [password_hash((string)$newPassword, PASSWORD_DEFAULT), (int)$user['id']]
        );

        return true;
    }
}