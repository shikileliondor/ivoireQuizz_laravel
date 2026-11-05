# Coins Per Correct Answer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Créditer des pièces personnalisables par bonne réponse dans tous les modes.

**Architecture:** Ajouter le réglage au snapshot des règles, au panel admin et aux deux chemins de fin de partie. Le flux V1 cumule ce gain avec le bonus fixe du niveau en cas de réussite.

**Tech Stack:** Laravel 12, Blade, Eloquent, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-05-coins-per-correct-answer-design.md`

## Global Constraints

- Taux global de 0 à 1 000, défaut 1.
- Historisation via `RewardService`.
- Bonus de niveau/boss conservé.

## Review Focus

- Échec d’un niveau : pièces par réponse conservées, bonus fixe absent.
- Partie en cours : ancien snapshot conservé après modification admin.
- Routes legacy et V1 cohérentes.
- Zéro désactive le gain sans créer de transaction inutile.
- Réponse API et colonne admin reflètent le montant crédité.

---

### Task 1: Réglage admin et attribution globale

**Files:**
- Modify: `config/gameplay.php`
- Modify: `app/Http/Controllers/Admin/GameplaySettingController.php`
- Modify: `resources/views/admin/gameplay/edit.blade.php`
- Modify: `app/Http/Controllers/Api/GameSessionController.php`
- Modify: `app/Services/Game/FinishGameSessionService.php`
- Modify/Test: tests des réglages et récompenses

- [ ] Écrire les tests en échec pour le panel, la route legacy et le service V1.
- [ ] Ajouter le réglage et son champ admin.
- [ ] Créditer et historiser les pièces dans les deux chemins.
- [ ] Exécuter les tests ciblés puis toute la suite.

