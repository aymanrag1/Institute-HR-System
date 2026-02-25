<?php
/**
 * HR Overtime — إدارة طلبات الوقت الإضافي
 *
 * سير العمل:
 *   1. الموظف يرفع الطلب بتوقيعه  → pending
 *   2. المدير المباشر يعتمد         → manager_approved
 *   3. مدير الموارد البشرية يعتمد   → hr_approved  ✓ نهائي
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Overtime {

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_submit_overtime',        [ __CLASS__, 'ajax_submit' ] );
        add_action( 'wp_ajax_rsyi_hr_get_overtime_requests',  [ __CLASS__, 'ajax_get_list' ] );
        add_action( 'wp_ajax_rsyi_hr_get_overtime',           [ __CLASS__, 'ajax_get_one' ] );
        add_action( 'wp_ajax_rsyi_hr_approve_overtime',       [ __CLASS__, 'ajax_approve' ] );
        add_action( 'wp_ajax_rsyi_hr_reject_overtime',        [ __CLASS__, 'ajax_reject' ] );
        add_action( 'wp_ajax_rsyi_hr_get_employee_overtime',  [ __CLASS__, 'ajax_employee_overtime' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static helpers
    // ═══════════════════════════════════════════════════════════════════════

    public static function get_all( array $args = [] ): array {
        global $wpdb;

        $ot  = $wpdb->prefix . 'rsyi_hr_overtime';
        $emp = $wpdb->prefix . 'rsyi_hr_employees';
        $jt  = $wpdb->prefix . 'rsyi_hr_job_titles';

        $defaults = [ 'status' => '', 'employee_id' => 0 ];
        $args     = wp_parse_args( $args, $defaults );

        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND o.status = %s';
            $params[] = $args['status'];
        }
        if ( ! empty( $args['employee_id'] ) ) {
            $where   .= ' AND o.employee_id = %d';
            $params[] = (int) $args['employee_id'];
        }

        $sql = "SELECT o.*, e.full_name, e.full_name_ar, jt.title AS job_title_name
                FROM {$ot} o
                LEFT JOIN {$emp} e  ON e.id  = o.employee_id
                LEFT JOIN {$jt}  jt ON jt.id = e.job_title_id
                WHERE {$where}
                ORDER BY o.created_at DESC";

        return empty( $params )
            ? $wpdb->get_results( $sql, ARRAY_A ) // phpcs:ignore
            : $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ); // phpcs:ignore
    }

    public static function get_by_id( int $id ): ?array {
        global $wpdb;

        $ot   = $wpdb->prefix . 'rsyi_hr_overtime';
        $emp  = $wpdb->prefix . 'rsyi_hr_employees';
        $dept = $wpdb->prefix . 'rsyi_hr_departments';
        $jt   = $wpdb->prefix . 'rsyi_hr_job_titles';

        $row = $wpdb->get_row(
            $wpdb->prepare( // phpcs:ignore
                "SELECT o.*,
                        e.full_name, e.full_name_ar, e.department_id,
                        jt.title AS job_title_name,
                        d.manager_id AS dept_manager_id
                 FROM {$ot} o
                 LEFT JOIN {$emp}  e  ON e.id  = o.employee_id
                 LEFT JOIN {$jt}   jt ON jt.id = e.job_title_id
                 LEFT JOIN {$dept} d  ON d.id  = e.department_id
                 WHERE o.id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /** رفع طلب وقت إضافي */
    public static function submit( array $data ): int|false {
        global $wpdb;

        $employee_id = absint( $data['employee_id'] ?? 0 );
        $work_date   = sanitize_text_field( $data['work_date']  ?? '' );
        $from_time   = sanitize_text_field( $data['from_time']  ?? '' );
        $to_time     = sanitize_text_field( $data['to_time']    ?? '' );

        if ( ! $employee_id || ! $work_date || ! $from_time || ! $to_time ) {
            return false;
        }

        // حساب إجمالي الساعات
        $from_ts     = strtotime( $work_date . ' ' . $from_time );
        $to_ts       = strtotime( $work_date . ' ' . $to_time );
        $total_hours = $to_ts > $from_ts ? round( ( $to_ts - $from_ts ) / 3600, 2 ) : null;

        $manager_id = Leaves::get_direct_manager( $employee_id );
        $status     = ( ! $manager_id || $manager_id === $employee_id ) ? 'manager_approved' : 'pending';

        $fields = [
            'employee_id'       => $employee_id,
            'work_date'         => $work_date,
            'from_time'         => $from_time,
            'to_time'           => $to_time,
            'total_hours'       => $total_hours,
            'reason'            => isset( $data['reason'] ) ? sanitize_textarea_field( $data['reason'] ) : null,
            'status'            => $status,
            'manager_id'        => $manager_id ?: null,
            'employee_signature'=> isset( $data['employee_signature'] ) ? $data['employee_signature'] : null,
        ];

        $wpdb->insert( $wpdb->prefix . 'rsyi_hr_overtime', $fields );
        $id = (int) $wpdb->insert_id;

        if ( $id ) {
            do_action( 'rsyi_hr_overtime_submitted', $id, $fields );
        }

        return $id ?: false;
    }

    /** اعتماد طلب وقت إضافي */
    public static function approve( int $ot_id, int $approver_id, string $signature = '' ): bool|string {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_overtime';
        $ot    = self::get_by_id( $ot_id );

        if ( ! $ot ) {
            return __( 'الطلب غير موجود.', 'rsyi-hr' );
        }

        $now    = current_time( 'mysql' );
        $update = [];

        switch ( $ot['status'] ) {
            case 'pending':
                $update = [
                    'status'              => 'manager_approved',
                    'manager_id'          => $approver_id,
                    'manager_signature'   => $signature,
                    'manager_approved_at' => $now,
                ];
                break;
            case 'manager_approved':
                $update = [
                    'status'        => 'hr_approved',
                    'hr_approved_at'=> $now,
                ];
                break;
            default:
                return __( 'لا يمكن اعتماد هذا الطلب.', 'rsyi-hr' );
        }

        $wpdb->update( $table, $update, [ 'id' => $ot_id ] );
        do_action( 'rsyi_hr_overtime_approved', $ot_id, $update['status'] ?? '', $approver_id );

        return true;
    }

    /** رفض طلب وقت إضافي */
    public static function reject( int $ot_id, int $rejector_id, string $reason = '' ): bool {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'rsyi_hr_overtime',
            [
                'status'           => 'rejected',
                'rejection_reason' => sanitize_textarea_field( $reason ),
            ],
            [ 'id' => $ot_id ]
        );

        return (bool) $result;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_submit(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );

        $data = wp_unslash( $_POST ); // phpcs:ignore

        if ( current_user_can( 'rsyi_hr_manage_overtime' ) ) {
            $employee_id = absint( $data['employee_id'] ?? 0 );
        } else {
            current_user_can( 'rsyi_hr_submit_overtime' ) || wp_die( -1 );
            $emp = Employees::get_by_user_id( get_current_user_id() );
            if ( ! $emp ) {
                wp_send_json_error( [ 'message' => __( 'لا يوجد سجل موظف مرتبط بحسابك.', 'rsyi-hr' ) ] );
                return;
            }
            $employee_id = (int) $emp['id'];
        }

        $data['employee_id'] = $employee_id;
        $id = self::submit( $data );

        false === $id
            ? wp_send_json_error( [ 'message' => __( 'بيانات الطلب غير مكتملة.', 'rsyi-hr' ) ] )
            : wp_send_json_success( [ 'id' => $id ] );
    }

    public static function ajax_get_list(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_view_overtime' ) || wp_die( -1 );

        $args = [
            'status'      => sanitize_text_field( $_POST['status']      ?? '' ), // phpcs:ignore
            'employee_id' => absint( $_POST['employee_id'] ?? 0 ),               // phpcs:ignore
        ];

        wp_send_json_success( self::get_all( $args ) );
    }

    public static function ajax_get_one(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_view_overtime' ) || wp_die( -1 );

        $id  = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        $row = self::get_by_id( $id );

        $row ? wp_send_json_success( $row )
             : wp_send_json_error( [ 'message' => __( 'الطلب غير موجود.', 'rsyi-hr' ) ] );
    }

    public static function ajax_approve(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_overtime' ) || wp_die( -1 );

        $id        = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        $signature = isset( $_POST['signature'] ) ? wp_unslash( $_POST['signature'] ) : ''; // phpcs:ignore

        $result = self::approve( $id, get_current_user_id(), $signature );

        is_string( $result )
            ? wp_send_json_error( [ 'message' => $result ] )
            : wp_send_json_success();
    }

    public static function ajax_reject(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_overtime' ) || wp_die( -1 );

        $id     = absint( $_POST['id']     ?? 0 ); // phpcs:ignore
        $reason = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ); // phpcs:ignore

        self::reject( $id, get_current_user_id(), $reason )
            ? wp_send_json_success()
            : wp_send_json_error( [ 'message' => __( 'تعذّر الرفض.', 'rsyi-hr' ) ] );
    }

    public static function ajax_employee_overtime(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_portal_access' ) || wp_die( -1 );

        $emp = Employees::get_by_user_id( get_current_user_id() );
        if ( ! $emp ) {
            wp_send_json_error( [ 'message' => __( 'لا يوجد سجل موظف.', 'rsyi-hr' ) ] );
            return;
        }

        wp_send_json_success( self::get_all( [ 'employee_id' => (int) $emp['id'] ] ) );
    }
}
