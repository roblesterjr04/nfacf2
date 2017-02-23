<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'nfacfdb');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

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
define('AUTH_KEY',         'rj/WU:8VeoajB#5n`rN6)Y;B12gBr/GM9_Ey`&o*p#zUjRmCWhIgRmnAzFny|mEb');
define('SECURE_AUTH_KEY',  'x*)@?#IHZWk5hT:MVao|lWJT/FZkg:bH%AmDsZOyzY5NE6Ued(iu67Q`5gb)8aMv');
define('LOGGED_IN_KEY',    'F*Wl4vO2Qqp6M_IYC"~$cr22cc@"5Vtv2CqXwDcGps@)HH6xd$RaOWFG!7wbsCD"');
define('NONCE_KEY',        '`uc2QUe~spf:V&(TV+`_fS!popa&Dz7iZuQAg0zMxc8??ZSe)(xAO`K7+q?4c~Et');
define('AUTH_SALT',        'm;mg6hW3!ks0$BZJ!7"OpwSX/xY$U(_q%HWt5An$~1G5zlty%`theU7ct$N0&#JY');
define('SECURE_AUTH_SALT', '7i1b#0q!3trpWwJFg&HtF7d!qocL&r*YBS7cpbxIxsM|nvM^"62fd4E_E1Z+bIUh');
define('LOGGED_IN_SALT',   'FFeffZyT+DE$"vm/AW7qISz?Fpo%R8YAM"JX);y__TDO/DqPIIXf!i%YPz9bvBB#');
define('NONCE_SALT',       'jIEPzM5NggWi$T!mpRUTIdUsnv`xY%%#smSlruz2GdY7@I9#;l3GW;wyik&Ok"R?');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_env2vg_';

/**
 * Limits total Post Revisions saved per Post/Page.
 * Change or comment this line out if you would like to increase or remove the limit.
 */
define('WP_POST_REVISIONS',  10);

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

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

