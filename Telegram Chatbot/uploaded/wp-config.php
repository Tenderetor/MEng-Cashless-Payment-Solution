<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'gjmyjgmy_WPECG');

/** MySQL database username */
define('DB_USER', 'gjmyjgmy_WPECG');

/** MySQL database password */
define('DB_PASSWORD', 'PwfUzBq$_0/Fls/5]');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define('AUTH_KEY', 'f67442d58c875a8407679d18339f34981b44febd3dacbbedf29584b95e3eda78');
define('SECURE_AUTH_KEY', 'ddaf3cfdc399b1fb877bc256e05062b99727431f842d3ff1eb30ad6762df7682');
define('LOGGED_IN_KEY', '8c646d58a54249e37a684f34997240e6c0735737e57fbb730c57efe494d239a8');
define('NONCE_KEY', '7393c356d77b45752dcaa205656e8894f9b274da1ebd9defb9f573744f24fab6');
define('AUTH_SALT', '9a4e8b6005dbefe842d6b5934ee50784729d09f8a634a1da534720da87ea3602');
define('SECURE_AUTH_SALT', 'd4734f5ce1ef667dbd49ff0058c91415a83d715977b5f963f638412f6cac9128');
define('LOGGED_IN_SALT', '46680492443f1b8af2d781892fd903cce4c44555f44b902ca0b910b86d9b247f');
define('NONCE_SALT', '92cf72f9b89a43b7b308a553170c8d2088989ce3564b0e5325a05973efe316ec');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'p4n_';
define('WP_CRON_LOCK_TIMEOUT', 120);
define('AUTOSAVE_INTERVAL', 300);
define('WP_POST_REVISIONS', 5);
define('EMPTY_TRASH_DAYS', 7);
define('WP_AUTO_UPDATE_CORE', true);

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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
