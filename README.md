# Installation Magento #

## Installation du module ##

### Cloner le repository de Bitbucket dans votre espace de travail ###

Cloner le repo dans votre espace de travail :

    cd ~/Documents/modules_lengow/magento/
    git clone git@bitbucket.org:lengow-dev/magento-v3.git Lengow_Export
    chmod 777 -R ~/Documents/modules_lengow/magento/Lengow_Export

### Installation dans Magento ###

Exécuter le script suivant :

    cd ~/Documents/modules_lengow/magento/Lengow_Export/tools/
    mkdir ~/Documents/sites/magento19/magento/var/connect
    sh install.sh ~/Documents/docker_images/magento19/magento

Le script va créer des liens symboliques vers les sources du module, vous devez ensuite activer l'option 'symlinks' dans la configuration Magento 

Configuration => Advanced => Developer => Template Settings Allow Simlinks => Yes
System => Cache management => Désactiver le cache pour toutes les entrées
Se déconnecter, puis se reconnecter sur l'admin magento.

## Traduction ##

Pour traduire le projet il faut modifier les fichier *.yml dans le répertoire : Documents/modules_lengow/magento/lengow/Lengow_Export/app/code/community/Lengow/Connector/locale/yml

### Installation de Yaml Parser ###

    sudo apt-get install php5-dev libyaml-dev
    sudo pecl install yaml

### Mise à jour des traductions ###

Une fois les traductions terminées, il suffit de lancer le script de mise à jour de traduction :

    cd ~/Documents/modules_lengow/magento/Lengow_Export/tools/
    php translate.php

## Mise à jour du fichier d'intégrité des données ##

    cd ~/Documents/modules_lengow/magento/Lengow_Export/tools/
    php checkmd5.php

Le fichier checkmd5.csv sera directement créé dans le dossier /toolbox

## Compiler le module ##

La compilation du module se fait directement à partir de Magento :

1 - Se rendre dans System => Magento Connect => Package Extensions
2 - Dans l'onglet Load Local Package et cliquer sur Lengow_Magento
3 - Dans l'onglet Release Info changer la version x.x.x
4 - Cliquer sur "Save Data and Create Package" pour créer le package.
5 - Récupérer l'archive Lengow_Export-x.x.x.tgz dans le dossier /var/connect de votre Magento


## Versionning GIT ##

1 - Prendre un ticket sur JIRA et cliquer sur Créer une branche dans le bloc développement à droite

2 - Sélectionner en "Repository" lengow-dev/magento-v3, pour "Branch from" prendre dev et laisser le nom du ticket pour "Branch name"

3 - Créer la nouvelle branche

4 - Exécuter le script suivant pour changer de branche 

    cd ~/Documents/modules_lengow/magento/Lengow_Export/
    git fetch
    git checkout "Branch name"

5 - Faire le développement spécifique

6 - Lorsque que le développement est terminé, faire un push sur la branche du ticket

    git add .
    git commit -m 'My ticket is finished'
    git pull origin "Branch name"
    git push origin "Branch name"

7 - Dans Bitbucket, dans l'onglet Pull Requests créer une pull request

8 - Sélectionner la branche du tiket et l'envoyer sur la branche de dev de lengow-dev/magento-v3

9 - Bien nommer la pull request et mettre toutes les informations nécessaires à la vérification

10 - Mettre tous les Reviewers nécessaires à la vérification et créer la pull request

11 - Lorsque la pull request est validée, elle sera mergée sur la branche de dev

## Bugs connus ##

Après installation, si vous obtenez une erreur 'Wrong type' lors de l'affichage des réglages du module, vous devez vous rendre dans les réglages des transporteurs et enregistrer la configuration.

### Installation avec Magento <= 1.6 + php >= 5.5 ###

    Error :
        CONNECT ERROR: Unsupported resource type
    Solution :
        Edit file downloader/lib/Mage/Archive/Tar.php
        Change the line :
            const FORMAT_PARSE_HEADER = 'a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix/a12closer';
        must be replaced by:
            const FORMAT_PARSE_HEADER = 'Z100name/Z8mode/Z8uid/Z8gid/Z12size/Z12mtime/Z8checksum/Z1type/Z100symlink/Z6magic/Z2version/Z32uname/Z32gname/Z8devmajor/Z8devminor/Z155prefix/Z12closer';
            
### Installation avec With Magento <= 1.5 ###

    Error :
        CONNECT ERROR: The 'community' channel is not installed.
    Solution :
        chmod 777 mage
        ./mage mage-setup
        
### Installation avec Magento = 1.7 ###

    Error :
        During installation : PHP Extensions "0" must be loaded.
    Solution
        Edit file app/code/core/Mage/Install/etc/config.xml
        Change the line :
            <extensions>
                <pdo_mysql/>
            </extensions>
        must be replaced by :
            <extensions>
                <pdo_mysql>1</pdo_mysql>
            </extensions>