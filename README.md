# Magento module #
## Installation ##

### Installation de magento ###

1 - Aller sur le site de magento : https://www.magentocommerce.com/download

2 - Dans l'onglet release archive, choisir la version a télécharger (ex: la 1.9.2)

3 - Décompresser le projet dans /var/www/magento/magento-1-9-2

4 - Modification du fichier /etc/hosts

    echo "127.0.0.1 magento-1-9.local" >> /etc/hosts

5 - Création du fichier virtualhost d'apache

    sudo vim /etc/apache2/sites-enabled/magento_1_9.conf 
    <VirtualHost *:80>
    DocumentRoot /var/www/magento-1-9/
    ServerName magento-1-9.local
    <Directory /var/www/magento-1-9/>
        Options FollowSymLinks Indexes MultiViews
        AllowOverride All
    </Directory>
        ErrorLog /var/log/apache2/magento-1-9-error_log
        CustomLog /var/log/apache2/magento-1-9-access_log common
    </VirtualHost>
6 - Rédémarrer apache

    sudo service apache2 restart
    
7 - Creation de la base de données
    
    mysql -u root -p -e "CREATE DATABASE magento-1-9"; 
    
7 -  Récupération des Sample Data

Les fichier sont dispo ici : https://github.com/Vinai/compressed-magento-sample-data

    cd /tmp
    wget https://raw.githubusercontent.com/Vinai/compressed-magento-sample-data/1.9.1.0/compressed-no-mp3-magento-sample-data-1.9.1.0.tgz
    tar -zxvf compressed-magento-sample-data-1.9.1.0.tgz
    cd magento-sample-data-1.9.1.0
    mysql -u root -p magento-1-9 < magento_sample_data_for_1.9.1.0.sql
    
8 - Se connecter sur magento pour lancer l'installation
    
    http://magento-1-9.local

### Récupération des sources ###

Cloner le repo dans votre espace de travail :

    cd /var/www/magento/
    git clone git@bitbucket.org:lengow-dev/magento-v3.git

### Installation dans Magento ###

Exécuter le script suivant :

    cd /var/www/magento/
    ./install.sh /var/www/magento/magento-1-9

Le script va créer des liens symboliques vers les sources du module, vous devez ensuite activer l'option 'symlinks' dans la configuration Magento 

Configuration => Advanced => Developer => Template Settings Allow Simlinks => Yes
System => Cache management => Désactiver le cache pour toutes les entrées
Se déconnecter, puis se reconnecter sur l'admin magento.

## Traduction ##

Pour traduire le projet il faut modifier les fichier *.yml dans le répertoire : /app/code/community/Lengow/Connector/locale/yml

### Installation de Yaml Parser ###

    sudo apt-get install php5-dev libyaml-dev
    sudo pecl install yaml

### Mise à jour des traductions ###

Une fois les traductions terminées, il suffit de lancer le script de mise à jour de traduction :

    cd /var/www/magento/magento-v3/tools
    php translate.php

## Test ##


### Modman Installation (https://github.com/colinmollenhour/modman) ###

    bash < <(wget -q --no-check-certificate -O - https://raw.github.com/colinmollenhour/modman/master/modman-installer)
    source ~/.profile

### Install EcomDev ###

    cd /var/www/magento/magento-1-9
    modman init
    modman clone git://github.com/EcomDev/EcomDev_PHPUnit.git

### create a new database magento_test ###

    cd shell
    php ecomdev-phpunit.php -a magento-config --db-name TEST_DATABASE_NAME --base-url http://BASE_URL

### Install phpunit 4.8 ###

    cd /tmp
    wget https://phar.phpunit.de/phpunit-old.phar
    chmod +x phpunit-old.phar
    sudo mv phpunit-old.phar /usr/local/bin/phpunit

### Check Phpunit and build test database ###

    cd /var/www/magento/magento-1-9
    phpunit
    
Tester un controller 
    
    cd /var/www/magento/magento-1-9
    phpunit --filter Nom_Du_Controller_Test

## Bugs connus ##

Après installation, si vous obtenez une erreur 'Wrong type' lors de l'affichage des réglages du module, vous devez vous rendre dans les réglages des transporteurs et enregistrer la configuration.

### Install With Magento <= 1.6 + php >= 5.5 ###

    Error :
        CONNECT ERROR: Unsupported resource type
    Solution :
        Edit file downloader/lib/Mage/Archive/Tar.php
        Change the line :
            const FORMAT_PARSE_HEADER = 'a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix/a12closer';
        must be replaced by:
            const FORMAT_PARSE_HEADER = 'Z100name/Z8mode/Z8uid/Z8gid/Z12size/Z12mtime/Z8checksum/Z1type/Z100symlink/Z6magic/Z2version/Z32uname/Z32gname/Z8devmajor/Z8devminor/Z155prefix/Z12closer';
            
### Install With Magento <= 1.5 ###

    Error :
        CONNECT ERROR: The 'community' channel is not installed.
    Solution :
        chmod 777 mage
        ./mage mage-setup
        
### Magento Installation = 1.7 ###

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