INSTALLER OUVRETAFERME

**Stack**

- Nginx
- PHP 8.4+ (extensions requises : bcmath, cli, curl, fpm, imagick, imap, intl, mbstring, pdo, redis, xml, yaml)
- MySQL 8+
- Redis

**Installation**

Une configuration docker est disponible ici pour installer l'environnement de développement :

https://github.com/emilieguth/otf-docker

Vous devez exécuter les commandes suivantes :
* `mkdir otf` (`otf` sera votre dossier root)
* `cd otf`
* `git clone git@github.com:emilieguth/otf-docker.git .`
* `git submodule update --init`
* `cp src/ouvretaferme/secret-example.c.php src/ouvretaferme/secret.c.php`
* `docker-compose up --build`
* Pour importer une base de données : copier le fichier SQL dans `otf/docker/mysql/tmp/demo.sql` puis connectez-vous à votre conteneur MySQL, puis à votre serveur SQL et créez la base de données `dev_ouvretaferme`. Ensuite, injectez en ligne de commande le fichier `demo.sql` dans cette nouvelle base. Exemple de commande : `mysql -u root -p -b dev_ouvretaferme < demo.sql`

**Base de données de démarrage**

La base de données du site de démo à importer dans une base _dev_ouvretaferme_ est disponible ici :

https://media.ouvretaferme.org/demo.sql

Elle est mise à jour automatiquement toutes les nuits et correspond à la dernière version du code source.
Le mot de passe de tous les utilisateurs de la base de démo est 123456.
Connectez-vous avec l'utilisateur 1@ouvretaferme.org pour accéder à la ferme de démo en tant que producteur.

**Arborescence du code source**

Chaque dossier dans le code correspond à un package :

- framework : le framework Lime (maison) utilisé par OTF
  - core : les fichiers de base du framework
  - editor : l'éditeur WYSIWYG utilisé un peu partout sur OTF
  - storage : le stockage des fichiers des utilisateurs (Image, Pdf...)
  - user : la gestion des utilisateurs
- ouvretaferme : le code relatif à OTF
  - analyze : les statistiques
  - farm : la ferme
  - gallery : la gestion des photos des utilisateurs
  - hr : la gestion RH
  - mail : l'envoi des e-mails
  - map : la cartographie des parcelles
  - media : le téléversement des fichiers des utilisateurs (Image, Pdf...)
  - payment : le paiement sur les boutiques en ligne
  - plant : les espèces et les variétés de plantes
  - sequence : les itinéraires techniques
  - selling : la commercialisation
  - series : les séries culturales
  - shop : les boutiques en ligne
  - website : le site Internet pour les fermes

Chaque package est décomposé en plusieurs dossiers :
- asset : les fichiers CSS, JS et les images
- lib : les traitements métiers
- module : les représentations de la base de données
- page : les routes (exemple : https://www.ouvretaferme.org/plant/plant:create appelle le fichier /ouvretaferme/plant/plant.p.php)
- ui : les traitements liés à l'affichage
- view : les vues
