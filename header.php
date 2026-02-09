<?php
if (!function_exists('render_page_start')) {
    function render_page_start(string $title = 'POLchatka'): void
    {
        $loggedIn = isset($_SESSION['user_id']);
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
        function persistThemeState(s){try{localStorage.setItem(THEME_STORAGE_KEY,JSON.stringify(s))}catch(_){}}
        function restoreThemeState(){try{const r=localStorage.getItem(THEME_STORAGE_KEY);return r?JSON.parse(r):null}catch(_){return null}}
        function applyThemeHref(href,el){const l=document.getElementById('theme-style');if(!l)return;l.onerror=()=>{l.href=DEFAULT_THEME;if(el)el.textContent='Błąd motywu — przywrócono domyślny.'};l.href=href||DEFAULT_THEME}
        function applyInlineCss(css){const e=document.getElementById('custom-inline-theme');if(e)e.remove();if(!css)return;const s=document.createElement('style');s.id='custom-inline-theme';s.textContent=css;document.head.appendChild(s)}
        document.addEventListener('DOMContentLoaded',()=>{
            const sel=document.getElementById('theme-selector'),urlIn=document.getElementById('custom-theme-url'),cssIn=document.getElementById('custom-theme-inline'),urlBtn=document.getElementById('apply-custom-theme'),cssBtn=document.getElementById('apply-inline-theme'),st=document.getElementById('theme-status');
            const saved=restoreThemeState(),init=saved||{mode:'preset',value:'default',customUrl:'',customCss:''};let cur={...init};
            const actPreset=v=>{applyInlineCss('');applyThemeHref(PRESET_THEMES[v]||DEFAULT_THEME,st);cur={mode:'preset',value:v,customUrl:'',customCss:''};persistThemeState(cur);if(st)st.textContent='Motyw: '+v};
            const actUrl=u=>{if(!u)return;applyInlineCss('');applyThemeHref(u,st);cur={mode:'custom-url',value:'custom-url',customUrl:u,customCss:''};persistThemeState(cur);if(st)st.textContent='CSS z URL'};
            const actInline=c=>{applyInlineCss(c);applyThemeHref(DEFAULT_THEME,st);cur={mode:'inline',value:'inline',customUrl:'',customCss:c};persistThemeState(cur);if(st)st.textContent='Własny CSS aktywny'};
            if(sel)sel.addEventListener('change',e=>actPreset(e.target.value));
            if(urlBtn)urlBtn.addEventListener('click',()=>actUrl(urlIn?.value.trim()));
            if(cssBtn)cssBtn.addEventListener('click',()=>actInline(cssIn?.value??''));
            sel&&(sel.value=init.mode==='preset'?init.value:'default');
            urlIn&&(urlIn.value=init.customUrl||'');cssIn&&(cssIn.value=init.customCss||'');
            if(init.mode==='custom-url'&&init.customUrl)actUrl(init.customUrl);
            else if(init.mode==='inline'&&init.customCss)actInline(init.customCss);
            else actPreset(init.value||'default');
        });
    </script>
</head>
<body>
<header class="site-header">
    <div class="site-brand"><a href="/php/wall.php">🇵🇱 POLchatka</a></div>
    <nav class="site-nav" aria-label="Główna nawigacja">
        <a href="/php/wall.php">📋 Ściana</a>
        <?php if ($loggedIn): ?>
            <a href="/php/profile.php">👤 Profil</a>
            <a href="/php/friends_page.php">👥 Znajomi</a>
            <a href="/php/messages.php">✉️ Wiadomości</a>
            <a href="/php/games.php">🎮 Gry</a>
            <a href="/php/logout.php" class="nav-logout">🚪 Wyloguj</a>
        <?php else: ?>
            <a href="/php/login_page.php">🔑 Zaloguj</a>
            <a href="/php/register_page.php">🚀 Rejestracja</a>
        <?php endif; ?>
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
                <button id="apply-custom-theme" class="button" type="button">Zastosuj</button>
            </div>
        </div>
        <div>
            <label for="custom-theme-inline">Własny CSS</label>
            <textarea id="custom-theme-inline" class="textarea" placeholder="body { background: #000; }"></textarea>
            <div class="theme-controls">
                <button id="apply-inline-theme" class="button" type="button">Wstrzyknij</button>
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
<footer class="site-footer">
    <p>POLchatka &copy; <?= date('Y') ?> — Czuj się jak u siebie. | <a href="https://github.com/mikusz3/POLchatka">GitHub</a></p>
</footer>
</body>
</html>
<?php
    }
}
