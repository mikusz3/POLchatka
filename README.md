# POLchatka 🇵🇱

![POLchatka Logo](static/img/logo.png)

**Polski retro portal społecznościowy w stylu lat 2000**

---

## 📖 Czym jest POLchatka?

POLchatka to nostalgiczny projekt odtwarzający atmosferę polskich portali społecznościowych z lat 2000-2010. Inspirowany takimi serwisami jak:

- 🎓 **Poszkole.pl** - legendarny portal dla uczniów
- 👥 **NaszaKlasa (NK.pl)** - pierwszy polski Facebook
- 🌐 **Grono.net** - portal szkolny i społecznościowy
- 🎨 **SpaceHey** - współczesna reinkarnacja MySpace

Portal oferuje klasyczne funkcje społecznościowe w retro stylistyce, przywracając magię dawnego internetu.

## 🎯 Dlaczego POLchatka trafia na GitHub?

Udostępniam kod POLchatki na GitHub z kilku powodów:

- 🔓 **Open Source** - Projekt ma potencjał stać się w pełni otwartym
- 🤝 **Współpraca** - Zapraszam innych do rozwoju i ulepszania
- 📚 **Edukacja** - Kod może służyć jako przykład retro web designu
- 🔄 **Wersjonowanie** - Lepsze zarządzanie zmianami w projekcie
- 🌍 **Społeczność** - Budowanie wspólnoty wokół nostalgii za starym internetem

## 💝 Projekt hobbystyczny z pasji

POLchatka powstaje z czystej nostalgi i miłości do dawnych czasów internetu:

### Dlaczego to robię?

- 📱 **Tęsknota za prostotą** - Współczesne social media są przesycone reklamami i algorytmami
- 🎮 **Autentyczne doświadczenia** - Chcę odtworzyć uczucie pierwszego logowania na NK.pl
- 👨‍💻 **Nauka przez praktykę** - Doskonalenie umiejętności webdev przy okazji projektu z serca
- 🇵🇱 **Polska nostalgia** - Oddanie hołdu polskim pionierom social mediów

### Wspomnienia, które chcę odtworzyć:

- Personalizowane profile z własnym CSS
- Gry Flash osadzone w portalach
- Proste, ale skuteczne komunikatory
- Fotogalerie bez kompresji i filtrów
- Prawdziwe społeczności, nie influencerów

## 🛠️ Narzędzia, które pomagają w realizacji

### Technologie główne:
- **Frontend**: HTML5, CSS3, JavaScript, Zola (Static Site Generator)
- **Backend**: PHP 8+ z MySQL
- **Hosting**: Planowany na OVH/Cloudflare/Home.pl

### Narzędzia wspomagające:
- 🤖 **ChatGPT** - Pomoc w kodowaniu i brainstormingu
- 🧠 **Claude.ai** - Wsparcie w architekturze i optymalizacji
- 💻 **Visual Studio Code** - Główne IDE do rozwoju
- ⚡ **Zola** - Generator stron statycznych (getzola.org)
- 🎨 **Retro CSS Frameworks** - Dla autentycznego wyglądu lat 2000

## 🚀 Planowane aktualizacje

### Najbliższe cele (Q3-Q4 2025):

#### 🎨 Personalizacja profili
- [ ] Niestandardowe motywy CSS (emo, rap, różowo-dziewczęcy, retro NK/Poszkole)
- [ ] Edytor profilu z podglądem na żywo
- [ ] Komponenty profilu (horoskop, związki, slajdy zdjęć, quizy)
- [ ] Własne tła i awatary
- [ ] Embed HTML dla YouTube, Spotify, BitView

#### 🎮 System gier
- [ ] **Gry Flash z emulatorem Ruffle** - dla współczesnych przeglądarek
- [ ] **Natywne wsparcie Flash** - dla retro komputerów (Windows XP SP1+)
- [ ] Galeria gier HTML5 stworzona przez użytkowników
- [ ] System ocen i komentarzy do gier
- [ ] Integracja z klasycznymi grami z lat 2000

#### 💬 Komunikacja
- [ ] Komunikator POLczatka (inspirowany NKTalk)
- [ ] System prywatnych wiadomości
- [ ] Forum z klasycznym wyglądem
- [ ] Ściana jak w starych portalach

#### 📱 Kompatybilność
- [ ] **Pełne wsparcie Windows XP** - nostalgiczna autentyczność
- [ ] Responsywność dla współczesnych urządzeń
- [ ] Tryb "Antyśledzik" - bez reklam i śledzenia
- [ ] Wersja mobilna w stylu WAP

### Długoterminowe plany (2026+):
- API dla zewnętrznych aplikacji
- Plugin system dla deweloperów
- Migracja profili z archiwalnych portali
- Sklep z gadżetami retro (cupsell.pl integracja)

## 🤝 Specjalne podziękowania

Ogromne wyrazy wdzięczności dla osób, które wspierają projekt:

- 🎯 **!suek** z Discorda - za cenne porady techniczne i doświadczenie w tworzeniu nostalgicznych projektów (yt2009.suek.ovh)
- 💪 **chudy dredd** - za nieocenione wsparcie mentalne i motywację do kontynuowania pracy (kaptur.network)
- 🧠 **Społeczność AI** - ChatGPT i Claude.ai za pomoc w kodowaniu
- 📚 **Społeczność retro web** - za inspirację i zachowanie historii internetu

## 🌟 Jak możesz pomóc?

### Deweloperzy:
- 🐛 Zgłaszaj bugi przez Issues
- 💡 Proponuj nowe funkcje
- 🔧 Twórz Pull Requests
- 📖 Poprawiaj dokumentację

### Designerzy:
- 🎨 Twórz nowe motywy CSS w stylu lat 2000
- 🖼️ Projektuj ikony i grafiki
- 💫 Dodawaj retro animacje i efekty

### Testerzy:
- 🧪 Testuj na różnych przeglądarkach (szczególnie starych!)
- 📱 Sprawdzaj responsywność
- 🎮 Testuj gry Flash i HTML5

### Społeczność:
- ⭐ Zostaw gwiazdkę jeśli projekt Ci się podoba
- 🗣️ Opowiadaj o POLchatce w social mediach
- 📝 Dziel się wspomnieniami z dawnych portali
- 🤝 Przyłącz się do naszej retro społeczności

## 📂 Struktura projektu

```
polchatka/
├── content/              # Strony główne (Zola)
│   ├── index.html       # Strona główna
│   ├── login.html       # Logowanie
│   └── register.html    # Rejestracja
├── php/                 # Backend PHP
│   ├── config.php       # Konfiguracja bazy danych
│   ├── register.php     # Logika rejestracji
│   ├── login.php        # Logika logowania
│   └── profile.php      # System profili
├── templates/           # Szablony Zola
├── css/                 # Style retro
├── js/                  # JavaScript
├── img/                 # Grafiki i logo
└── themes/              # Motywy CSS
```

## 🔗 Przydatne linki

- 🌐 **Demo**: [polchatka.pl](https://polchatka.pl) *(w budowie)*
- 🎨 **Inspiracje**: [Poszkole.pl](http://poszkole.pl), [SpaceHey](https://spacehey.com)
- 🎮 **Retro gaming**: [Ruffle Flash Emulator](https://ruffle.rs)
- 📚 **Zola docs**: [getzola.org](https://getzola.org)

## 📄 Licencja

Projekt POLchatka jest udostępniany na licencji MIT - patrz plik [LICENSE](LICENSE) po szczegóły.

---

<div align="center">

**Zrobione z ❤️ dla polskiej nostalgii internetowej**

*Powrót do czasów, gdy internet był prosty, kolorowy i pełen możliwości*

[![GitHub stars](https://img.shields.io/github/stars/username/polchatka.svg?style=social&label=Star)](https://github.com/username/polchatka)
[![Discord](https://img.shields.io/discord/123456789?color=7289da&label=Discord&logo=discord&logoColor=white)](https://discord.gg/polchatka)

</div>

---

*Ostatnia aktualizacja: Sierpień 2025*
