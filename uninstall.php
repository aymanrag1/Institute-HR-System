<?php
/**
 * Uninstall RSYI HR System
 *
 * يُستدعى فقط عند حذف البلجن نهائياً من لوحة التحكم.
 *
 * ⚠️ لحماية البيانات: لن تُحذف الجداول إلا إذا قام المسؤول بتفعيل
 *    خيار "حذف البيانات عند الإزالة" من إعدادات البلجن.
 *
 * الخيار محفوظ في: wp_options → rsyi_hr_delete_data_on_uninstall = '1'
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// ─── حماية الداتا: لا حذف إلا بموافقة صريحة ────────────────────────────────
$delete_data = get_option( 'rsyi_hr_delete_data_on_uninstall', '0' );

if ( '1' !== $delete_data ) {
    // الخيار غير مفعَّل → نحذف فقط الـ options ونترك الجداول سليمة
    delete_option( 'rsyi_hr_roles_version' );
    delete_option( 'rsyi_hr_db_version' );
    // لا نحذف: rsyi_hr_delete_data_on_uninstall حتى يبقى الإعداد محفوظاً
    return;
}

// ─── الخيار مفعَّل → حذف كامل للجداول والبيانات ────────────────────────────
require_once plugin_dir_path( __FILE__ ) . 'includes/class-hr-db-installer.php';

RSYI_HR\DB_Installer::drop_tables();

delete_option( 'rsyi_hr_roles_version' );
delete_option( 'rsyi_hr_db_version' );
delete_option( 'rsyi_hr_delete_data_on_uninstall' );
