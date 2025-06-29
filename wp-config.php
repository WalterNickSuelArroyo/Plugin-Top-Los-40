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
define( 'DB_NAME', 'bd_plugin_votacion' );

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
define( 'AUTH_KEY',         ',rcCbCR_mRn6M 9j{]p0e|$-I]/5(*]#KShOW,j0[2?<e6gS!}48b9g^ @[tPJDK' );
define( 'SECURE_AUTH_KEY',  '|7}3# %5/O`.P$0JJmqogdV=q{TuPgO#H{ufHJ>a#uA ry;{HUEfEf`l5C*lj<B0' );
define( 'LOGGED_IN_KEY',    '%VTDt4m;H[|qI/%wGkhQV|T5kZ*=KTZuyUXf%y-hG47v7V:-HtukW7{AM4M.:jl5' );
define( 'NONCE_KEY',        '5P8Ys&J$`L&u*R;pp:8T^58C7_4yO<Kj/&o_j@!-R^Qv%AsVkYM{~{ntysTPW*[j' );
define( 'AUTH_SALT',        'K-XPqIX1mn][6DMvnX[n vv6b_CK:wA4T+_6wdT[rp_=[r@d`p==Y#Nj7oMMHlGz' );
define( 'SECURE_AUTH_SALT', 'wr0~%UWp6dSH+}!A1/4n^P]VViCMtvPC>s&TkJ cs`78^ZYEc%]pyu9gy<=PVM:A' );
define( 'LOGGED_IN_SALT',   'q5x@iCD)6(coG){X8/QL;?)Vt^>.9*Nbhg6BIH`6C{ldE?&OZ9U&.InEa;,h;I}<' );
define( 'NONCE_SALT',       ',` ]#k?3q$8P%cmN[m#[[Foz>$Q8kid6dEW $B+ViGH5%plja87$`@b5#;,t|K%.' );

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
