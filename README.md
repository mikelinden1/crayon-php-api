# A PHP API that works with Crayon CMS (WIP)

## Composer
Run `composer install` from the project root to install the JWT dependencies.

## Sample .htaccess file
```
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^([a-zA-Z0-9\,\-\/]+)$ router.php/$1?request=$1 [QSA,L]
</IfModule>
```
