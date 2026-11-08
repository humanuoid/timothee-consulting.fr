# Design

## System

- Admin : EasyAdminBundle comme socle CRUD (users, médias), pages **custom** Symfony UX pour l'éditeur de blocs et la preview live
- Site public : **multi-pages** (pas une one-page), design inspiré de emonin.dev, responsive mobile
- Styling : CSS propre dans `public/assets/site.css` (variables CSS pour les tokens)

## Tokens

- Couleurs, espacements, typographie : variables CSS en tête de `public/assets/site.css`

## Components

- Blocs de page réutilisables (hero, parcours, compétences, projets, contact, texte riche) : partials Twig + formulaires admin typés par type de bloc
- Éditeur admin : liste ordonnée de blocs + formulaire du bloc sélectionné + preview iframe (même Twig que le public)

## Accessibility

- Contraste WCAG AA, focus visible, ARIA sur les composants interactifs du dashboard
