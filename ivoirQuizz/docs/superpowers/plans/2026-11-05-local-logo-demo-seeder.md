# Local Logo Demo Seeder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter un seeder local idempotent fournissant quatre logos de démonstration entièrement affichables.

**Architecture:** Des SVG fictifs versionnés sont copiés vers le disque public par un seeder local. Le seeder synchronise les questions et leurs quatre réponses sans toucher au seed de production.

**Tech Stack:** Laravel 12, Eloquent, Storage, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-05-local-logo-demo-seeder-design.md`

## Global Constraints

- Seeder utilisable uniquement en environnements `local` et `testing`.
- Aucune marque réelle ni dépendance réseau.
- Exécution idempotente.

## Review Focus

- Le disque public doit contenir chaque image après le seed.
- Chaque question doit avoir exactement quatre réponses et une seule bonne réponse.
- Une seconde exécution ne doit créer aucun doublon.
- La source de l’image ne doit jamais être vide.
- Le seeder doit refuser l’environnement `production`.

---

### Task 1: Seeder et ressources de démonstration

**Files:**
- Create: `database/seeders/Local/LogoDemoSeeder.php`
- Create: `database/seeders/Local/logos/*.svg`
- Create: `tests/Feature/Seeders/LogoDemoSeederTest.php`
- Modify: `docs/ADMIN.md`

**Interfaces:**
- Consumes: catégorie `logos`, disque Laravel `public`.
- Produces: `Database\Seeders\Local\LogoDemoSeeder::run(): void`.

- [ ] Écrire le test vérifiant images, questions, réponses, sources, idempotence et protection de l’environnement.
- [ ] Exécuter le test et constater l’échec faute de seeder.
- [ ] Ajouter les quatre SVG fictifs et le seeder minimal.
- [ ] Documenter la commande Artisan explicite.
- [ ] Exécuter le test ciblé puis la suite complète.

