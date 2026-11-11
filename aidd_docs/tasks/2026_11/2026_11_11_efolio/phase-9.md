---
status: pending
---

# Instruction: Contact asynchrone + pages légales

## Architecture projection

```txt
.
├── ✏️ config/packages/messenger.yaml
├── ✅ src/Presentation/Form/ContactType.php
├── ✅ src/Presentation/Controller/ContactController.php
├── ✅ src/Application/Messenger/ContactMessage.php
├── ✅ src/Application/Messenger/ContactMessageHandler.php
├── ✅ src/Presentation/Controller/LegalController.php
├── ✅ templates/site/_components/contact.html.twig
├── ✅ templates/site/legal/mentions.html.twig
├── ✅ templates/site/legal/privacy.html.twig
└── ✅ tests/Feature/Site/ContactTest.php
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | Page publiée, Messenger configuré, DevMailer lancé | Formulaire visible sur la one-page | browser |
| Happy path | POST valide puis `messenger:consume` | Ligne dans messenger_messages puis mail dans DevMailer ; réponse Turbo Stream immédiate | browser, cli |
| Edge case | Honeypot rempli ; consentement non coché ; 4e envoi/min | Aucun message en file, 200 neutre ; erreur validation ; 429 | browser, api |
| Teardown | Vider la file et DevMailer | Baseline | system |

## Tasks to do

### `1)` Formulaire de contact

> Champs spec §6 + consentement RGPD (obligation française).

1. `ContactType` : nom, email, téléphone, message, consentement obligatoire (case), honeypot, CSRF
2. POST → message Messenger (transport Doctrine async, table `messenger_messages`) → réponse Turbo Stream « message envoyé » immédiate
3. Rate limiter 3/min par IP

### `2)` Handler + échecs

> Rien ne se perd : retries puis failure transport.

1. `ContactMessageHandler` : mail au destinataire (variable d'env), Reply-To = email du visiteur
2. `--tries=3` + failure transport (`messenger:failed:show`) ; en local, consume manuel

### `3)` Design direction B

> Variante approuvée : serif doré sur anthracite ; le partial contact créé en phase 4 est complété ici.

1. Bloc contact : titre « Me contacter » en serif doré sur fond anthracite, champs blancs à bordure gris clair, bouton doré à texte blanc

### `4)` Pages légales

> Mentions légales + politique de confidentialité, bilingues.

1. Routes /mentions-legales et /politique-de-confidentialite (+ équivalents EN) : templates bilingues
2. Liens en footer de la one-page

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | POST valide → ligne dans messenger_messages ; consume → mail dans DevMailer ; réponse Turbo Stream immédiate |
| 2 | Honeypot rempli → rien en file, 200 neutre ; sans consentement → erreur ; 4e envoi/min → 429 |
| 3 | Mail reçu avec Reply-To du visiteur ; 3 échecs → message en failure transport, rien de perdu |
| 4 | Bloc contact conforme direction B ; pages légales accessibles FR et EN depuis le footer |
