# CAHIER DES CHARGES TECHNIQUE – DÉVELOPPEMENT BACKEND

## Projet

**Conception et réalisation d'une plateforme web de gestion des incidents et de suivi du support technique des solutions logicielles**

---

# 1. Objectif

Développer une API REST sécurisée sous **Laravel** permettant la gestion des utilisateurs, des entreprises clientes, des solutions logicielles, des incidents, des interventions, des messages, des notifications, des évaluations et des statistiques.

L'API devra être conçue de manière modulaire, sécurisée et évolutive afin d'être consommée par une application frontend développée en Vue.js.

---

# 2. Technologies imposées

Le backend devra être développé avec :

* Laravel 12
* PHP 8.4+
* MySQL
* Eloquent ORM
* Laravel Sanctum
* API REST (JSON)
* Migrations
* Form Request Validation
* API Resources
* Policies / Middleware

---

# 3. Architecture attendue

Le projet devra respecter l'architecture Laravel.

```
app/
├── Models/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   ├── Resources/
│   └── Middleware/
├── Policies/
├── Notifications/
└── Providers/
```

Le code devra respecter la séparation des responsabilités.

La logique métier ne devra pas être écrite directement dans les contrôleurs.

---

# 4. Authentification

Le système devra permettre :

* Connexion
* Déconnexion
* Changement du mot de passe

L'authentification sera réalisée avec **Laravel Sanctum**.

Les accès devront être contrôlés selon les rôles suivants :

* Administrateur
* Technicien
* Entreprise cliente

---

# 5. Gestion des utilisateurs

L'administrateur est le seul habilité à gérer les utilisateurs.

Le backend devra permettre :

* créer un compte administrateur ;
* créer un compte technicien ;
* créer un compte entreprise cliente ;
* modifier un compte ;
* désactiver un compte ;
* consulter les comptes.

### Règles métier

* Une entreprise cliente possède **un seul compte utilisateur**.
* Un compte désactivé ne peut plus se connecter.

---

# 6. Gestion des entreprises clientes

Le backend devra permettre :

* créer une entreprise cliente ;
* modifier une entreprise cliente ;
* consulter une entreprise ;
* associer une ou plusieurs solutions logicielles.

### Règles métier

Lors de la création d'une entreprise cliente :

* le compte utilisateur est créé ;
* les solutions utilisées par cette entreprise sont immédiatement associées.

Si l'entreprise obtient une nouvelle solution ultérieurement, l'administrateur met simplement à jour cette association.

Relation :

**EntrepriseCliente ⇄ SolutionLogicielle (N:N)**

---

# 7. Gestion des solutions logicielles

Le backend devra permettre :

* créer une solution logicielle ;
* modifier une solution logicielle ;
* consulter une solution.

Pour chaque solution, l'administrateur devra associer les techniciens ayant participé à son développement.

### Règles métier

* Une solution possède un ou plusieurs techniciens référents.
* Un technicien peut être référent de plusieurs solutions.

Relation :

**Technicien ⇄ SolutionLogicielle (N:N)**

---

# 8. Gestion des incidents

Le backend devra permettre :

* déclarer un incident ;
* consulter les incidents ;
* consulter le détail d'un incident ;
* prendre en charge un incident ;
* modifier le statut ;
* clôturer un incident.

### Règles métier

* Une entreprise ne peut déclarer un incident que sur une solution qui lui est associée.
* Un incident concerne une seule solution.
* Un incident appartient à une seule catégorie.
* Un incident possède une seule priorité.

---

# 9. Prise en charge des incidents

Les techniciens ne sont pas affectés manuellement.

Grâce aux associations entre techniciens et solutions :

* chaque technicien visualise automatiquement les incidents concernant les solutions dont il est référent ;
* le premier technicien qui prend en charge un incident devient son responsable ;
* un incident ne peut être pris en charge que par un seul technicien à la fois.

---

# 10. Cycle de vie d'un incident

Le backend devra respecter les statuts suivants :

* Déclaré
* Analysé
* Pris en charge
* En traitement
* Résolu
* Clôturé

Les changements de statut devront respecter cet ordre.

Un incident clôturé ne pourra plus être modifié.

---

# 11. Gestion des interventions

Le backend devra permettre :

* ajouter une intervention ;
* consulter les interventions d'un incident.

Chaque intervention enregistrera :

* le technicien ;
* la date ;
* la durée ;
* la description.

Un incident peut posséder plusieurs interventions.

---

# 12. Messagerie

Chaque incident possède une discussion.

Le backend devra permettre :

* envoyer un message ;
* consulter les messages ;
* marquer un message comme lu.

### Règles métier

Les échanges sont autorisés uniquement entre :

* l'entreprise ayant déclaré l'incident ;
* le technicien responsable.

Chaque message devra enregistrer :

* son auteur ;
* son contenu ;
* sa date d'envoi ;
* son état de lecture.

---

# 13. Notifications

Le backend devra générer automatiquement des notifications lors :

* de la prise en charge d'un incident ;
* d'un changement de statut ;
* de la réception d'un message ;
* de la résolution d'un incident ;
* de la clôture d'un incident.

Chaque notification pourra être marquée comme lue.

---

# 14. Satisfaction

Le backend devra permettre :

* enregistrer une évaluation.

### Contraintes

* uniquement lorsqu'un incident est clôturé ;
* une seule évaluation par incident ;
* note comprise entre 1 et 5.

---

# 15. Historique

Chaque changement de statut devra être enregistré automatiquement.

L'historique conservera :

* ancien statut ;
* nouveau statut ;
* date ;
* utilisateur ayant effectué la modification.

L'historique ne pourra jamais être modifié.

---

# 16. Tableau de bord

Le backend devra fournir les API nécessaires au tableau de bord administrateur.

Les statistiques devront être calculées automatiquement.

Les indicateurs attendus sont :

* nombre total d'incidents ;
* incidents par statut ;
* incidents par priorité ;
* incidents par catégorie ;
* incidents par solution logicielle ;
* entreprises ayant déclaré le plus d'incidents ;
* techniciens ayant traité le plus d'incidents ;
* temps moyen de résolution ;
* taux moyen de satisfaction ;
* incidents récurrents.

---

# 17. Recherche

Toutes les API de consultation devront intégrer une recherche.

La recherche devra :

* être insensible aux majuscules/minuscules ;
* fonctionner par correspondance partielle.

Exemple :

Recherche : **"ges"**

Résultats :

* Gestion RH
* Gestion Commerciale
* Gestion de Stock

La recherche devra retrouver une donnée dès qu'une partie du texte correspond à la saisie.

---

# 18. Pagination

Toutes les listes importantes devront être paginées.

Le nombre d'éléments par page devra être paramétrable.

---

# 19. Tri

Les API devront permettre :

* tri croissant ;
* tri décroissant.

---

# 20. Filtres

Les API devront accepter des filtres adaptés au contexte.

Exemples :

* statut ;
* priorité ;
* catégorie ;
* solution ;
* entreprise ;
* technicien ;
* période.

Les filtres pourront être combinés.

---

# 21. Validation

Toutes les données devront être validées avec les **Form Requests** de Laravel.

Les erreurs devront être retournées au format JSON.

---

# 22. Sécurité

Le backend devra garantir :

* l'authentification sécurisée ;
* le contrôle des rôles ;
* les autorisations via Policies ;
* la protection des routes.

Chaque utilisateur ne devra accéder qu'aux données qui lui sont autorisées.

---

# 23. Bonnes pratiques de développement

Le développeur devra respecter les bonnes pratiques Laravel.

Notamment :

* séparation entre logique métier et contrôleurs ;
* modèles Eloquent correctement définis ;
* relations Eloquent complètes ;
* utilisation des API Resources ;
* principe **DRY (Don't Repeat Yourself)** ;
* code réutilisable ;
* nommage clair des classes, méthodes et variables.

---

# 24. Livrables attendus

Le développeur devra fournir :

* le projet Laravel complet ;
* les migrations de la base de données MySQL ;
* les modèles Eloquent ;
* les relations Eloquent ;
* les contrôleurs REST ;
* les Form Requests ;
* les API Resources ;
* les routes API ;
* l'authentification avec Laravel Sanctum ;
* les endpoints de recherche, filtres, tri et pagination ;
* les endpoints nécessaires au tableau de bord ;
* un code documenté, propre, maintenable et prêt à être consommé par le frontend.

---

# Consigne générale

Le backend devra être développé de manière **modulaire, sécurisée, évolutive et performante**, en respectant l'ensemble des règles métier définies pour la plateforme. Toutes les validations critiques et les contraintes fonctionnelles devront être appliquées côté serveur afin de garantir l'intégrité et la cohérence des données.
