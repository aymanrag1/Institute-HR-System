<?php
/**
 * HR Leaves — إدارة طلبات الإجازة
 *
 * سير العمل:
 *   1. الموظف يرفع طلب بتوقيعه الإلكتروني  → pending
 *   2. المدير المباشر يعتمد                → manager_approved
 *      (إذا كان الموظف مديراً يتخطى هذه الخطوة)
 *   3. مدير الموارد البشرية يعتمد          → hr_approved
 *   4. عميد المعهد يصادق                   → dean_approved  ✓ نهائي
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Leaves {

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_submit_leave',          [ __CLASS__, 'ajax_submit' ] );
        add_action( 'wp_ajax_rsyi_hr_get_leaves',            [ __CLASS__, 'ajax_get_leaves' ] );
        add_action( 'wp_ajax_rsyi_hr_get_leave',             [ __CLASS__, 'ajax_get_leave' ] );
        add_action( 'wp_ajax_rsyi_hr_approve_leave',         [ __CLASS__, 'ajax_approve' ] );
        add_action( 'wp_ajax_rsyi_hr_reject_leave',          [ __CLASS__, 'ajax_reject' ] );
        add_action( 'wp_ajax_rsyi_hr_get_employee_leaves',   [ __CLASS__, 'ajax_employee_leaves' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static helpers
    // ═══════════════════════════════════════════════════════════════════════

    /** كل طلبات الإجازة */
    public static function get_all( array $args = [] ): array {
        global $wpdb;

        $leaves = $wpdb->prefix . 'rsyi_hr_leaves';
        $emp    = $wpdb->prefix . 'rsyi_hr_employees';
        $jt     = $wpdb->prefix . 'rsyi_hr_job_titles';

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
            $where   .= ' AND l.status = %s';
            $params[] = $args['status'];
        }
        if ( ! empty( $args['employee_id'] ) ) {
            $where   .= ' AND l.employee_id = %d';
            $params[] = (int) $args['employee_id'];
        }

        $sql = "SELECT l.*, e.full_name, e.full_name_ar, jt.title AS job_title_name
                FROM {$leaves} l
                LEFT JOIN {$emp} e  ON e.id  = l.employee_id
                LEFT JOIN {$jt}  jt ON jt.id = e.job_title_id
                WHERE {$where}
                ORDER BY l.created_at DESC";

        if ( ! empty( $args['per_page'] ) ) {
            $offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
            $sql   .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['per_page'], $offset ); // phpcs:ignore
        }

        return empty( $params )
            ? $wpdb->get_results( $sql, ARRAY_A ) // phpcs:ignore
            : $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ); // phpcs:ignore
    }

    /** طلب إجازة واحد بالـ ID */
    public static function get_by_id( int $id ): ?array {
        global $wpdb;

        $leaves = $wpdb->prefix . 'rsyi_hr_leaves';
        $emp    = $wpdb->prefix . 'rsyi_hr_employees';
        $dept   = $wpdb->prefix . 'rsyi_hr_departments';
        $jt     = $wpdb->prefix . 'rsyi_hr_job_titles';

        $row = $wpdb->get_row(
            $wpdb->prepare( // phpcs:ignore
                "SELECT l.*,
                        e.full_name, e.full_name_ar, e.department_id,
                        jt.title AS job_title_name,
                        d.name   AS department_name,
                        d.manager_id AS dept_manager_id
                 FROM {$leaves} l
                 LEFT JOIN {$emp}  e  ON e.id  = l.employee_id
                 LEFT JOIN {$jt}   jt ON jt.id = e.job_title_id
                 LEFT JOIN {$dept} d  ON d.id  = e.department_id
                 WHERE l.id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /** رفع طلب إجازة جديد */
    public static function submit( array $data ): int|false {
        global $wpdb;

        $employee_id = absint( $data['employee_id'] ?? 0 );
        if ( ! $employee_id ) {
            return false;
        }

        $from = sanitize_text_field( $data['from_date'] ?? '' );
        $to   = sanitize_text_field( $data['to_date']   ?? '' );

        if ( ! $from || ! $to ) {
            return false;
        }

        $valid_types = [ 'annual', 'emergency', 'sick', 'unpaid' ];
        $leave_type  = in_array( $data['leave_type'] ?? '', $valid_types, true )
                       ? $data['leave_type'] : 'annual';

        // حساب عدد الأيام
        $total_days = (int) ( ( strtotime( $to ) - strtotime( $from ) ) / 86400 ) + 1;

        // تحديد المرحلة الأولى من سير العمل
        $status = 'pending';
        $manager_id = self::get_direct_manager( $employee_id );

        // إذا الموظف هو المدير أو ما في مدير مباشر → يتخطى خطوة المدير
        if ( ! $manager_id || $manager_id === $employee_id ) {
            $status = 'manager_approved';
        }

        $fields = [
            'employee_id'       => $employee_id,
            'leave_type'        => $leave_type,
            'from_date'         => $from,
            'to_date'           => $to,
            'return_date'       => ! empty( $data['return_date'] )     ? sanitize_text_field( $data['return_date'] )    : null,
            'last_day_before'   => ! empty( $data['last_day_before'] ) ? sanitize_text_field( $data['last_day_before'] ): null,
            'substitute_name'   => isset( $data['substitute_name'] )   ? sanitize_text_field( $data['substitute_name'] ): null,
            'reason'            => isset( $data['reason'] )            ? sanitize_textarea_field( $data['reason'] )     : null,
            'total_days'        => $total_days,
            'status'            => $status,
            'manager_id'        => $manager_id ?: null,
            'employee_signature'=> isset( $data['employee_signature'] ) ? $data['employee_signature'] : null,
            'employee_signed_at'=> ! empty( $data['employee_signature'] ) ? current_time( 'mysql' ) : null,
        ];

        $wpdb->insert( $wpdb->prefix . 'rsyi_hr_leaves', $fields );
        $id = (int) $wpdb->insert_id;

        if ( $id ) {
            do_action( 'rsyi_hr_leave_submitted', $id, $fields );
            self::notify_next_approver( $id );
        }

        return $id ?: false;
    }

    /** اعتماد طلب إجازة */
    public static function approve( int $leave_id, int $approver_id, string $signature = '' ): bool|string {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_leaves';

        $leave = self::get_by_id( $leave_id );
        if ( ! $leave ) {
            return __( 'الطلب غير موجود.', 'rsyi-hr' );
        }

        $update   = [];
        $now      = current_time( 'mysql' );
        $new_status = '';

        switch ( $leave['status'] ) {
            case 'pending':
                // المدير المباشر يعتمد
                $update = [
                    'status'              => 'manager_approved',
                    'manager_id'          => $approver_id,
                    'manager_signature'   => $signature,
                    'manager_approved_at' => $now,
                ];
                $new_status = 'manager_approved';
                break;

            case 'manager_approved':
                // مدير الموارد البشرية يعتمد
                $update = [
                    'status'        => 'hr_approved',
                    'hr_manager_id' => $approver_id,
                    'hr_signature'  => $signature,
                    'hr_approved_at'=> $now,
                ];
                $new_status = 'hr_approved';
                break;

            case 'hr_approved':
                // العميد يصادق
                $update = [
                    'status'          => 'dean_approved',
                    'dean_signature'  => $signature,
                    'dean_approved_at'=> $now,
                ];
                $new_status = 'dean_approved';
                break;

            default:
                return __( 'لا يمكن اعتماد هذا الطلب في حالته الحالية.', 'rsyi-hr' );
        }

        $wpdb->update( $table, $update, [ 'id' => $leave_id ] );
        do_action( 'rsyi_hr_leave_approved', $leave_id, $new_status, $approver_id );

        if ( 'dean_approved' !== $new_status ) {
            self::notify_next_approver( $leave_id );
        } else {
            self::notify_employee_approved( $leave_id );
        }

        return true;
    }

    /** رفض طلب إجازة */
    public static function reject( int $leave_id, int $rejector_id, string $reason = '' ): bool {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'rsyi_hr_leaves',
            [
                'status'           => 'rejected',
                'rejection_reason' => sanitize_textarea_field( $reason ),
            ],
            [ 'id' => $leave_id ]
        );

        if ( $result ) {
            do_action( 'rsyi_hr_leave_rejected', $leave_id, $rejector_id );
        }

        return (bool) $result;
    }

    /** إيجاد المدير المباشر لموظف */
    public static function get_direct_manager( int $employee_id ): ?int {
        global $wpdb;

        $emp  = $wpdb->prefix . 'rsyi_hr_employees';
        $dept = $wpdb->prefix . 'rsyi_hr_departments';

        $manager_id = $wpdb->get_var( // phpcs:ignore
            $wpdb->prepare(
                "SELECT d.manager_id
                 FROM {$emp} e
                 LEFT JOIN {$dept} d ON d.id = e.department_id
                 WHERE e.id = %d AND d.manager_id IS NOT NULL
                 LIMIT 1",
                $employee_id
            )
        );

        return $manager_id ? (int) $manager_id : null;
    }

    /** إرسال إشعار للمعتمد التالي */
    private static function notify_next_approver( int $leave_id ): void {
        $leave = self::get_by_id( $leave_id );
        if ( ! $leave ) {
            return;
        }

        $recipient_emp_id = null;

        if ( 'pending' === $leave['status'] && $leave['manager_id'] ) {
            $recipient_emp_id = (int) $leave['manager_id'];
        } elseif ( 'manager_approved' === $leave['status'] ) {
            // أرسل لمدير الموارد البشرية (أول مستخدم بدور rsyi_hr_manager)
            $hr_users = get_users( [ 'role' => 'rsyi_hr_manager', 'number' => 1 ] );
            if ( ! empty( $hr_users ) ) {
                $email = $hr_users[0]->user_email;
                $name  = $leave['full_name'] ?? '';
                wp_mail(
                    $email,
                    __( 'طلب إجازة بانتظار اعتمادك / Leave Request Pending', 'rsyi-hr' ),
                    sprintf( __( 'يوجد طلب إجازة من الموظف %s بانتظار اعتمادك.', 'rsyi-hr' ), $name )
                );
                return;
            }
        }

        if ( $recipient_emp_id ) {
            $mgr_emp = Employees::get_by_id( $recipient_emp_id );
            if ( $mgr_emp && $mgr_emp['user_id'] ) {
                $user  = get_userdata( (int) $mgr_emp['user_id'] );
                $name  = $leave['full_name'] ?? '';
                if ( $user ) {
                    wp_mail(
                        $user->user_email,
                        __( 'طلب إجازة بانتظار اعتمادك / Leave Request Pending', 'rsyi-hr' ),
                        sprintf( __( 'يوجد طلب إجازة من الموظف %s بانتظار اعتمادك.', 'rsyi-hr' ), $name )
                    );
                }
            }
        }
    }

    /** إشعار الموظف بالاعتماد النهائي */
    private static function notify_employee_approved( int $leave_id ): void {
        $leave = self::get_by_id( $leave_id );
        if ( ! $leave ) {
            return;
        }

        $emp = Employees::get_by_id( (int) $leave['employee_id'] );
        if ( $emp && $emp['user_id'] ) {
            $user = get_userdata( (int) $emp['user_id'] );
            if ( $user ) {
                wp_mail(
                    $user->user_email,
                    __( 'تم اعتماد طلب الإجازة / Leave Request Approved', 'rsyi-hr' ),
                    __( 'تم اعتماد طلب إجازتك من قِبل جميع الجهات المختصة.', 'rsyi-hr' )
                );
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_submit(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );

        $data = wp_unslash( $_POST ); // phpcs:ignore

        // تحديد الموظف: admin يحدده، الموظف يرفع باسمه
        if ( current_user_can( 'rsyi_hr_manage_leaves' ) ) {
            $employee_id = absint( $data['employee_id'] ?? 0 );
        } else {
            current_user_can( 'rsyi_hr_submit_leave' ) || wp_die( -1 );
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

    public static function ajax_get_leaves(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_view_leaves' ) || wp_die( -1 );

        $args = [
            'status'      => sanitize_text_field( $_POST['status']      ?? '' ), // phpcs:ignore
            'employee_id' => absint( $_POST['employee_id'] ?? 0 ),               // phpcs:ignore
        ];

        wp_send_json_success( self::get_all( $args ) );
    }

    public static function ajax_get_leave(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_view_leaves' ) || wp_die( -1 );

        $id  = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        $row = self::get_by_id( $id );

        $row ? wp_send_json_success( $row )
             : wp_send_json_error( [ 'message' => __( 'الطلب غير موجود.', 'rsyi-hr' ) ] );
    }

    public static function ajax_approve(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );

        $id        = absint( $_POST['id']        ?? 0 ); // phpcs:ignore
        $signature = sanitize_text_field( $_POST['signature'] ?? '' ); // phpcs:ignore — base64 stripped by sanitize_text_field issue
        // للتوقيع نحتاج البيانات الخام
        $signature = isset( $_POST['signature'] ) ? wp_unslash( $_POST['signature'] ) : ''; // phpcs:ignore

        $leave = self::get_by_id( $id );
        if ( ! $leave ) {
            wp_send_json_error( [ 'message' => __( 'الطلب غير موجود.', 'rsyi-hr' ) ] );
            return;
        }

        // التحقق من الصلاحية حسب المرحلة
        $can = false;
        if ( 'pending' === $leave['status'] && current_user_can( 'rsyi_hr_manage_leaves' ) ) {
            $can = true;
        } elseif ( 'manager_approved' === $leave['status'] && current_user_can( 'rsyi_hr_manage_leaves' ) ) {
            $can = true;
        } elseif ( 'hr_approved' === $leave['status'] && current_user_can( 'rsyi_hr_approve_leaves_dean' ) ) {
            $can = true;
        }

        $can || wp_die( -1 );

        $result = self::approve( $id, get_current_user_id(), $signature );

        is_string( $result )
            ? wp_send_json_error( [ 'message' => $result ] )
            : wp_send_json_success();
    }

    public static function ajax_reject(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_leaves' ) || wp_die( -1 );

        $id     = absint( $_POST['id']     ?? 0 ); // phpcs:ignore
        $reason = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ); // phpcs:ignore

        self::reject( $id, get_current_user_id(), $reason )
            ? wp_send_json_success()
            : wp_send_json_error( [ 'message' => __( 'تعذّر الرفض.', 'rsyi-hr' ) ] );
    }

    public static function ajax_employee_leaves(): void {
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
