# 🎬 ScénArt - Plateforme Collaborative pour Créateurs Narratifs

**TFE 2024-2025 - EAFC Namur-Cadets**  
*Développé par Thibault*

![Symfony](https://img.shields.io/badge/Symfony-8.0-000000?style=flat&logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat&logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-4169E1?style=flat&logo=postgresql)
![License](https://img.shields.io/badge/License-Academic-blue)

---

## 📖 Description

**ScénArt** est une plateforme web collaborative conçue pour les créateurs narratifs (scénaristes, game designers, auteurs). Elle permet de structurer, organiser et développer des projets créatifs (films, séries, jeux vidéo) de manière professionnelle.

### 🎯 Objectifs du projet

- Démontrer la maîtrise du framework **Symfony 8.0** et de l'architecture MVC
- Créer une application robuste avec gestion avancée de la sécurité
- Implémenter un système de collaboration temps réel
- Appliquer les bonnes pratiques de développement web moderne

---

## ✨ Fonctionnalités principales

### 🎭 Gestion de projets narratifs
- **Multi-types** : Films, Séries TV, Jeux Vidéo
- **Structures personnalisées** : Adaptées à chaque type de projet
- **Collaboration** : Système de membres avec permissions granulaires

### 👥 Ressources narratives
- **Personnages** : Biographie, relations, arcs narratifs
- **Lieux** : Description, atmosphère, importance narrative
- **Scénarios** : Éléments de scénario structurés
- **Notes & Tâches** : Organisation du travail créatif

### 🔐 Sécurité & Authentification
- **Authentification** complète avec gestion des rôles
- **Permissions** via système de Voters Symfony
- **CSRF Protection** sur tous les formulaires
- **Rate Limiting** sur endpoints publics (formulaire contact : 5 req/heure)
- **XSS Prevention** avec échappement automatique Twig

### 🌐 Interface utilisateur
- **Design System** : Style brutalist dark avec jaune (#F5C400)
- **Responsive** : Interface adaptative
- **Stimulus & Turbo** : Interactions fluides sans rechargement
- **Traductions** : Support multilingue (FR/EN/NL)

---

## 🛠️ Stack Technique

### Backend
- **Framework** : Symfony 8.0
- **Langage** : PHP 8.4
- **Base de données** : PostgreSQL 15+
- **ORM** : Doctrine 3.0+
- **Validation** : Symfony Validator (syntaxe PHP 8.4)

### Frontend
- **JavaScript** : Stimulus 3.2+ (Hotwired)
- **Navigation** : Turbo 8+
- **CSS** : Custom (Brutalist design)
- **Assets** : AssetMapper (sans build)
- **Templates** : Twig

### Environnement de développement
- **Serveur local** : Laragon (Windows)
- **Gestionnaire de dépendances** : Composer 2+
- **Contrôle de version** : Git / GitHub

---

## 📊 Architecture

### Structure de la base de données (22 entités)

**Utilisateurs & Projets :**
- `User`, `Project`, `ProjectMember`, `ProjectTypeConfig`, `FeatureDefinition`

**Ressources narratives :**
- `Character`, `CharacterRelation`, `Location`, `ScenarioElement`, `Note`, `Task`

**Système :**
- `Tag`, `Contact`, `ActivityLog`, `Notification`, `PublicComment`, `OwnerResponse`, `Report`

**Relations :**
- `ProjectFeature`, `EntityMention`, `ProjectMention`

### Principes architecturaux

- **MVC strict** : Séparation claire des responsabilités
- **Services** : Logique métier externalisée
- **Repositories** : Requêtes DB personnalisées
- **Voters** : Gestion fine des permissions
- **Form Types** : Validation centralisée

---

## 💻 Installation

### Prérequis

- PHP 8.4+
- PostgreSQL 15+
- Composer 2+
- Symfony CLI (optionnel mais recommandé)

### Installation locale

```bash
# 1. Cloner le projet
git clone https://github.com/TiboStage/tfe_namur_cadets_tibo.git
cd tfe_namur_cadets_tibo

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos credentials PostgreSQL

# 4. Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. (Optionnel) Charger les fixtures
php bin/console doctrine:fixtures:load

# 6. Lancer le serveur
symfony server:start
# OU
php -S localhost:8000 -t public/
```

### Configuration PostgreSQL

**Dans `.env.local` :**

```env
DATABASE_URL="postgresql://votre_user:votre_password@127.0.0.1:5432/storyforge?serverVersion=16&charset=utf8"
```

---

## 🎨 Design System

### Couleurs

```css
--color-primary: #F5C400;    /* Jaune signature */
--color-bg-dark: #0D0D0D;    /* Fond principal */
--color-bg-card: #111114;    /* Cartes/conteneurs */
--color-border: #222;        /* Bordures */
--text-main: #FFF;           /* Texte principal */
--text-muted: #888;          /* Texte secondaire */
```

### Typographie

- **Titres** : Bebas Neue (bold, uppercase)
- **Code/Mono** : Space Mono
- **Corps** : DM Sans

### Style

- **Aesthetic** : Brutalist Dark
- **Border radius** : 0px (sharp corners)
- **Bordures** : 1px solid
- **Grid gap** : 1px

---

## 🧪 Tests

```bash
# Tests unitaires
php bin/phpunit

# Tests fonctionnels
php bin/phpunit --group functional
```

---

## 📝 Conventions de code

### Formulaires
- ✅ **Toujours** Symfony Forms + `form_row()`
- ✅ Validation avec syntaxe PHP 8.4 (arguments nommés)
- ✅ CSRF activé sur tous les formulaires

### Controllers
- ✅ Légers (coordination uniquement)
- ✅ Logique métier déléguée aux Services
- ✅ Utilisation de `#[IsGranted]` pour permissions

### Sécurité
- ✅ Voters pour permissions complexes
- ✅ Rate Limiting sur endpoints publics
- ✅ XSS prevention (htmlspecialchars dans emails)

### Traductions
- ✅ Convention : `entity.field.constraint`
- ✅ Domaines : `validators`, `workshop_interface`, `messages`
- ✅ Support FR/EN/NL

---

## 🚀 Roadmap

### ✅ Phase 1 - Formulaire Contact (En cours)
- [x] ContactType avec validation Symfony 8
- [x] Rate Limiting (5 requêtes/heure)
- [x] Configuration rate_limiter.yaml
- [ ] Template avec form_row()
- [ ] CSS erreurs formulaire

### 📋 Phase 2 - Système de modales
- [ ] Composants modales réutilisables
- [ ] Stimulus controllers pour interactions
- [ ] Animations fluides

### 📋 Phases futures
- [ ] Système de notifications temps réel
- [ ] Export professionnel (PDF, formats métier)
- [ ] Collaboration temps réel (WebSockets)
- [ ] Système de templates de projets

---

## 📚 Documentation

- **Architecture** : Voir `docs/` (si créé)
- **Conventions de code** : Standards Symfony 8.0
- **API** : Documentation Swagger (à venir)

---

## 👨‍💻 Auteur

**Thibault**  
*Étudiant en Technicien Informatique*  
EAFC Namur-Cadets - Promotion 2024-2025

---

## 📄 Licence

Projet académique - TFE 2024-2025  
© 2025 Thibault - Tous droits réservés

---

## 🙏 Remerciements

- **EAFC Namur-Cadets** pour l'enseignement
- **Symfony Community** pour la documentation
- **Hotwire** (Stimulus/Turbo) pour les outils modernes

---

**Défense TFE prévue :** Fin juin 2026
