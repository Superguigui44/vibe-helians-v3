# Direction artistique — Hélians

## Direction choisie : "Éditorial juridique"

Un site qui emprunte les codes d'une revue juridique prestigieuse. Le contenu (expertises, références, articles) est mis en avant comme un magazine. La forme sert le fond — on démontre que ce cabinet *sait* de quoi il parle.

## Palette de couleurs

| Token | Hex | Usage |
|-------|-----|-------|
| `--white` | `#FFFFFF` | Fond principal |
| `--black` | `#1D1D1D` | Texte principal |
| `--violet` | `#6B4C8A` | Accent principal (repris du logo Hélians) |
| `--violet-light` | `#8B6DAA` | Hover, liens secondaires |
| `--violet-bg` | `#F5F0FA` | Fond accent léger (cards, sections alternées) |
| `--grey-100` | `#F7F7F7` | Fond sections alternées |
| `--grey-200` | `#E5E5E5` | Bordures, séparateurs |
| `--grey-500` | `#6B6B6B` | Texte secondaire |
| `--grey-800` | `#333333` | Sous-titres |

## Typographies (Google Fonts)

- **Display / Titres** : **Cormorant Garamond** (serif classique, registre juridique, élégant)
  - H1 : 700, 3.5rem
  - H2 : 600, 2.5rem
  - H3 : 600, 1.75rem
- **Body / Corps** : **Inter** (sans-serif, neutralité, lisibilité optimale)
  - Body : 400, 1.125rem, line-height 1.7
  - Nav : 500, 0.875rem, letter-spacing 0.05em, uppercase

## Layout & composition

- **Hero** : Typographique. Pas d'image hero — le texte *est* le hero. Grand H1 en Cormorant, sous-titre descriptif, CTA discret.
- **Asymétrie éditoriale** : Grille 8/4 ou 7/5 pour les pages d'expertise (contenu principal + sidebar avec références/liens)
- **Espace blanc généreux** : Margins et paddings larges. Le vide est un choix de design.
- **Cards d'expertise** : Fond blanc, fine bordure gauche violette, titre en Cormorant, texte court. Hover : bordure complète + léger lift.
- **Blog** : Grille magazine — article en vedette (grand) + grille 2 colonnes pour les suivants
- **Navigation** : Barre horizontale épurée. Logo à gauche, liens principaux centrés, CTA "Contact" à droite. Sticky au scroll avec fond blanc + ombre subtile.
- **Footer** : Sobre. Coordonnées, liens rapides, mentions légales. Fond gris très léger.

## Micro-interactions & animations

- **Apparition au scroll** : Fade-in + léger slide-up (IntersectionObserver), délai échelonné pour les grilles
- **Liens** : Soulignement animé (underline qui s'étend de gauche à droite au hover)
- **Cards** : translateY(-4px) + box-shadow au hover, transition 0.3s
- **Navigation scroll** : Background-color transition (transparent → blanc) + ajout ombre
- **Texte hero** : Animation d'apparition séquentielle (mot par mot ou ligne par ligne)
- **Priorité CSS-only** : Pas de librairies d'animation. CSS transitions + animations keyframes.

## Éléments distinctifs

- **Filet éditorial** : Fine ligne horizontale violette entre les sections, rappelant les séparateurs de revues
- **Numérotation des expertises** : Chiffre stylisé (grand, léger, Cormorant) devant chaque domaine
- **Citations** : Blocs en retrait avec bordure gauche épaisse violette, texte en italique Cormorant
- **"Nos références"** : Encarts discrets dans un fond violet très léger, typographie réduite

## Responsive

- Desktop (>1024px) : Layout éditorial complet, sidebar, grille magazine
- Tablette (768-1024px) : Passage en single-column, sidebar passe sous le contenu
- Mobile (<768px) : Navigation hamburger, hero condensé, cards empilées, typographie réduite proportionnellement

## Pages

| Page | URL | Description |
|------|-----|-------------|
| Accueil | `/` | Hero typo + 7 expertises cards + à propos condensé + CTA contact |
| Le Cabinet | `/cabinet.html` | Équipe (3 membres avec photos) + présentation + valeurs |
| Expropriation | `/expropriation.html` | Défense des expropriés + références |
| Préemption | `/preemption.html` | Expertise préemption + références |
| Immobilier | `/immobilier.html` | Droit immobilier |
| Construction publique | `/construction.html` | Marchés publics, contentieux |
| Hôpitaux & secteur social | `/hopitaux.html` | Secteur médico-social |
| Urbanisme | `/urbanisme.html` | PLU, permis, aménagement |
| Baux commerciaux | `/baux.html` | Baux commerciaux |
| Blog | `/blog.html` | Liste d'articles (géré via admin) |
| Contact | `/contact.html` | Formulaire + carte + coordonnées |
| Mentions légales | `/mentions.html` | Informations SELARL |
