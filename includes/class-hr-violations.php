<?php
/**
 * HR Violations — المخالفات والجزاءات
 *
 * سير العمل:
 *   1. مدير الموارد البشرية ينشئ المخالفة  → pending
 *   2. العميد يعتمد                         → approved ✓
 *   3. يظهر في بوابة الموظف
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Violations {

    // أنواع المخالفات القياسية
    public static function get_types(): array {
        return [
            'late'            => __( 'تأخير / Late',           'rsyi-hr' ),
            'absent'          => __( 'غياب / Absent',          'rsyi-hr' ),
            'misconduct'      => __( 'سلوك مخالف / Misconduct','rsyi-hr' ),
            'negligence'      => __( 'إهمال / Negligence',      'rsyi-hr' ),
            'policy_breach'   => __( 'مخالفة لائحة / Policy',  'rsyi-hr' ),
            'other'           => __( 'أخرى / Other',            'rsyi-hr' ),
        ];
    }

    // أنواع الجزاءات
    public static function get_penalty_types(): array {
        return [
            'warning'         => __( 'إنذار / Warning',              'rsyi-hr' ),
            'deduction'       => __( 'خصم من المرتب / Deduction',    'rsyi-hr' ),
            'suspension'      => __( 'إيقاف / Suspension',           'rsyi-hr' ),
            'demotion'        => __( 'تنزيل درجة / Demotion',        'rsyi-hr' ),
            'termination'     => __( 'إنهاء خدمة / Termination',     'rsyi-hr' ),
        ];
    }

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_save_violation',        [ __CLASS__, 'ajax_save' ] );
        add_action( 'wp_ajax_rsyi_hr_get_violations',        [ __CLASS__, 'ajax_get_list' ] );
        add_action( 'wp_ajax_rsyi_hr_get_violation',         [ __CLASS__, 'ajax_get_one' ] );
        add_action( 'wp_ajax_rsyi_hr_approve_violation',     [ __CLASS__, 'ajax_approve' ] );
        add_action( 'wp_ajax_rsyi_hr_reject_violation',      [ __CLASS__, 'ajax_reject' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_violation',      [ __CLASS__, 'ajax_delete' ] );
        add_action( 'wp_ajax_rsyi_hr_get_employee_violations', [ __CLASS__, 'ajax_employee_violations' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static helpers
    // ═══════════════════════════════════════════════════════════════════════

    public static function get_all( array $args = [] ): array {
        global $wpdb;

        $vio = $wpdb->prefix . 'rsyi_hr_violations';
        $emp = $wpdb->prefix . 'rsyi_hr_employees';
        $jt  = $wpdb->prefix . 'rsyi_hr_job_titles';

        $defaults = [ 'status' => '', 'employee_id' => 0 ];
        $args     = wp_parse_args( $args, $defaults );
        $where    = '1=1';
        $params   = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND v.status = %s';
            $params[] = $args['status'];
        }
        if ( ! empty( $args['employee_id'] ) ) {
            $where   .= ' AND v.employee_id = %d';
            $params[] = (int) $args['employee_id'];
        }

        $sql = "SELECT v.*, e.full_name, e.full_name_ar, jt.title AS job_title_name
                FROM {$vio} v
                LEFT JOIN {$emp} e  ON e.id  = v.employee_id
                LEFT JOIN {$jt}  jt ON jt.id = e.job_title_id
                WHERE {$where}
                ORDER BY v.created_at DESC";

        return empty( $params )
            ? $wpdb->get_results( $sql, ARRAY_A ) // phpcs:ignore
            : $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ); // phpcs:ignore
    }

    public static function get_by_id( int $id ): ?array {
        global $wpdb;

        $vio = $wpdb->prefix . 'rsyi_hr_violations';
        $emp = $wpdb->prefix . 'rsyi_hr_employees';
        $jt  = $wpdb->prefix . 'rsyi_hr_job_titles';

        $row = $wpdb->get_row(
            $wpdb->prepare( // phpcs:ignore
                "SELECT v.*, e.full_name, e.full_name_ar, jt.title AS job_title_name
                 FROM {$vio} v
                 LEFT JOIN {$emp} e  ON e.id  = v.employee_id
                 LEFT JOIN {$jt}  jt ON jt.id = e.job_title_id
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

        $employee_id = absint( $data['employee_id'] ?? 0 );
        if ( ! $employee_id ) {
            return false;
        }

        $valid_types    = array_keys( self::get_types() );
        $valid_penalties= array_keys( self::get_penalty_types() );

        $fields = [
            'employee_id'   => $employee_id,
            'violation_type'=> in_array( $data['violation_type'] ?? '', $valid_types, true )
                               ? $data['violation_type'] : 'other',
            'description'   => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : null,
            'penalty_type'  => in_array( $data['penalty_type'] ?? '', $valid_penalties, true )
                               ? $data['penalty_type'] : null,
            'penalty_value' => isset( $data['penalty_value'] ) ? sanitize_text_field( $data['penalty_value'] ) : null,
            'hr_manager_id' => get_current_user_id(),
            'status'        => 'pending',
        ];

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $fields, [ 'id' => absint( $data['id'] ) ] );
            return absint( $data['id'] );
        }

        $wpdb->insert( $table, $fields );
        $id = (int) $wpdb->insert_id;

        if ( $id ) {
            do_action( 'rsyi_hr_violation_created', $id, $fields );
        }

        return $id ?: false;
    }

    public static function approve( int $id, string $notes = '' ): bool {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'rsyi_hr_violations',
            [
                'status'          => 'approved',
                'dean_notes'      => sanitize_textarea_field( $notes ),
                'dean_approved_at'=> current_time( 'mysql' ),
            ],
            [ 'id' => $id ]
        );

        if ( $result ) {
            do_action( 'rsyi_hr_violation_approved', $id );
        }

        return (bool) $result;
    }

    public static function reject_violation( int $id, string $notes = '' ): bool {
        global $wpdb;

        return (bool) $wpdb->update(
            $wpdb->prefix . 'rsyi_hr_violations',
            [
                'status'    => 'rejected',
                'dean_notes'=> sanitize_textarea_field( $notes ),
            ],
            [ 'id' => $id ]
        );
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $wpdb->prefix . 'rsyi_hr_violations', [ 'id' => $id ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_save(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_violations' ) || wp_die( -1 );

        $data = array_map( 'sanitize_text_field', wp_unslash( $_POST ) ); // phpcs:ignore
        if ( isset( $data['description'] ) ) {
            $data['description'] = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ); // phpcs:ignore
        }

        $id = self::save( $data );
        false === $id
            ? wp_send_json_error( [ 'message' => __( 'بيانات المخالفة غير مكتملة.', 'rsyi-hr' ) ] )
            : wp_send_json_success( [ 'id' => $id ] );
    }

    public static function ajax_get_list(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_view_violations' ) || wp_die( -1 );

        $args = [
            'status'      => sanitize_text_field( $_POST['status']      ?? '' ), // phpcs:ignore
            'employee_id' => absint( $_POST['employee_id'] ?? 0 ),               // phpcs:ignore
        ];

        wp_send_json_success( self::get_all( $args ) );
    }

    public static function ajax_get_one(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_view_violations' ) || wp_die( -1 );

        $id  = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        $row = self::get_by_id( $id );

        $row ? wp_send_json_success( $row )
             : wp_send_json_error( [ 'message' => __( 'المخالفة غير موجودة.', 'rsyi-hr' ) ] );
    }

    public static function ajax_approve(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_approve_violations' ) || wp_die( -1 );

        $id    = absint( $_POST['id']    ?? 0 ); // phpcs:ignore
        $notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ); // phpcs:ignore

        self::approve( $id, $notes )
            ? wp_send_json_success()
            : wp_send_json_error( [ 'message' => __( 'تعذّر الاعتماد.', 'rsyi-hr' ) ] );
    }

    public static function ajax_reject(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_approve_violations' ) || wp_die( -1 );

        $id    = absint( $_POST['id']    ?? 0 ); // phpcs:ignore
        $notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ); // phpcs:ignore

        self::reject_violation( $id, $notes )
            ? wp_send_json_success()
            : wp_send_json_error( [ 'message' => __( 'تعذّر الرفض.', 'rsyi-hr' ) ] );
    }

    public static function ajax_delete(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_violations' ) || wp_die( -1 );

        $id = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        self::delete( $id )
            ? wp_send_json_success()
            : wp_send_json_error( [ 'message' => __( 'تعذّر الحذف.', 'rsyi-hr' ) ] );
    }

    public static function ajax_employee_violations(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_portal_access' ) || wp_die( -1 );

        $emp = Employees::get_by_user_id( get_current_user_id() );
        if ( ! $emp ) {
            wp_send_json_error( [ 'message' => __( 'لا يوجد سجل موظف.', 'rsyi-hr' ) ] );
            return;
        }

        wp_send_json_success( self::get_all( [ 'employee_id' => (int) $emp['id'], 'status' => 'approved' ] ) );
    }
}
