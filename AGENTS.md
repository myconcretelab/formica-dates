# Procédure de livraison

Après chaque modification apportée à ce dépôt et validée localement :

1. créer un commit dédié sur `main` ;
2. pousser le commit vers `origin/main` ;
3. mettre la production à jour avec `update formica` ;
4. vérifier que le thème et le plugin ont bien été traités et que le site répond correctement.

La mise à jour du thème et celle du plugin doivent rester indépendantes : l'échec de l'une ne doit pas empêcher de tenter l'autre. Signaler clairement tout échec de commit, de push, de déploiement ou de vérification.
