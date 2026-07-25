# Décisions de conception — Plateforme de gestion des incidents et du support technique

Ce document résume les décisions métier prises en amont du code, qui ne sont pas toujours évidentes rien qu'en lisant les fichiers. À lire avant toute modification du projet.

---

## Terminologie

**Le mot "ticket" n'est jamais utilisé dans ce projet.** Le terme retenu partout (code, interface, rapport) est **incident**. Si "ticket" apparaît quelque part (variable, libellé, commentaire), c'est une scorie à corriger, pas un choix voulu.

---

## Les 3 rôles

- **Administrateur** : gère les comptes, les solutions logicielles, les entreprises clientes, consulte le tableau de bord global.
- **Technicien** : développeur référent d'une ou plusieurs solutions logicielles, traite les incidents de ces solutions.
- **Entreprise cliente (`company`)** : organisation ayant acheté une ou plusieurs solutions, déclare des incidents dessus.

Il n'y a **pas de 4e rôle "utilisateur final"** distinct de l'entreprise cliente : une entreprise cliente possède un **compte unique** (pas de comptes individuels par employé).

---

## Comptes et onboarding

- **Seul l'administrateur crée des comptes** — aucune auto-inscription, pour aucun rôle.
- Une entreprise cliente n'a connaissance de la plateforme et n'y accède qu'à partir du moment où l'admin lui crée un compte (typiquement au moment où elle achète une première solution).
- À la création du compte entreprise cliente, l'admin lui associe directement la ou les solutions qu'elle utilise. Il peut mettre à jour cette liste plus tard si l'entreprise acquiert une nouvelle solution.

---

## Modèle de données : pas de "Souscription"

Une version antérieure du modèle envisageait une entité `Souscription` (avec dates de début/fin, statut actif/expiré) entre `EntrepriseCliente` et `Logiciel`. **Cette entité a été abandonnée.** Le lien retenu est une simple relation many-to-many directe :

- Table pivot `company_software_solution` : une entreprise cliente est reliée directement à ses solutions, sans notion de dates d'abonnement ou de statut d'expiration.
- Une entreprise cliente ne peut déclarer un incident que sur une solution qui lui est associée dans cette table.

De la même façon, la classe `CategorieIncident` envisagée à un moment comme entité séparée **n'a pas été retenue** : `category` est un simple champ texte sur `Incident`.

---

## Le routage par référent (le cœur métier du projet)

C'est le point qui différencie ce projet d'un simple outil de ticketing générique :

- Quand l'administrateur enregistre une solution logicielle, il lui associe les techniciens qui ont participé à son développement — table pivot `software_solution_user`. Ces techniciens deviennent les **référents** de la solution.
- Un technicien **ne voit que les incidents concernant les solutions dont il est référent**. Un technicien peut être référent de plusieurs solutions ; une solution peut avoir plusieurs référents.
- **Il n'y a pas d'affectation manuelle par l'administrateur.** Quand un incident est déclaré (et éventuellement analysé), il devient visible par tous les techniciens référents de la solution concernée. Le premier qui clique sur "Prendre en charge" devient responsable de l'incident ; les autres ne peuvent plus le prendre.

---

## Cycle de vie d'un incident

Statuts, dans l'ordre :
```
declared (Déclaré)
  → analyzed (Analysé)
  → taken_over (Pris en charge)
  → in_progress (En traitement)
  → resolved (Résolu)
  → closed (Clôturé)
```

Transitions valides (voir `Incident::VALID_TRANSITIONS`) :
- `declared` → `analyzed` ou `taken_over`
- `analyzed` → `taken_over`
- `taken_over` → `in_progress`
- `in_progress` → `resolved`
- `resolved` → `closed` **ou** `in_progress` (voir boucle de réouverture ci-dessous)
- `closed` → (aucune, statut final)

**Boucle de validation client / réouverture** : quand un incident passe à `resolved`, l'entreprise cliente doit valider la résolution.
- Si elle confirme → l'incident passe à `closed`, et elle peut ensuite laisser une évaluation de satisfaction.
- Si elle indique que le problème persiste → l'incident **repasse automatiquement à `in_progress`**, avec le même technicien responsable (le `technician_id` n'est pas réinitialisé).

Chaque changement de statut est historisé dans `IncidentStatusHistory` (ancien statut, nouveau statut, auteur du changement, date).

Un incident clôturé devient en lecture seule (aucune modification possible, hormis l'ajout d'une évaluation de satisfaction si pas encore faite).

---

## Autorisations clés (policies)

- Un technicien ne peut consulter/modifier que les incidents des solutions dont il est référent, et ne peut modifier le statut que d'un incident qu'il a personnellement pris en charge.
- Une entreprise cliente ne voit jamais les incidents d'une autre entreprise cliente.
- Une entreprise cliente peut faire transitionner un incident **uniquement depuis le statut `resolved`** (vers `closed` ou `in_progress`) — c'est le mécanisme de validation/réouverture. Elle ne peut pas faire les autres transitions.
- `viewDashboard` doit être autorisé pour les 3 rôles (admin, technicien, company) — voir section Tableau de bord ci-dessous.

---

## Messagerie vs Notifications — deux systèmes distincts, à ne pas fusionner

- **Message** (`messages` table) : échange libre et manuel entre l'entreprise cliente et le technicien, à l'intérieur d'un incident. Initié par un humain.
- **Notification** (`notifications` table) : alerte automatique générée par le système, sans action humaine directe. Déclenchée par : prise en charge, changement de statut, nouveau message, résolution, clôture.

Ces deux systèmes sont volontairement séparés — ne pas les fusionner en un seul mécanisme.

---

## Satisfaction

- Une entreprise cliente peut évaluer un incident **uniquement après qu'il soit passé par `closed`** (donc après validation de la résolution).
- Une seule évaluation par incident (note 1-5 + commentaire).

---

## Tableau de bord — adapté aux 3 rôles

Point tranché explicitement (le cahier des charges backend ne mentionnait que l'admin, celui du frontend demandait les 3 rôles — le frontend l'a emporté) :

- **Un seul endpoint** `/api/dashboard`, protégé uniquement par `auth:sanctum` (pas de middleware `role:admin`).
- Le contrôleur adapte les données retournées selon `$user->role` :
  - **admin** : statistiques globales (tous incidents, par statut/priorité/catégorie, top solutions, top entreprises, stats par technicien, temps moyen de résolution, satisfaction moyenne, incidents récurrents).
  - **technicien** : uniquement ses propres incidents assignés (`technician_id`).
  - **company** : uniquement les incidents de sa propre entreprise (`company_id`), + ses stats de satisfaction.
- La policy `IncidentPolicy::viewDashboard` doit autoriser les 3 rôles.

---

## Stack technique

Backend : Laravel 12, PHP 8+, MySQL (base de données créée via phpMyAdmin) avec seeders pour les données de démonstration, Sanctum pour l'authentification, Policies pour les autorisations.

Frontend : Vue.js 3, Vite, Vue Router, Pinia, Axios, Tailwind CSS, SweetAlert2 pour toutes les notifications utilisateur (succès/erreur/confirmation).
---

## Erreurs déjà rencontrées et corrigées (pour éviter de les reproduire)

- `app/Http/Controllers/Controller.php` doit utiliser le trait `AuthorizesRequests` (`use Illuminate\Foundation\Auth\Access\AuthorizesRequests;`) pour que `$this->authorize()` fonctionne dans les contrôleurs — ce n'est plus inclus par défaut dans Laravel 11+.
- Les champs `nullable` mais `required_if` dans les Form Requests doivent être marqués `nullable` explicitement, car le middleware `ConvertEmptyStringsToNull` de Laravel convertit les chaînes vides envoyées par le frontend en `null` avant la validation.
- Dans les layouts Vue utilisés comme composants de route imbriqués (`children` dans le router), utiliser `<router-view />` et non `<slot />` pour afficher le contenu de la page active.