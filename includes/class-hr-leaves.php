<?php
/**
 * HR Leaves — طلبات الإجازة
 *
 * سير العمل:
 *   1. الموظف يرفع الطلب (draft → pending_manager)
 *   2. المدير المباشر يعتمد (pending_manager → pending_hr)
 *      أو إذا كان الموظف مديراً: مباشرة pending_hr
 *   3. مدير HR يعتمد (pending_hr → pending_dean)
 *   4. عميد المعهد يعتمد (pending_dean → approved)
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Leaves {

    public static function init(): void {
        // Admin AJAX
        add_action( 'wp_ajax_rsyi_hr_get_leaves',           [ __CLASS__, 'ajax_get_leaves' ] );
        add_action( 'wp_ajax_rsyi_hr_get_leave',            [ __CLASS__, 'ajax_get_leave' ] );
        add_action( 'wp_ajax_rsyi_hr_save_leave',           [ __CLASS__, 'ajax_save_leave' ] );
        add_action( 'wp_ajax_rsyi_hr_approve_leave',        [ __CLASS__, 'ajax_approve_leave' ] );
        add_action( 'wp_ajax_rsyi_hr_reject_leave',         [ __CLASS__, 'ajax_reject_leave' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_leave',         [ __CLASS__, 'ajax_delete_leave' ] );

        // Last leave date for auto-fill
        add_action( 'wp_ajax_rsyi_hr_get_last_leave_date',  [ __CLASS__, 'ajax_get_last_leave_date' ] );

        // Print
        add_action( 'wp_ajax_rsyi_hr_print_leave',          [ __CLASS__, 'ajax_print_leave' ] );

        // Frontend AJAX (employee portal)
        add_action( 'wp_ajax_rsyi_hr_submit_leave',         [ __CLASS__, 'ajax_submit_leave' ] );
        add_action( 'wp_ajax_rsyi_hr_my_leaves',            [ __CLASS__, 'ajax_my_leaves' ] );
        add_action( 'wp_ajax_rsyi_hr_pending_my_approval',  [ __CLASS__, 'ajax_pending_my_approval' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static Helpers
    // ═══════════════════════════════════════════════════════════════════════

    public static function get_all( array $args = [] ): array {
        global $wpdb;

        $tbl  = $wpdb->prefix . 'rsyi_hr_leaves';
        $emp  = $wpdb->prefix . 'rsyi_hr_employees';
        $jt   = $wpdb->prefix . 'rsyi_hr_job_titles';

        $defaults = [
            'status'      => '',
            'employee_id' => 0,
            'manager_id'  => 0,
            'per_page'    => 0,
            'page'        => 1,
        ];
        $args = wp_parse_args( $args, $defaults );

        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND l.status = %s';
            $params[] = $args['status'];
        }
        if ( ! empty( $args['employee_id'] ) ) {
            $where   .= ' AND l.employee_id = %d';
            $params[] = (int) $args['employee_id'];
        }
        if ( ! empty( $args['manager_id'] ) ) {
            $where   .= ' AND l.manager_id = %d';
            $params[] = (int) $args['manager_id'];
        }

        $sql = "SELECT l.*,
                       e.full_name    AS employee_name,
                       e.full_name_ar AS employee_name_ar,
                       e.employee_number,
                       jt.title       AS job_title_name
                FROM   {$tbl} l
                LEFT JOIN {$emp} e  ON e.id  = l.employee_id
                LEFT JOIN {$jt}  jt ON jt.id = e.job_title_id
                WHERE  {$where}
                ORDER BY l.created_at DESC";

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
        $tbl = $wpdb->prefix . 'rsyi_hr_leaves';
        $emp = $wpdb->prefix . 'rsyi_hr_employees';
        $jt  = $wpdb->prefix . 'rsyi_hr_job_titles';

        $row = $wpdb->get_row(
            $wpdb->prepare( // phpcs:ignore
                "SELECT l.*,
                        e.full_name    AS employee_name,
                        e.full_name_ar AS employee_name_ar,
                        e.employee_number,
                        e.signature_url AS employee_signature_img,
                        jt.title        AS job_title_name
                 FROM   {$tbl} l
                 LEFT JOIN {$emp} e  ON e.id  = l.employee_id
                 LEFT JOIN {$jt}  jt ON jt.id = e.job_title_id
                 WHERE  l.id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * حساب عدد أيام الإجازة.
     */
    public static function calc_days( string $from, string $to ): int {
        $d1 = new \DateTime( $from );
        $d2 = new \DateTime( $to );
        return max( 1, (int) $d1->diff( $d2 )->days + 1 );
    }

    /**
     * حفظ (إنشاء أو تعديل) طلب إجازة.
     * يُعيد ID أو false عند الفشل.
     */
    public static function save( array $data ): int|false {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_leaves';

        $valid_types = [ 'regular', 'sick', 'casual', 'unpaid' ];

        $from_date = sanitize_text_field( $data['from_date'] ?? '' );
        $to_date   = sanitize_text_field( $data['to_date']   ?? '' );

        if ( empty( $from_date ) || empty( $to_date ) ) {
            return false;
        }

        $fields = [
            'employee_id'      => absint( $data['employee_id'] ?? 0 ),
            'leave_type'       => in_array( $data['leave_type'] ?? '', $valid_types, true )
                                  ? $data['leave_type'] : 'regular',
            'from_date'        => $from_date,
            'to_date'          => $to_date,
            'days_count'       => self::calc_days( $from_date, $to_date ),
            'return_date'      => ! empty( $data['return_date'] )    ? sanitize_text_field( $data['return_date'] )   : null,
            'last_leave_date'  => ! empty( $data['last_leave_date'] )? sanitize_text_field( $data['last_leave_date'] ) : null,
            'person_covering'  => isset( $data['person_covering'] ) ? sanitize_text_field( $data['person_covering'] ) : null,
            'reason'           => isset( $data['reason'] )           ? sanitize_textarea_field( $data['reason'] )    : null,
            'status'           => sanitize_text_field( $data['status'] ?? 'draft' ),
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

    /**
     * اعتماد طلب إجازة من أحد الأطراف.
     * $stage: 'manager' | 'hr_manager' | 'dean'
     */
    public static function approve( int $id, string $stage, string $signature = '', string $notes = '' ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_leaves';

        $leave = self::get_by_id( $id );
        if ( ! $leave ) {
            return false;
        }

        $now    = current_time( 'mysql' );
        $update = [];

        switch ( $stage ) {
            case 'manager':
                if ( 'pending_manager' !== $leave['status'] ) {
                    return false;
                }
                $update = [
                    'manager_signature'    => sanitize_textarea_field( $signature ),
                    'manager_notes'        => sanitize_textarea_field( $notes ),
                    'manager_signed_at'    => $now,
                    'status'               => 'pending_hr',
                ];
                break;

            case 'hr_manager':
                if ( 'pending_hr' !== $leave['status'] ) {
                    return false;
                }
                $update = [
                    'hr_manager_signature'  => sanitize_textarea_field( $signature ),
                    'hr_manager_notes'      => sanitize_textarea_field( $notes ),
                    'hr_manager_signed_at'  => $now,
                    'status'                => 'pending_dean',
                ];
                break;

            case 'dean':
                if ( 'pending_dean' !== $leave['status'] ) {
                    return false;
                }
                $update = [
                    'dean_notes'     => sanitize_textarea_field( $notes ),
                    'dean_signed_at' => $now,
                    'status'         => 'approved',
                ];
                break;

            default:
                return false;
        }

        $result = $wpdb->update( $table, $update, [ 'id' => $id ] );
        if ( $result !== false ) {
            do_action( 'rsyi_hr_leave_approved', $id, $stage );
        }
        return $result !== false;
    }

    /**
     * رفض طلب إجازة.
     */
    public static function reject( int $id, string $rejected_by, string $reason = '' ): bool {
        global $wpdb;
        $table  = $wpdb->prefix . 'rsyi_hr_leaves';
        $result = $wpdb->update( $table, [
            'status'           => 'rejected',
            'rejected_by'      => sanitize_text_field( $rejected_by ),
            'rejection_reason' => sanitize_textarea_field( $reason ),
        ], [ 'id' => $id ] );

        if ( $result !== false ) {
            do_action( 'rsyi_hr_leave_rejected', $id, $rejected_by );
        }
        return $result !== false;
    }

    /**
     * رفع طلب الإجازة (submit) من قِبَل الموظف.
     * يحدد المسار بناءً على is_manager للموظف.
     */
    public static function submit( int $leave_id ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_leaves';
        $leave = self::get_by_id( $leave_id );
        if ( ! $leave || 'draft' !== $leave['status'] ) {
            return false;
        }

        $emp = Employees::get_by_id( (int) $leave['employee_id'] );
        if ( ! $emp ) {
            return false;
        }

        // إذا كان الموظف مديراً → يتجاوز المدير المباشر
        $next_status = ( $emp['is_manager'] || empty( $emp['manager_id'] ) )
            ? 'pending_hr'
            : 'pending_manager';

        $update = [
            'status'             => $next_status,
            'employee_signed_at' => current_time( 'mysql' ),
        ];

        if ( 'pending_manager' === $next_status && ! empty( $emp['manager_id'] ) ) {
            $update['manager_id'] = (int) $emp['manager_id'];
        }

        $result = $wpdb->update( $table, $update, [ 'id' => $leave_id ] );
        if ( $result !== false ) {
            do_action( 'rsyi_hr_leave_submitted', $leave_id, $next_status );
        }
        return $result !== false;
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $wpdb->prefix . 'rsyi_hr_leaves', [ 'id' => $id ] );
    }

    /**
     * آخر تاريخ إجازة معتمدة للموظف (to_date).
     */
    public static function get_last_approved_leave_date( int $employee_id ): ?string {
        global $wpdb;
        $tbl = $wpdb->prefix . 'rsyi_hr_leaves';
        $val = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
            "SELECT MAX(to_date) FROM {$tbl} WHERE employee_id = %d AND status = 'approved'",
            $employee_id
        ) );
        return $val ?: null;
    }

    public static function ajax_get_last_leave_date(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_leaves' ) || wp_die( -1 );

        $employee_id = absint( $_POST['employee_id'] ?? 0 ); // phpcs:ignore
        if ( ! $employee_id ) {
            wp_send_json_error();
        }

        $date = self::get_last_approved_leave_date( $employee_id );
        wp_send_json_success( [ 'last_leave_date' => $date ?: '' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX Handlers — Admin
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_get_leaves(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_leaves' ) || wp_die( -1 );

        $args = [
            'status'      => sanitize_text_field( $_POST['status']      ?? '' ), // phpcs:ignore
            'employee_id' => absint( $_POST['employee_id'] ?? 0 ),               // phpcs:ignore
        ];

        wp_send_json_success( self::get_all( $args ) );
    }

    public static function ajax_get_leave(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_leaves' ) || wp_die( -1 );

        $id  = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        $row = self::get_by_id( $id );
        $row ? wp_send_json_success( $row ) : wp_send_json_error();
    }

    public static function ajax_save_leave(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_leaves' ) || wp_die( -1 );

        // phpcs:ignore
        $data = array_map( 'sanitize_text_field', wp_unslash( $_POST ) );
        $id   = self::save( $data );
        $id ? wp_send_json_success( [ 'id' => $id ] ) : wp_send_json_error();
    }

    public static function ajax_approve_leave(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );

        $id        = absint( $_POST['id']        ?? 0 );   // phpcs:ignore
        $stage     = sanitize_text_field( $_POST['stage'] ?? '' ); // phpcs:ignore
        $signature = sanitize_textarea_field( wp_unslash( $_POST['signature'] ?? '' ) ); // phpcs:ignore
        $notes     = sanitize_textarea_field( wp_unslash( $_POST['notes']     ?? '' ) ); // phpcs:ignore

        // التحقق من الصلاحية حسب المرحلة
        $cap_map = [
            'manager'    => 'rsyi_hr_approve_leaves_manager',
            'hr_manager' => 'rsyi_hr_manage_leaves',
            'dean'       => 'rsyi_hr_dean_approve',
        ];
        $needed = $cap_map[ $stage ] ?? 'rsyi_hr_manage_leaves';
        current_user_can( $needed ) || wp_die( -1 );

        $result = self::approve( $id, $stage, $signature, $notes );
        $result ? wp_send_json_success() : wp_send_json_error( [ 'message' => __( 'تعذّر الاعتماد.', 'rsyi-hr' ) ] );
    }

    public static function ajax_reject_leave(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_leaves' ) || wp_die( -1 );

        $id     = absint( $_POST['id'] ?? 0 );                          // phpcs:ignore
        $by     = sanitize_text_field( $_POST['rejected_by'] ?? '' );   // phpcs:ignore
        $reason = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ); // phpcs:ignore

        $result = self::reject( $id, $by, $reason );
        $result ? wp_send_json_success() : wp_send_json_error();
    }

    public static function ajax_delete_leave(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_leaves' ) || wp_die( -1 );

        $id = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        self::delete( $id ) ? wp_send_json_success() : wp_send_json_error();
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX Handlers — Employee Portal
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_submit_leave(): void {
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
            'employee_id'      => $emp['id'],
            'leave_type'       => sanitize_text_field( $_POST['leave_type']      ?? 'regular' ), // phpcs:ignore
            'from_date'        => sanitize_text_field( $_POST['from_date']        ?? '' ),        // phpcs:ignore
            'to_date'          => sanitize_text_field( $_POST['to_date']          ?? '' ),        // phpcs:ignore
            'return_date'      => sanitize_text_field( $_POST['return_date']      ?? '' ),        // phpcs:ignore
            'last_leave_date'  => sanitize_text_field( $_POST['last_leave_date']  ?? '' ),        // phpcs:ignore
            'person_covering'  => sanitize_text_field( $_POST['person_covering']  ?? '' ),        // phpcs:ignore
            'reason'           => sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ), // phpcs:ignore
            'status'           => 'draft',
        ];

        $leave_id = self::save( $data );
        if ( ! $leave_id ) {
            wp_send_json_error( [ 'message' => __( 'تاريخ الإجازة مطلوب.', 'rsyi-hr' ) ] );
        }

        // تخزين التوقيع الإلكتروني
        $signature = sanitize_textarea_field( wp_unslash( $_POST['employee_signature'] ?? '' ) ); // phpcs:ignore
        if ( $signature ) {
            global $wpdb;
            $wpdb->update( $wpdb->prefix . 'rsyi_hr_leaves', [
                'employee_signature' => $signature,
            ], [ 'id' => $leave_id ] );
        }

        // رفع الطلب
        self::submit( $leave_id );

        wp_send_json_success( [ 'id' => $leave_id, 'message' => __( 'تم رفع طلب الإجازة بنجاح.', 'rsyi-hr' ) ] );
    }

    public static function ajax_my_leaves(): void {
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

    public static function ajax_print_leave(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'read' ) || wp_die( -1 );

        $id    = absint( $_GET['id'] ?? 0 ); // phpcs:ignore
        $leave = self::get_by_id( $id );

        if ( ! $leave ) {
            wp_die( __( 'الطلب غير موجود.', 'rsyi-hr' ) );
        }

        // Security: employees can only print their own leaves
        if ( ! current_user_can( 'rsyi_hr_manage_leaves' ) ) {
            $emp = Employees::get_by_user_id( get_current_user_id() );
            if ( ! $emp || (int) $emp['id'] !== (int) $leave['employee_id'] ) {
                wp_die( __( 'غير مصرح.', 'rsyi-hr' ) );
            }
        }

        // Render printable template
        include RSYI_HR_DIR . 'portal/leave-print.php';
        exit;
    }

    public static function ajax_pending_my_approval(): void {
        check_ajax_referer( 'rsyi_hr_portal', 'nonce' );
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error();
        }

        $emp = Employees::get_by_user_id( $user_id );
        if ( ! $emp ) {
            wp_send_json_success( [] );
        }

        wp_send_json_success( self::get_all( [
            'manager_id' => $emp['id'],
            'status'     => 'pending_manager',
        ] ) );
    }
}
