<?php
/**
 * ============================================================
 * 初高中作业大赏 - 通用验证类
 * 文件：classes/Validator.php
 * 说明：提供邮箱、用户名、密码、图片、整数、长度等验证方法。
 *       image() 验证失败时抛出中文异常，其余方法返回 bool。
 * ============================================================
 */

class Validator
{
    /**
     * 验证邮箱格式
     */
    public static function email($email)
    {
        $email = trim((string)$email);
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 验证用户名：长度 3-20，仅允许字母、数字、下划线
     */
    public static function username($username)
    {
        $username = trim((string)$username);
        $length = strlen($username);
        return $length >= 3 && $length <= 20 && preg_match('/^[A-Za-z0-9_]+$/', $username) === 1;
    }

    /**
     * 验证密码：长度至少 6 位
     */
    public static function password($password)
    {
        return strlen((string)$password) >= 6;
    }

    /**
     * 验证上传图片：错误码、大小、MIME 类型、真实图片（getimagesize + finfo）
     * @param array $file $_FILES 中的文件数组
     * @param int $maxSize 最大字节数
     * @return bool 验证通过返回 true，失败抛出异常
     * @throws Exception
     */
    public static function image($file, $maxSize = 5242880)
    {
        if (!is_array($file)) {
            throw new Exception('请选择要上传的图片');
        }
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception('请选择要上传的图片');
        }
        if (is_array($file['error'])) {
            throw new Exception('文件上传参数错误');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                throw new Exception('文件大小超过限制（最大 5MB）');
            }
            throw new Exception('文件上传失败，请重试');
        }
        if ((int)$file['size'] > (int)$maxSize) {
            throw new Exception('文件大小超过限制（最大 5MB）');
        }
        if ((int)$file['size'] <= 0) {
            throw new Exception('文件内容为空');
        }

        // getimagesize 确认是真实图片
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new Exception('文件不是有效的图片');
        }

        // finfo 校验真实 MIME 类型
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $allowedMime = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mime, $allowedMime, true)) {
                throw new Exception('仅支持 JPG、PNG、GIF、WEBP 格式图片');
            }
        }

        return true;
    }

    /**
     * 验证非负整数范围
     */
    public static function integer($value, $min = 0, $max = 100000)
    {
        if (is_int($value)) {
            $num = $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', trim($value)) === 1) {
            $num = (int)$value;
        } else {
            return false;
        }
        return $num >= (int)$min && $num <= (int)$max;
    }

    /**
     * 验证字符串长度范围
     */
    public static function length($value, $min, $max)
    {
        $length = function_exists('mb_strlen') ? mb_strlen((string)$value) : strlen((string)$value);
        return $length >= (int)$min && $length <= (int)$max;
    }
}