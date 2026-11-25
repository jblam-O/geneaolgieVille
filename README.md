# geneaolgieVille

## Déploiement Docker Swarm

Le projet peut tourner sans Vercel. La base fournie ici s’appuie sur un conteneur PHP/Apache et des variables d’environnement.

1. Construis l’image `genealogieville:latest` sur ta machine de déploiement.
2. Prépare un fichier d’environnement à partir de [`.env.swarm.example`](/Users/jb/Documents/DocumentsSaved/code/genealogie.nosync/.env.swarm.example).
3. Déploie le stack avec `docker stack deploy -c docker/swarm-stack.yml <nom-du-stack>`.

Pour les médias, l’application stocke désormais les fichiers localement dans `public/uploads/history-towns/` si aucun token Blob n’est configuré.

### Installation et mode hors connexion

L’atelier `/visualisation` propose l’installation en PWA. Servir le site en HTTPS
(localhost convient au développement), puis ouvrir une famille une première fois
en ligne. Les familles ouvertes sont conservées dans IndexedDB, séparément par
compte. Les créations de familles, personnes, liens et les modifications de fiches
sont enregistrées localement avant envoi. La file survit à la fermeture de l’application.
Ne pas effacer les données du navigateur avant synchronisation.

Appliquer la migration `Version20261016120000` lors du déploiement : la table
`offline_receipt` rend les reprises d’envoi idempotentes (aucune création en double).
La synchronisation se déclenche à l’ouverture, au retour du réseau et toutes les
30 secondes lorsque l’application est ouverte, avec une commande manuelle de reprise.
Une session expirée ou un autre compte bloque l’envoi et conserve les données locales.
Les navigateurs mobiles ne garantissent pas l’exécution lorsque l’application est
fermée : rouvrir l’atelier permet de reprendre les envois.

Les photos et archives distantes ne sont pas téléchargées pour le mode hors connexion.
Les dernières modifications synchronisées d’une même fiche remplacent les champs envoyés
(pas de fusion collaborative des changements concurrents). Le mode hors connexion
concerne l’atelier React ; les anciennes pages serveur nécessitent le réseau.

Après une mise à jour des fichiers de l’application, incrémenter la version du cache
dans `public/sw.js`. Fermer puis rouvrir toutes les fenêtres de l’atelier active la
nouvelle version du service worker.
