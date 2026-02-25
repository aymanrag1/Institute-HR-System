<?php
/**
 * HR User Permissions Manager — صلاحيات المستخدمين
 *
 * نظام صلاحيات لكل مستخدم على كل وحدة في النظام:
 *   none | view | read | read_write
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Permissions_Mgr {

    /** وحدات النظام مع تسمياتها */
    public static function get_modules(): array {
        return [
            'dashboard'  => [ 'ar' => 'لوحة التحكم',          'en' => 'Dashboard' ],
            'employees'  => [ 'ar' => 'الموظفون',              'en' => 'Employees' ],
            'departments'=> [ 'ar' => 'الأقسام',               'en' => 'Departments' ],
            'job_titles' => [ 'ar' => 'التقسيم الوظيفي',       'en' => 'Job Titles' ],
            'leaves'     => [ 'ar' => 'طلبات الإجازة',         'en' => 'Leave Requests' ],
            'overtime'   => [ 'ar' => 'العمل الإضافي',         'en' => 'Overtime' ],
            'attendance' => [ 'ar' => 'الحضور والانصراف',      'en' => 'Attendance' ],
            'violations' => [ 'ar' => 'المخالفات والجزاءات',   'en' => 'Violations' ],
            'reports'    => [ 'ar' => 'التقارير',              'en' => 'Reports' ],
            'settings'   => [ 'ar' => 'الإعدادات',             'en' => 'Settings' ],
            'permissions'=> [ 'ar' => 'الصلاحيات',            'en' => 'Permissions' ],
        ];
    }

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_load_user_permissions', [ __CLASS__, 'ajax_load' ] );
        add_action( 'wp_ajax_rsyi_hr_save_user_permissions', [ __CLASS__, 'ajax_save' ] );
        add_action( 'wp_ajax_rsyi_hr_reset_user_permissions',[ __CLASS__, 'ajax_reset' ] );
    }

    /**
     * جلب صلاحيات مستخدم معين.
     */
    public static function get_for_user( int $user_id ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_user_permissions';

        $rows   = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
            "SELECT module, permission FROM {$table} WHERE user_id = %d",
            $user_id
        ), ARRAY_A );

        $perms = [];
        foreach ( $rows as $row ) {
            $perms[ $row['module'] ] = $row['permission'];
        }

        // defaults: none for every module
        foreach ( array_keys( self::get_modules() ) as $mod ) {
            if ( ! isset( $perms[ $mod ] ) ) {
                $perms[ $mod ] = 'none';
            }
        }

        return $perms;
    }

    /**
     * حفظ صلاحيات مستخدم.
     * $permissions = [ 'module_key' => 'none|view|read|read_write' ]
     */
    public static function save_for_user( int $user_id, array $permissions ): void {
        global $wpdb;
        $table       = $wpdb->prefix . 'rsyi_hr_user_permissions';
        $valid_perms = [ 'none', 'view', 'read', 'read_write' ];
        $modules     = array_keys( self::get_modules() );

        foreach ( $modules as $mod ) {
            $perm = sanitize_text_field( $permissions[ $mod ] ?? 'none' );
            if ( ! in_array( $perm, $valid_perms, true ) ) {
                $perm = 'none';
            }

            $wpdb->query( $wpdb->prepare( // phpcs:ignore
                "INSERT INTO {$table} (user_id, module, permission)
                 VALUES (%d, %s, %s)
                 ON DUPLICATE KEY UPDATE permission = VALUES(permission), updated_at = NOW()",
                $user_id, $mod, $perm
            ) );
        }
    }

    /**
     * إعادة ضبط صلاحيات مستخدم على none.
     */
    public static function reset_user( int $user_id ): void {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'rsyi_hr_user_permissions', [ 'user_id' => $user_id ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX Handlers
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_load(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_settings' ) || wp_die( -1 );

        $user_id = absint( $_POST['user_id'] ?? 0 ); // phpcs:ignore
        if ( ! $user_id ) {
            wp_send_json_error( [ 'message' => __( 'يجب اختيار مستخدم.', 'rsyi-hr' ) ] );
        }

        wp_send_json_success( [
            'permissions' => self::get_for_user( $user_id ),
            'modules'     => self::get_modules(),
        ] );
    }

    public static function ajax_save(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_settings' ) || wp_die( -1 );

        $user_id     = absint( $_POST['user_id'] ?? 0 );                   // phpcs:ignore
        $permissions = $_POST['permissions'] ?? [];                         // phpcs:ignore
        if ( ! $user_id || ! is_array( $permissions ) ) {
            wp_send_json_error();
        }

        self::save_for_user( $user_id, array_map( 'sanitize_text_field', $permissions ) );
        wp_send_json_success( [ 'message' => __( 'تم حفظ الصلاحيات بنجاح.', 'rsyi-hr' ) ] );
    }

    public static function ajax_reset(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_settings' ) || wp_die( -1 );

        $user_id = absint( $_POST['user_id'] ?? 0 ); // phpcs:ignore
        if ( ! $user_id ) {
            wp_send_json_error();
        }
        self::reset_user( $user_id );
        wp_send_json_success( [ 'message' => __( 'تم إعادة ضبط الصلاحيات.', 'rsyi-hr' ) ] );
    }
}
