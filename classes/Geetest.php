<?php
/**
 * ============================================================
 * 初高中作业大赏 - Geetest4 滑动验证封装类
 * 文件：classes/Geetest.php
 * 说明：封装 Geetest4 服务端验证（参照官方 v4 集成规范）。
 *       前端直接使用 captchaId 初始化（无需服务端预请求），
 *       二次校验使用 HMAC-SHA256 生成 sign_token 并调用
 *       极验 validate 接口；极验服务异常时放行（fail-open），
 *       避免极验故障阻塞正常业务，验证失败则一律拦截。
 * 配置：请在 config/config.php 中填写 GEETEST_ID 与 GEETEST_KEY。
 * 安全：验证码必须服务端二次校验，不能只依赖前端结果。
 * ============================================================
 */

class Geetest
{
    /** @var string 验证码 ID */
    private $captchaId;

    /** @var string 验证码 Key */
    private $captchaKey;

    /** @var string 极验服务地址 */
    private $apiServer = 'https://gcaptcha4.geetest.com';

    /**
     * 构造：从配置常量读取 ID 与 Key
     */
    public function __construct()
    {
        $this->captchaId = defined('GEETEST_ID') ? (string)GEETEST_ID : '';
        $this->captchaKey = defined('GEETEST_KEY') ? (string)GEETEST_KEY : '';
    }

    /**
     * 获取前端初始化参数（GeeTest v4 只需 captchaId，无需预请求 challenge）
     * @return array
     */
    public function getVerifyParams()
    {
        return ['captcha_id' => $this->captchaId];
    }

    /**
     * 服务端二次校验验证结果
     * @param string $lotNumber 验证流水号
     * @param string $captchaOutput 验证输出
     * @param string $passToken 通过凭证
     * @param string $genTime 生成时间
     * @return bool 校验通过返回 true
     */
    public function verify($lotNumber, $captchaOutput, $passToken, $genTime)
    {
        $lotNumber = (string)$lotNumber;
        $captchaOutput = (string)$captchaOutput;
        $passToken = (string)$passToken;
        $genTime = (string)$genTime;

        // 未配置或参数缺失：一律视为验证失败
        if ($this->captchaId === '' || $this->captchaKey === '') {
            return false;
        }
        if ($lotNumber === '' || $captchaOutput === '' || $passToken === '' || $genTime === '') {
            return false;
        }

        // 生成签名：sign_token = HMAC-SHA256(lot_number, captcha_key)
        $signToken = hash_hmac('sha256', $lotNumber, $this->captchaKey);

        // 调用极验 validate 接口进行二次校验
        $url = $this->apiServer . '/validate?captcha_id=' . urlencode($this->captchaId);
        $data = $this->httpPost($url, [
            'lot_number'     => $lotNumber,
            'captcha_output' => $captchaOutput,
            'pass_token'     => $passToken,
            'gen_time'       => $genTime,
            'sign_token'     => $signToken,
        ]);

        // 极验服务异常（网络/解析失败）：放行，避免阻塞正常业务
        if ($data === null) {
            return true;
        }

        // 正常响应：仅 result=success 视为通过
        return is_array($data) && isset($data['result']) && $data['result'] === 'success';
    }

    /**
     * HTTP POST 请求（优先 curl，降级文件流方式）
     * @param string $url 请求地址
     * @param array $params 表单参数
     * @return array|null 解析成功返回数组，网络或解析异常返回 null
     */
    private function httpPost($url, $params)
    {
        $body = http_build_query($params);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            if ($errno !== 0 || $response === false) {
                return null;
            }
            $data = json_decode((string)$response, true);
            return is_array($data) ? $data : null;
        }

        // 无 curl 扩展时使用 HTTP 流上下文
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $body,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }
        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }
}