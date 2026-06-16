<?php

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'cms-new-imperia');

/** Database username */
define('DB_USER', 'root');

/** Database password */
define('DB_PASSWORD', '');

/** Database hostname */
define('DB_HOST', 'MySQL-8.0');

/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The database collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         ':uE%h5eaQVn7DwUJM!^@2!#w-~rOT(n2.){Dk6esq{g(]+`osc*=K,k?=wjwN2X7');
define('SECURE_AUTH_KEY',  'WC1Ri&H+t5B,}<?KI6SR7$Q/iPR;B;3eC.4!)0eT(SQ;;m6P[p~kF^yaDnqygwo<');
define('LOGGED_IN_KEY',    '2mBrw);]{PTMeC~acC4&S6Lye+rQM(z]b4 f,&o[4yKP-)tsv$2>wyLk?[8q!|4N');
define('NONCE_KEY',        'R]4!/`yi@FWn lP.KkQ$5:CT4]Fnh{#GI)wf_j9H~5WX~_hJY$~{y[y&:FUyJ?3.');
define('AUTH_SALT',        'PHPaSqqRm.i!tS2G2~OAC}+k^Kg*JmAVE>?G%*ED(<_viR!f>Fz)6J6aPu%RU7{z');
define('SECURE_AUTH_SALT', '%R~,GSdWLhQ9_)_QFP{2C#o`ZK3]AqC~nDvFzGVEuGc{SMsb7aZ^PK2>v8JmZFF6');
define('LOGGED_IN_SALT',   'P~i};]S-Q~eHRyNF6rs}g)K~m<HOu[f_)o};k*aq0guN%^VZZxkXH^x=KpdoO9Fp');
define('NONCE_SALT',       'P$^+T0Suqc1Wh`,B^*WI9o5TDCi]fD:3,ThgXc$;VFw:0;zvyFvr2&H.)Fxst^^A');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', false);

//define('WP_DEBUG', true);
//define('WP_DEBUG_LOG', true);
//define('WP_DEBUG_DISPLAY', false);

/* Add any custom values between this line and the "stop editing" line. */

/**
 * Отключить встроенный редактор
 */
define('DISALLOW_FILE_EDIT', true);

/**
 * Отключить автоматические обновления WordPress.
 */
define('AUTOMATIC_UPDATER_DISABLED', true);

/**
 * Отключить автоматические обновления ядра WordPress.
 */
define('WP_AUTO_UPDATE_CORE', false);


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (! defined('ABSPATH')) {
	define('ABSPATH', __DIR__ . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
