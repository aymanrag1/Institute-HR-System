<?php
/**
 * Plugin Name:       RSYI HR System
 * Plugin URI:        https://redsea-yacht-institute.com
 * Description:       نظام الموارد البشرية المركزي لمعهد البحر الأحمر لليخوت — يشمل إدارة الموظفين، طلبات الإجازة، العمل الإضافي، الحضور والانصراف، المخالفات والجزاءات، وبوابة الموظف.
 * Version:           2.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            AYMAN RAGAB
 * Author URI:        tel:+201159230034
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rsyi-hr
 * Domain Path:       /languages
 *
 * Developer: AYMAN RAGAB | Mobile: +201159230034
 *
 * @package RSYI_HR
 */

defined( 'ABSPATH' ) || exit;

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'RSYI_HR_VERSION',     '2.2.0' );
define( 'RSYI_HR_PLUGIN_FILE', __FILE__ );
define( 'RSYI_HR_DIR',         plugin_dir_path( __FILE__ ) );
define( 'RSYI_HR_URL',         plugin_dir_url( __FILE__ ) );
define( 'RSYI_HR_DEVELOPER',   'AYMAN RAGAB' );
define( 'RSYI_HR_DEV_MOBILE',  '+201159230034' );

// ─── Autoloader ───────────────────────────────────────────────────────────────
spl_autoload_register( static function ( string $class ): void {
    $prefix = 'RSYI_HR\\';
    if ( ! str_starts_with( $class, $prefix ) ) {
        return;
    }

    $map = [
        'DB_Installer'   => 'includes/class-hr-db-installer.php',
        'Roles'          => 'includes/class-hr-roles.php',
        'Departments'    => 'includes/class-hr-departments.php',
        'Employees'      => 'includes/class-hr-employees.php',
        'Leaves'         => 'includes/class-hr-leaves.php',
        'Overtime'       => 'includes/class-hr-overtime.php',
        'Attendance'     => 'includes/class-hr-attendance.php',
        'Violations'     => 'includes/class-hr-violations.php',
        'Permissions_Mgr'=> 'includes/class-hr-permissions-mgr.php',
        'Leave_Balance'  => 'includes/class-hr-leave-balance.php',
        'Portal'         => 'includes/class-hr-portal.php',
        'API'            => 'includes/class-hr-api.php',
        'Admin_Menu'     => 'admin/class-hr-admin.php',
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

    // ── مزامنة الأدوار والجداول عند كل تحديث ────────────────────────────
    $stored_ver = get_option( RSYI_HR\Roles::ROLES_VERSION_OPTION, '0.0.0' );
    if ( version_compare( $stored_ver, RSYI_HR_VERSION, '<' ) ) {
        RSYI_HR\Roles::sync_roles();
        RSYI_HR\DB_Installer::create_tables();
    }

    // ── تهيئة الوحدات الأساسية ───────────────────────────────────────────
    RSYI_HR\Departments::init();
    RSYI_HR\Employees::init();
    RSYI_HR\API::init();

    // ── وحدات v2.1.0 ─────────────────────────────────────────────────────
    RSYI_HR\Leaves::init();
    RSYI_HR\Overtime::init();
    RSYI_HR\Attendance::init();
    RSYI_HR\Violations::init();
    RSYI_HR\Permissions_Mgr::init();
    RSYI_HR\Leave_Balance::init();
    RSYI_HR\Portal::init();

    // ── لوحة التحكم الإدارية ────────────────────────────────────────────
    if ( is_admin() ) {
        RSYI_HR\Admin_Menu::init();
    }
}
