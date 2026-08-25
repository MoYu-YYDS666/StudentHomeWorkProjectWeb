<?php
/**
 * ============================================================
 * 初高中作业大赏 - 邮件发送类（PHPMailer + SMTP）
 * 文件：classes/Mailer.php
 * 说明：使用 PHPMailer 通过 SMTP 发送邮箱验证邮件与密码重置邮件。
 * 依赖：需执行 composer require phpmailer/phpmailer。
 * 配置：SMTP_HOST / SMTP_PORT / SMTP_USERNAME / SMTP_PASSWORD /
 *       SMTP_ENCRYPTION / SMTP_FROM_EMAIL / SMTP_FROM_NAME 均在
 *       config/config.php 中配置。
 * ============================================================
 */

use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    /**
     * 发送邮箱验证邮件
     * @param string $email 收件人邮箱
     * @param string $token 验证 Token
     * @return bool 发送成功返回 true
     * @throws Exception 发送失败时抛出中文异常
     */
    public static function sendVerificationEmail($email, $token)
    {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            throw new Exception('邮件组件未安装，请先运行 composer install 安装 phpmailer/phpmailer');
        }

        // 验证链接（24 小时有效）
        $verifyUrl = BASE_URL . '/verify.php?token=' . urlencode($token) . '&email=' . urlencode($email);
        $siteName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : '初高中作业大赏';

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = '请验证您的邮箱 - ' . $siteName;

            $mail->Body = '
                <div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;padding:24px;background:#f4f5fb;border-radius:12px;">
                    <h2 style="color:#4f46e5;margin:0 0 16px;">' . $siteName . '</h2>
                    <p>您好，请点击下方按钮完成邮箱验证：</p>
                    <p style="text-align:center;margin:24px 0;">
                        <a href="' . $verifyUrl . '" style="display:inline-block;padding:12px 32px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;">验证邮箱</a>
                    </p>
                    <p>如果按钮无法点击，请复制以下链接到浏览器打开：</p>
                    <p style="word-break:break-all;color:#64748b;font-size:13px;">' . $verifyUrl . '</p>
                    <p style="color:#94a3b8;font-size:12px;margin-top:24px;">链接 24 小时内有效，请尽快完成验证。如果这不是您的操作，请忽略本邮件。</p>
                </div>';

            $mail->AltBody = '请打开以下链接完成邮箱验证（24 小时内有效）：' . $verifyUrl;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // 记录详细错误，页面只提示友好信息
            error_log('[Mailer] 发送失败: ' . $e->getMessage());
            throw new Exception('邮件发送失败，请稍后重试');
        }
    }

    /**
     * 发送密码重置邮件
     * @param string $email 收件人邮箱
     * @param string $token 重置 Token
     * @return bool 发送成功返回 true
     * @throws Exception 发送失败时抛出中文异常
     */
    public static function sendPasswordResetEmail($email, $token)
    {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            throw new Exception('邮件组件未安装，请先运行 composer install 安装 phpmailer/phpmailer');
        }

        // 重置链接（24 小时有效）
        $resetUrl = BASE_URL . '/reset_password.php?token=' . urlencode($token) . '&email=' . urlencode($email);
        $siteName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : '初高中作业大赏';

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = '重置您的密码 - ' . $siteName;

            $mail->Body = '
                <div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;padding:24px;background:#f4f5fb;border-radius:12px;">
                    <h2 style="color:#4f46e5;margin:0 0 16px;">' . $siteName . '</h2>
                    <p>您好，我们收到了您的密码重置请求，请点击下方按钮设置新密码：</p>
                    <p style="text-align:center;margin:24px 0;">
                        <a href="' . $resetUrl . '" style="display:inline-block;padding:12px 32px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;">重置密码</a>
                    </p>
                    <p>如果按钮无法点击，请复制以下链接到浏览器打开：</p>
                    <p style="word-break:break-all;color:#64748b;font-size:13px;">' . $resetUrl . '</p>
                    <p style="color:#94a3b8;font-size:12px;margin-top:24px;">链接 24 小时内有效。如果这不是您的操作，请忽略本邮件，您的密码不会被修改。</p>
                </div>';

            $mail->AltBody = '请打开以下链接重置密码（24 小时内有效）：' . $resetUrl;

            $mail->send();
            return true;
        } catch (Exception $e) {
            // 记录详细错误，页面只提示友好信息
            error_log('[Mailer] 密码重置邮件发送失败: ' . $e->getMessage());
            throw new Exception('邮件发送失败，请稍后重试');
        }
    }
}