# ScénArt - Plateforme Collaborative pour Créateurs Narratifs

![Symfony](https://img.shields.io/badge/Symfony-8.0-000000?style=flat&logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat&logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-4169E1?style=flat&logo=postgresql)

**Plateforme collaborative pour créateurs narratifs**

Projet de Travail de Fin d'Études (TFE) 2025-2026  
*Développé par Thibault*
EAFC Namur-Cadets - Technicien Informatique

---

## À propos

**ScénArt** est une application web permettant aux scénaristes, game designers et auteurs de structurer et développer leurs projets créatifs (films, séries, jeux vidéo) de manière collaborative.

### Fonctionnalités principales

- Gestion de projets narratifs multi-types
- Création et organisation de personnages, lieux et scénarios
- Système de collaboration avec permissions
- Interface moderne et responsive

---

## Stack Technique

- **Framework** : Symfony 8.0
- **Langage** : PHP 8.4
- **Base de données** : PostgreSQL 15+
- **Frontend** : Stimulus (Hotwired) + Turbo
- **Templates** : Twig

---

## Installation

### Prérequis

- PHP 8.4+
- PostgreSQL 15+
- Composer 2+

### Installation locale

```bash
# Cloner le projet
git clone https://github.com/TiboStage/tfe_namur_cadets_tibo.git
cd tfe_namur_cadets_tibo

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos credentials PostgreSQL

# Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Lancer le serveur
symfony server:start
```

### Configuration

**Dans `.env.local` :**

```env
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/storyforge?serverVersion=16&charset=utf8"
```

---

## Design

Interface moderne avec design brutalist dark  
Support multilingue (FR/EN/NL)

---

## Conventions de code

- Architecture MVC Symfony
- Validation côté serveur (Symfony Validator)
- Sécurité : CSRF, Voters, Rate Limiting
- Standards PSR-12

---

## Auteur

**Thibault**  
Étudiant - EAFC Namur-Cadets  
TFE 2024-2025

---

## Licence

Projet académique © 2025

---

**Défense TFE :** Juin 2026
