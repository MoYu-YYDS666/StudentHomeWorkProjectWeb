<?php
/**
 * ============================================================
 * 初高中作业大赏 - PDO 数据库单例类
 * 文件：classes/Database.php
 * 说明：基于 PDO 的数据库封装，禁止使用 mysqli。
 *       所有 SQL 一律使用预处理语句。
 * ============================================================
 */

class Database
{
    /** @var Database|null 单例实例 */
    private static $instance = null;

    /** @var PDO */
    private $pdo;

    /**
     * 私有构造：创建 PDO 实例并初始化连接参数
     */
    private function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 5,
            ]);
            $this->pdo->exec('SET NAMES ' . DB_CHARSET);
        } catch (PDOException $e) {
            // 详细错误写入日志，页面只展示友好信息
            error_log('[Database] 连接失败: ' . $e->getMessage());
            if (defined('DEV_MODE') && DEV_MODE === true) {
                throw new RuntimeException('数据库连接失败：' . $e->getMessage());
            }
            throw new RuntimeException('数据库连接失败，请检查数据库配置。');
        }
    }

    /**
     * 获取数据库单例
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取原始 PDO 对象（供需要 bindValue 等高级操作的场景使用）
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * 预处理 SQL，返回 PDOStatement
     */
    public function prepare($sql)
    {
        try {
            return $this->pdo->prepare($sql);
        } catch (PDOException $e) {
            error_log('[Database] 预处理失败: ' . $e->getMessage());
            if (defined('DEV_MODE') && DEV_MODE === true) {
                throw new RuntimeException('SQL 预处理失败：' . $e->getMessage());
            }
            throw new RuntimeException('系统繁忙，请稍后重试。');
        }
    }

    /**
     * 执行预处理语句（支持增删改等），返回 PDOStatement
     */
    public function execute($sql, $params = [])
    {
        $stmt = $this->prepare($sql);
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('[Database] 执行失败: ' . $e->getMessage());
            if (defined('DEV_MODE') && DEV_MODE === true) {
                throw new RuntimeException('SQL 执行失败：' . $e->getMessage());
            }
            throw new RuntimeException('系统繁忙，请稍后重试。');
        }
        return $stmt;
    }

    /**
     * 查询单行关联数组
     */
    public function fetch($sql, $params = [])
    {
        return $this->execute($sql, $params)->fetch();
    }

    /**
     * 查询多行关联数组
     */
    public function fetchAll($sql, $params = [])
    {
        return $this->execute($sql, $params)->fetchAll();
    }

    /**
     * 返回最后插入的自增 ID
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}