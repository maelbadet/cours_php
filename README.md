# cours_php

Repo git pour les differents exos PHP de la semaine de cours.

## Branches liées à la caisse enregistreuse

Les branches ci-dessous montrent l'évolution d'une caisse enregistreuse au fil des étapes. Chaque branche contient uniquement la logique de l'étape concernée, ce qui permet de lire l'historique pédagogique ou de se placer directement sur l'exercice souhaité.

> 💡 Chaque branche possède son propre code commenté. Pour tester une étape précise : `git checkout feat/shopping-regsiter-step-X`, lancer le serveur PHP (`docker compose up` ou `php -S localhost:8000 -t www`) puis accéder à l'interface de la caisse.

### `feat/shopping-regsiter-step-1` — Caisse classique
- Objectif : réaliser la caisse enregistreuse de base qui prend un prix, un montant donné et calcule le rendu.
- Fonctionnalités :
    - Calcul du montant à rendre quand le client paie plus que le total.
    - Détail du rendu en billets et pièces (du plus grand au plus petit).
    - Calcul de l'état de la caisse après le rendu pour vérifier les stocks restants.
- Intérêt : c'est l'étape d'initiation où l'on manipule les boucles et les tableaux pour faire un rendu optimal « greedy » classique.

### `feat/shopping-regsiter-step-2` — Préférence de billet/pièce
- Objectif : conserver tout le comportement de la step 1 mais ajouter la possibilité de « forcer » une valeur à utiliser en priorité.
- Fonctionnalités :
    - Paramètre supplémentaire à saisir (ou via l'UI) pour choisir le billet/la pièce à privilégier.
    - L'algorithme essaie d'utiliser la valeur choisie tant que cela reste possible, puis complète avec les autres valeurs.
- Intérêt : introduire des contraintes utilisateur et montrer comment adapter l'algorithme greedy sans casser le rendu final.

### `feat/shopping_regsiter_step_3` — Rendu inversé
- Objectif : explorer une stratégie alternative de rendu pour comprendre l'effet de l'ordre de distribution.
- Fonctionnalités :
    - L'algorithme parcourt les dénominations du plus petit vers le plus grand.
    - Permet d'étudier les limites du greedy classique (peut nécessiter plus de pièces, oblige à raisonner sur les stocks).
- Intérêt : réfléchir à l'impact métier (ex. écouler les petites pièces) et comparer les résultats avec ceux des étapes précédentes.
