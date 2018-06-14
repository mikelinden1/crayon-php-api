# A crude php api that works with Crayon CMS (WIP)

## Sample .htaccess file
```
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^login(\/?)?$ /php-crud-api/login.php [QSA,L]
    RewriteRule ^validate-jwt(\/?)?$ /php-crud-api/validate-jwt.php [QSA,L]
    RewriteRule ^users(\/?)?([0-9])?(\/?)?$ /php-crud-api/users.php?id=$2 [QSA,L]
    RewriteRule ^([a-zA-Z0-9\,\-\/]+)$ /php-crud-api/api.php/$1 [QSA,L]
</IfModule>
```

### Ideally it would be more like this, with a `router.php` file so I only have to add one rewrite rule to the root project
```
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^([a-zA-Z0-9\,\-\/]+)$ /php-crud-api/router.php?request=$1 [QSA,L]
</IfModule>
```

The `router.php` file would essentially do this:

```
switch request
    case 'login'
        DO THE LOGIN
    case 'validate-jwt'
        VALIDATE JWT
    case 'users'
        DO USERS CRUD
    default
        DO STANDARD CRUD API (api.php)
```