<?php
/**
 * HR Leave Balance — رصيد الإجازات
 *
 * يدير رصيد الإجازات لكل موظف (إجمالي الأيام المسموح بها سنوياً).
 * الأيام المستخدمة تُحسب من جدول rsyi_hr_leaves (الطلبات المعتمدة).
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Leave_Balance {

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_get_employee_balances', [ __CLASS__, 'ajax_get_employee_balances' ] );
        add_action( 'wp_ajax_rsyi_hr_set_leave_balance',     [ __CLASS__, 'ajax_set_balance' ] );
        add_action( 'wp_ajax_rsyi_hr_get_all_balances',      [ __CLASS__, 'ajax_get_all_balances' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static Helpers
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * إجمالي الأيام المخصصة للموظف لنوع إجازة وسنة معينة.
     */
    public static function get_balance( int $employee_id, string $leave_type, int $year ): int {
        global $wpdb;
        $tbl = $wpdb->prefix . 'rsyi_hr_leave_balances';
        $val = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
            "SELECT total_days FROM {$tbl} WHERE employee_id = %d AND leave_type = %s AND year = %d",
            $employee_id, $leave_type, $year
        ) );
        return (int) $val;
    }

    /**
     * الأيام المستخدمة فعلاً (من الطلبات المعتمدة).
     */
    public static function get_used_days( int $employee_id, string $leave_type, int $year ): int {
        global $wpdb;
        $tbl = $wpdb->prefix . 'rsyi_hr_leaves';
        $val = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
            "SELECT COALESCE(SUM(days_count),0) FROM {$tbl}
             WHERE employee_id = %d AND leave_type = %s
               AND status = 'approved'
               AND YEAR(from_date) = %d",
            $employee_id, $leave_type, $year
        ) );
        return (int) $val;
    }

    /**
     * الرصيد المتبقي.
     */
    public static function get_remaining( int $employee_id, string $leave_type, int $year ): int {
        return self::get_balance( $employee_id, $leave_type, $year ) - self::get_used_days( $employee_id, $leave_type, $year );
    }

    /**
     * تعيين / تحديث رصيد الإجازات (upsert).
     */
    public static function set_balance( int $employee_id, string $leave_type, int $year, int $total_days ): bool {
        global $wpdb;
        $tbl = $wpdb->prefix . 'rsyi_hr_leave_balances';

        $existing = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
            "SELECT id FROM {$tbl} WHERE employee_id = %d AND leave_type = %s AND year = %d",
            $employee_id, $leave_type, $year
        ) );

        if ( $existing ) {
            return $wpdb->update( $tbl, [ 'total_days' => $total_days ], [ 'id' => (int) $existing ] ) !== false;
        }

        return $wpdb->insert( $tbl, [
            'employee_id' => $employee_id,
            'leave_type'  => $leave_type,
            'year'        => $year,
            'total_days'  => $total_days,
        ] ) !== false;
    }

    /**
     * جميع أرصدة الموظف لسنة معينة (كل أنواع الإجازات).
     */
    public static function get_all_for_employee( int $employee_id, int $year ): array {
        $types  = [ 'regular', 'sick', 'casual', 'unpaid' ];
        $result = [];
        foreach ( $types as $type ) {
            $total     = self::get_balance( $employee_id, $type, $year );
            $used      = self::get_used_days( $employee_id, $type, $year );
            $result[ $type ] = [
                'total'     => $total,
                'used'      => $used,
                'remaining' => $total - $used,
            ];
        }
        return $result;
    }

    /**
     * جميع الأرصدة المسجلة لسنة معينة (كل الموظفين).
     */
    public static function get_all_for_year( int $year ): array {
        global $wpdb;
        $tbl = $wpdb->prefix . 'rsyi_hr_leave_balances';
        $emp = $wpdb->prefix . 'rsyi_hr_employees';

        return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
            "SELECT b.*, e.full_name, e.full_name_ar, e.employee_number
             FROM {$tbl} b
             LEFT JOIN {$emp} e ON e.id = b.employee_id
             WHERE b.year = %d
             ORDER BY e.full_name ASC",
            $year
        ), ARRAY_A );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX Handlers
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_get_employee_balances(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_leaves' ) || wp_die( -1 );

        $employee_id = absint( $_POST['employee_id'] ?? 0 ); // phpcs:ignore
        $year        = absint( $_POST['year']        ?? date( 'Y' ) ); // phpcs:ignore

        if ( ! $employee_id ) {
            wp_send_json_error();
        }

        wp_send_json_success( self::get_all_for_employee( $employee_id, $year ) );
    }

    public static function ajax_set_balance(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_settings' ) || wp_die( -1 );

        $employee_id = absint( $_POST['employee_id'] ?? 0 );          // phpcs:ignore
        $leave_type  = sanitize_text_field( $_POST['leave_type'] ?? '' ); // phpcs:ignore
        $year        = absint( $_POST['year']        ?? date( 'Y' ) ); // phpcs:ignore
        $total_days  = absint( $_POST['total_days']  ?? 0 );          // phpcs:ignore

        $valid_types = [ 'regular', 'sick', 'casual', 'unpaid' ];
        if ( ! $employee_id || ! in_array( $leave_type, $valid_types, true ) || ! $year ) {
            wp_send_json_error( [ 'message' => __( 'بيانات غير صحيحة.', 'rsyi-hr' ) ] );
        }

        self::set_balance( $employee_id, $leave_type, $year, $total_days )
            ? wp_send_json_success()
            : wp_send_json_error();
    }

    public static function ajax_get_all_balances(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_settings' ) || wp_die( -1 );

        $year = absint( $_POST['year'] ?? date( 'Y' ) ); // phpcs:ignore
        wp_send_json_success( self::get_all_for_year( $year ) );
    }
}
