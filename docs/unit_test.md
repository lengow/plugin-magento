
h2. Modman Installation (https://github.com/colinmollenhour/modman)

<pre>
bash < <(wget -q --no-check-certificate -O - https://raw.github.com/colinmollenhour/modman/master/modman-installer)
source ~/.profile
</pre>

h2. Install EcomDev
go to magento root directory
<pre>
modman init
modman clone git://github.com/EcomDev/EcomDev_PHPUnit.git
</pre>
create a new database magento_test
<pre>
cd shell
php ecomdev-phpunit.php -a magento-config --db-name TEST_DATABASE_NAME --base-url http://BASE_URL
</pre>

h2. Install phpunit 4.8

<pre>
cd /tmp
wget https://phar.phpunit.de/phpunit-old.phar
chmod +x phpunit-old.phar
sudo mv phpunit-old.phar /usr/local/bin/phpunit
</pre>

h2. Check Phpunit and build test database
go to magento root directory
<pre>
phpunit
</pre>