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
define( 'DB_NAME', 'foodMarket' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

if ( !defined('WP_CLI') ) {
    define( 'WP_SITEURL', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] );
    define( 'WP_HOME',    $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] );
}



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
define( 'AUTH_KEY',         'Yz0eUhuPWjTMGmSIjVslgY8py3oJcURzBDHknEWfAm0Kr20ticX6T4fBAZJF4Np5' );
define( 'SECURE_AUTH_KEY',  'j5rDRRQNefGRLncYZLbP0qDpNkcKR7NlGSeD8LCQslbYS9rJMP5A3XQva5nVQNUS' );
define( 'LOGGED_IN_KEY',    'O1RbrChi99grOHr1X2wGe0PaH5IJJ2oh2vtvArhih3FltlXSSokJtqwcnVVDOYll' );
define( 'NONCE_KEY',        'XZaUeBBgo89SBoTyvpe8yoXEBaRKavXupjhciQDi5sUR5oYAJKOaYxWEEA3Swuah' );
define( 'AUTH_SALT',        '400yCX62BtCIDS7uBonbIKvhk8dc9MSZoDRnCjMbRvDOgF7aDfD9zjFJFOwjyhAA' );
define( 'SECURE_AUTH_SALT', 'gAhJKD2SgSSfyrDzCMQRM9P6UF5ojK5OWQWUVjwxGPI30hdsEs2mKKjCSMscHzuD' );
define( 'LOGGED_IN_SALT',   '1moaXvoHm5oIxVHgIjOedHGBofdFyvZemosAvvCHCo0cwXDSuM4HduVOgvESBgZI' );
define( 'NONCE_SALT',       'xhvmcbFFuHhBYTnjq9fuONXEMcIDbetqOlefcFXNelYSZzhYONkxT73ABJVBo74l' );

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
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
