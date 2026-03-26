# helians-v3 Architecture

**Multi-page Vite site** (Vanilla JS) — Hélians v3, cabinet d'avocats. 12 pages, contenu externalisé via `content.json` + admin panel PHP. Repo : `Superguigui44/vibe-helians-v3`. Deploy branch : `master`.

## Améliorations v3 vs v2

- **Design system** : échelle typo ratio 1.25 (`--text-xs` à `--text-5xl`), grille 8px (`--space-1` à `--space-12`)
- **Contraste WCAG AA** : `--text-light` #595959, `--text-muted` #757575
- **Accessibilité** : `:focus-visible`, `prefers-reduced-motion`, `:active` states
- **Hero** : grid avec stats aside (30+ ans, 7 expertises, Paris 1er), responsive horizontal mobile
- **Page Cabinet** : équipe au-dessus du fold, description scindée intro + détaillée
- **Blog** : placeholder image si image manquante/cassée
- **Animations** : fix stagger delays, mobile typo scale -25%

## Key files

- `index.html` + 11 pages HTML — `data-page` attribute, Google Fonts (Playfair Display + Source Sans 3), SEO meta, JSON-LD (LegalService + WebSite)
- `src/main.js` — SPA-like renderer : fetch `/content.php`, détecte page via `data-page`, `PAGE_RENDERERS`. `EXPERTISES` constant (7 pages expertises). `renderExpertise` gère variants de contenu (string/array/object) via helpers `toArray`/`toStringArray`/`toObjectArray`. Blog clés françaises (statut/publie). Honeypot anti-spam contact
- `src/style.css` — CSS custom properties design system complet (échelle typo + grille 8px)
- `content.json` — tout le contenu, clés françaises
- `public/content.php` — proxy PHP servant content.json depuis le répertoire parent
- `public/contact.php` — PHPMailer SMTP avec honeypot. PHPMailer dans `public/vendor/`
- `public/admin/` — admin panel PHP
- `admin-config.php` — schéma des champs admin (sections nav + blog activé)
- `admin-users.json` — credentials bcrypt (hors git)

## Design

Direction "Institution contemporaine" — navy `#1A2744` dominant, cream `#F8F6F2`, gold `#B8976A` accent. Playfair Display (display) + Source Sans 3 (body).

## Local dev

`npm run dev` + plugin Vite custom `serve-content-json`. Proxy `/contact.php` et `/admin` vers `localhost:8888`.
