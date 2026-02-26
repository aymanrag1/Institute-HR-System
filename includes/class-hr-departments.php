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
        add_action( 'wp_ajax_rsyi_hr_import_departments', [ __CLASS__, 'ajax_import_departments' ] );
        add_action( 'wp_ajax_rsyi_hr_dept_template',      [ __CLASS__, 'ajax_download_dept_template' ] );

        // ── التقسيم الوظيفي ────────────────────────────────────────────────
        add_action( 'wp_ajax_rsyi_hr_get_job_titles',     [ __CLASS__, 'ajax_get_job_titles' ] );
        add_action( 'wp_ajax_rsyi_hr_save_job_title',     [ __CLASS__, 'ajax_save_job_title' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_job_title',   [ __CLASS__, 'ajax_delete_job_title' ] );
        add_action( 'wp_ajax_rsyi_hr_import_job_titles',  [ __CLASS__, 'ajax_import_job_titles' ] );
        add_action( 'wp_ajax_rsyi_hr_jt_template',        [ __CLASS__, 'ajax_download_jt_template' ] );
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
            'code'        => ! empty( $data['code'] ) ? sanitize_text_field( $data['code'] ) : null,
            'description' => ! empty( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : null,
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
            'code'          => ! empty( $data['code'] ) ? sanitize_text_field( $data['code'] ) : null,
            'department_id' => ! empty( $data['department_id'] ) ? absint( $data['department_id'] ) : null,
            'grade'         => ! empty( $data['grade'] ) ? sanitize_text_field( $data['grade'] ) : null,
            'description'   => ! empty( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : null,
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

    // ═══════════════════════════════════════════════════════════════════════
    //  DEPARTMENTS — CSV Import
    // ═══════════════════════════════════════════════════════════════════════

    private static function dept_csv_columns(): array {
        return [
            'name'        => 'Department Name * / اسم القسم*',
            'code'        => 'Code / الكود',
            'parent_name' => 'Parent Department / القسم الأعلى',
            'description' => 'Description / الوصف',
            'status'      => 'Status (active|inactive) / الحالة',
        ];
    }

    public static function import_departments_csv( string $file_path ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_departments';

        // Build name→id map for parent resolution
        $name_map = [];
        foreach ( (array) $wpdb->get_results( "SELECT id, name FROM {$table}", ARRAY_A ) as $row ) { // phpcs:ignore
            $name_map[ mb_strtolower( $row['name'] ) ] = (int) $row['id'];
        }

        $columns  = array_keys( self::dept_csv_columns() );
        $inserted = 0;
        $updated  = 0;
        $errors   = [];

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            return [ 'inserted' => 0, 'updated' => 0, 'errors' => [ __( 'تعذّر فتح الملف.', 'rsyi-hr' ) ] ];
        }

        // Strip BOM
        $bom = fread( $handle, 3 );
        if ( $bom !== "\xEF\xBB\xBF" ) { rewind( $handle ); }

        $header = fgetcsv( $handle );
        if ( ! $header ) {
            fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
            return [ 'inserted' => 0, 'updated' => 0, 'errors' => [ __( 'الملف فارغ أو تالف.', 'rsyi-hr' ) ] ];
        }

        // Map columns
        $col_index = [];
        $labels    = self::dept_csv_columns();
        foreach ( $header as $idx => $h ) {
            $h = trim( $h );
            foreach ( $columns as $field ) {
                if ( mb_strtolower( $h ) === mb_strtolower( $field )
                     || mb_strtolower( explode( ' / ', $h )[0] ) === mb_strtolower( explode( ' / ', $labels[ $field ] )[0] )
                     || mb_strtolower( $h ) === mb_strtolower( $labels[ $field ] )
                ) {
                    $col_index[ $field ] = $idx;
                    break;
                }
            }
        }

        $row_num = 1;
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $row_num++;
            if ( empty( array_filter( $row ) ) ) { continue; }

            $get = static fn( string $f ) => isset( $col_index[ $f ], $row[ $col_index[ $f ] ] )
                ? trim( $row[ $col_index[ $f ] ] ) : '';

            $name = sanitize_text_field( $get( 'name' ) );
            if ( '' === $name ) {
                $errors[] = sprintf( __( 'صف %d: اسم القسم مطلوب — تم تخطيه.', 'rsyi-hr' ), $row_num );
                continue;
            }

            // Resolve parent by name
            $parent_name = mb_strtolower( $get( 'parent_name' ) );
            $parent_id   = '' !== $parent_name ? ( $name_map[ $parent_name ] ?? null ) : null;

            $data = [
                'name'        => $name,
                'code'        => sanitize_text_field( $get( 'code' ) ),
                'parent_id'   => $parent_id,
                'description' => sanitize_textarea_field( $get( 'description' ) ),
                'status'      => $get( 'status' ) ?: 'active',
            ];

            // Update if name already exists
            $key = mb_strtolower( $name );
            if ( isset( $name_map[ $key ] ) ) {
                $data['id'] = $name_map[ $key ];
                self::save( $data );
                $updated++;
            } else {
                $new_id = self::save( $data );
                if ( $new_id ) {
                    $name_map[ $key ] = $new_id; // allow subsequent rows to use it as parent
                }
                $inserted++;
            }
        }

        fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        return compact( 'inserted', 'updated', 'errors' );
    }

    public static function ajax_import_departments(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_departments' ) || wp_die( -1 );

        if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
            wp_send_json_error( [ 'message' => __( 'لم يتم رفع أي ملف.', 'rsyi-hr' ) ] );
        }

        $file    = $_FILES['csv_file']; // phpcs:ignore
        $ext_ok  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) === 'csv';
        $mime_ok = in_array( $file['type'], [ 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel' ], true );

        if ( ! $mime_ok && ! $ext_ok ) {
            wp_send_json_error( [ 'message' => __( 'نوع الملف غير مدعوم. استخدم ملف CSV.', 'rsyi-hr' ) ] );
        }

        $result = self::import_departments_csv( $file['tmp_name'] );
        $msg    = sprintf(
            __( 'تمت العملية: %1$d سجل جديد، %2$d سجل محدَّث.', 'rsyi-hr' ),
            $result['inserted'],
            $result['updated']
        );

        wp_send_json_success( [ 'message' => $msg, 'errors' => $result['errors'] ] );
    }

    public static function ajax_download_dept_template(): void {
        check_ajax_referer( 'rsyi_hr_dept_template', 'nonce' );
        current_user_can( 'rsyi_hr_manage_departments' ) || wp_die( -1 );

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="departments-template.csv"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        fputcsv( $out, array_values( self::dept_csv_columns() ) );
        fputcsv( $out, [ 'Training Department', 'TRAIN', '', 'Training and development', 'active' ] );
        fputcsv( $out, [ 'Maritime Training', 'MTRAIN', 'Training Department', 'Maritime courses', 'active' ] );
        fputcsv( $out, [ 'Administration', 'ADMIN', '', 'Admin and finance', 'active' ] );
        fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  JOB TITLES — CSV Import
    // ═══════════════════════════════════════════════════════════════════════

    private static function jt_csv_columns(): array {
        return [
            'title'           => 'Job Title * / المسمى الوظيفي*',
            'code'            => 'Code / الكود',
            'department_name' => 'Department Name / اسم القسم',
            'grade'           => 'Grade / الدرجة',
            'description'     => 'Description / الوصف',
            'status'          => 'Status (active|inactive) / الحالة',
        ];
    }

    public static function import_job_titles_csv( string $file_path ): array {
        global $wpdb;

        $dept_table = $wpdb->prefix . 'rsyi_hr_departments';
        $jt_table   = $wpdb->prefix . 'rsyi_hr_job_titles';

        // Department name→id map
        $dept_map = [];
        foreach ( (array) $wpdb->get_results( "SELECT id, name FROM {$dept_table}", ARRAY_A ) as $row ) { // phpcs:ignore
            $dept_map[ mb_strtolower( $row['name'] ) ] = (int) $row['id'];
        }

        // Job title name→id map (for update detection)
        $jt_map = [];
        foreach ( (array) $wpdb->get_results( "SELECT id, title FROM {$jt_table}", ARRAY_A ) as $row ) { // phpcs:ignore
            $jt_map[ mb_strtolower( $row['title'] ) ] = (int) $row['id'];
        }

        $columns  = array_keys( self::jt_csv_columns() );
        $inserted = 0;
        $updated  = 0;
        $errors   = [];

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            return [ 'inserted' => 0, 'updated' => 0, 'errors' => [ __( 'تعذّر فتح الملف.', 'rsyi-hr' ) ] ];
        }

        // Strip BOM
        $bom = fread( $handle, 3 );
        if ( $bom !== "\xEF\xBB\xBF" ) { rewind( $handle ); }

        $header = fgetcsv( $handle );
        if ( ! $header ) {
            fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
            return [ 'inserted' => 0, 'updated' => 0, 'errors' => [ __( 'الملف فارغ أو تالف.', 'rsyi-hr' ) ] ];
        }

        $col_index = [];
        $labels    = self::jt_csv_columns();
        foreach ( $header as $idx => $h ) {
            $h = trim( $h );
            foreach ( $columns as $field ) {
                if ( mb_strtolower( $h ) === mb_strtolower( $field )
                     || mb_strtolower( explode( ' / ', $h )[0] ) === mb_strtolower( explode( ' / ', $labels[ $field ] )[0] )
                     || mb_strtolower( $h ) === mb_strtolower( $labels[ $field ] )
                ) {
                    $col_index[ $field ] = $idx;
                    break;
                }
            }
        }

        $row_num = 1;
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $row_num++;
            if ( empty( array_filter( $row ) ) ) { continue; }

            $get = static fn( string $f ) => isset( $col_index[ $f ], $row[ $col_index[ $f ] ] )
                ? trim( $row[ $col_index[ $f ] ] ) : '';

            $title = sanitize_text_field( $get( 'title' ) );
            if ( '' === $title ) {
                $errors[] = sprintf( __( 'صف %d: المسمى الوظيفي مطلوب — تم تخطيه.', 'rsyi-hr' ), $row_num );
                continue;
            }

            $dept_key = mb_strtolower( $get( 'department_name' ) );
            $dept_id  = '' !== $dept_key ? ( $dept_map[ $dept_key ] ?? null ) : null;

            $data = [
                'title'         => $title,
                'code'          => sanitize_text_field( $get( 'code' ) ),
                'department_id' => $dept_id,
                'grade'         => sanitize_text_field( $get( 'grade' ) ),
                'description'   => sanitize_textarea_field( $get( 'description' ) ),
                'status'        => $get( 'status' ) ?: 'active',
            ];

            $key = mb_strtolower( $title );
            if ( isset( $jt_map[ $key ] ) ) {
                $data['id'] = $jt_map[ $key ];
                self::save_job_title( $data );
                $updated++;
            } else {
                $new_id = self::save_job_title( $data );
                if ( $new_id ) {
                    $jt_map[ $key ] = $new_id;
                }
                $inserted++;
            }
        }

        fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        return compact( 'inserted', 'updated', 'errors' );
    }

    public static function ajax_import_job_titles(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_job_titles' ) || wp_die( -1 );

        if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
            wp_send_json_error( [ 'message' => __( 'لم يتم رفع أي ملف.', 'rsyi-hr' ) ] );
        }

        $file    = $_FILES['csv_file']; // phpcs:ignore
        $ext_ok  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) === 'csv';
        $mime_ok = in_array( $file['type'], [ 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel' ], true );

        if ( ! $mime_ok && ! $ext_ok ) {
            wp_send_json_error( [ 'message' => __( 'نوع الملف غير مدعوم. استخدم ملف CSV.', 'rsyi-hr' ) ] );
        }

        $result = self::import_job_titles_csv( $file['tmp_name'] );
        $msg    = sprintf(
            __( 'تمت العملية: %1$d سجل جديد، %2$d سجل محدَّث.', 'rsyi-hr' ),
            $result['inserted'],
            $result['updated']
        );

        wp_send_json_success( [ 'message' => $msg, 'errors' => $result['errors'] ] );
    }

    public static function ajax_download_jt_template(): void {
        check_ajax_referer( 'rsyi_hr_jt_template', 'nonce' );
        current_user_can( 'rsyi_hr_manage_job_titles' ) || wp_die( -1 );

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="job-titles-template.csv"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        fputcsv( $out, array_values( self::jt_csv_columns() ) );
        fputcsv( $out, [ 'Instructor', 'INST', 'Training Department', 'Grade 3', 'Maritime instructor', 'active' ] );
        fputcsv( $out, [ 'Secretary', 'SEC', 'Administration', 'Grade 2', 'Administrative secretary', 'active' ] );
        fputcsv( $out, [ 'Senior Instructor', 'SINST', 'Training Department', 'Grade 4', '', 'active' ] );
        fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        exit;
    }
}
