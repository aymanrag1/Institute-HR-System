<?php
/**
 * HR Permissions Manager — إدارة صلاحيات المستخدمين
 *
 * يتيح تعيين مستوى صلاحية لكل مستخدم على كل وحدة في النظام:
 *   0 = بدون صلاحية
 *   1 = عرض فقط
 *   2 = قراءة
 *   3 = قراءة وكتابة
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Permissions_Manager {

    /** الوحدات المتاحة في النظام */
    public static function get_modules(): array {
        return [
            'dashboard'   => __( 'لوحة التحكم / Dashboard',          'rsyi-hr' ),
            'employees'   => __( 'الموظفون / Employees',              'rsyi-hr' ),
            'departments' => __( 'الأقسام / Departments',             'rsyi-hr' ),
            'job_titles'  => __( 'التقسيم الوظيفي / Job Titles',      'rsyi-hr' ),
            'leaves'      => __( 'طلبات الإجازة / Leave Requests',    'rsyi-hr' ),
            'overtime'    => __( 'الوقت الإضافي / Overtime',          'rsyi-hr' ),
            'attendance'  => __( 'الحضور والانصراف / Attendance',     'rsyi-hr' ),
            'violations'  => __( 'المخالفات والجزاءات / Violations',  'rsyi-hr' ),
            'reports'     => __( 'التقارير / Reports',                 'rsyi-hr' ),
            'settings'    => __( 'الإعدادات / Settings',               'rsyi-hr' ),
            'permissions' => __( 'الصلاحيات / Permissions',            'rsyi-hr' ),
        ];
    }

    /** مستويات الصلاحية */
    public static function get_levels(): array {
        return [
            0 => __( 'بدون صلاحية', 'rsyi-hr' ),
            1 => __( 'عرض فقط',     'rsyi-hr' ),
            2 => __( 'قراءة',        'rsyi-hr' ),
            3 => __( 'قراءة وكتابة','rsyi-hr' ),
        ];
    }

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_get_user_permissions',  [ __CLASS__, 'ajax_get' ] );
        add_action( 'wp_ajax_rsyi_hr_save_user_permissions', [ __CLASS__, 'ajax_save' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static helpers
    // ═══════════════════════════════════════════════════════════════════════

    /** جلب كل صلاحيات مستخدم */
    public static function get_for_user( int $user_id ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_user_permissions';

        $rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT module, permission_level FROM {$table} WHERE user_id = %d", $user_id ), // phpcs:ignore
            ARRAY_A
        );

        $result = array_fill_keys( array_keys( self::get_modules() ), 0 );
        foreach ( $rows as $row ) {
            $result[ $row['module'] ] = (int) $row['permission_level'];
        }

        return $result;
    }

    /** حفظ صلاحيات مستخدم (يستبدل القديمة) */
    public static function save_for_user( int $user_id, array $permissions ): void {
        global $wpdb;
        $table   = $wpdb->prefix . 'rsyi_hr_user_permissions';
        $modules = array_keys( self::get_modules() );

        foreach ( $modules as $module ) {
            $level = isset( $permissions[ $module ] ) ? (int) $permissions[ $module ] : 0;
            $level = max( 0, min( 3, $level ) );

            $exists = $wpdb->get_var( // phpcs:ignore
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE user_id = %d AND module = %s",
                    $user_id, $module
                )
            );

            if ( $exists ) {
                $wpdb->update( $table, [ 'permission_level' => $level ], [ 'id' => (int) $exists ] );
            } else {
                $wpdb->insert( $table, [
                    'user_id'          => $user_id,
                    'module'           => $module,
                    'permission_level' => $level,
                ] );
            }
        }

        do_action( 'rsyi_hr_permissions_updated', $user_id, $permissions );
    }

    /** التحقق من مستوى الصلاحية */
    public static function user_can( int $user_id, string $module, int $required_level = 1 ): bool {
        $perms = self::get_for_user( $user_id );
        return isset( $perms[ $module ] ) && $perms[ $module ] >= $required_level;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_get(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_permissions' ) || wp_die( -1 );

        $user_id = absint( $_POST['user_id'] ?? 0 ); // phpcs:ignore
        if ( ! $user_id ) {
            wp_send_json_error( [ 'message' => __( 'المستخدم غير محدد.', 'rsyi-hr' ) ] );
            return;
        }

        wp_send_json_success( self::get_for_user( $user_id ) );
    }

    public static function ajax_save(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_permissions' ) || wp_die( -1 );

        $user_id = absint( $_POST['user_id'] ?? 0 ); // phpcs:ignore
        if ( ! $user_id ) {
            wp_send_json_error( [ 'message' => __( 'المستخدم غير محدد.', 'rsyi-hr' ) ] );
            return;
        }

        $permissions = isset( $_POST['permissions'] ) && is_array( $_POST['permissions'] ) // phpcs:ignore
                       ? array_map( 'absint', wp_unslash( $_POST['permissions'] ) ) // phpcs:ignore
                       : [];

        self::save_for_user( $user_id, $permissions );
        wp_send_json_success();
    }
}
