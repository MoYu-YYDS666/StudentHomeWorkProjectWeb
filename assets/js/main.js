/* ============================================================
   初高中作业大赏 - 全局前端脚本
   文件：assets/js/main.js
   说明：原生 Lightbox 图片预览（不依赖第三方库）、图片懒加载、
        IP 归属地查询（支持 IPv4 / IPv6）、操作确认、
        Flash 消息自动淡出。
   ============================================================ */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ---------- 图片懒加载（IntersectionObserver，原生 loading="lazy" 兜底） ---------- */
        var lazyImages = document.querySelectorAll('img.lazy[data-src]');
        if ('IntersectionObserver' in window && lazyImages.length > 0) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }
                    var img = entry.target;
                    img.src = img.getAttribute('data-src');
                    img.removeAttribute('data-src');
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                });
            }, { rootMargin: '200px' });
            lazyImages.forEach(function (img) {
                observer.observe(img);
            });
        } else {
            lazyImages.forEach(function (img) {
                img.src = img.getAttribute('data-src');
                img.removeAttribute('data-src');
            });
        }

        /* ---------- 原生 Lightbox 图片预览 ---------- */
        var lightbox = null;      // Lightbox DOM 容器
        var lightboxItems = [];   // 当前分组内的图片列表
        var lightboxIndex = 0;    // 当前显示索引

        function buildLightbox() {
            lightbox = document.createElement('div');
            lightbox.className = 'lightbox';
            lightbox.setAttribute('role', 'dialog');
            lightbox.setAttribute('aria-modal', 'true');
            lightbox.setAttribute('aria-label', '图片预览');
            lightbox.innerHTML = ''
                + '<div class="lightbox-backdrop"></div>'
                + '<div class="lightbox-stage">'
                + '  <div class="lightbox-header">'
                + '    <span class="lightbox-counter"></span>'
                + '    <button type="button" class="lightbox-close" aria-label="关闭">&times;</button>'
                + '  </div>'
                + '  <div class="lightbox-body">'
                + '    <button type="button" class="lightbox-nav lightbox-prev" aria-label="上一张">&lsaquo;</button>'
                + '    <div class="lightbox-image-wrap">'
                + '      <img class="lightbox-img" alt="作业大图">'
                + '      <div class="lightbox-loading"><span class="spinner-border text-light"></span></div>'
                + '      <div class="lightbox-error">图片加载失败，请稍后重试</div>'
                + '    </div>'
                + '    <button type="button" class="lightbox-nav lightbox-next" aria-label="下一张">&rsaquo;</button>'
                + '  </div>'
                + '  <div class="lightbox-caption"></div>'
                + '</div>';
            document.body.appendChild(lightbox);

            // 点击遮罩层关闭
            lightbox.querySelector('.lightbox-backdrop').addEventListener('click', closeLightbox);
            // 点击关闭按钮关闭
            lightbox.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
            // 上一张 / 下一张
            lightbox.querySelector('.lightbox-prev').addEventListener('click', function () {
                showLightbox(lightboxIndex - 1);
            });
            lightbox.querySelector('.lightbox-next').addEventListener('click', function () {
                showLightbox(lightboxIndex + 1);
            });
        }

        function openLightbox(items, index) {
            if (!lightbox) {
                buildLightbox();
            }
            lightboxItems = items;
            lightboxIndex = index;
            lightbox.classList.add('lightbox-active');
            document.body.classList.add('lightbox-open'); // 锁定页面滚动
            showLightbox(lightboxIndex);
        }

        function showLightbox(index) {
            if (!lightboxItems.length) {
                return;
            }
            // 首尾循环切换
            var total = lightboxItems.length;
            lightboxIndex = (index + total) % total;
            var item = lightboxItems[lightboxIndex];

            var img = lightbox.querySelector('.lightbox-img');
            var loading = lightbox.querySelector('.lightbox-loading');
            var error = lightbox.querySelector('.lightbox-error');

            // 重置加载状态
            img.classList.remove('lightbox-loaded');
            error.classList.remove('lightbox-visible');
            loading.classList.add('lightbox-visible');

            img.onload = function () {
                loading.classList.remove('lightbox-visible');
                img.classList.add('lightbox-loaded');
            };
            img.onerror = function () {
                loading.classList.remove('lightbox-visible');
                error.classList.add('lightbox-visible');
            };
            img.src = item.href;

            // 图片信息（PHP 端已做 HTML 转义，这里安全渲染）
            lightbox.querySelector('.lightbox-caption').innerHTML = item.caption || '';
            // 位置计数
            lightbox.querySelector('.lightbox-counter').textContent = (lightboxIndex + 1) + ' / ' + total;
            // 单张图片时隐藏切换按钮
            lightbox.querySelector('.lightbox-prev').style.display = total > 1 ? '' : 'none';
            lightbox.querySelector('.lightbox-next').style.display = total > 1 ? '' : 'none';
        }

        function closeLightbox() {
            if (!lightbox) {
                return;
            }
            lightbox.classList.remove('lightbox-active');
            document.body.classList.remove('lightbox-open');
        }

        // 键盘操作：Esc 关闭，左右方向键切换
        document.addEventListener('keydown', function (event) {
            if (!lightbox || !lightbox.classList.contains('lightbox-active')) {
                return;
            }
            if (event.key === 'Escape') {
                closeLightbox();
            } else if (event.key === 'ArrowLeft') {
                showLightbox(lightboxIndex - 1);
            } else if (event.key === 'ArrowRight') {
                showLightbox(lightboxIndex + 1);
            }
        });

        // 绑定所有 .js-lightbox 触发器，并按 data-lightbox-group 分组
        var lightboxLinks = document.querySelectorAll('.js-lightbox');
        if (lightboxLinks.length > 0) {
            var groups = {};
            lightboxLinks.forEach(function (link) {
                var group = link.getAttribute('data-lightbox-group') || 'default';
                if (!groups[group]) {
                    groups[group] = [];
                }
                groups[group].push({
                    href: link.getAttribute('href') || '',
                    caption: link.getAttribute('data-caption') || ''
                });
            });

            lightboxLinks.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    var group = link.getAttribute('data-lightbox-group') || 'default';
                    var items = groups[group] || [];
                    var href = link.getAttribute('href') || '';
                    var index = 0;
                    for (var i = 0; i < items.length; i++) {
                        if (items[i].href === href) {
                            index = i;
                            break;
                        }
                    }
                    openLightbox(items, index);
                });
            });
        }

        /* ---------- IP 归属地查询（支持 IPv4 / IPv6） ---------- */
        // 缓存：ip -> 已查到的位置文本（如（中国·江苏省·南京市）或（位置未知））
        var ipLocationCache = {};
        // 等待队列：ip -> 元素数组（同一 IP 多个元素只发一次请求）
        var ipLocationQueue = {};

        /**
         * 判断是否为私网 / 保留 / 本机地址（IPv4 与 IPv6）
         * 这类地址无法通过公网服务查询归属地，直接显示“本机/内网”
         */
        function isPrivateIp(ip) {
            ip = String(ip || '').trim().toLowerCase();
            if (ip === '') {
                return true;
            }
            // ---------- IPv4 ----------
            if (ip.indexOf(':') === -1) {
                var parts = ip.split('.');
                if (parts.length !== 4) {
                    return true;
                }
                var a = parseInt(parts[0], 10);
                var b = parseInt(parts[1], 10);
                if (isNaN(a) || isNaN(b)) {
                    return true;
                }
                // 0.0.0.0/8、10.0.0.0/8、127.0.0.0/8、169.254.0.0/16、172.16.0.0/12、
                // 192.168.0.0/16、100.64.0.0/10、198.18.0.0/15、224.0.0.0/4 等保留段
                if (a === 0 || a === 10 || a === 127) {
                    return true;
                }
                if (a === 100 && b >= 64 && b <= 127) {
                    return true;
                }
                if (a === 169 && b === 254) {
                    return true;
                }
                if (a === 172 && b >= 16 && b <= 31) {
                    return true;
                }
                if (a === 192 && b === 168) {
                    return true;
                }
                if (a === 198 && (b === 18 || b === 19)) {
                    return true;
                }
                if (a >= 224) {
                    return true;
                }
                return false;
            }
            // ---------- IPv6 ----------
            // 本机回环 ::1、未指定地址 ::
            if (ip === '::' || ip === '::1') {
                return true;
            }
            // IPv4 映射地址 ::ffff:x.x.x.x，转成 IPv4 再判断
            if (ip.indexOf('::ffff:') === 0) {
                return isPrivateIp(ip.substring(7));
            }
            // 链路本地 fe80::/10、唯一本地地址 fc00::/7
            if (ip.indexOf('fe80') === 0 || ip.indexOf('fc') === 0 || ip.indexOf('fd') === 0) {
                return true;
            }
            // 文档保留地址 2001:db8::/32
            if (ip.indexOf('2001:db8') === 0) {
                return true;
            }
            // 以 :: 开头（如 ::2 等保留地址）或第一个段为空
            var first = ip.split(':')[0];
            if (first === '' || first === '0') {
                return true;
            }
            return false;
        }

        /**
         * 通过 ip-api.com 免费接口查询 IP 归属地（免费版支持 IPv4 与 IPv6）
         * 查询结果写入元素文本：原IP（国家·省·市），失败显示（位置未知）
         */
        function queryIpLocation(el) {
            var ip = String(el.getAttribute('data-ip') || '').trim();
            if (ip === '') {
                return;
            }

            // 私网 / 本机地址：不发起网络请求
            if (isPrivateIp(ip)) {
                el.textContent = ip + '（本机/内网）';
                return;
            }

            // 已有缓存：直接显示
            if (ipLocationCache[ip]) {
                el.textContent = ip + ipLocationCache[ip];
                return;
            }

            // 同一 IP 已有一个请求在途：只排队，不重复请求
            if (ipLocationQueue[ip]) {
                ipLocationQueue[ip].push(el);
                return;
            }
            ipLocationQueue[ip] = [el];

            var controller = new AbortController();
            var timer = setTimeout(function () {
                controller.abort();
            }, 6000);

            fetch('https://ip-api.com/json/' + encodeURIComponent(ip) + '?lang=zh-CN&fields=status,country,regionName,city', {
                signal: controller.signal
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    clearTimeout(timer);
                    var location = '（位置未知）';
                    if (data && data.status === 'success') {
                        var parts = [data.country, data.regionName, data.city].filter(function (item) {
                            return item && String(item).trim() !== '';
                        });
                        if (parts.length > 0) {
                            location = '（' + parts.join('·') + '）';
                        }
                    }
                    ipLocationCache[ip] = location;
                    applyIpLocation(ip, location);
                })
                .catch(function () {
                    clearTimeout(timer);
                    ipLocationCache[ip] = '（位置未知）';
                    applyIpLocation(ip, '（位置未知）');
                });
        }

        /**
         * 将查询结果写入该 IP 对应的所有等待元素
         */
        function applyIpLocation(ip, location) {
            var elements = ipLocationQueue[ip] || [];
            elements.forEach(function (item) {
                item.textContent = ip + location;
            });
            delete ipLocationQueue[ip];
        }

        // 遍历页面中所有 .js-ip-loc 元素发起归属地查询
        document.querySelectorAll('.js-ip-loc[data-ip]').forEach(function (el) {
            queryIpLocation(el);
        });

        /* ---------- 删除 / 隐藏等操作确认 ---------- */
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var message = form.getAttribute('data-confirm') || '确定执行该操作吗？';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });

        /* ---------- Flash 消息 3 秒后自动淡出 ---------- */
        document.querySelectorAll('.flash-message').forEach(function (msg) {
            setTimeout(function () {
                msg.classList.add('fade');
                setTimeout(function () {
                    msg.remove();
                }, 500);
            }, 3000);
        });

    });
})();