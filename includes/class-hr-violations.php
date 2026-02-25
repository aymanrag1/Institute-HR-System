<?php
/**
 * HR Violations — المخالفات والجزاءات
 *
 * سير العمل:
 *   1. مدير HR يُنشئ المخالفة (draft → pending_dean)
 *   2. عميد المعهد يعتمد (pending_dean → approved)
 *   - تظهر في شاشة الموظف بعد الاعتماد
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Violations {

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_get_violations',    [ __CLASS__, 'ajax_get_list' ] );
        add_action( 'wp_ajax_rsyi_hr_get_violation',     [ __CLASS__, 'ajax_get_one' ] );
        add_action( 'wp_ajax_rsyi_hr_save_violation',    [ __CLASS__, 'ajax_save' ] );
        add_action( 'wp_ajax_rsyi_hr_approve_violation', [ __CLASS__, 'ajax_approve' ] );
        add_action( 'wp_ajax_rsyi_hr_reject_violation',  [ __CLASS__, 'ajax_reject' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_violation',  [ __CLASS__, 'ajax_delete' ] );

        // Portal — employee views their own violations
        add_action( 'wp_ajax_rsyi_hr_my_violations',     [ __CLASS__, 'ajax_my_violations' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static Helpers
    // ═══════════════════════════════════════════════════════════════════════

    public static function get_all( array $args = [] ): array {
        global $wpdb;
        $tbl = $wpdb->prefix . 'rsyi_hr_violations';
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
            $where   .= ' AND v.status = %s';
            $params[] = $args['status'];
        }
        if ( ! empty( $args['employee_id'] ) ) {
            $where   .= ' AND v.employee_id = %d';
            $params[] = (int) $args['employee_id'];
        }

        $sql = "SELECT v.*, e.full_name AS employee_name, e.full_name_ar AS employee_name_ar, e.employee_number
                FROM {$tbl} v
                LEFT JOIN {$emp} e ON e.id = v.employee_id
                WHERE {$where}
                ORDER BY v.violation_date DESC";

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
        $tbl = $wpdb->prefix . 'rsyi_hr_violations';
        $emp = $wpdb->prefix . 'rsyi_hr_employees';

        $row = $wpdb->get_row(
            $wpdb->prepare( // phpcs:ignore
                "SELECT v.*, e.full_name AS employee_name, e.full_name_ar AS employee_name_ar, e.employee_number
                 FROM {$tbl} v
                 LEFT JOIN {$emp} e ON e.id = v.employee_id
                 WHERE v.id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    public static function save( array $data ): int|false {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_violations';

        $fields = [
            'employee_id'     => absint( $data['employee_id']     ?? 0 ),
            'violation_type'  => sanitize_text_field( $data['violation_type']  ?? '' ),
            'violation_date'  => sanitize_text_field( $data['violation_date']  ?? '' ),
            'description'     => sanitize_textarea_field( wp_unslash( $data['description']   ?? '' ) ),
            'penalty_type'    => sanitize_text_field( $data['penalty_type']    ?? '' ),
            'penalty_value'   => sanitize_text_field( $data['penalty_value']   ?? '' ),
            'hr_manager_id'   => get_current_user_id() ?: null,
            'hr_manager_notes'=> sanitize_textarea_field( wp_unslash( $data['hr_manager_notes'] ?? '' ) ),
            'status'          => sanitize_text_field( $data['status'] ?? 'draft' ),
        ];

        if ( empty( $fields['employee_id'] ) || empty( $fields['violation_type'] ) || empty( $fields['violation_date'] ) ) {
            return false;
        }

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $fields, [ 'id' => absint( $data['id'] ) ] );
            return absint( $data['id'] );
        }

        $wpdb->insert( $table, $fields );
        return (int) $wpdb->insert_id;
    }

    /**
     * رفع المخالفة للاعتماد من قِبَل عميد المعهد.
     */
    public static function submit_for_dean( int $id ): bool {
        global $wpdb;
        $row = self::get_by_id( $id );
        if ( ! $row || 'draft' !== $row['status'] ) {
            return false;
        }
        return $wpdb->update( $wpdb->prefix . 'rsyi_hr_violations', [
            'status'         => 'pending_dean',
            'hr_applied_at'  => current_time( 'mysql' ),
        ], [ 'id' => $id ] ) !== false;
    }

    /**
     * اعتماد العميد.
     */
    public static function approve( int $id, string $notes = '' ): bool {
        global $wpdb;
        $row = self::get_by_id( $id );
        if ( ! $row || 'pending_dean' !== $row['status'] ) {
            return false;
        }
        $result = $wpdb->update( $wpdb->prefix . 'rsyi_hr_violations', [
            'status'         => 'approved',
            'dean_notes'     => sanitize_textarea_field( $notes ),
            'dean_signed_at' => current_time( 'mysql' ),
        ], [ 'id' => $id ] );

        if ( $result !== false ) {
            do_action( 'rsyi_hr_violation_approved', $id );
        }
        return $result !== false;
    }

    public static function reject( int $id, string $reason = '' ): bool {
        global $wpdb;
        return $wpdb->update( $wpdb->prefix . 'rsyi_hr_violations', [
            'status'           => 'rejected',
            'rejection_reason' => sanitize_textarea_field( $reason ),
        ], [ 'id' => $id ] ) !== false;
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $wpdb->prefix . 'rsyi_hr_violations', [ 'id' => $id ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX Handlers
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_get_list(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_violations' ) || wp_die( -1 );

        $args = [
            'status'      => sanitize_text_field( $_POST['status']      ?? '' ), // phpcs:ignore
            'employee_id' => absint( $_POST['employee_id'] ?? 0 ),               // phpcs:ignore
        ];
        wp_send_json_success( self::get_all( $args ) );
    }

    public static function ajax_get_one(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_violations' ) || wp_die( -1 );

        $row = self::get_by_id( absint( $_POST['id'] ?? 0 ) ); // phpcs:ignore
        $row ? wp_send_json_success( $row ) : wp_send_json_error();
    }

    public static function ajax_save(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_violations' ) || wp_die( -1 );

        $data = wp_unslash( $_POST ); // phpcs:ignore
        $id   = self::save( $data );

        if ( $id && ! empty( $data['submit_for_dean'] ) ) {
            self::submit_for_dean( $id );
        }

        $id ? wp_send_json_success( [ 'id' => $id ] ) : wp_send_json_error( [ 'message' => __( 'البيانات ناقصة.', 'rsyi-hr' ) ] );
    }

    public static function ajax_approve(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_dean_approve' ) || wp_die( -1 );

        $id    = absint( $_POST['id'] ?? 0 );                                     // phpcs:ignore
        $notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );  // phpcs:ignore
        self::approve( $id, $notes ) ? wp_send_json_success() : wp_send_json_error();
    }

    public static function ajax_reject(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_dean_approve' ) || current_user_can( 'rsyi_hr_manage_violations' ) || wp_die( -1 );

        $id     = absint( $_POST['id'] ?? 0 );                                        // phpcs:ignore
        $reason = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );    // phpcs:ignore
        self::reject( $id, $reason ) ? wp_send_json_success() : wp_send_json_error();
    }

    public static function ajax_delete(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_violations' ) || wp_die( -1 );

        self::delete( absint( $_POST['id'] ?? 0 ) ) ? wp_send_json_success() : wp_send_json_error(); // phpcs:ignore
    }

    public static function ajax_my_violations(): void {
        check_ajax_referer( 'rsyi_hr_portal', 'nonce' );
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error();
        }
        $emp = Employees::get_by_user_id( $user_id );
        if ( ! $emp ) {
            wp_send_json_success( [] );
        }
        wp_send_json_success( self::get_all( [ 'employee_id' => $emp['id'], 'status' => 'approved' ] ) );
    }
}
