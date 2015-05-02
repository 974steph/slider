Slider
========================

install
========================
curl -sS https://getcomposer.org/installer | php5.4

php5.4-cli composer install
mkdir data
php5.4-cli app/console doctrine:database:create
php5.4-cli app/console doctrine:schema:update --force

conf
========================
create user

php5.4-cli app/console fos:user:create admin
php5.4-cli app/console fos:user:promote admin --super

php5.4-cli app/console assets:install web --symlink

creation  des dossiers images et cache dans web





config htaccess
========================
<IfModule mod_rewrite.c>
    Options +FollowSymlinks
    RewriteEngine On

    # Explicitly disable rewriting for front controllers
    RewriteRule ^app_dev.php - [L]
    RewriteRule ^app.php - [L]

    RewriteCond %{REQUEST_FILENAME} !-f

    # Change below before deploying to production
    #RewriteRule ^(.*)$ /app.php [QSA,L]
    RewriteRule ^(.*)$ /app_dev.php [QSA,L]
</IfModule>
