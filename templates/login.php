<div class="login-container">
    <div class="card login-card">
        <h2 class="card-title text-center">🔐 管理者ログイン</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="?action=login" method="post" class="stack" id="login-form" data-login-error="<?= !empty($error) ? '1' : '0' ?>" data-logged-out="<?= isset($_GET['logged_out']) ? '1' : '0' ?>">
            <div class="form-group">
                <label for="username">ユーザー名</label>
                <input type="text" name="username" id="username" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">ログイン</button>
        </form>
    </div>
</div>
<script>
(() => {
    const STORAGE_KEY = 'carpair.credentials.v1';
    const AUTO_FLAG_KEY = 'carpair.autoLoginAttempted';
    const encoder = new TextEncoder();
    const decoder = new TextDecoder();

    const bufferToBase64 = (buffer) => {
        return btoa(String.fromCharCode(...new Uint8Array(buffer)));
    };

    const base64ToBuffer = (base64) => {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    };

    const deriveKey = async () => {
        const passphrase = 'carpair-auto-login-key';
        const salt = encoder.encode('carpair-auto-login-salt');
        const baseKey = await crypto.subtle.importKey(
            'raw',
            encoder.encode(passphrase),
            'PBKDF2',
            false,
            ['deriveKey']
        );
        return crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt,
                iterations: 100000,
                hash: 'SHA-256'
            },
            baseKey,
            {
                name: 'AES-GCM',
                length: 256
            },
            false,
            ['encrypt', 'decrypt']
        );
    };

    const encryptCredentials = async (username, password) => {
        const key = await deriveKey();
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const payload = encoder.encode(JSON.stringify({ u: username, p: password }));
        const encrypted = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, payload);
        return { iv: bufferToBase64(iv), payload: bufferToBase64(encrypted) };
    };

    const decryptCredentials = async (stored) => {
        const parsed = JSON.parse(stored);
        if (!parsed || !parsed.iv || !parsed.payload) {
            throw new Error('Invalid stored credentials');
        }
        const key = await deriveKey();
        const iv = base64ToBuffer(parsed.iv);
        const payload = base64ToBuffer(parsed.payload);
        const decrypted = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, key, payload);
        return JSON.parse(decoder.decode(decrypted));
    };

    const clearStored = () => {
        localStorage.removeItem(STORAGE_KEY);
        sessionStorage.removeItem(AUTO_FLAG_KEY);
    };

    const init = () => {
        const form = document.getElementById('login-form');
        if (!form || !window.crypto?.subtle) return;

        const hasError = form.dataset.loginError === '1';
        const loggedOut = form.dataset.loggedOut === '1';

        if (hasError || loggedOut) {
            clearStored();
        }

        form.addEventListener('submit', async () => {
            const username = form.username.value.trim();
            const password = form.password.value;
            if (!username || !password) return;
            try {
                const encrypted = await encryptCredentials(username, password);
                localStorage.setItem(STORAGE_KEY, JSON.stringify(encrypted));
                sessionStorage.removeItem(AUTO_FLAG_KEY);
            } catch (err) {
                console.warn('Failed to store credentials', err);
            }
        });

        if (!hasError && !loggedOut && !sessionStorage.getItem(AUTO_FLAG_KEY)) {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                decryptCredentials(stored).then((creds) => {
                    if (!creds?.u || !creds?.p) return;
                    form.username.value = creds.u;
                    form.password.value = creds.p;
                    sessionStorage.setItem(AUTO_FLAG_KEY, '1');
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }).catch((err) => {
                    console.warn('Auto login skipped', err);
                    clearStored();
                });
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
</script>
