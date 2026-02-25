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
        add_action( 'wp_ajax_rsyi_hr_get_employees',         [ __CLASS__, 'ajax_get_employees' ] );
        add_action( 'wp_ajax_rsyi_hr_get_employee',          [ __CLASS__, 'ajax_get_employee' ] );
        add_action( 'wp_ajax_rsyi_hr_save_employee',         [ __CLASS__, 'ajax_save_employee' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_employee',       [ __CLASS__, 'ajax_delete_employee' ] );
        add_action( 'wp_ajax_rsyi_hr_search_employees',      [ __CLASS__, 'ajax_search_employees' ] );
        add_action( 'wp_ajax_rsyi_hr_import_employees',      [ __CLASS__, 'ajax_import_employees' ] );
        add_action( 'wp_ajax_rsyi_hr_emp_template',          [ __CLASS__, 'ajax_download_emp_template' ] );
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
            $where   .= ' AND (e.full_name LIKE %s OR e.full_name_ar LIKE %s OR e.employee_number LIKE %s OR e.national_id LIKE %s OR e.email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
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

    /** موظف برقم الموظف */
    public static function get_by_employee_number( string $number ): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_employees';

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE employee_number = %s LIMIT 1", $number ), // phpcs:ignore
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

        $valid_marital   = [ 'single', 'married', 'divorced', 'widowed' ];
        $valid_military  = [ 'completed', 'exempt', 'pending', 'not_applicable' ];
        $valid_contracts = [ 'permanent', 'temporary', 'part_time', 'project' ];

        $fields = [
            // ── هوية ──────────────────────────────────────────────────────
            'full_name'       => sanitize_text_field( $data['full_name']    ?? '' ),
            'full_name_ar'    => isset( $data['full_name_ar'] )    ? sanitize_text_field( $data['full_name_ar'] )    : null,
            'employee_number' => isset( $data['employee_number'] ) ? sanitize_text_field( $data['employee_number'] ) : null,
            'national_id'     => isset( $data['national_id'] )     ? sanitize_text_field( $data['national_id'] )     : null,
            'date_of_birth'   => ! empty( $data['date_of_birth'] ) ? sanitize_text_field( $data['date_of_birth'] )   : null,
            // ── عمل ───────────────────────────────────────────────────────
            'department_id'   => ! empty( $data['department_id'] ) ? absint( $data['department_id'] )                : null,
            'job_title_id'    => ! empty( $data['job_title_id'] )  ? absint( $data['job_title_id'] )                 : null,
            'grade'           => isset( $data['grade'] )           ? sanitize_text_field( $data['grade'] )           : null,
            'hire_date'       => ! empty( $data['hire_date'] )     ? sanitize_text_field( $data['hire_date'] )       : null,
            'contract_start'  => ! empty( $data['contract_start'] )? sanitize_text_field( $data['contract_start'] )  : null,
            'contract_end'    => ! empty( $data['contract_end'] )  ? sanitize_text_field( $data['contract_end'] )    : null,
            'contract_type'   => isset( $data['contract_type'] ) && in_array( $data['contract_type'], $valid_contracts, true )
                                 ? $data['contract_type'] : null,
            'status'          => in_array( $data['status'] ?? '', [ 'active', 'inactive', 'on_leave' ], true )
                                 ? $data['status'] : 'active',
            // ── شخصية ─────────────────────────────────────────────────────
            'marital_status'  => isset( $data['marital_status'] ) && in_array( $data['marital_status'], $valid_marital, true )
                                 ? $data['marital_status'] : null,
            'religion'        => isset( $data['religion'] )        ? sanitize_text_field( $data['religion'] )        : null,
            'military_status' => isset( $data['military_status'] ) && in_array( $data['military_status'], $valid_military, true )
                                 ? $data['military_status'] : null,
            'education'       => isset( $data['education'] )       ? sanitize_text_field( $data['education'] )       : null,
            // ── تواصل وسكن ────────────────────────────────────────────────
            'phone'           => isset( $data['phone'] )           ? sanitize_text_field( $data['phone'] )           : null,
            'email'           => isset( $data['email'] )           ? sanitize_email( $data['email'] )                : null,
            'housing'         => isset( $data['housing'] )         ? sanitize_text_field( $data['housing'] )         : null,
            // ── تأمين وبنك ────────────────────────────────────────────────
            'insurance_number'=> isset( $data['insurance_number'] )? sanitize_text_field( $data['insurance_number'] ): null,
            'bank_name'       => isset( $data['bank_name'] )       ? sanitize_text_field( $data['bank_name'] )       : null,
            'bank_account'    => isset( $data['bank_account'] )    ? sanitize_text_field( $data['bank_account'] )    : null,
            // ── ملاحظات ───────────────────────────────────────────────────
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

    // ═══════════════════════════════════════════════════════════════════════
    //  CSV / Excel Import
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Column header → field name map for the import template.
     */
    private static function csv_columns(): array {
        return [
            'employee_number' => 'Employee No. / رقم الموظف',
            'full_name'       => 'Full Name (EN) * / الاسم (إنجليزي)*',
            'full_name_ar'    => 'Full Name (AR) / الاسم (عربي)',
            'national_id'     => 'National ID / الرقم القومي',
            'date_of_birth'   => 'Date of Birth (YYYY-MM-DD) / تاريخ الميلاد',
            'hire_date'       => 'Hire Date (YYYY-MM-DD) / تاريخ التعيين',
            'contract_start'  => 'Contract Start (YYYY-MM-DD) / بداية العقد',
            'contract_end'    => 'Contract End (YYYY-MM-DD) / نهاية العقد',
            'contract_type'   => 'Contract Type (permanent|temporary|part_time|project) / نوع العقد',
            'status'          => 'Status (active|inactive|on_leave) / الحالة',
            'department_name' => 'Department Name / اسم القسم',
            'job_title_name'  => 'Job Title / المسمى الوظيفي',
            'grade'           => 'Grade / الدرجة الوظيفية',
            'marital_status'  => 'Marital Status (single|married|divorced|widowed) / الحالة الاجتماعية',
            'religion'        => 'Religion (muslim|christian|other) / الديانة',
            'military_status' => 'Military Status (completed|exempt|pending|not_applicable) / التجنيد',
            'education'       => 'Education (elementary|middle|high_school|diploma|bachelor|master|doctorate) / المؤهل',
            'phone'           => 'Phone / الهاتف',
            'email'           => 'Email / البريد الإلكتروني',
            'housing'         => 'Housing / السكن',
            'insurance_number'=> 'Insurance No. / الرقم التأميني',
            'bank_name'       => 'Bank Name / اسم البنك',
            'bank_account'    => 'Bank Account / رقم الحساب',
            'notes'           => 'Notes / ملاحظات',
        ];
    }

    /**
     * Parse an uploaded CSV and insert/update employees.
     * Returns [ 'inserted' => int, 'updated' => int, 'errors' => string[] ]
     */
    public static function import_csv( string $file_path ): array {
        global $wpdb;

        $dept_table = $wpdb->prefix . 'rsyi_hr_departments';
        $jt_table   = $wpdb->prefix . 'rsyi_hr_job_titles';

        // Cache department & job-title name→id maps (case-insensitive)
        $dept_map = [];
        foreach ( (array) $wpdb->get_results( "SELECT id, name FROM {$dept_table}", ARRAY_A ) as $row ) { // phpcs:ignore
            $dept_map[ mb_strtolower( $row['name'] ) ] = (int) $row['id'];
        }

        $jt_map = [];
        foreach ( (array) $wpdb->get_results( "SELECT id, title FROM {$jt_table}", ARRAY_A ) as $row ) { // phpcs:ignore
            $jt_map[ mb_strtolower( $row['title'] ) ] = (int) $row['id'];
        }

        $columns  = array_keys( self::csv_columns() );
        $inserted = 0;
        $updated  = 0;
        $errors   = [];

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            return [ 'inserted' => 0, 'updated' => 0, 'errors' => [ __( 'تعذّر فتح الملف.', 'rsyi-hr' ) ] ];
        }

        // Strip UTF-8 BOM if present
        $bom = fread( $handle, 3 );
        if ( $bom !== "\xEF\xBB\xBF" ) {
            rewind( $handle );
        }

        // Read header row
        $header = fgetcsv( $handle );
        if ( ! $header ) {
            fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
            return [ 'inserted' => 0, 'updated' => 0, 'errors' => [ __( 'الملف فارغ أو تالف.', 'rsyi-hr' ) ] ];
        }

        // Map header positions → field names (match by the key part before ' / ')
        $col_index = [];
        foreach ( $header as $idx => $h ) {
            $h = trim( $h );
            // Match by exact column key or by the English portion before ' / '
            foreach ( $columns as $field ) {
                if ( mb_strtolower( $h ) === mb_strtolower( $field )
                     || mb_strtolower( explode( ' / ', $h )[0] ) === mb_strtolower( explode( ' / ', self::csv_columns()[ $field ] )[0] )
                     || mb_strtolower( $h ) === mb_strtolower( self::csv_columns()[ $field ] )
                ) {
                    $col_index[ $field ] = $idx;
                    break;
                }
            }
        }

        $row_num = 1;
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $row_num++;

            // Skip blank rows
            if ( empty( array_filter( $row ) ) ) {
                continue;
            }

            // Extract cell by field
            $get = static function ( string $field ) use ( $row, $col_index ): string {
                return isset( $col_index[ $field ], $row[ $col_index[ $field ] ] )
                    ? trim( $row[ $col_index[ $field ] ] )
                    : '';
            };

            $full_name = sanitize_text_field( $get( 'full_name' ) );
            if ( '' === $full_name ) {
                $errors[] = sprintf( __( 'صف %d: حقل الاسم (EN) مطلوب — تم تخطيه.', 'rsyi-hr' ), $row_num );
                continue;
            }

            // Resolve department by name
            $dept_name = mb_strtolower( $get( 'department_name' ) );
            $dept_id   = $dept_map[ $dept_name ] ?? null;

            // Resolve job title by name
            $jt_name = mb_strtolower( $get( 'job_title_name' ) );
            $jt_id   = $jt_map[ $jt_name ] ?? null;

            $data = [
                'employee_number' => $get( 'employee_number' ),
                'full_name'       => $full_name,
                'full_name_ar'    => sanitize_text_field( $get( 'full_name_ar' ) ),
                'national_id'     => sanitize_text_field( $get( 'national_id' ) ),
                'date_of_birth'   => $get( 'date_of_birth' ),
                'hire_date'       => $get( 'hire_date' ),
                'contract_start'  => $get( 'contract_start' ),
                'contract_end'    => $get( 'contract_end' ),
                'contract_type'   => $get( 'contract_type' ),
                'status'          => $get( 'status' ) ?: 'active',
                'department_id'   => $dept_id,
                'job_title_id'    => $jt_id,
                'grade'           => sanitize_text_field( $get( 'grade' ) ),
                'marital_status'  => $get( 'marital_status' ),
                'religion'        => $get( 'religion' ),
                'military_status' => $get( 'military_status' ),
                'education'       => $get( 'education' ),
                'phone'           => sanitize_text_field( $get( 'phone' ) ),
                'email'           => sanitize_email( $get( 'email' ) ),
                'housing'         => sanitize_text_field( $get( 'housing' ) ),
                'insurance_number'=> sanitize_text_field( $get( 'insurance_number' ) ),
                'bank_name'       => sanitize_text_field( $get( 'bank_name' ) ),
                'bank_account'    => sanitize_text_field( $get( 'bank_account' ) ),
                'notes'           => sanitize_textarea_field( $get( 'notes' ) ),
            ];

            // Check if employee_number exists → update
            $emp_number = $data['employee_number'];
            if ( '' !== $emp_number ) {
                $existing = self::get_by_employee_number( $emp_number );
                if ( $existing ) {
                    $data['id'] = $existing['id'];
                    self::save( $data );
                    $updated++;
                    continue;
                }
            }

            self::save( $data );
            $inserted++;
        }

        fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions

        return compact( 'inserted', 'updated', 'errors' );
    }

    /** AJAX: receive uploaded CSV, run import */
    public static function ajax_import_employees(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_employees' ) || wp_die( -1 );

        if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
            wp_send_json_error( [ 'message' => __( 'لم يتم رفع أي ملف.', 'rsyi-hr' ) ] );
        }

        $file      = $_FILES['csv_file']; // phpcs:ignore
        $mime_ok   = in_array( $file['type'], [ 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel' ], true );
        $ext_ok    = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) === 'csv';

        if ( ! $mime_ok && ! $ext_ok ) {
            wp_send_json_error( [ 'message' => __( 'نوع الملف غير مدعوم. استخدم ملف CSV.', 'rsyi-hr' ) ] );
        }

        $result = self::import_csv( $file['tmp_name'] );

        $msg = sprintf(
            /* translators: 1: inserted, 2: updated */
            __( 'تمت العملية: %1$d سجل جديد، %2$d سجل محدَّث.', 'rsyi-hr' ),
            $result['inserted'],
            $result['updated']
        );

        wp_send_json_success( [
            'message' => $msg,
            'errors'  => $result['errors'],
        ] );
    }

    /** AJAX: stream downloadable CSV template */
    public static function ajax_download_emp_template(): void {
        check_ajax_referer( 'rsyi_hr_emp_template', 'nonce' );
        current_user_can( 'rsyi_hr_manage_employees' ) || wp_die( -1 );

        $columns = self::csv_columns();

        // Headers must be sent before any output
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="employees-template.csv"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

        // UTF-8 BOM so Excel opens Arabic correctly
        fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions

        // Header row: use the human-readable label
        fputcsv( $out, array_values( $columns ) );

        // Two example rows
        fputcsv( $out, [
            'EMP-001', 'Ahmed Mohamed', 'أحمد محمد', '12345678901234', '1990-05-15',
            '2020-01-01', '2020-01-01', '2022-12-31', 'permanent', 'active',
            'Training Department', 'Instructor', 'Grade 3',
            'married', 'muslim', 'completed', 'bachelor',
            '01000000000', 'ahmed@example.com', 'Company Housing',
            '12345678', 'National Bank', '1234567890', '',
        ] );
        fputcsv( $out, [
            'EMP-002', 'Sara Ali', 'سارة علي', '98765432101234', '1995-08-22',
            '2022-03-15', '2022-03-15', '', 'temporary', 'active',
            'Administration', 'Secretary', 'Grade 2',
            'single', 'christian', 'not_applicable', 'master',
            '01100000000', 'sara@example.com', 'Private',
            '', 'Misr Bank', '9876543210', '',
        ] );

        fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        exit;
    }
}
