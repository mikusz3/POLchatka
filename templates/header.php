<?php
if (!function_exists('render_page_start')) {
    function render_page_start(string $title = 'POLchatka'): void
    {
        ?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link id="theme-style" rel="stylesheet" href="/themes/default.css">
    <script>
        const THEME_STORAGE_KEY = 'polchatka-theme';
        const DEFAULT_THEME = '/themes/default.css';
        const PRESET_THEMES = {
            'default': DEFAULT_THEME,
            'retro-green': '/themes/retro-green.css',
            'retro-amber': '/themes/retro-amber.css'
        };

        function persistThemeState(state) {
            try { localStorage.setItem(THEME_STORAGE_KEY, JSON.stringify(state)); } catch (_) {}
        }

        function restoreThemeState() {
            try {
                const raw = localStorage.getItem(THEME_STORAGE_KEY);
                return raw ? JSON.parse(raw) : null;
            } catch (_) { return null; }
        }

        function applyThemeHref(href, statusEl) {
            const link = document.getElementById('theme-style');
            if (!link) return;

            link.onerror = () => {
                link.href = DEFAULT_THEME;
                if (statusEl) statusEl.textContent = 'Błąd ładowania motywu — przywrócono domyślny.';
            };
            link.href = href || DEFAULT_THEME;
        }

        function applyInlineCss(cssText) {
            const existing = document.getElementById('custom-inline-theme');
            if (existing) existing.remove();
            if (!cssText) return;
            const style = document.createElement('style');
            style.id = 'custom-inline-theme';
            style.textContent = cssText;
            document.head.appendChild(style);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const selector = document.getElementById('theme-selector');
            const customUrlInput = document.getElementById('custom-theme-url');
            const customCssInput = document.getElementById('custom-theme-inline');
            const applyUrlButton = document.getElementById('apply-custom-theme');
            const applyInlineButton = document.getElementById('apply-inline-theme');
            const statusEl = document.getElementById('theme-status');

            const saved = restoreThemeState();
            const initial = saved || { mode: 'preset', value: 'default', customUrl: '', customCss: '' };
            let currentState = { ...initial };

            const activatePreset = (value) => {
                const href = PRESET_THEMES[value] || DEFAULT_THEME;
                applyInlineCss('');
                applyThemeHref(href, statusEl);
                currentState = { mode: 'preset', value, customUrl: '', customCss: '' };
                persistThemeState(currentState);
                if (statusEl) statusEl.textContent = `Aktywny motyw: ${value}`;
            };

            const activateCustomUrl = (url) => {
                if (!url) return;
                applyInlineCss('');
                applyThemeHref(url, statusEl);
                currentState = { mode: 'custom-url', value: 'custom-url', customUrl: url, customCss: '' };
                persistThemeState(currentState);
                if (statusEl) statusEl.textContent = 'Załadowano motyw z własnego adresu URL.';
            };

            const activateInline = (cssText) => {
                applyInlineCss(cssText);
                applyThemeHref(PRESET_THEMES['default'], statusEl);
                currentState = { mode: 'inline', value: 'inline', customUrl: '', customCss: cssText };
                persistThemeState(currentState);
                if (statusEl) statusEl.textContent = 'Wstrzyknięto własny CSS (na bazie motywu domyślnego).';
            };

            if (selector) selector.addEventListener('change', (e) => activatePreset(e.target.value));
            if (applyUrlButton) applyUrlButton.addEventListener('click', () => activateCustomUrl(customUrlInput?.value.trim()));
            if (applyInlineButton) applyInlineButton.addEventListener('click', () => activateInline(customCssInput?.value ?? ''));

            selector && (selector.value = initial.mode === 'preset' ? initial.value : 'default');
            customUrlInput && (customUrlInput.value = initial.customUrl || '');
            customCssInput && (customCssInput.value = initial.customCss || '');

            if (initial.mode === 'custom-url' && initial.customUrl) {
                activateCustomUrl(initial.customUrl);
            } else if (initial.mode === 'inline' && initial.customCss) {
                activateInline(initial.customCss);
            } else {
                activatePreset(initial.value || 'default');
            }
        });
    </script>
</head>
<body>
<header class="site-header">
    <div class="site-brand"><a href="/php/wall.php">POLchatka</a></div>
    <nav class="site-nav" aria-label="Główna nawigacja">
        <a href="/php/wall.php">Ściana</a>
        <a href="/php/profile.php">Profil</a>
        <a href="/php/games.php">Gry</a>
    </nav>
    <div class="theme-panel">
        <div>
            <label for="theme-selector">Motyw</label>
            <div class="theme-controls">
                <select id="theme-selector" class="select">
                    <option value="default">Domyślny</option>
                    <option value="retro-green">Retro Green</option>
                    <option value="retro-amber">Retro Amber</option>
                </select>
            </div>
        </div>
        <div>
            <label for="custom-theme-url">Własny CSS (URL)</label>
            <div class="theme-controls">
                <input id="custom-theme-url" class="input" type="url" placeholder="https://example.com/theme.css">
                <button id="apply-custom-theme" class="button" type="button">Zastosuj URL</button>
            </div>
        </div>
        <div>
            <label for="custom-theme-inline">Własny CSS (wklej)</label>
            <textarea id="custom-theme-inline" class="textarea" placeholder="body { background: #000; }"></textarea>
            <div class="theme-controls">
                <button id="apply-inline-theme" class="button" type="button">Wstrzyknij CSS</button>
            </div>
        </div>
        <div class="theme-status" id="theme-status" aria-live="polite"></div>
    </div>
</header>
<main class="page-content">
<?php
    }

    function render_page_end(): void
    {
        ?>
</main>
</body>
</html>
<?php
    }
}
