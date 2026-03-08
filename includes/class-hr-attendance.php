<?php
/**
 * HR Attendance — الحضور والانصراف
 *
 * يدعم:
 *   - التسجيل اليدوي من مدير الموارد البشرية
 *   - الاستيراد عبر Excel (CSV أو XLSX)
 *   - تنزيل نموذج Excel الجاهز
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Attendance {

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_get_attendance',        [ __CLASS__, 'ajax_get_attendance' ] );
        add_action( 'wp_ajax_rsyi_hr_save_attendance',       [ __CLASS__, 'ajax_save_attendance' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_attendance',     [ __CLASS__, 'ajax_delete_attendance' ] );
        add_action( 'wp_ajax_rsyi_hr_import_attendance',     [ __CLASS__, 'ajax_import_attendance' ] );
        add_action( 'wp_ajax_rsyi_hr_download_att_template', [ __CLASS__, 'ajax_download_template' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static Helpers
    // ═══════════════════════════════════════════════════════════════════════

    public static function get_all( array $args = [] ): array {
        global $wpdb;
        $tbl = $wpdb->prefix . 'rsyi_hr_attendance';
        $emp = $wpdb->prefix . 'rsyi_hr_employees';

        $defaults = [
            'employee_id'   => 0,
            'date_from'     => '',
            'date_to'       => '',
            'att_type'      => '',
            'per_page'      => 50,
            'page'          => 1,
        ];
        $args   = wp_parse_args( $args, $defaults );
        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['employee_id'] ) ) {
            $where   .= ' AND a.employee_id = %d';
            $params[] = (int) $args['employee_id'];
        }
        if ( ! empty( $args['date_from'] ) ) {
            $where   .= ' AND a.att_date >= %s';
            $params[] = sanitize_text_field( $args['date_from'] );
        }
        if ( ! empty( $args['date_to'] ) ) {
            $where   .= ' AND a.att_date <= %s';
            $params[] = sanitize_text_field( $args['date_to'] );
        }
        if ( ! empty( $args['att_type'] ) ) {
            $where   .= ' AND a.att_type = %s';
            $params[] = sanitize_text_field( $args['att_type'] );
        }

        $sql = "SELECT a.*, e.full_name AS employee_name, e.full_name_ar AS employee_name_ar, e.employee_number
                FROM {$tbl} a
                LEFT JOIN {$emp} e ON e.id = a.employee_id
                WHERE {$where}
                ORDER BY a.att_date DESC, e.full_name ASC";

        $page   = max( 1, (int) $args['page'] );
        $offset = ( $page - 1 ) * (int) $args['per_page'];
        $sql   .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['per_page'], $offset ); // phpcs:ignore

        return empty( $params )
            ? $wpdb->get_results( $sql, ARRAY_A ) // phpcs:ignore
            : $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ); // phpcs:ignore
    }

    public static function save( array $data ): int|false {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_attendance';

        $valid_types = [ 'present', 'absent', 'late', 'half_day', 'holiday', 'vacation' ];

        $fields = [
            'employee_id' => absint( $data['employee_id'] ?? 0 ),
            'att_date'    => sanitize_text_field( $data['att_date'] ?? '' ),
            'check_in'    => ! empty( $data['check_in'] )  ? sanitize_text_field( $data['check_in'] )  : null,
            'check_out'   => ! empty( $data['check_out'] ) ? sanitize_text_field( $data['check_out'] ) : null,
            'att_type'    => in_array( $data['att_type'] ?? '', $valid_types, true )
                             ? $data['att_type'] : 'present',
            'notes'       => sanitize_textarea_field( wp_unslash( $data['notes'] ?? '' ) ),
            'recorded_by' => get_current_user_id() ?: null,
        ];

        if ( empty( $fields['employee_id'] ) || empty( $fields['att_date'] ) ) {
            return false;
        }

        // Upsert: إذا وُجد سجل بنفس الموظف واليوم → تحديث
        $existing = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
            "SELECT id FROM {$table} WHERE employee_id = %d AND att_date = %s",
            $fields['employee_id'], $fields['att_date']
        ) );

        if ( $existing ) {
            $wpdb->update( $table, $fields, [ 'id' => (int) $existing ] );
            return (int) $existing;
        }

        $wpdb->insert( $table, $fields );
        return (int) $wpdb->insert_id;
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $wpdb->prefix . 'rsyi_hr_attendance', [ 'id' => $id ] );
    }

    /**
     * استيراد من CSV.
     * يتوقع الأعمدة: employee_number, att_date, check_in, check_out, att_type, notes
     */
    public static function import_csv( string $file_path ): array {
        $result  = [ 'inserted' => 0, 'updated' => 0, 'errors' => [] ];
        $handle  = fopen( $file_path, 'r' ); // phpcs:ignore
        if ( ! $handle ) {
            $result['errors'][] = __( 'تعذّر فتح الملف.', 'rsyi-hr' );
            return $result;
        }

        // Strip UTF-8 BOM (Excel/fingerprint machine exports)
        $bom = fread( $handle, 3 ); // phpcs:ignore
        if ( $bom !== "\xEF\xBB\xBF" ) {
            rewind( $handle ); // phpcs:ignore
        }

        $header = fgetcsv( $handle );
        if ( ! $header ) {
            fclose( $handle ); // phpcs:ignore
            $result['errors'][] = __( 'الملف فارغ أو تالف.', 'rsyi-hr' );
            return $result;
        }

        // normalize header (trim + remove any remaining BOM bytes)
        $header = array_map( static function ( string $h ): string {
            return trim( str_replace( "\xEF\xBB\xBF", '', $h ) );
        }, $header );
        $row_num = 1;

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $row_num++;
            if ( count( $row ) !== count( $header ) ) {
                $result['errors'][] = sprintf( __( 'السطر %d: عدد الأعمدة غير متطابق.', 'rsyi-hr' ), $row_num );
                continue;
            }

            $data_row = array_combine( $header, $row );

            // البحث عن الموظف برقم الموظف
            $emp = Employees::get_by_employee_number( trim( $data_row['employee_number'] ?? '' ) );
            if ( ! $emp ) {
                $result['errors'][] = sprintf(
                    __( 'السطر %d: رقم الموظف "%s" غير موجود.', 'rsyi-hr' ),
                    $row_num,
                    $data_row['employee_number'] ?? ''
                );
                continue;
            }

            $data = [
                'employee_id' => $emp['id'],
                'att_date'    => trim( $data_row['att_date']  ?? '' ),
                'check_in'    => trim( $data_row['check_in']  ?? '' ),
                'check_out'   => trim( $data_row['check_out'] ?? '' ),
                'att_type'    => trim( $data_row['att_type']  ?? 'present' ),
                'notes'       => trim( $data_row['notes']     ?? '' ),
            ];

            $id = self::save( $data );
            if ( $id ) {
                $result['inserted']++;
            } else {
                $result['errors'][] = sprintf( __( 'السطر %d: خطأ في الحفظ.', 'rsyi-hr' ), $row_num );
            }
        }

        fclose( $handle ); // phpcs:ignore
        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX Handlers
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_get_attendance(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_attendance' ) || wp_die( -1 );

        $args = [
            'employee_id' => absint( $_POST['employee_id']  ?? 0 ),                // phpcs:ignore
            'date_from'   => sanitize_text_field( $_POST['date_from'] ?? '' ),    // phpcs:ignore
            'date_to'     => sanitize_text_field( $_POST['date_to']   ?? '' ),    // phpcs:ignore
            'att_type'    => sanitize_text_field( $_POST['att_type']  ?? '' ),    // phpcs:ignore
        ];

        wp_send_json_success( self::get_all( $args ) );
    }

    public static function ajax_save_attendance(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_attendance' ) || wp_die( -1 );

        $data = wp_unslash( $_POST ); // phpcs:ignore
        $id   = self::save( $data );
        $id ? wp_send_json_success( [ 'id' => $id ] ) : wp_send_json_error( [ 'message' => __( 'البيانات ناقصة.', 'rsyi-hr' ) ] );
    }

    public static function ajax_delete_attendance(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_attendance' ) || wp_die( -1 );

        self::delete( absint( $_POST['id'] ?? 0 ) ) ? wp_send_json_success() : wp_send_json_error(); // phpcs:ignore
    }

    public static function ajax_import_attendance(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_attendance' ) || wp_die( -1 );

        if ( empty( $_FILES['att_file'] ) || UPLOAD_ERR_OK !== $_FILES['att_file']['error'] ) { // phpcs:ignore
            wp_send_json_error( [ 'message' => __( 'لم يتم رفع أي ملف.', 'rsyi-hr' ) ] );
        }

        $file     = $_FILES['att_file']; // phpcs:ignore
        $tmp_path = $file['tmp_name'];

        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, [ 'csv' ], true ) ) {
            wp_send_json_error( [ 'message' => __( 'يُقبَل فقط ملفات CSV.', 'rsyi-hr' ) ] );
        }

        $result = self::import_csv( $tmp_path );
        wp_send_json_success( $result );
    }

    public static function ajax_download_template(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_attendance' ) || wp_die( -1 );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="rsyi-attendance-template.csv"' );

        $out = fopen( 'php://output', 'w' ); // phpcs:ignore
        // BOM لدعم UTF-8 في Excel
        fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore

        fputcsv( $out, [ 'employee_number', 'att_date', 'check_in', 'check_out', 'att_type', 'notes' ] );
        fputcsv( $out, [ 'EMP001', date( 'Y-m-d' ), '08:00', '16:00', 'present', '' ] );
        fputcsv( $out, [ 'EMP002', date( 'Y-m-d' ), '', '', 'absent', 'مريض' ] );
        fclose( $out ); // phpcs:ignore
        exit;
    }
}
