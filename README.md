# Application de Sondage (Fullstack Laravel + Vue.js)

Ce projet est une application de gestion et de participation à des sondages, réalisée dans le cadre du cours WebMobUi par **Nuno Amaro M53-2**. Elle permet aux utilisateurs connectés de créer des sondages personnalisés et aux utilisateurs (connectés ou non) d'y répondre.

## 🚀 Fonctionnalités implémentées

- **Tableau de bord (Dashboard)** : Visualisation de tous les sondages créés, avec accès rapide à l'édition, la suppression et le lien de partage.
- **Éditeur de sondage** : Interface dynamique pour gérer la question, les options (ajout/suppression illimité) et les paramètres :
    - Mode brouillon ou publié.
    - Choix simple ou multiple.
    - Visibilité des résultats (publics ou privés).
    - Durée de disponibilité (expiration automatique).
    - **Bonus** : Autoriser ou non la modification d'un vote après soumission.
- **Page de Vote (Viewer)** : 
    - Accessible via un token sécurisé.
    - Vote en temps réel avec mise à jour automatique des résultats (Polling toutes les 5 secondes).
    - Aperçu graphique des résultats via des barres de progression dynamiques.
    - Gestion intelligente des accès (doit être connecté pour voter).

## 🛠️ Choix Techniques & Architecture

### Frontend (Vue.js 3.4)
- **Architecture modulaire** : Découpage en pages (`DashboardPage`, `PollEditorPage`) et composants réutilisables.
- **Routage** : Gestion par Hash (`#dashboard`, `#edit`) pour une expérience SPA (Single Page Application) fluide à l'intérieur du dashboard Laravel.
- **Composables** : 
    - `useFetchApi` : Centralisation des appels API JSON avec gestion du chargement et des erreurs.
    - `usePolling` : Logique de mise à jour automatique des données.
- **Réactivité** : Utilisation intensive de `computed` pour la gestion des états complexes (ex: visibilité des résultats, calcul des pourcentages).

### Backend (Laravel 12.x)
- **API JSON Versionnée** (`/api/v1`) : Endpoints robustes pour le CRUD et le système de vote.
- **Sécurité** : Validation stricte des données entrantes et gestion des autorisations (Middleware `auth`).
- **Logique métier** : Calcul automatique des dates d'expiration et transactions SQL pour garantir l'intégrité des votes.

## 📦 Installation

1. **Cloner le projet** :
   ```bash
   git clone <url-du-repo>
   cd WebMobUi53-fullstack
   ```

2. **Installer les dépendances PHP** :
   ```bash
   composer install
   ```

3. **Installer les dépendances JS** :
   ```bash
   npm install
   ```

4. **Configurer l'environnement** :
   - Copier le fichier `.env.example` en `.env`.
   - Créer une base de données (SQLite par défaut ou MySQL).
   - Générer la clé d'application : `php artisan key:generate`.

5. **Lancer les migrations** :
   ```bash
   php artisan migrate
   ```

6. **Lancer le projet** :
   - Terminal 1 : `php artisan serve`
   - Terminal 2 : `npm run dev`

Accédez ensuite à `http://127.0.0.1:8000`.

---
*Réalisé par Nuno Amaro Faria (M53-2 COMEM) dans le cadre du cours WebMobUi53 - Mai 2026.*
