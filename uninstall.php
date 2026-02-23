<?php
/**
 * Uninstall RSYI HR System
 * يُستدعى فقط عند حذف البلجن نهائياً من لوحة التحكم.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-hr-db-installer.php';

RSYI_HR\DB_Installer::drop_tables();

delete_option( 'rsyi_hr_roles_version' );
