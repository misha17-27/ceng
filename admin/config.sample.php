<?php
/*
 * Copy this file to  config.php  (in the same /admin folder) and fill in your
 * MySQL details from cPanel -> "MySQL Databases".
 * config.php is git-ignored, so your credentials are NOT stored in the repo
 * and are never overwritten by a redeploy.
 */
return [
    'db_host' => 'localhost',
    'db_name' => 'ceng_admin',      // e.g. cpaneluser_dbname
    'db_user' => 'ceng_admin',      // e.g. cpaneluser_dbuser
    'db_pass' => 'CHANGE_ME',
    'db_charset' => 'utf8mb4',

    // Used once by install.php to create the first admin account:
    'install_admin_email' => 'admin@ceng.az',
    'install_admin_pass'  => 'change-this-strong-password',

    // Secret token required to run install.php in the browser (then delete the file).
    'install_token' => 'setup-4729',
];
