<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, and ABSPATH. You can find more information by visiting
 * {@link https://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 * Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'authorblog');

/** MySQL database username */
define('DB_USER', 'authorblog');

/** MySQL database password */
define('DB_PASSWORD', 'qL92kZK43t42y3j8');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '9S?^-jvQWi9.ss9Rnb(;t$QDuWHYAmo%u*A*|n9i-B<GB~VvEABsk05x(+[r()M]');
define('SECURE_AUTH_KEY',  '2%gF?y{/iwhUCQ*>,J]hhCCE1$K*W9>XV+nSlJIYpbg`PGm%TaTym$ww_9& ?:E,');
define('LOGGED_IN_KEY',    'AsBEL}p1I!z}+`d}@9JQsIf;X?^{rSf&rO5Rn[@F/Za43OkPt+wj5BN7Yq&W4K|[');
define('NONCE_KEY',        '3Rm-<+q+Ww8b;t5=N3y|b3$M~-Mq|3;q5K<.E(_1V}/DZY6<LkMzorY7f}{OiXh/');
define('AUTH_SALT',        'Q>U[-9g3B{RHr/iI=|(NgVE]E0z5Yq*|+!-8F8W@Ny8V|.PJ;6-+^>3:_} hVZ!O');
define('SECURE_AUTH_SALT', 'CLebsi-}ey*?X5*Gcne%/FK!1a|@-.?3{wI1Hi-+CX@p7~8bO~NmZ_Y%b7i7b6pL');
define('LOGGED_IN_SALT',   'fFdn3DkTCu{>B6MJ#~4@)Ox@63kpK,JIuq!BrH;EvSy$s&M6FqI-lp|9g.6nY)S5');
define('NONCE_SALT',       '@*pp{A=Rib  mMgqDA0A!rYW(V9cgFP7V=;8zA-);Z=UYAOF,W AUb.3Q&m%|7m-');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
