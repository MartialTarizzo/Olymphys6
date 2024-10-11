
# Olymphys6

Ce dépôt contient le code permettant le fonctionnement du site des olympiades de physique

https://www.olymphys.fr/


## Installation
### Environnement
Le framework utilisé est Symfony 6.4 (LTS).

Il faut donc installer au préalable :
- PHP (>= 8.2)
- un moteur de base de données (MySQL)
- Symfony framework (https://symfony.com/doc/current/setup.html), en particulier "composer" et Symfony CLI

Un serveur Web n'est pas indispensable, car Symfony possède son propre serveur pour le développement.

Penser à activer les modules complémentaires nécessaires (Imagick, ...) dans php.ini

Il se peut que la commande PHP "composer update" (cf plus loin) ne puisse s'exécuter lors de l'effacement du cache en raison de la taille mémoire allouée à PHP (ça coince avec 128 Mo, ça passe avec 192 Mo)



### Mise en route
- récupérer la base de données du site et l'importer via phpMyAdmin dans la base olymphys_odpf
- cloner le dépôt dans un répertoire du poste de travail
- récupérer le fichier .env (ou .env.local) et mettre à jour le lien vers la base de données dans  serveur MySQL
- dans le répertoire cloné, lancer dans un shell "composer update" : ceci va charger tous les packages définis dans composer.json
### Finalisation
Il manque des dossiers présents sur le site pour avoir un version locale fonctionnelle.

Copier dans le répertoire public :
- odpf ; ce dossier contient plusieurs sous-répertoires. Le sous répertoire archives est très gros (>22Go), et peut être omis dans un premier temps, car il ne contient que les infos des éditions antérieures du concours
- docequipes

## Vérification du fonctionnement
Le lancement de "symfony server:start -d" dans la console permet de vérifier le fonctionnement en local du site d'olymphys
Pour arrêter le serveur : "symfony server:stop"
