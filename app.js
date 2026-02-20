// ============================================
// POLchatka - Główna logika aplikacji
// ============================================

const App = {
    user: null,
    csrfToken: null,
    currentPage: null,

    // ============================================
    // INICJALIZACJA
    // ============================================
    async init() {
        await this.checkAuth();
        this.renderNav();
        this.handleRoute();
        window.addEventListener('hashchange', () => this.handleRoute());
    },

    // ============================================
    // SPRAWDŹ AUTENTYKACJĘ
    // ============================================
    async checkAuth() {
        try {
            const data = await api('auth.php?action=me');
            if (data.logged_in) {
                this.user = data.user;
                this.csrfToken = data.csrf_token;
            }
        } catch (e) {}
    },

    // ============================================
    // NAWIGACJA
    // ============================================
    renderNav() {
        const nav = document.getElementById('navbar');
        const links = document.getElementById('nav-links');
        const userArea = document.getElementById('nav-user');

        if (this.user) {
            links.innerHTML = `
                <button class="nav-btn" onclick="App.goto('feed')">🏠 Główna</button>
                <button class="nav-btn" onclick="App.goto('friends')">👥 Znajomi</button>
                <button class="nav-btn" onclick="App.goto('messages')">💬 Wiadomości
                    ${this.user.unread_messages > 0 ? `<span class="badge">${this.user.unread_messages}</span>` : ''}
                </button>
                <button class="nav-btn" onclick="App.goto('search')">🔍 Szukaj</button>
            `;
            userArea.innerHTML = `
                <span id="nav-avatar">${this.user.avatar}</span>
                <span id="nav-username" onclick="App.gotoProfile('${this.user.username}')">${this.user.username}</span>
                <button class="nav-btn btn-sm" onclick="App.goto('settings')">⚙️</button>
                <button class="nav-btn btn-sm btn-danger" onclick="App.logout()">Wyloguj</button>
            `;
        } else {
            links.innerHTML = '';
            userArea.innerHTML = `
                <button class="nav-btn btn-primary" onclick="App.showModal('login')">🔐 Zaloguj się</button>
                <button class="nav-btn" onclick="App.showModal('register')">📝 Zarejestruj się</button>
            `;
        }
    },

    handleRoute() {
        const hash = window.location.hash.replace('#', '') || 'home';
        const [page, ...params] = hash.split('/');
        this.navigate(page, params);
    },

    navigate(page, params = []) {
        this.currentPage = page;
        const app = document.getElementById('app');

        // Zaznacz aktywny przycisk nawigacji
        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));

        switch (page) {
            case 'home':       this.renderHome(app); break;
            case 'feed':       this.renderFeed(app); break;
            case 'profile':    this.renderProfile(app, params[0]); break;
            case 'friends':    this.renderFriends(app); break;
            case 'messages':   this.renderMessages(app, params[0]); break;
            case 'search':     this.renderSearch(app); break;
            case 'settings':   this.renderSettings(app); break;
            default:           this.renderHome(app);
        }
    },

    goto(page, param = '') {
        window.location.hash = param ? `${page}/${param}` : page;
    },

    gotoProfile(username) {
        this.goto('profile', username);
    },

    // ============================================
    // STRONA GŁÓWNA (dla niezalogowanych)
    // ============================================
    renderHome(app) {
        if (this.user) { this.renderFeed(app); return; }

        app.innerHTML = `
            <div id="page-home">
                <div class="hero">
                    <h1><span>POL</span>chatka<span class="cursor"></span></h1>
                    <p>Polski retro portal społecznościowy. Czuj się jak u siebie.</p>
                    <div class="hero-btns">
                        <button class="btn btn-primary" onclick="App.showModal('login')">🔐 Zaloguj się</button>
                        <button class="btn btn-secondary" onclick="App.showModal('register')">📝 Utwórz konto</button>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                    ${['👥 Znajomi i społeczność', '💬 Prywatne wiadomości', '📝 Ściana profilu', '🔍 Wyszukaj znajomych'].map(f => `
                        <div class="panel text-center">
                            <div style="font-size:32px;margin-bottom:8px;">${f[0]}</div>
                            <div style="color:var(--text-dim);font-size:13px;">${f.slice(2)}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    },

    // ============================================
    // FEED (dla zalogowanych)
    // ============================================
    async renderFeed(app) {
        if (!this.user) { this.renderHome(app); return; }

        app.innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 280px;gap:16px;">
                <div>
                    <div class="panel">
                        <div class="panel-header">📝 Co słychać?</div>
                        <textarea id="feed-post-content" placeholder="Napisz coś na swojej ścianie..." rows="3"></textarea>
                        <div class="mt-8">
                            <button class="btn btn-primary" onclick="App.addWallPost('${this.user.username}')">Opublikuj</button>
                        </div>
                    </div>
                    <div id="feed-wall"></div>
                </div>
                <div>
                    <div class="panel">
                        <div class="panel-header">👤 Mój profil</div>
                        <div style="text-align:center;margin-bottom:10px;">
                            <div style="font-size:48px;">${this.user.avatar}</div>
                            <div style="color:var(--green);font-weight:bold;font-size:18px;cursor:pointer;" onclick="App.gotoProfile('${this.user.username}')">${this.user.username}</div>
                            <div style="color:var(--text-dim);font-size:12px;">${this.user.city || ''}</div>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:center;">
                            <div class="stat-box"><div class="num">${this.user.friends_count}</div><div class="lbl">Znajomych</div></div>
                        </div>
                    </div>
                    <div class="panel" id="pending-requests-panel"></div>
                </div>
            </div>
        `;

        await this.loadWall('feed-wall', this.user.username, '#feed-post-content');
        await this.loadPendingRequests('pending-requests-panel');
    },

    // ============================================
    // PROFIL UŻYTKOWNIKA
    // ============================================
    async renderProfile(app, username) {
        if (!username) {
            if (this.user) username = this.user.username;
            else { this.renderHome(app); return; }
        }

        app.innerHTML = `<div class="text-muted text-center" style="padding:30px;">Ładowanie profilu...</div>`;

        try {
            const data = await api(`profile.php?action=view&username=${encodeURIComponent(username)}`);
            if (!data.success) {
                app.innerHTML = `<div class="alert alert-error">${data.error}</div>`;
                return;
            }

            const u = data.user;
            const s = data.stats;
            const isOwn = data.is_own_profile;
            const fs = data.friendship_status;

            let friendBtn = '';
            if (!isOwn && this.user) {
                if (!fs) {
                    friendBtn = `<button class="btn btn-primary btn-sm" onclick="App.sendFriendRequest(${u.id})">➕ Dodaj do znajomych</button>`;
                } else if (fs === 'pending' && data.i_sent) {
                    friendBtn = `<button class="btn btn-secondary btn-sm" disabled>⏳ Zaproszenie wysłane</button>`;
                } else if (fs === 'pending' && !data.i_sent) {
                    friendBtn = `
                        <button class="btn btn-primary btn-sm" onclick="App.acceptRequest(${data.friendship_id})">✅ Akceptuj</button>
                        <button class="btn btn-danger btn-sm" onclick="App.rejectRequest(${data.friendship_id})">❌ Odrzuć</button>
                    `;
                } else if (fs === 'accepted') {
                    friendBtn = `
                        <button class="btn btn-secondary btn-sm" onclick="App.goto('messages','${u.username}')">💬 Napisz</button>
                        <button class="btn btn-danger btn-sm" onclick="App.removeFriend(${u.id})">🗑️ Usuń znajomego</button>
                    `;
                }
            }

            app.innerHTML = `
                <div class="profile-header">
                    <div class="profile-avatar">${u.avatar}</div>
                    <div>
                        <div class="profile-name">${u.username}</div>
                        <div class="profile-meta">
                            ${u.first_name ? `<span>${u.first_name} ${u.last_name || ''}</span>` : ''}
                            ${u.city ? `<span>📍 ${u.city}</span>` : ''}
                            ${u.birth_year ? `<span>🎂 ur. ${u.birth_year}</span>` : ''}
                            <span>👁️ ${u.profile_views || 0} wyświetleń</span>
                        </div>
                        ${u.bio ? `<div class="profile-bio">"${u.bio}"</div>` : ''}
                        <div class="mt-8" style="display:flex;gap:6px;flex-wrap:wrap;">
                            ${friendBtn}
                            ${isOwn ? `<button class="btn btn-secondary btn-sm" onclick="App.goto('settings')">✏️ Edytuj profil</button>` : ''}
                        </div>
                    </div>
                    <div class="profile-stats">
                        <div class="stat-box"><div class="num">${s.friends_count}</div><div class="lbl">Znajomych</div></div>
                        <div class="stat-box"><div class="num">${s.wall_posts_count}</div><div class="lbl">Posty</div></div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 220px;gap:16px;">
                    <div>
                        ${(this.user && (isOwn || fs === 'accepted')) ? `
                        <div class="panel">
                            <div class="panel-header">📝 Napisz na ścianie</div>
                            <textarea id="wall-post-content" placeholder="Napisz coś..." rows="3"></textarea>
                            <div class="mt-8">
                                <button class="btn btn-primary" onclick="App.addWallPost('${u.username}')">Opublikuj</button>
                            </div>
                        </div>
                        ` : ''}
                        <div class="panel">
                            <div class="panel-header">🧱 Ściana</div>
                            <div id="profile-wall"></div>
                        </div>
                    </div>
                    <div>
                        <div class="panel">
                            <div class="panel-header">👥 Znajomi (${s.friends_count})</div>
                            <div id="profile-friends-mini"></div>
                        </div>
                    </div>
                </div>
            `;

            await this.loadWall('profile-wall', u.username, '#wall-post-content');
            await this.loadFriendsMini('profile-friends-mini', u.username);

        } catch (e) {
            app.innerHTML = `<div class="alert alert-error">Błąd ładowania profilu</div>`;
        }
    },

    // ============================================
    // ZNAJOMI
    // ============================================
    async renderFriends(app) {
        if (!this.user) { this.showModal('login'); return; }

        app.innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 300px;gap:16px;">
                <div class="panel">
                    <div class="panel-header">👥 Moi znajomi</div>
                    <div id="friends-list">Ładowanie...</div>
                </div>
                <div>
                    <div class="panel" id="friend-requests-panel">
                        <div class="panel-header">📨 Zaproszenia</div>
                        <div id="requests-list">Ładowanie...</div>
                    </div>
                </div>
            </div>
        `;

        await this.loadFriendsList();
        await this.loadFriendRequests();
    },

    async loadFriendsList() {
        const el = document.getElementById('friends-list');
        if (!el) return;

        const data = await api('friends.php?action=list');
        if (!data.success || data.friends.length === 0) {
            el.innerHTML = `<div class="text-muted text-center" style="padding:20px;">Brak znajomych. <a href="#search" style="color:var(--green);">Szukaj użytkowników</a></div>`;
            return;
        }

        el.innerHTML = `<div class="friend-grid">${data.friends.map(f => `
            <div class="friend-card" onclick="App.gotoProfile('${f.username}')">
                <div class="avatar">${f.avatar}</div>
                <div class="name">${f.username}</div>
                ${f.first_name ? `<div style="font-size:11px;color:var(--text-dim);">${f.first_name} ${f.last_name||''}</div>` : ''}
                <div class="city">${f.city || ''}</div>
            </div>
        `).join('')}</div>`;
    },

    async loadFriendRequests() {
        const el = document.getElementById('requests-list');
        if (!el) return;

        const data = await api('friends.php?action=requests');
        if (!data.success) return;

        const received = data.received;
        if (received.length === 0) {
            el.innerHTML = `<div class="text-muted" style="padding:10px;font-size:12px;">Brak nowych zaproszeń</div>`;
            return;
        }

        el.innerHTML = received.map(r => `
            <div class="request-item">
                <div class="avatar">${r.avatar}</div>
                <div class="info">
                    <div class="name" onclick="App.gotoProfile('${r.username}')">${r.username}</div>
                    ${r.city ? `<div style="font-size:11px;color:var(--text-muted);">📍 ${r.city}</div>` : ''}
                </div>
                <div class="actions">
                    <button class="btn btn-primary btn-sm" onclick="App.acceptRequest(${r.id})">✅</button>
                    <button class="btn btn-danger btn-sm" onclick="App.rejectRequest(${r.id})">❌</button>
                </div>
            </div>
        `).join('');
    },

    async loadPendingRequests(elId) {
        const el = document.getElementById(elId);
        if (!el) return;

        const data = await api('friends.php?action=requests');
        if (!data.success || data.received.length === 0) {
            el.innerHTML = '';
            return;
        }

        el.innerHTML = `
            <div class="panel-header">📨 Zaproszenia (${data.received.length})</div>
            ${data.received.slice(0, 3).map(r => `
                <div class="request-item">
                    <div class="avatar">${r.avatar}</div>
                    <div class="info">
                        <div class="name" onclick="App.gotoProfile('${r.username}')">${r.username}</div>
                    </div>
                    <div class="actions">
                        <button class="btn btn-primary btn-sm" onclick="App.acceptRequest(${r.id})">✅</button>
                        <button class="btn btn-danger btn-sm" onclick="App.rejectRequest(${r.id})">❌</button>
                    </div>
                </div>
            `).join('')}
        `;
    },

    // ============================================
    // WIADOMOŚCI
    // ============================================
    async renderMessages(app, withUser) {
        if (!this.user) { this.showModal('login'); return; }

        app.innerHTML = `
            <div class="panel-header">💬 Wiadomości</div>
            <div class="messages-layout">
                <div class="conversations-list" id="conv-list">Ładowanie...</div>
                <div class="chat-window" id="chat-window">
                    <div class="text-muted text-center" style="padding:30px;flex:1;">
                        Wybierz rozmowę z listy
                    </div>
                </div>
            </div>
        `;

        await this.loadConversations(withUser);
    },

    async loadConversations(openWith) {
        const el = document.getElementById('conv-list');
        if (!el) return;

        const data = await api('messages.php?action=conversations');
        if (!data.success) return;

        if (data.conversations.length === 0) {
            el.innerHTML = `<div class="text-muted" style="padding:15px;font-size:12px;">Brak rozmów. Napisz do znajomego!</div>`;
        } else {
            el.innerHTML = data.conversations.map(c => `
                <div class="conv-item" id="conv-${c.id}" onclick="App.openChat(${c.id}, '${c.username}', '${c.avatar}', '${c.first_name || c.username}')">
                    <div class="conv-avatar">${c.avatar}</div>
                    <div class="conv-info">
                        <div class="conv-name">${c.first_name ? `${c.first_name} ${c.last_name||''}` : c.username}</div>
                        <div class="conv-preview">${c.last_sender_id == this.user.id ? 'Ty: ' : ''}${c.last_message.substring(0, 40)}${c.last_message.length > 40 ? '...' : ''}</div>
                    </div>
                    ${c.unread_count > 0 ? `<span class="conv-unread">${c.unread_count}</span>` : ''}
                </div>
            `).join('');
        }

        if (openWith) {
            const conv = data.conversations.find(c => c.username === openWith);
            if (conv) {
                this.openChat(conv.id, conv.username, conv.avatar, conv.first_name || conv.username);
            } else {
                // Nowa rozmowa
                this.openNewChat(openWith);
            }
        }
    },

    async openChat(userId, username, avatar, displayName) {
        document.querySelectorAll('.conv-item').forEach(e => e.classList.remove('active'));
        const item = document.getElementById(`conv-${userId}`);
        if (item) item.classList.add('active');

        this.currentChatUser = { id: userId, username, avatar, displayName };
        await this.loadChatThread(userId, username, avatar, displayName);
    },

    async openNewChat(username) {
        const data = await api(`profile.php?action=view&username=${encodeURIComponent(username)}`);
        if (!data.success) return;
        const u = data.user;
        this.currentChatUser = { id: u.id, username: u.username, avatar: u.avatar, displayName: u.first_name || u.username };
        await this.loadChatThread(u.id, u.username, u.avatar, u.first_name || u.username);
    },

    async loadChatThread(userId, username, avatar, displayName) {
        const win = document.getElementById('chat-window');
        if (!win) return;

        win.innerHTML = `
            <div class="chat-header">
                <span style="font-size:24px;">${avatar}</span>
                <span style="color:var(--green);font-weight:bold;cursor:pointer;" onclick="App.gotoProfile('${username}')">${displayName}</span>
            </div>
            <div class="chat-messages" id="chat-messages">Ładowanie...</div>
            <div class="chat-input-area">
                <textarea id="chat-input" placeholder="Napisz wiadomość..." onkeydown="App.chatKeydown(event)"></textarea>
                <button class="btn btn-primary" onclick="App.sendMessage()">▶</button>
            </div>
        `;

        const data = await api(`messages.php?action=thread&with=${userId}`);
        const msgs = document.getElementById('chat-messages');
        if (!msgs) return;

        if (!data.success || data.messages.length === 0) {
            msgs.innerHTML = `<div class="text-muted text-center">Brak wiadomości. Napisz pierwszą!</div>`;
        } else {
            msgs.innerHTML = data.messages.map(m => `
                <div class="msg-bubble ${m.sender_id == this.user.id ? 'me' : 'them'}">
                    ${m.content}
                    <div class="msg-time">${this.timeAgo(m.created_at)}</div>
                </div>
            `).join('');
            msgs.scrollTop = msgs.scrollHeight;
        }
    },

    chatKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            this.sendMessage();
        }
    },

    async sendMessage() {
        const input = document.getElementById('chat-input');
        const content = input?.value.trim();
        if (!content || !this.currentChatUser) return;

        const data = await api('messages.php?action=send', {
            receiver_id: this.currentChatUser.id,
            content,
            csrf_token: this.csrfToken
        });

        if (data.success) {
            input.value = '';
            const msgs = document.getElementById('chat-messages');
            if (msgs) {
                const bubble = document.createElement('div');
                bubble.className = 'msg-bubble me';
                bubble.innerHTML = `${content}<div class="msg-time">Teraz</div>`;
                msgs.appendChild(bubble);
                msgs.scrollTop = msgs.scrollHeight;
            }
        } else {
            showAlert(data.error, 'error');
        }
    },

    // ============================================
    // WYSZUKIWANIE UŻYTKOWNIKÓW
    // ============================================
    renderSearch(app) {
        if (!this.user) { this.showModal('login'); return; }

        app.innerHTML = `
            <div class="panel">
                <div class="panel-header">🔍 Szukaj użytkowników</div>
                <div style="display:flex;gap:8px;margin-bottom:16px;">
                    <input type="text" id="search-input" placeholder="Wpisz nazwę użytkownika, imię lub nazwisko..." 
                           oninput="App.searchUsers(this.value)">
                </div>
                <div id="search-results"></div>
            </div>
        `;
    },

    searchTimeout: null,
    async searchUsers(q) {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(async () => {
            const el = document.getElementById('search-results');
            if (!el) return;

            if (q.length < 2) {
                el.innerHTML = '<div class="text-muted">Wpisz co najmniej 2 znaki...</div>';
                return;
            }

            const data = await api(`friends.php?action=search&q=${encodeURIComponent(q)}`);
            if (!data.success) { el.innerHTML = `<div class="alert alert-error">${data.error}</div>`; return; }

            if (data.users.length === 0) {
                el.innerHTML = '<div class="text-muted">Brak wyników</div>';
                return;
            }

            el.innerHTML = data.users.map(u => {
                const fs = u.friendship_status;
                let btn = `<button class="btn btn-primary btn-sm" onclick="App.sendFriendRequest(${u.id}, this)">➕ Dodaj</button>`;
                if (fs === 'accepted') btn = `<button class="btn btn-secondary btn-sm" onclick="App.goto('messages','${u.username}')">💬 Napisz</button>`;
                else if (fs === 'pending') btn = `<button class="btn btn-secondary btn-sm" disabled>${u.friendship_sender == this.user.id ? '⏳ Wysłano' : '📨 Odpowiedz'}</button>`;

                return `
                    <div class="user-search-result">
                        <div class="avatar">${u.avatar}</div>
                        <div class="info">
                            <div class="username" onclick="App.gotoProfile('${u.username}')">${u.username}</div>
                            <div class="meta">${u.first_name ? `${u.first_name} ${u.last_name||''}` : ''} ${u.city ? `• 📍 ${u.city}` : ''}</div>
                        </div>
                        <div class="actions">${btn}</div>
                    </div>
                `;
            }).join('');
        }, 300);
    },

    // ============================================
    // USTAWIENIA
    // ============================================
    renderSettings(app) {
        if (!this.user) { this.showModal('login'); return; }
        const u = this.user;

        const AVATARS = ['👤', '😎', '🧑', '👩', '🧔', '👨‍💻', '🐱', '🐶', '🦊', '🐸', '🎮', '🎸', '🌟', '🔥', '💀'];

        app.innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <div class="panel">
                        <div class="panel-header">✏️ Edytuj profil</div>
                        <div id="settings-alert"></div>
                        <div class="form-group">
                            <label>Avatar</label>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                                ${AVATARS.map(a => `<span style="font-size:28px;cursor:pointer;padding:4px;border:2px solid ${a === u.avatar ? 'var(--green)' : 'transparent'};border-radius:4px;" onclick="App.selectAvatar('${a}',this)">${a}</span>`).join('')}
                            </div>
                            <input type="hidden" id="settings-avatar" value="${u.avatar}">
                        </div>
                        <div class="form-group">
                            <label>Imię</label>
                            <input id="settings-first-name" value="${u.first_name || ''}" placeholder="Twoje imię">
                        </div>
                        <div class="form-group">
                            <label>Nazwisko</label>
                            <input id="settings-last-name" value="${u.last_name || ''}" placeholder="Twoje nazwisko">
                        </div>
                        <div class="form-group">
                            <label>Miasto</label>
                            <input id="settings-city" value="${u.city || ''}" placeholder="Twoje miasto">
                        </div>
                        <div class="form-group">
                            <label>Rok urodzenia</label>
                            <input id="settings-birth-year" type="number" value="${u.birth_year || ''}" min="1900" max="${new Date().getFullYear()}">
                        </div>
                        <div class="form-group">
                            <label>Płeć</label>
                            <select id="settings-gender">
                                <option value="">Nie podano</option>
                                <option value="M" ${u.gender === 'M' ? 'selected' : ''}>Mężczyzna</option>
                                <option value="K" ${u.gender === 'K' ? 'selected' : ''}>Kobieta</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Bio (max 500 znaków)</label>
                            <textarea id="settings-bio" rows="3" maxlength="500">${u.bio || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="settings-public" ${u.profile_public ? 'checked' : ''}>
                                Profil publiczny
                            </label>
                        </div>
                        <button class="btn btn-primary btn-full" onclick="App.saveProfile()">💾 Zapisz zmiany</button>
                    </div>
                </div>
                <div>
                    <div class="panel">
                        <div class="panel-header">🔑 Zmień hasło</div>
                        <div id="pass-alert"></div>
                        <div class="form-group">
                            <label>Obecne hasło</label>
                            <input type="password" id="curr-pass" placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label>Nowe hasło</label>
                            <input type="password" id="new-pass" placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label>Powtórz nowe hasło</label>
                            <input type="password" id="new-pass2" placeholder="••••••••">
                        </div>
                        <button class="btn btn-warning btn-full" onclick="App.changePassword()">🔑 Zmień hasło</button>
                    </div>

                    <div class="panel" style="border-color:var(--red);">
                        <div class="panel-header" style="color:var(--red);">⚠️ Strefa niebezpieczna</div>
                        <p style="color:var(--text-dim);font-size:13px;margin-bottom:12px;">
                            Usunięcie konta jest nieodwracalne. Wszystkie Twoje dane zostaną usunięte.
                        </p>
                        <button class="btn btn-danger btn-full" onclick="App.showDeleteAccountModal()">🗑️ Usuń konto</button>
                    </div>
                </div>
            </div>
        `;
    },

    selectAvatar(emoji, el) {
        document.querySelectorAll('#settings-avatar-display span').forEach(s => s.style.borderColor = 'transparent');
        el.style.borderColor = 'var(--green)';
        document.getElementById('settings-avatar').value = emoji;
    },

    async saveProfile() {
        const body = new FormData();
        body.append('first_name', document.getElementById('settings-first-name').value);
        body.append('last_name', document.getElementById('settings-last-name').value);
        body.append('city', document.getElementById('settings-city').value);
        body.append('bio', document.getElementById('settings-bio').value);
        body.append('avatar', document.getElementById('settings-avatar').value);
        body.append('birth_year', document.getElementById('settings-birth-year').value);
        body.append('gender', document.getElementById('settings-gender').value);
        if (document.getElementById('settings-public').checked) body.append('profile_public', '1');
        body.append('csrf_token', this.csrfToken);

        const data = await apiForm('profile.php?action=update', body);
        const el = document.getElementById('settings-alert');
        if (data.success) {
            showAlertIn(el, 'Profil zaktualizowany!', 'success');
            await this.checkAuth();
            this.renderNav();
        } else {
            showAlertIn(el, data.error, 'error');
        }
    },

    async changePassword() {
        const curr = document.getElementById('curr-pass').value;
        const np = document.getElementById('new-pass').value;
        const np2 = document.getElementById('new-pass2').value;
        const el = document.getElementById('pass-alert');

        if (np !== np2) { showAlertIn(el, 'Hasła nie są identyczne', 'error'); return; }

        const data = await api('profile.php?action=change_password', {
            current_password: curr,
            new_password: np,
            csrf_token: this.csrfToken
        });

        if (data.success) {
            showAlertIn(el, 'Hasło zmienione!', 'success');
            document.getElementById('curr-pass').value = '';
            document.getElementById('new-pass').value = '';
            document.getElementById('new-pass2').value = '';
        } else {
            showAlertIn(el, data.error, 'error');
        }
    },

    showDeleteAccountModal() {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
            <div class="modal">
                <h2 style="color:var(--red);">⚠️ Usuń konto</h2>
                <p style="margin-bottom:12px;color:var(--text-dim);">Ta operacja jest nieodwracalna. Wpisz hasło, aby potwierdzić.</p>
                <div id="delete-alert"></div>
                <div class="form-group">
                    <label>Twoje hasło</label>
                    <input type="password" id="delete-pass" placeholder="••••••••">
                </div>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-danger btn-full" onclick="App.deleteAccount()">🗑️ Usuń na zawsze</button>
                    <button class="btn btn-secondary btn-full" onclick="this.closest('.modal-overlay').remove()">Anuluj</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
    },

    async deleteAccount() {
        const pass = document.getElementById('delete-pass').value;
        const el = document.getElementById('delete-alert');

        const data = await api('auth.php?action=delete_account', {
            password: pass,
            csrf_token: this.csrfToken
        });

        if (data.success) {
            this.user = null;
            document.querySelector('.modal-overlay')?.remove();
            this.renderNav();
            window.location.hash = '';
            this.renderHome(document.getElementById('app'));
            showAlert('Konto zostało usunięte', 'info');
        } else {
            showAlertIn(el, data.error, 'error');
        }
    },

    // ============================================
    // ŚCIANA - ładowanie postów
    // ============================================
    async loadWall(elId, username, inputSelector) {
        const el = document.getElementById(elId);
        if (!el) return;

        const data = await api(`profile.php?action=wall&username=${encodeURIComponent(username)}`);
        if (!data.success) { el.innerHTML = '<div class="text-muted">Brak postów</div>'; return; }

        if (data.posts.length === 0) {
            el.innerHTML = '<div class="text-muted text-center" style="padding:16px;">Brak postów na ścianie</div>';
            return;
        }

        el.innerHTML = data.posts.map(p => this.renderPost(p, username)).join('');
    },

    renderPost(p, wallOwner) {
        const canDelete = this.user && (this.user.username === p.author_username || this.user.username === wallOwner);
        return `
            <div class="wall-post" id="post-${p.id}">
                <div class="post-header">
                    <span class="post-author-avatar">${p.author_avatar}</span>
                    <a class="post-author-name" onclick="App.gotoProfile('${p.author_username}')">${p.author_username}</a>
                    <span class="post-time">${this.timeAgo(p.created_at)}</span>
                    ${canDelete ? `<button class="btn btn-danger btn-sm" onclick="App.deletePost(${p.id})">🗑️</button>` : ''}
                </div>
                <div class="post-content">${p.content}</div>
            </div>
        `;
    },

    async addWallPost(username) {
        const input = document.getElementById('wall-post-content') || document.getElementById('feed-post-content');
        const content = input?.value.trim();
        if (!content) return;

        const data = await api('profile.php?action=wall_post', {
            username,
            content,
            csrf_token: this.csrfToken
        });

        if (data.success) {
            input.value = '';
            // Odśwież ścianę
            const wallId = document.getElementById('profile-wall') ? 'profile-wall' : 'feed-wall';
            await this.loadWall(wallId, username, null);
        } else {
            showAlert(data.error, 'error');
        }
    },

    async deletePost(postId) {
        if (!confirm('Usunąć post?')) return;
        const data = await api('profile.php?action=delete_post', {
            post_id: postId,
            csrf_token: this.csrfToken
        });
        if (data.success) {
            document.getElementById(`post-${postId}`)?.remove();
        } else {
            showAlert(data.error, 'error');
        }
    },

    // ============================================
    // MINI-LISTA ZNAJOMYCH (dla profilu)
    // ============================================
    async loadFriendsMini(elId, username) {
        const el = document.getElementById(elId);
        if (!el) return;

        const data = await api(`friends.php?action=list&user_id=0&username=${encodeURIComponent(username)}`);
        // Alternatywnie pobierz po username - tu uproszczone
        if (!data.success || data.friends.length === 0) {
            el.innerHTML = '<div class="text-muted" style="font-size:12px;">Brak znajomych</div>';
            return;
        }

        el.innerHTML = `<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;">
            ${data.friends.slice(0, 9).map(f => `
                <div style="text-align:center;cursor:pointer;padding:4px;" onclick="App.gotoProfile('${f.username}')">
                    <div style="font-size:24px;">${f.avatar}</div>
                    <div style="font-size:10px;color:var(--text-dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${f.username}</div>
                </div>
            `).join('')}
        </div>`;
    },

    // ============================================
    // AKCJE ZNAJOMYCH
    // ============================================
    async sendFriendRequest(targetId, btn) {
        const data = await api('friends.php?action=send', {
            target_id: targetId,
            csrf_token: this.csrfToken
        });
        if (data.success) {
            showAlert('Zaproszenie wysłane!', 'success');
            if (btn) { btn.textContent = '⏳ Wysłano'; btn.disabled = true; }
        } else {
            showAlert(data.error, 'error');
        }
    },

    async acceptRequest(requestId) {
        const data = await api('friends.php?action=accept', {
            request_id: requestId,
            csrf_token: this.csrfToken
        });
        if (data.success) {
            showAlert('Zaproszenie zaakceptowane!', 'success');
            await this.checkAuth();
            this.renderNav();
            if (this.currentPage === 'friends') await this.loadFriendsList();
            await this.loadPendingRequests('pending-requests-panel');
        } else {
            showAlert(data.error, 'error');
        }
    },

    async rejectRequest(requestId) {
        const data = await api('friends.php?action=reject', {
            request_id: requestId,
            csrf_token: this.csrfToken
        });
        if (data.success) {
            showAlert('Zaproszenie odrzucone', 'info');
            if (this.currentPage === 'friends') await this.loadFriendRequests();
        }
    },

    async removeFriend(targetId) {
        if (!confirm('Usunąć ze znajomych?')) return;
        const data = await api('friends.php?action=remove', {
            target_id: targetId,
            csrf_token: this.csrfToken
        });
        if (data.success) {
            showAlert('Usunięto ze znajomych', 'info');
            location.reload();
        }
    },

    // ============================================
    // MODALNE - LOGOWANIE / REJESTRACJA
    // ============================================
    showModal(type) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.onclick = e => { if (e.target === overlay) overlay.remove(); };

        if (type === 'login') {
            overlay.innerHTML = `
                <div class="modal">
                    <h2>🔐 Logowanie</h2>
                    <div id="login-alert"></div>
                    <div class="form-group">
                        <label>Login lub email</label>
                        <input id="login-input" placeholder="Twój login lub email" onkeydown="if(event.key==='Enter')App.login()">
                    </div>
                    <div class="form-group">
                        <label>Hasło</label>
                        <input type="password" id="login-pass" placeholder="••••••••" onkeydown="if(event.key==='Enter')App.login()">
                    </div>
                    <button class="btn btn-primary btn-full" onclick="App.login()">Zaloguj się</button>
                    <div class="mt-12 text-center" style="color:var(--text-dim);font-size:12px;">
                        Nie masz konta? <a href="#" style="color:var(--green);" onclick="document.querySelector('.modal-overlay').remove();App.showModal('register')">Zarejestruj się</a>
                    </div>
                </div>
            `;
        } else {
            const AVATARS = ['👤', '😎', '🧑', '👩', '🧔', '👨‍💻', '🐱', '🐶', '🦊', '🐸'];
            overlay.innerHTML = `
                <div class="modal">
                    <h2>📝 Rejestracja</h2>
                    <div id="reg-alert"></div>
                    <div class="form-group">
                        <label>Avatar</label>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            ${AVATARS.map((a, i) => `<span style="font-size:24px;cursor:pointer;padding:3px;border:2px solid ${i===0?'var(--green)':'transparent'};border-radius:3px;" onclick="this.parentElement.querySelectorAll('span').forEach(s=>s.style.borderColor='transparent');this.style.borderColor='var(--green)';document.getElementById('reg-avatar').value='${a}'">${a}</span>`).join('')}
                        </div>
                        <input type="hidden" id="reg-avatar" value="👤">
                    </div>
                    <div class="form-group">
                        <label>Nazwa użytkownika *</label>
                        <input id="reg-username" placeholder="np. kowalski123">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input id="reg-email" type="email" placeholder="email@example.com">
                    </div>
                    <div class="form-group">
                        <label>Hasło *</label>
                        <input id="reg-pass" type="password" placeholder="Min. 6 znaków">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        <div class="form-group">
                            <label>Imię</label>
                            <input id="reg-fname" placeholder="Imię">
                        </div>
                        <div class="form-group">
                            <label>Nazwisko</label>
                            <input id="reg-lname" placeholder="Nazwisko">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Miasto</label>
                        <input id="reg-city" placeholder="Twoje miasto">
                    </div>
                    <button class="btn btn-primary btn-full" onclick="App.register()">Utwórz konto</button>
                    <div class="mt-12 text-center" style="color:var(--text-dim);font-size:12px;">
                        Masz już konto? <a href="#" style="color:var(--green);" onclick="document.querySelector('.modal-overlay').remove();App.showModal('login')">Zaloguj się</a>
                    </div>
                </div>
            `;
        }

        document.body.appendChild(overlay);
        setTimeout(() => overlay.querySelector('input')?.focus(), 50);
    },

    async login() {
        const login = document.getElementById('login-input').value;
        const pass = document.getElementById('login-pass').value;
        const el = document.getElementById('login-alert');

        const data = await api('auth.php?action=login', { login, password: pass });
        if (data.success) {
            document.querySelector('.modal-overlay')?.remove();
            await this.checkAuth();
            this.renderNav();
            this.goto('feed');
        } else {
            showAlertIn(el, data.error, 'error');
        }
    },

    async register() {
        const el = document.getElementById('reg-alert');
        const body = {
            username: document.getElementById('reg-username').value,
            email: document.getElementById('reg-email').value,
            password: document.getElementById('reg-pass').value,
            first_name: document.getElementById('reg-fname').value,
            last_name: document.getElementById('reg-lname').value,
            city: document.getElementById('reg-city').value,
            avatar: document.getElementById('reg-avatar').value
        };

        const data = await api('auth.php?action=register', body);
        if (data.success) {
            document.querySelector('.modal-overlay')?.remove();
            await this.checkAuth();
            this.renderNav();
            this.goto('feed');
        } else {
            showAlertIn(el, data.error, 'error');
        }
    },

    async logout() {
        await api('auth.php?action=logout');
        this.user = null;
        this.csrfToken = null;
        this.renderNav();
        window.location.hash = '';
        this.renderHome(document.getElementById('app'));
    },

    // ============================================
    // POMOCNICZE
    // ============================================
    timeAgo(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'Przed chwilą';
        if (diff < 3600) return `${Math.floor(diff/60)} min temu`;
        if (diff < 86400) return `${Math.floor(diff/3600)} godz. temu`;
        if (diff < 604800) return `${Math.floor(diff/86400)} dni temu`;
        return date.toLocaleDateString('pl-PL');
    }
};

// ============================================
// FUNKCJE POMOCNICZE
// ============================================
async function api(endpoint, postData = null) {
    const PHP_BASE = 'php/'; // zmień jeśli inna ścieżka
    const url = PHP_BASE + endpoint;

    const opts = { method: 'GET' };
    if (postData) {
        opts.method = 'POST';
        const form = new FormData();
        for (const [k, v] of Object.entries(postData)) form.append(k, v);
        opts.body = form;
    }

    try {
        const res = await fetch(url, opts);
        return await res.json();
    } catch (e) {
        console.error('API error:', e);
        return { success: false, error: 'Błąd połączenia' };
    }
}

async function apiForm(endpoint, formData) {
    const PHP_BASE = 'php/';
    const res = await fetch(PHP_BASE + endpoint, { method: 'POST', body: formData });
    return await res.json();
}

function showAlert(msg, type = 'info') {
    const el = document.createElement('div');
    el.className = `alert alert-${type}`;
    el.style.cssText = 'position:fixed;top:70px;right:16px;z-index:9999;min-width:250px;max-width:400px;';
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}

function showAlertIn(el, msg, type) {
    if (!el) return;
    el.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
}

// ============================================
// START
// ============================================
document.addEventListener('DOMContentLoaded', () => App.init());
