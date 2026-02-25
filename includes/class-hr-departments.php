<?php
/**
 * HR Departments & Job Titles
 *
 * إدارة الأقسام والتقسيم الوظيفي مع AJAX handlers.
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Departments {

    public static function init(): void {
        // ── الأقسام ────────────────────────────────────────────────────────
        add_action( 'wp_ajax_rsyi_hr_get_departments',    [ __CLASS__, 'ajax_get_departments' ] );
        add_action( 'wp_ajax_rsyi_hr_save_department',    [ __CLASS__, 'ajax_save_department' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_department',  [ __CLASS__, 'ajax_delete_department' ] );

        // ── التقسيم الوظيفي ────────────────────────────────────────────────
        add_action( 'wp_ajax_rsyi_hr_get_job_titles',     [ __CLASS__, 'ajax_get_job_titles' ] );
        add_action( 'wp_ajax_rsyi_hr_save_job_title',     [ __CLASS__, 'ajax_save_job_title' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_job_title',   [ __CLASS__, 'ajax_delete_job_title' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  DEPARTMENTS — Static helpers
    // ═══════════════════════════════════════════════════════════════════════

    /** كل الأقسام النشطة مرتبة أبجدياً */
    public static function get_all( array $args = [] ): array {
        global $wpdb;

        $table    = $wpdb->prefix . 'rsyi_hr_departments';
        $defaults = [ 'status' => 'active', 'orderby' => 'name', 'order' => 'ASC' ];
        $args     = wp_parse_args( $args, $defaults );

        $where = '1=1';
        $params = [];

        if ( 'all' !== $args['status'] ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }

        $orderby = in_array( $args['orderby'], [ 'name', 'code', 'created_at' ], true )
            ? $args['orderby'] : 'name';
        $order   = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT d.*, e.full_name AS manager_name
                FROM {$table} d
                LEFT JOIN {$wpdb->prefix}rsyi_hr_employees e ON e.id = d.manager_id
                WHERE {$where}
                ORDER BY d.{$orderby} {$order}";

        return empty( $params )
            ? $wpdb->get_results( $sql, ARRAY_A ) // phpcs:ignore
            : $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ); // phpcs:ignore
    }

    /** قسم واحد بالـ ID */
    public static function get_by_id( int $id ): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_departments';

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), // phpcs:ignore
            ARRAY_A
        );

        return $row ?: null;
    }

    /** إضافة أو تعديل قسم */
    public static function save( array $data ): int|false {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_departments';

        $fields = [
            'name'        => sanitize_text_field( $data['name'] ?? '' ),
            'code'        => isset( $data['code'] ) ? sanitize_text_field( $data['code'] ) : null,
            'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : null,
            'parent_id'   => ! empty( $data['parent_id'] ) ? absint( $data['parent_id'] ) : null,
            'manager_id'  => ! empty( $data['manager_id'] ) ? absint( $data['manager_id'] ) : null,
            'status'      => in_array( $data['status'] ?? '', [ 'active', 'inactive' ], true )
                             ? $data['status'] : 'active',
        ];

        if ( empty( $fields['name'] ) ) {
            return false;
        }

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $fields, [ 'id' => absint( $data['id'] ) ] );
            $id = absint( $data['id'] );
            do_action( 'rsyi_hr_department_updated', $id, $fields );
        } else {
            $wpdb->insert( $table, $fields );
            $id = (int) $wpdb->insert_id;
            do_action( 'rsyi_hr_department_created', $id, $fields );
        }

        return $id;
    }

    /** حذف قسم (فقط إذا لم يكن فيه موظفون) */
    public static function delete( int $id ): bool|string {
        global $wpdb;

        $emp_table = $wpdb->prefix . 'rsyi_hr_employees';
        $count     = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$emp_table} WHERE department_id = %d", $id ) // phpcs:ignore
        );

        if ( $count > 0 ) {
            return __( 'لا يمكن حذف القسم لوجود موظفين مرتبطين به.', 'rsyi-hr' );
        }

        $result = $wpdb->delete( $wpdb->prefix . 'rsyi_hr_departments', [ 'id' => $id ] );
        if ( $result ) {
            do_action( 'rsyi_hr_department_deleted', $id );
        }

        return (bool) $result;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  JOB TITLES — Static helpers
    // ═══════════════════════════════════════════════════════════════════════

    /** كل الوظائف */
    public static function get_all_job_titles( array $args = [] ): array {
        global $wpdb;

        $table    = $wpdb->prefix . 'rsyi_hr_job_titles';
        $defaults = [ 'status' => 'active', 'department_id' => 0 ];
        $args     = wp_parse_args( $args, $defaults );

        $where  = '1=1';
        $params = [];

        if ( 'all' !== $args['status'] ) {
            $where   .= ' AND jt.status = %s';
            $params[] = $args['status'];
        }

        if ( ! empty( $args['department_id'] ) ) {
            $where   .= ' AND (jt.department_id = %d OR jt.department_id IS NULL)';
            $params[] = (int) $args['department_id'];
        }

        $sql = "SELECT jt.*, d.name AS department_name
                FROM {$table} jt
                LEFT JOIN {$wpdb->prefix}rsyi_hr_departments d ON d.id = jt.department_id
                WHERE {$where}
                ORDER BY jt.title ASC";

        return empty( $params )
            ? $wpdb->get_results( $sql, ARRAY_A ) // phpcs:ignore
            : $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ); // phpcs:ignore
    }

    /** وظيفة واحدة بالـ ID */
    public static function get_job_title_by_id( int $id ): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_job_titles';

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), // phpcs:ignore
            ARRAY_A
        );

        return $row ?: null;
    }

    /** إضافة أو تعديل وظيفة */
    public static function save_job_title( array $data ): int|false {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_job_titles';

        $fields = [
            'title'         => sanitize_text_field( $data['title'] ?? '' ),
            'code'          => isset( $data['code'] ) ? sanitize_text_field( $data['code'] ) : null,
            'department_id' => ! empty( $data['department_id'] ) ? absint( $data['department_id'] ) : null,
            'grade'         => isset( $data['grade'] ) ? sanitize_text_field( $data['grade'] ) : null,
            'description'   => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : null,
            'status'        => in_array( $data['status'] ?? '', [ 'active', 'inactive' ], true )
                               ? $data['status'] : 'active',
        ];

        if ( empty( $fields['title'] ) ) {
            return false;
        }

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $table, $fields, [ 'id' => absint( $data['id'] ) ] );
            $id = absint( $data['id'] );
            do_action( 'rsyi_hr_job_title_updated', $id, $fields );
        } else {
            $wpdb->insert( $table, $fields );
            $id = (int) $wpdb->insert_id;
            do_action( 'rsyi_hr_job_title_created', $id, $fields );
        }

        return $id;
    }

    /** حذف وظيفة */
    public static function delete_job_title( int $id ): bool|string {
        global $wpdb;

        $emp_table = $wpdb->prefix . 'rsyi_hr_employees';
        $count     = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$emp_table} WHERE job_title_id = %d", $id ) // phpcs:ignore
        );

        if ( $count > 0 ) {
            return __( 'لا يمكن حذف الوظيفة لوجود موظفين مرتبطين بها.', 'rsyi-hr' );
        }

        $result = $wpdb->delete( $wpdb->prefix . 'rsyi_hr_job_titles', [ 'id' => $id ] );

        return (bool) $result;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_get_departments(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );

        // الأقسام مطلوبة لملء نموذج الموظف، لذا يُسمح لمن يدير الموظفين أيضاً
        $can = current_user_can( 'rsyi_hr_view_departments' )
            || current_user_can( 'rsyi_hr_manage_employees' )
            || current_user_can( 'rsyi_hr_manage_departments' );
        $can || wp_die( -1 );

        $status = sanitize_text_field( $_POST['status'] ?? 'all' ); // phpcs:ignore
        wp_send_json_success( self::get_all( [ 'status' => $status ] ) );
    }

    public static function ajax_save_department(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_departments' ) || wp_die( -1 );

        // phpcs:ignore WordPress.Security.NonceVerification
        $data = array_map( 'sanitize_text_field', wp_unslash( $_POST ) );
        $id   = self::save( $data );

        if ( false === $id ) {
            wp_send_json_error( [ 'message' => __( 'اسم القسم مطلوب.', 'rsyi-hr' ) ] );
        }

        wp_send_json_success( [ 'id' => $id ] );
    }

    public static function ajax_delete_department(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_departments' ) || wp_die( -1 );

        $id     = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        $result = self::delete( $id );

        if ( is_string( $result ) ) {
            wp_send_json_error( [ 'message' => $result ] );
        }

        wp_send_json_success();
    }

    public static function ajax_get_job_titles(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );

        // الوظائف مطلوبة لملء نموذج الموظف، لذا يُسمح لمن يدير الموظفين أيضاً
        $can = current_user_can( 'rsyi_hr_view_job_titles' )
            || current_user_can( 'rsyi_hr_manage_employees' )
            || current_user_can( 'rsyi_hr_manage_job_titles' );
        $can || wp_die( -1 );

        $args = [ 'status' => sanitize_text_field( $_POST['status'] ?? 'all' ) ]; // phpcs:ignore

        if ( ! empty( $_POST['department_id'] ) ) { // phpcs:ignore
            $args['department_id'] = absint( $_POST['department_id'] ); // phpcs:ignore
        }

        wp_send_json_success( self::get_all_job_titles( $args ) );
    }

    public static function ajax_save_job_title(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_job_titles' ) || wp_die( -1 );

        // phpcs:ignore WordPress.Security.NonceVerification
        $data = array_map( 'sanitize_text_field', wp_unslash( $_POST ) );
        $id   = self::save_job_title( $data );

        if ( false === $id ) {
            wp_send_json_error( [ 'message' => __( 'اسم الوظيفة مطلوب.', 'rsyi-hr' ) ] );
        }

        wp_send_json_success( [ 'id' => $id ] );
    }

    public static function ajax_delete_job_title(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_job_titles' ) || wp_die( -1 );

        $id     = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        $result = self::delete_job_title( $id );

        if ( is_string( $result ) ) {
            wp_send_json_error( [ 'message' => $result ] );
        }

        wp_send_json_success();
    }
}
