<?php
/**
 * Plugin Name:       RSYI HR System
 * Plugin URI:        https://redsea-yacht-institute.com
 * Description:       نظام الموارد البشرية المركزي للمعهد — يوفّر الأقسام والتقسيم الوظيفي وسجل الموظفين كمصدر موحّد لجميع plugins المعهد.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            RSYI Dev Team
 * Author URI:        https://redsea-yacht-institute.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rsyi-hr
 * Domain Path:       /languages
 *
 * @package RSYI_HR
 */

defined( 'ABSPATH' ) || exit;

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'RSYI_HR_VERSION',     '2.0.0' );
define( 'RSYI_HR_PLUGIN_FILE', __FILE__ );
define( 'RSYI_HR_DIR',         plugin_dir_path( __FILE__ ) );
define( 'RSYI_HR_URL',         plugin_dir_url( __FILE__ ) );

// ─── Autoloader ───────────────────────────────────────────────────────────────
spl_autoload_register( static function ( string $class ): void {
    $prefix = 'RSYI_HR\\';
    if ( ! str_starts_with( $class, $prefix ) ) {
        return;
    }

    $map = [
        'DB_Installer' => 'includes/class-hr-db-installer.php',
        'Roles'        => 'includes/class-hr-roles.php',
        'Departments'  => 'includes/class-hr-departments.php',
        'Employees'    => 'includes/class-hr-employees.php',
        'API'          => 'includes/class-hr-api.php',
        'Admin_Menu'   => 'admin/class-hr-admin.php',
    ];

    $relative = substr( $class, strlen( $prefix ) );
    if ( isset( $map[ $relative ] ) ) {
        $file = RSYI_HR_DIR . $map[ $relative ];
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
} );

// ─── تحميل الدوال المساعدة (Procedural API) ──────────────────────────────────
require_once RSYI_HR_DIR . 'includes/class-hr-api.php';

// ─── Activation / Deactivation ───────────────────────────────────────────────
register_activation_hook( __FILE__,   [ 'RSYI_HR\\DB_Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'RSYI_HR\\DB_Installer', 'deactivate' ] );

// ─── Bootstrap ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'rsyi_hr_init' );

function rsyi_hr_init(): void {
    load_plugin_textdomain( 'rsyi-hr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // ── مزامنة الأدوار عند كل تحديث للإصدار ─────────────────────────────
    $stored_ver = get_option( RSYI_HR\Roles::ROLES_VERSION_OPTION, '0.0.0' );
    if ( version_compare( $stored_ver, RSYI_HR_VERSION, '<' ) ) {
        RSYI_HR\Roles::sync_roles();
        RSYI_HR\DB_Installer::create_tables();
    }

    // ── تهيئة الوحدات ────────────────────────────────────────────────────
    RSYI_HR\Departments::init();
    RSYI_HR\Employees::init();
    RSYI_HR\API::init();

    if ( is_admin() ) {
        RSYI_HR\Admin_Menu::init();
    }
}
