INSTALLER OUVRETAFERME

**Stack**

- Nginx
- PHP 8.3+ (extensions requises : mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql, bcmath, curl, intl, mbstring, xml, imap)
- MySQL 8+
- Redis

**Installation**

Un docker est disponible ici pour l'installer l'environnement de développement :

https://github.com/emilieguth/otf-docker

Vous devez ensuite :
* Télécharger le code source
* Créer un fichier secret.c.php copié à partir de secret-example.c.php à la racine du code source et le personnaliser le cas échéant (seule la configuration MySQL est indispensable pour démarrer)
* Importer une base de données 

**Base de données de démarrage**

La base de données du site de démo à importer dans une base _dev_ouvretaferme_ est disponible ici :

https://media.ouvretaferme.org/demo.sql

Elle est mise à jour automatiquement toutes les nuits.
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
  - production : les itinéraires techniques
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