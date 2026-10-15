# CampusEats – front Symfony

TP en équipe : les étudiants commandent leur repas le matin et le récupèrent à midi.

## Architecture
- Symfony 7.4, PHP 8.2+ (attributs `#[Route]`, injection par constructeur). Lancement : `symfony server:start`.
- Ce front **ne touche jamais à une base de données** : pas de Doctrine, pas d'entités, pas de migrations
  (les paquets Doctrine sont installés, mais on ne s'en sert pas pour nos données).
- Toutes les données passent par une API REST Django, via `App\Service\ApiClient` (`get($path)`, `post($path, $data)`).
  - `.env` : `API_BASE_URL`, `API_MOCK`. Avec `API_MOCK=1`, les réponses viennent de `mock/` :
    `GET /v1/menus/1` → `mock/get_v1_menus_1.json`, `POST /v1/orders` → `mock/post_v1_orders.json`.
  - Une erreur API (`Symfony\Contracts\HttpClient\Exception\ExceptionInterface`) doit produire un flash d'erreur lisible, jamais une page 500.

## Routes de l'API Django
- `GET /v1/menus/{id}` → `{ id, date, plats: [{ id, nom, prix }] }` (forme à confirmer avec Valentin)
- `POST /v1/login` (email + mot de passe)
- `GET /v1/orders/{order}` : détail de la commande, avec les statuts payé et livré
- `GET /v1/plat/stats`, `POST /v1/plats` (prévue)
- `POST /v1/orders` **n'existe pas encore**. On suppose le body `{ etudiant_id, lignes: [{ plat_id, quantite }], moyen_paiement }`
  et la réponse `{ id, ... }`. L'appel est isolé à un seul endroit, marqué `// TODO`.

## Conventions d'équipe
- Session `user` = `['id', 'email', 'role' => 'etudiant'|'admin']`, remplie par le login (coéquipier). Route de login : `app_login`.
  Si `user` est absent : redirection vers `app_login`. Si la route n'existe pas et que `API_MOCK=1`, on utilise un user factice.
- Session `panier` = `[plat_id => ['nom', 'prix', 'qte']]`.
- La navbar n'affiche « Dashboard » (route `admin_dashboard`) que si `user.role == 'admin'`.
- Ne pas toucher à `security/`, `admin/` ni aux contrôleurs associés : c'est la partie d'un coéquipier.
- Style : CSS minimal en CDN. Chart.js est chargé en CDN dans `base.html.twig`.
