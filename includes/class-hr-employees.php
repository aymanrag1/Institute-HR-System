<?php
/**
 * HR Employees
 *
 * إدارة الموظفين مع AJAX handlers.
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Employees {

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_get_employees',    [ __CLASS__, 'ajax_get_employees' ] );
        add_action( 'wp_ajax_rsyi_hr_get_employee',     [ __CLASS__, 'ajax_get_employee' ] );
        add_action( 'wp_ajax_rsyi_hr_save_employee',    [ __CLASS__, 'ajax_save_employee' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_employee',  [ __CLASS__, 'ajax_delete_employee' ] );
        add_action( 'wp_ajax_rsyi_hr_search_employees', [ __CLASS__, 'ajax_search_employees' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static helpers (يُستخدَم من class-hr-api.php والـ plugins الأخرى)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * قائمة الموظفين مع بيانات القسم والوظيفة.
     *
     * @param array $args {
     *   @type string $status        'active'|'inactive'|'on_leave'|'all'
     *   @type int    $department_id  تصفية بالقسم
     *   @type int    $job_title_id   تصفية بالوظيفة
     *   @type string $search         بحث في الاسم / رقم الموظف
     *   @type int    $per_page
     *   @type int    $page
     * }
     */
    public static function get_all( array $args = [] ): array {
        global $wpdb;

        $emp   = $wpdb->prefix . 'rsyi_hr_employees';
        $dept  = $wpdb->prefix . 'rsyi_hr_departments';
        $jt    = $wpdb->prefix . 'rsyi_hr_job_titles';

        $defaults = [
            'status'        => 'active',
            'department_id' => 0,
            'job_title_id'  => 0,
            'search'        => '',
            'per_page'      => 0,
            'page'          => 1,
        ];
        $args = wp_parse_args( $args, $defaults );

        $where  = '1=1';
        $params = [];

        if ( 'all' !== $args['status'] ) {
            $where   .= ' AND e.status = %s';
            $params[] = $args['status'];
        }

        if ( ! empty( $args['department_id'] ) ) {
            $where   .= ' AND e.department_id = %d';
            $params[] = (int) $args['department_id'];
        }

        if ( ! empty( $args['job_title_id'] ) ) {
            $where   .= ' AND e.job_title_id = %d';
            $params[] = (int) $args['job_title_id'];
        }

        if ( ! empty( $args['search'] ) ) {
            $like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
            $where   .= ' AND (e.full_name LIKE %s OR e.employee_number LIKE %s OR e.email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT e.*,
                       d.name  AS department_name,
                       jt.title AS job_title_name
                FROM   {$emp} e
                LEFT JOIN {$dept} d  ON d.id  = e.department_id
                LEFT JOIN {$jt}  jt ON jt.id = e.job_title_id
                WHERE  {$where}
                ORDER BY e.full_name ASC";

        if ( ! empty( $args['per_page'] ) ) {
            $page     = max( 1, (int) $args['page'] );
            $offset   = ( $page - 1 ) * (int) $args['per_page'];
            $sql     .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['per_page'], $offset ); // phpcs:ignore
        }

        return empty( $params )
            ? $wpdb->get_results( $sql, ARRAY_A ) // phpcs:ignore
            : $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ); // phpcs:ignore
    }

    /** موظف واحد بالـ ID */
    public static function get_by_id( int $id ): ?array {
        global $wpdb;

        $emp  = $wpdb->prefix . 'rsyi_hr_employees';
        $dept = $wpdb->prefix . 'rsyi_hr_departments';
        $jt   = $wpdb->prefix . 'rsyi_hr_job_titles';

        $row = $wpdb->get_row(
            $wpdb->prepare( // phpcs:ignore
                "SELECT e.*,
                        d.name   AS department_name,
                        jt.title AS job_title_name
                 FROM   {$emp} e
                 LEFT JOIN {$dept} d  ON d.id  = e.department_id
                 LEFT JOIN {$jt}  jt ON jt.id = e.job_title_id
                 WHERE  e.id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /** موظف بالـ WordPress user_id */
    public static function get_by_user_id( int $user_id ): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_employees';

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d LIMIT 1", $user_id ), // phpcs:ignore
            ARRAY_A
        );

        return $row ?: null;
    }

    /** إضافة أو تعديل موظف */
    public static function save( array $data ): int|false {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_employees';

        $fields = [
            'full_name'       => sanitize_text_field( $data['full_name'] ?? '' ),
            'employee_number' => isset( $data['employee_number'] ) ? sanitize_text_field( $data['employee_number'] ) : null,
            'national_id'     => isset( $data['national_id'] )     ? sanitize_text_field( $data['national_id'] )     : null,
            'department_id'   => ! empty( $data['department_id'] ) ? absint( $data['department_id'] )                : null,
            'job_title_id'    => ! empty( $data['job_title_id'] )  ? absint( $data['job_title_id'] )                 : null,
            'phone'           => isset( $data['phone'] )           ? sanitize_text_field( $data['phone'] )           : null,
            'email'           => isset( $data['email'] )           ? sanitize_email( $data['email'] )                : null,
            'hire_date'       => ! empty( $data['hire_date'] )     ? sanitize_text_field( $data['hire_date'] )       : null,
            'status'          => in_array( $data['status'] ?? '', [ 'active', 'inactive', 'on_leave' ], true )
                                 ? $data['status'] : 'active',
            'notes'           => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : null,
            'user_id'         => ! empty( $data['user_id'] ) ? absint( $data['user_id'] ) : null,
        ];

        if ( empty( $fields['full_name'] ) ) {
            return false;
        }

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $fields, [ 'id' => absint( $data['id'] ) ] );
            $id = absint( $data['id'] );
            do_action( 'rsyi_hr_employee_updated', $id, $fields );
        } else {
            $wpdb->insert( $table, $fields );
            $id = (int) $wpdb->insert_id;
            do_action( 'rsyi_hr_employee_created', $id, $fields );
        }

        return $id;
    }

    /** حذف موظف */
    public static function delete( int $id ): bool {
        global $wpdb;
        $result = $wpdb->delete( $wpdb->prefix . 'rsyi_hr_employees', [ 'id' => $id ] );

        if ( $result ) {
            do_action( 'rsyi_hr_employee_deleted', $id );
        }

        return (bool) $result;
    }

    /** عدد الموظفين (مفيد للـ Dashboard) */
    public static function count( string $status = 'active' ): int {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_employees';

        if ( 'all' === $status ) {
            return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) // phpcs:ignore
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_get_employees(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_view_employees' ) || wp_die( -1 );

        // phpcs:ignore WordPress.Security.NonceVerification
        $args = [
            'status'        => sanitize_text_field( $_POST['status']        ?? 'all' ),
            'department_id' => absint( $_POST['department_id'] ?? 0 ),
            'job_title_id'  => absint( $_POST['job_title_id']  ?? 0 ),
            'search'        => sanitize_text_field( $_POST['search']        ?? '' ),
        ];

        wp_send_json_success( self::get_all( $args ) );
    }

    public static function ajax_get_employee(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_view_employees' ) || wp_die( -1 );

        $id  = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        $row = self::get_by_id( $id );

        $row ? wp_send_json_success( $row ) : wp_send_json_error( [ 'message' => __( 'الموظف غير موجود.', 'rsyi-hr' ) ] );
    }

    public static function ajax_save_employee(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_employees' ) || wp_die( -1 );

        // phpcs:ignore WordPress.Security.NonceVerification
        $data = array_map( 'sanitize_text_field', wp_unslash( $_POST ) );
        $id   = self::save( $data );

        if ( false === $id ) {
            wp_send_json_error( [ 'message' => __( 'اسم الموظف مطلوب.', 'rsyi-hr' ) ] );
        }

        wp_send_json_success( [ 'id' => $id ] );
    }

    public static function ajax_delete_employee(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_employees' ) || wp_die( -1 );

        $id     = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        $result = self::delete( $id );

        $result ? wp_send_json_success() : wp_send_json_error( [ 'message' => __( 'تعذّر الحذف.', 'rsyi-hr' ) ] );
    }

    public static function ajax_search_employees(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_view_employees' ) || wp_die( -1 );

        $search = sanitize_text_field( $_POST['q'] ?? '' ); // phpcs:ignore
        $rows   = self::get_all( [ 'search' => $search, 'status' => 'active', 'per_page' => 20 ] );

        // نُعيد بيانات خفيفة لـ select2 / autocomplete
        $results = array_map( fn( $r ) => [
            'id'   => $r['id'],
            'text' => $r['full_name'] . ( $r['employee_number'] ? ' — ' . $r['employee_number'] : '' ),
        ], $rows );

        wp_send_json_success( $results );
    }
}
