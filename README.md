# Magento module #
## Installation ##

### Récupération des sources ###

Cloner le repo dans votre espace de travail :

    git clone git@bitbucket.org:lengow-dev/magento-v3.git

### Installation dans Magento ###

Exécuter le script suivant :

    ./install.sh /path/instance/magento

Le script va créer des liens symboliques vers les sources du module, vous devez ensuite activer l'option 'symlinks' dans la configuration Magento 

    Configuration / Advanced / Developer / Template Settings Allow Simlinks : Yes

### Bugs connus ###

Après installation, si vous obtenez une erreur 'Wrong type' lors de l'affichage des réglages du module, vous devez vous rendre dans les réglages des transporteurs et enregistrer la configuration.