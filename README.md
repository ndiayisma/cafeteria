# Cafétéria Campus — Front de commande (Symfony)

Application web permettant aux étudiants de commander leur repas le matin et de le récupérer à midi. La responsable consulte les statistiques de ventes.

Ce dépôt contient **uniquement le front** (Symfony). Toutes les données passent par l'API développée par l'autre groupe (Django). Il n'y a ni Doctrine ni migrations ici : `Plat`, `Commande` et `Etudiant` sont de simples classes PHP qui reçoivent le JSON de l'API.

## Équipe

| Équipe | Membres |
|---|---|
| Front (Symfony) | Valentin, Léopold, Paco, Ismaïl |
| Back (Django / API) | Nathan, Leito, Edwyn |

## Architecture

```
   ┌─────────────────────────┐
   │  Étudiant / Responsable │
   │       (navigateur)      │
   └────────────┬────────────┘
                │  pages web (HTTP)
                ▼
   ┌─────────────────────────┐
   │   FRONT : Symfony       │
   │   - pages Twig          │
   │   - ApiClient           │
   │   - panier en session   │
   │   - JWT en session      │
   │   - graphiques Chart.js │
   └────────────┬────────────┘
                │  requêtes JSON + JWT (/v1/...)
                ▼
   ┌─────────────────────────┐
   │   BACK : API Django     │
   │   - API REST            │
   │   - stats (pandas)      │
   └────────────┬────────────┘
                │  ORM
                ▼
   ┌─────────────────────────┐
   │   Base PostgreSQL       │
   └─────────────────────────┘
```

- Symfony n'accède jamais à la base : il appelle l'API avec `HttpClient` via le service `ApiClient`.
- Après connexion, l'API renvoie un **JWT** (contenant `idUser` et `idRole`). Il est stocké en session et envoyé dans `Authorization: Bearer <token>`.
- Le panier est stocké en session côté Symfony.
- Le calcul des statistiques (pandas) reste côté Django ; Symfony ne fait que l'affichage (Chart.js).

## Endpoints de l'API utilisés

> Contrat fixé avec le groupe Django. À compléter/corriger selon le fichier de contrat partagé.

| Méthode | Route | Usage | Corps / paramètres |
|---|---|---|---|
| POST | `/v1/login` | Connexion | `{ "email": "...", "password": "..." }` → JWT (`idUser`, `idRole`) |
| GET | `/v1/menus/{menu}` | Menu et plats | — |
| GET | `/v1/orders/{order}` | Détail d'une commande | — |
| GET | `/v1/plat/stats` | Statistiques de ventes | `dateDebut`, `dateFin` |

Endpoint à confirmer avec l'équipe back : création d'une commande (`POST /v1/orders` ?).

## Lancer le projet

Prérequis : PHP 8.2+, Composer, Symfony CLI (optionnel).

```bash
git clone <url-du-depot>
cd <dossier>
composer install
cp .env .env.local        # puis adapter les valeurs ci-dessous
symfony server:start      # ou : php -S localhost:8000 -t public
```

Ouvrir ensuite http://localhost:8000.

### Mode démo sans API (mock)

Dans `.env.local` :

```
API_MOCK=1
```

`ApiClient` lit alors les fichiers JSON de démonstration au lieu d'appeler l'API réelle.

## Variables d'environnement

| Variable | Rôle | Exemple |
|---|---|---|
| `APP_ENV` | Environnement Symfony (`dev` ou `prod`) | `dev` |
| `APP_SECRET` | Secret Symfony (sessions, CSRF) | chaîne aléatoire |
| `API_BASE_URL` | URL de base de l'API Django | `http://localhost:8001/v1` |
| `API_MOCK` | `1` = utiliser les JSON de démo, `0` = API réelle | `0` |

`.env` contient les valeurs par défaut (versionné). `.env.local` contient les valeurs propres à chaque machine (non versionné).

## Tests

```bash
php bin/phpunit
```

Les tests `WebTestCase` couvrent les pages principales (liste des plats, panier, connexion, tableau de bord).

## Passer en production

```
APP_ENV=prod
APP_DEBUG=0
```

```bash
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
```

## Organisation Git

Une branche par développeur, merges courts vers `master`.
