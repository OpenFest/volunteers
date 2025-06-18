Simple web platform for volunteer management. 
The codebase is built with vanilla PHP (developed on PHP 8.0, but may work on earlier versions) and uses PostgreSQL as the database (db_skeleton.sql is provided for initial setup).

## Features
- Volunteer registration
- Admin dashboard

## Installation
1. Clone the reporsitory:
   ```bash
   git clone https://github.com/OpenFest/volunteers.git
    ```
2. Navigate to the project directory:
   ```bash
   cd volunteers
   ```
3. Set up the database
4. Import the `db_skeleton.sql` file into your PostgreSQL database.
   ```bash
   psql -U your_username -d your_database -f db_skeleton.sql
   ```
5. Configure the database connection in `config.php`.

6. Set up a web server (e.g., Apache or Nginx) to serve the project directory. Make sure to force every request to `index.php` for routing purposes (something similar to the following for Apache):
 ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^ index.php [L]
    
   <Directory /path/to/your/project>
       AllowOverride All
   </Directory>
   ```

### Internal routing
The application uses a simple routing mechanism.

All requests are directed to `index.php`, which handles the routing based on the URL path. No URI will lead to the `home.php` file directly. The rest of the files are used for specific functionalities and should not be accessed directly via URL, but matched on their respective names. E.g., '/admin' will match `admin.php`, '/register' will match `register.php`, etc. 

There is also "extended" routing mechanism that allows nested paths by replacing existing slashes in the URI with underscores in the filename. For example, `/admin/users` will match `admin_users.php`.

There is no support for parameters in the URI's main part of the url, so all data should be passed via POST requests or query strings. For example, `/admin/users?user_id=123` will match `admin_users.php`, and you can access the `user_id` parameter in the script.