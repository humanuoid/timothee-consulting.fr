---
status: pending
---

# Instruction: Bibliothèque de composants + markdown

## Architecture projection

```txt
.
├── ✏️ composer.json
├── ✏️ importmap.php
├── ✅ src/Domain/Component/ComponentDefinition.php
├── ✅ src/Domain/Component/FieldSchema.php
├── ✅ src/Domain/Component/FieldCollection.php
├── ✅ src/Application/Component/ComponentRegistry.php
├── ✅ src/Application/Component/ComponentFormBuilder.php
├── ✅ src/Application/Component/MarkdownRenderer.php
├── ✅ src/Infrastructure/Component/HeaderDefinition.php
├── ✅ src/Infrastructure/Component/HeroDefinition.php
├── ✅ src/Infrastructure/Component/ParcoursDefinition.php
├── ✅ src/Infrastructure/Component/CompetencesDefinition.php
├── ✅ src/Infrastructure/Component/ProjetsDefinition.php
├── ✅ src/Infrastructure/Component/ContactDefinition.php
├── ✅ templates/site/_components/header.html.twig
├── ✅ templates/site/_components/hero.html.twig
├── ✅ templates/site/_components/parcours.html.twig
├── ✅ templates/site/_components/competences.html.twig
├── ✅ templates/site/_components/projets.html.twig
├── ✅ templates/site/_components/contact.html.twig
└── ✅ tests/Unit/Component/
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | Phase 2 OK, commonmark installé | Registry vide prête | cli |
| Happy path | Rendre chaque composant avec des données d'exemple | HTML complet, markdown rendu proprement | cli |
| Edge case | Payloads XSS dans un champ markdown (`<script>`, `onerror=`, `javascript:`) | Neutralisés au rendu | cli |
| Teardown | — | — | — |

## Tasks to do

### `1)` Contrat composant + types de champs

> Option B : les composants sont des artefacts de code, typés et testables.

1. `ComponentDefinition` : key, label, fields() ; autoconfiguration par attribut `#[Autoconfigure]` (tag de service), zéro YAML
2. `FieldSchema` types V1 : text, markdown, media, link, collection (sous-champs répétables), select (couleur pastille : rouge, vert, magenta, bleu, noir)

### `2)` ComponentFormBuilder

> Le formulaire admin se déduit du schéma, jamais écrit à la main.

1. Construit un FormType Symfony depuis le FieldSchema
2. DataTransformer media-id ; CollectionType pour les collections

### `3)` MarkdownRenderer

> Jamais de HTML en base ; HTML brut échappé.

1. league/commonmark, `html_input: escaped`
2. Rendu serveur uniquement

### `4)` Les 6 composants (spec §3)

> Champs calés sur les textes de référence de la spec.

1. `header` : cv_pdf (media), dc_pdf (media)
2. `hero` : salutation, nom, titre, texte (markdown), photo (media), citation, auteur
3. `parcours` : texte (markdown) + timeline collection {poste, entreprise, logo (media optionnel), couleur (select)}
4. `competences` : metiers, savoirFaire, hashtags (collection text), garant, preuves, formations (collection {intitule, logo (media)})
5. `projets` : cartes collection {titre, besoin, objectif, contraintes, livrables, solution, resultats, envTechnique, maquette (media), projetsLies (collection text)}
6. `contact` : email, telephone, linkedin (link)

### `5)` Spike éditeur markdown visuel

> Critère durci : 100 % servi en local via importmap.

1. Tester Milkdown et Tiptap (sérialisation markdown) : installation locale via `importmap:require` (modifie `importmap.php`), saisie, rendu
2. Retenir un éditeur et noter la décision dans le dossier de plan

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | La registry résout les 6 clés ; clé inconnue → exception explicite (test unitaire) |
| 2 | Le builder mappe chaque type ; un champ media persiste un id, pas un path |
| 3 | Payloads `<script>`, `onerror=`, `javascript:` neutralisés au rendu (tests unitaires) |
| 4 | Chaque composant rend ses données d'exemple sans erreur |
| 5 | L'éditeur retenu s'installe via importmap et fonctionne servi en local |
