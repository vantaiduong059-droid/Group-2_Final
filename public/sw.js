// EduManager Student PWA - Service Worker
const CACHE_NAME = 'edumanager-student-v1';
const BASE = '/Group-2_Final_Student/public';

// Các tài nguyên cần cache để chạy offline
const STATIC_ASSETS = [
    BASE + '/student/schedule',
    BASE + '/student/dashboard',
    BASE + '/student/my-courses',
    BASE + '/assets/css/style.css',
    BASE + '/assets/js/main.js',
    BASE + '/assets/images/icon-512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
];

// ============================================
// INSTALL: Cache static assets
// ============================================
self.addEventListener('install', event => {
    console.log('[SW] Installing EduManager PWA...');
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('[SW] Caching static assets');
            // Cache từng item, bỏ qua nếu lỗi
            return Promise.allSettled(
                STATIC_ASSETS.map(url => cache.add(url).catch(e => console.warn('[SW] Cannot cache:', url)))
            );
        }).then(() => self.skipWaiting())
    );
});

// ============================================
// ACTIVATE: Xóa cache cũ
// ============================================
self.addEventListener('activate', event => {
    console.log('[SW] Activating...');
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => {
                    console.log('[SW] Deleting old cache:', k);
                    return caches.delete(k);
                })
            );
        }).then(() => self.clients.claim())
    );
});

// ============================================
// FETCH: Chiến lược Network-First
// - Thử lấy từ network trước (luôn mới nhất)
// - Nếu offline → fallback sang cache
// - API calls: không cache, trả lỗi offline nếu thất bại
// ============================================
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Bỏ qua các request không phải GET
    if (event.request.method !== 'GET') return;

    // API requests: Network only (không cache dữ liệu động)
    if (url.pathname.includes('/api/')) {
        event.respondWith(
            fetch(event.request).catch(() => {
                return new Response(
                    JSON.stringify({ status: 'error', message: 'Bạn đang offline. Vui lòng kiểm tra kết nối mạng.' }),
                    { headers: { 'Content-Type': 'application/json' } }
                );
            })
        );
        return;
    }

    // Static assets: Cache-first (CSS, JS, fonts)
    if (url.pathname.match(/\.(css|js|png|jpg|woff2?|ttf|svg)$/)) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                return cached || fetch(event.request).then(response => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    return response;
                });
            }).catch(() => new Response('Asset not available offline', { status: 503 }))
        );
        return;
    }

    // HTML pages: Network-first với offline fallback
    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Cache page mới nhất
                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                return response;
            })
            .catch(() => {
                return caches.match(event.request).then(cached => {
                    if (cached) return cached;
                    // Offline fallback page
                    return new Response(`
                        <!DOCTYPE html>
                        <html lang="vi">
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>EduManager - Offline</title>
                            <style>
                                body { font-family: 'Inter', sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; background:#f8fafc; }
                                .box { text-align:center; padding:40px 30px; }
                                .icon { font-size:4rem; margin-bottom:16px; }
                                h2 { color:#1e293b; font-size:1.5rem; margin-bottom:8px; }
                                p { color:#64748b; margin-bottom:24px; }
                                button { background:#3b82f6; color:#fff; border:none; padding:12px 28px; border-radius:24px; font-size:1rem; cursor:pointer; }
                                button:hover { background:#2563eb; }
                            </style>
                        </head>
                        <body>
                            <div class="box">
                                <div class="icon">📡</div>
                                <h2>Bạn đang offline</h2>
                                <p>Không thể kết nối đến EduManager.<br>Vui lòng kiểm tra kết nối Internet.</p>
                                <button onclick="window.location.reload()">🔄 Thử lại</button>
                            </div>
                        </body>
                        </html>
                    `, { headers: { 'Content-Type': 'text/html; charset=utf-8' }, status: 503 });
                });
            })
    );
});

// ============================================
// PUSH NOTIFICATIONS (nếu backend gửi push)
// ============================================
self.addEventListener('push', event => {
    if (!event.data) return;
    try {
        const data = event.data.json();
        const options = {
            body: data.body || 'Bạn có thông báo mới từ EduManager',
            icon: BASE + '/assets/images/icon-192.png',
            badge: BASE + '/assets/images/icon-192.png',
            vibrate: [200, 100, 200],
            data: { url: data.url || BASE + '/student/dashboard' },
            actions: [
                { action: 'open', title: 'Mở app' },
                { action: 'close', title: 'Bỏ qua' }
            ]
        };
        event.waitUntil(
            self.registration.showNotification(data.title || 'EduManager', options)
        );
    } catch(e) {
        console.warn('[SW] Push notification error:', e);
    }
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    if (event.action === 'close') return;
    const url = event.notification.data?.url || BASE + '/student/dashboard';
    event.waitUntil(clients.openWindow(url));
});
