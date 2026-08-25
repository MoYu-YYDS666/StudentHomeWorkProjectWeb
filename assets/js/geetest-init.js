/* ============================================================
   初高中作业大赏 - Geetest4 前端初始化（内嵌按钮式）
   文件：assets/js/geetest-init.js
   说明：以「按钮式」模式初始化 Geetest4：页面内嵌一个极验
        原生按钮（nativeButton），点击按钮开始验证，验证通过
        后自动填充隐藏字段；表单提交时校验验证结果，未完成
        验证则阻止提交并提示。
   依赖：需在页面引入官方脚本 https://static.geetest.com/v4/gt4.js
   ============================================================ */
(function () {
    'use strict';

    /**
     * 在验证码容器中显示可见错误提示（方便排查配置问题）
     */
    function showCaptchaError(message) {
        var container = document.getElementById('captcha');
        if (container) {
            container.innerHTML = '<div class="alert alert-danger py-2 small mb-0">' + message + '</div>';
        }
        if (window.console && typeof console.error === 'function') {
            console.error('[Geetest] ' + message);
        }
    }

    /**
     * 填充验证结果隐藏字段（供表单提交时随请求发送）
     */
    function fillResult(result) {
        var fieldMap = {
            'geetest_lot_number': result.lot_number,
            'geetest_captcha_output': result.captcha_output,
            'geetest_pass_token': result.pass_token,
            'geetest_gen_time': result.gen_time
        };
        Object.keys(fieldMap).forEach(function (id) {
            var field = document.getElementById(id);
            if (field) {
                field.value = fieldMap[id];
            }
        });
    }

    function initGeetest() {
        //console.log('回调执行了！');
        var container = document.getElementById('captcha');
        if (!container) {
            return;
        }

        // 1. 未配置 ID：提示检查 config/config.php
        if (!window.GEETEST_ID) {
            showCaptchaError('滑动验证未启用：请检查 config.php 中的 GEETEST_ID 与 GEETEST_KEY 是否已填写并保存');
            return;
        }

        // 2. 官方脚本未加载：提示检查网络
        if (typeof window.initGeetest4 !== 'function') {
            showCaptchaError('验证码组件加载失败：请确认浏览器能访问 https://static.geetest.com/v4/gt4.js');
            return;
        }

        // 3. 初始化：页面内嵌极验原生按钮，点击按钮开始验证（弹出式）
        try {
            window.initGeetest4({
                captchaId: window.GEETEST_ID,
                product: 'popup',
                riskType: 'ai',
                language: 'zho',
                // 极验原生按钮样式（页面内嵌的“点击验证”按钮）
                nativeButton: {
                    width: 300,
                    height: 40,
                    background: '#f0f2f5',          // 亮灰色背景
                    color: '#333',                  // 深色文字
                    border: '1px solid #d9d9d9',   // 边框
                    borderRadius: '4px',           // 圆角
                    fontSize: '16px'           // 字体大小
                    
                }
            }, function (captchaObj) {
                window.__captchaObj = captchaObj;
                captchaObj.appendTo('#captcha');

                // 验证通过：填充隐藏字段，按钮由极验自动切换为“验证通过”状态
                captchaObj.onSuccess(function () {
                    var result = captchaObj.getValidate();
                    if (!result) {
                        return;
                    }
                    fillResult(result);
                });

                // 验证码加载/验证失败：显示可见提示
                captchaObj.onError(function () {
                    showCaptchaError('验证码加载失败：请检查极验后台的「Web 安全域名」是否包含当前站点域名（本地测试需添加 localhost），然后刷新页面重试');
                });
            });
        } catch (error) {
            showCaptchaError('验证码初始化异常：' + (error && error.message ? error.message : '请刷新页面重试'));
        }
    }

    // 表单提交时：未完成验证则阻止提交
    document.querySelectorAll('form.js-geetest-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var passToken = document.getElementById('geetest_pass_token');
            if (passToken && passToken.value === '') {
                event.preventDefault();
                window.alert('请先点击上方按钮完成滑动验证');
            }
        });
    });

    document.addEventListener('DOMContentLoaded', initGeetest);
})();