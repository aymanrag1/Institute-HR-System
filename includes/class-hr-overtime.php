<?php
/**
 * HR Overtime — طلبات العمل الإضافي
 *
 * سير العمل:
 *   1. الموظف يرفع الطلب (draft → pending_manager)
 *   2. المدير المباشر يعتمد (pending_manager → pending_hr)
 *   3. مدير HR يعتمد (pending_hr → approved)
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Overtime {

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_get_overtime_list',   [ __CLASS__, 'ajax_get_list' ] );
        add_action( 'wp_ajax_rsyi_hr_get_overtime',        [ __CLASS__, 'ajax_get_one' ] );
        add_action( 'wp_ajax_rsyi_hr_save_overtime',       [ __CLASS__, 'ajax_save' ] );
        add_action( 'wp_ajax_rsyi_hr_approve_overtime',    [ __CLASS__, 'ajax_approve' ] );
        add_action( 'wp_ajax_rsyi_hr_reject_overtime',     [ __CLASS__, 'ajax_reject' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_overtime',     [ __CLASS__, 'ajax_delete' ] );

        // Portal
        add_action( 'wp_ajax_rsyi_hr_submit_overtime',     [ __CLASS__, 'ajax_submit_portal' ] );
        add_action( 'wp_ajax_rsyi_hr_my_overtime',         [ __CLASS__, 'ajax_my_overtime' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static Helpers
    // ═══════════════════════════════════════════════════════════════════════

    public static function calc_hours( string $from, string $to ): float {
        if ( empty( $from ) || empty( $to ) ) {
            return 0.0;
        }
        $f = strtotime( $from );
        $t = strtotime( $to );
        if ( $t <= $f ) {
            return 0.0;
        }
        return round( ( $t - $f ) / 3600, 2 );
    }

    public static function get_all( array $args = [] ): array {
        global $wpdb;
        $tbl = $wpdb->prefix . 'rsyi_hr_overtime';
        $emp = $wpdb->prefix . 'rsyi_hr_employees';

        $defaults = [
            'status'      => '',
            'employee_id' => 0,
            'per_page'    => 0,
            'page'        => 1,
        ];
        $args   = wp_parse_args( $args, $defaults );
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

        $sql = "SELECT o.*, e.full_name AS employee_name, e.full_name_ar AS employee_name_ar, e.employee_number
                FROM {$tbl} o
                LEFT JOIN {$emp} e ON e.id = o.employee_id
                WHERE {$where}
                ORDER BY o.created_at DESC";

        if ( ! empty( $args['per_page'] ) ) {
            $page   = max( 1, (int) $args['page'] );
            $offset = ( $page - 1 ) * (int) $args['per_page'];
            $sql   .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['per_page'], $offset ); // phpcs:ignore
        }

        return empty( $params )
            ? $wpdb->get_results( $sql, ARRAY_A ) // phpcs:ignore
            : $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ); // phpcs:ignore
    }

    public static function get_by_id( int $id ): ?array {
        global $wpdb;
        $tbl = $wpdb->prefix . 'rsyi_hr_overtime';
        $emp = $wpdb->prefix . 'rsyi_hr_employees';

        $row = $wpdb->get_row(
            $wpdb->prepare( // phpcs:ignore
                "SELECT o.*, e.full_name AS employee_name, e.full_name_ar AS employee_name_ar,
                        e.employee_number, e.signature_url AS employee_signature_img
                 FROM {$tbl} o
                 LEFT JOIN {$emp} e ON e.id = o.employee_id
                 WHERE o.id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    public static function save( array $data ): int|false {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_overtime';

        $from_time = sanitize_text_field( $data['from_time'] ?? '' );
        $to_time   = sanitize_text_field( $data['to_time']   ?? '' );

        if ( empty( $data['work_date'] ) || empty( $from_time ) || empty( $to_time ) ) {
            return false;
        }

        $fields = [
            'employee_id'  => absint( $data['employee_id'] ?? 0 ),
            'work_date'    => sanitize_text_field( $data['work_date'] ),
            'from_time'    => $from_time,
            'to_time'      => $to_time,
            'hours_count'  => self::calc_hours( $from_time, $to_time ),
            'reason'       => sanitize_textarea_field( wp_unslash( $data['reason'] ?? '' ) ),
            'status'       => sanitize_text_field( $data['status'] ?? 'draft' ),
        ];

        if ( empty( $fields['employee_id'] ) ) {
            return false;
        }

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $fields, [ 'id' => absint( $data['id'] ) ] );
            return absint( $data['id'] );
        }

        $wpdb->insert( $table, $fields );
        return (int) $wpdb->insert_id;
    }

    public static function approve( int $id, string $stage, string $signature = '', string $notes = '' ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_overtime';
        $row   = self::get_by_id( $id );
        if ( ! $row ) {
            return false;
        }

        $now    = current_time( 'mysql' );
        $update = [];

        switch ( $stage ) {
            case 'manager':
                if ( 'pending_manager' !== $row['status'] ) {
                    return false;
                }
                // إذا كان المدير المباشر هو نفسه مدير الموارد البشرية → اعتماد مباشر
                $manager_is_hr = false;
                if ( ! empty( $row['manager_id'] ) ) {
                    $manager_emp = Employees::get_by_id( (int) $row['manager_id'] );
                    if ( $manager_emp && ! empty( $manager_emp['user_id'] ) ) {
                        $manager_is_hr = (bool) user_can( (int) $manager_emp['user_id'], 'rsyi_hr_manage_settings' );
                    }
                }
                $update = [
                    'manager_signature' => sanitize_textarea_field( $signature ),
                    'manager_notes'     => sanitize_textarea_field( $notes ),
                    'manager_signed_at' => $now,
                    'status'            => $manager_is_hr ? 'approved' : 'pending_hr',
                ];
                if ( $manager_is_hr ) {
                    $update['hr_manager_signed_at'] = $now;
                }
                break;

            case 'hr_manager':
                if ( 'pending_hr' !== $row['status'] ) {
                    return false;
                }
                $update = [
                    'hr_manager_notes'      => sanitize_textarea_field( $notes ),
                    'hr_manager_signed_at'  => $now,
                    'status'                => 'approved',
                ];
                break;

            default:
                return false;
        }

        return $wpdb->update( $table, $update, [ 'id' => $id ] ) !== false;
    }

    public static function reject( int $id, string $reason = '' ): bool {
        global $wpdb;
        return $wpdb->update( $wpdb->prefix . 'rsyi_hr_overtime', [
            'status'           => 'rejected',
            'rejection_reason' => sanitize_textarea_field( $reason ),
        ], [ 'id' => $id ] ) !== false;
    }

    public static function submit( int $overtime_id ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_overtime';
        $row   = self::get_by_id( $overtime_id );
        if ( ! $row || 'draft' !== $row['status'] ) {
            return false;
        }

        $emp = Employees::get_by_id( (int) $row['employee_id'] );
        if ( ! $emp ) {
            return false;
        }

        $update = [
            'status'            => 'pending_manager',
            'employee_signed_at' => current_time( 'mysql' ),
        ];

        if ( ! empty( $emp['manager_id'] ) ) {
            $update['manager_id'] = (int) $emp['manager_id'];
        }

        return $wpdb->update( $table, $update, [ 'id' => $overtime_id ] ) !== false;
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $wpdb->prefix . 'rsyi_hr_overtime', [ 'id' => $id ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX Handlers
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_get_list(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_overtime' ) || wp_die( -1 );

        $args = [
            'status'      => sanitize_text_field( $_POST['status']      ?? '' ), // phpcs:ignore
            'employee_id' => absint( $_POST['employee_id'] ?? 0 ),               // phpcs:ignore
        ];
        wp_send_json_success( self::get_all( $args ) );
    }

    public static function ajax_get_one(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_overtime' ) || wp_die( -1 );

        $row = self::get_by_id( absint( $_POST['id'] ?? 0 ) ); // phpcs:ignore
        $row ? wp_send_json_success( $row ) : wp_send_json_error();
    }

    public static function ajax_save(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_overtime' ) || wp_die( -1 );

        $data = wp_unslash( $_POST ); // phpcs:ignore
        $id   = self::save( $data );
        $id ? wp_send_json_success( [ 'id' => $id ] ) : wp_send_json_error();
    }

    public static function ajax_approve(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        $id        = absint( $_POST['id']    ?? 0 );                                       // phpcs:ignore
        $stage     = sanitize_text_field( $_POST['stage']     ?? '' );                    // phpcs:ignore
        $signature = sanitize_textarea_field( wp_unslash( $_POST['signature'] ?? '' ) );  // phpcs:ignore
        $notes     = sanitize_textarea_field( wp_unslash( $_POST['notes']     ?? '' ) );  // phpcs:ignore

        current_user_can( 'rsyi_hr_manage_overtime' ) || wp_die( -1 );
        self::approve( $id, $stage, $signature, $notes ) ? wp_send_json_success() : wp_send_json_error();
    }

    public static function ajax_reject(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_overtime' ) || wp_die( -1 );

        $id     = absint( $_POST['id'] ?? 0 );                                         // phpcs:ignore
        $reason = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );    // phpcs:ignore
        self::reject( $id, $reason ) ? wp_send_json_success() : wp_send_json_error();
    }

    public static function ajax_delete(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_overtime' ) || wp_die( -1 );
        self::delete( absint( $_POST['id'] ?? 0 ) ) ? wp_send_json_success() : wp_send_json_error(); // phpcs:ignore
    }

    public static function ajax_submit_portal(): void {
        check_ajax_referer( 'rsyi_hr_portal', 'nonce' );
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error( [ 'message' => __( 'يجب تسجيل الدخول.', 'rsyi-hr' ) ] );
        }

        $emp = Employees::get_by_user_id( $user_id );
        if ( ! $emp ) {
            wp_send_json_error( [ 'message' => __( 'لم يتم ربط حسابك بسجل موظف.', 'rsyi-hr' ) ] );
        }

        $data = [
            'employee_id' => $emp['id'],
            'work_date'   => sanitize_text_field( $_POST['work_date']  ?? '' ), // phpcs:ignore
            'from_time'   => sanitize_text_field( $_POST['from_time']  ?? '' ), // phpcs:ignore
            'to_time'     => sanitize_text_field( $_POST['to_time']    ?? '' ), // phpcs:ignore
            'reason'      => sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ), // phpcs:ignore
            'status'      => 'draft',
        ];

        $ot_id = self::save( $data );
        if ( ! $ot_id ) {
            wp_send_json_error( [ 'message' => __( 'البيانات غير مكتملة.', 'rsyi-hr' ) ] );
        }

        $signature = sanitize_textarea_field( wp_unslash( $_POST['employee_signature'] ?? '' ) ); // phpcs:ignore
        if ( $signature ) {
            global $wpdb;
            $wpdb->update( $wpdb->prefix . 'rsyi_hr_overtime', [
                'employee_signature' => $signature,
            ], [ 'id' => $ot_id ] );
        }

        self::submit( $ot_id );
        wp_send_json_success( [ 'id' => $ot_id, 'message' => __( 'تم رفع طلب العمل الإضافي بنجاح.', 'rsyi-hr' ) ] );
    }

    public static function ajax_my_overtime(): void {
        check_ajax_referer( 'rsyi_hr_portal', 'nonce' );
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error();
        }
        $emp = Employees::get_by_user_id( $user_id );
        if ( ! $emp ) {
            wp_send_json_success( [] );
        }
        wp_send_json_success( self::get_all( [ 'employee_id' => $emp['id'] ] ) );
    }
}
