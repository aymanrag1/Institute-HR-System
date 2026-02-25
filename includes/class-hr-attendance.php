<?php
/**
 * HR Attendance — إدارة الحضور والانصراف
 *
 * يدعم:
 *   - الإدخال اليدوي
 *   - استيراد ملف CSV
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Attendance {

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_hr_save_attendance',       [ __CLASS__, 'ajax_save' ] );
        add_action( 'wp_ajax_rsyi_hr_get_attendance',        [ __CLASS__, 'ajax_get' ] );
        add_action( 'wp_ajax_rsyi_hr_delete_attendance',     [ __CLASS__, 'ajax_delete' ] );
        add_action( 'wp_ajax_rsyi_hr_import_attendance_csv', [ __CLASS__, 'ajax_import_csv' ] );
        add_action( 'wp_ajax_rsyi_hr_download_att_template', [ __CLASS__, 'ajax_download_template' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Static helpers
    // ═══════════════════════════════════════════════════════════════════════

    public static function get_all( array $args = [] ): array {
        global $wpdb;

        $att = $wpdb->prefix . 'rsyi_hr_attendance';
        $emp = $wpdb->prefix . 'rsyi_hr_employees';

        $defaults = [
            'employee_id'   => 0,
            'date_from'     => '',
            'date_to'       => '',
            'status'        => '',
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
            $where   .= ' AND a.attendance_date >= %s';
            $params[] = $args['date_from'];
        }
        if ( ! empty( $args['date_to'] ) ) {
            $where   .= ' AND a.attendance_date <= %s';
            $params[] = $args['date_to'];
        }
        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND a.status = %s';
            $params[] = $args['status'];
        }

        $offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
        $sql    = "SELECT a.*, e.full_name, e.full_name_ar, e.employee_number
                   FROM {$att} a
                   LEFT JOIN {$emp} e ON e.id = a.employee_id
                   WHERE {$where}
                   ORDER BY a.attendance_date DESC, e.full_name ASC
                   LIMIT %d OFFSET %d";

        $params[] = (int) $args['per_page'];
        $params[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ); // phpcs:ignore
    }

    /** حفظ سجل حضور (إضافة أو تعديل) */
    public static function save( array $data ): int|false {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_attendance';

        $employee_id = absint( $data['employee_id'] ?? 0 );
        $date        = sanitize_text_field( $data['attendance_date'] ?? '' );

        if ( ! $employee_id || ! $date ) {
            return false;
        }

        $valid_status = [ 'present', 'absent', 'late', 'leave', 'holiday' ];
        $status       = in_array( $data['status'] ?? '', $valid_status, true )
                        ? $data['status'] : 'present';

        $fields = [
            'employee_id'     => $employee_id,
            'attendance_date' => $date,
            'check_in'        => ! empty( $data['check_in'] )  ? sanitize_text_field( $data['check_in'] )  : null,
            'check_out'       => ! empty( $data['check_out'] ) ? sanitize_text_field( $data['check_out'] ) : null,
            'status'          => $status,
            'notes'           => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : null,
            'created_by'      => get_current_user_id() ?: null,
        ];

        // UPSERT: إذا وجد السجل نحدّثه
        $existing = $wpdb->get_var( // phpcs:ignore
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE employee_id = %d AND attendance_date = %s",
                $employee_id, $date
            )
        );

        if ( $existing ) {
            unset( $fields['employee_id'], $fields['attendance_date'], $fields['created_by'] );
            $wpdb->update( $table, $fields, [ 'id' => (int) $existing ] );
            return (int) $existing;
        }

        $wpdb->insert( $table, $fields );
        return (int) $wpdb->insert_id ?: false;
    }

    /** حذف سجل حضور */
    public static function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( $wpdb->prefix . 'rsyi_hr_attendance', [ 'id' => $id ] );
    }

    /**
     * استيراد من CSV
     *
     * تنسيق السطر: employee_number,attendance_date,check_in,check_out,status,notes
     * أو:           employee_id,attendance_date,check_in,check_out,status,notes
     */
    public static function import_csv( string $csv_content ): array {
        $lines   = explode( "\n", trim( $csv_content ) );
        $results = [ 'success' => 0, 'errors' => [] ];

        if ( empty( $lines ) ) {
            return $results;
        }

        // تخطي السطر الأول (header)
        $header = array_shift( $lines );

        foreach ( $lines as $line_num => $line ) {
            $line = trim( $line );
            if ( empty( $line ) ) {
                continue;
            }

            $cols = str_getcsv( $line );

            if ( count( $cols ) < 2 ) {
                $results['errors'][] = sprintf( __( 'سطر %d: بيانات غير كافية.', 'rsyi-hr' ), $line_num + 2 );
                continue;
            }

            // البحث عن الموظف برقم الموظف
            $employee_number = sanitize_text_field( $cols[0] ?? '' );
            $emp = self::get_employee_by_number( $employee_number );

            if ( ! $emp ) {
                $results['errors'][] = sprintf(
                    __( 'سطر %d: الموظف "%s" غير موجود.', 'rsyi-hr' ),
                    $line_num + 2,
                    esc_html( $employee_number )
                );
                continue;
            }

            $data = [
                'employee_id'     => $emp['id'],
                'attendance_date' => sanitize_text_field( $cols[1] ?? '' ),
                'check_in'        => sanitize_text_field( $cols[2] ?? '' ),
                'check_out'       => sanitize_text_field( $cols[3] ?? '' ),
                'status'          => sanitize_text_field( $cols[4] ?? 'present' ),
                'notes'           => sanitize_text_field( $cols[5] ?? '' ),
            ];

            $id = self::save( $data );
            if ( $id ) {
                $results['success']++;
            } else {
                $results['errors'][] = sprintf( __( 'سطر %d: فشل الحفظ.', 'rsyi-hr' ), $line_num + 2 );
            }
        }

        return $results;
    }

    private static function get_employee_by_number( string $number ): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_hr_employees';

        if ( is_numeric( $number ) && strlen( $number ) < 10 ) {
            // قد يكون employee_id
            $row = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d OR employee_number = %s LIMIT 1", (int) $number, $number ), // phpcs:ignore
                ARRAY_A
            );
        } else {
            $row = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM {$table} WHERE employee_number = %s LIMIT 1", $number ), // phpcs:ignore
                ARRAY_A
            );
        }

        return $row ?: null;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_save(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_attendance' ) || wp_die( -1 );

        $data = array_map( 'sanitize_text_field', wp_unslash( $_POST ) ); // phpcs:ignore
        $id   = self::save( $data );

        false === $id
            ? wp_send_json_error( [ 'message' => __( 'بيانات غير مكتملة.', 'rsyi-hr' ) ] )
            : wp_send_json_success( [ 'id' => $id ] );
    }

    public static function ajax_get(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_view_attendance' ) || wp_die( -1 );

        $args = [
            'employee_id' => absint( $_POST['employee_id'] ?? 0 ),               // phpcs:ignore
            'date_from'   => sanitize_text_field( $_POST['date_from'] ?? '' ),   // phpcs:ignore
            'date_to'     => sanitize_text_field( $_POST['date_to']   ?? '' ),   // phpcs:ignore
            'status'      => sanitize_text_field( $_POST['status']    ?? '' ),   // phpcs:ignore
            'per_page'    => absint( $_POST['per_page'] ?? 50 ),                 // phpcs:ignore
            'page'        => absint( $_POST['page']     ?? 1 ),                  // phpcs:ignore
        ];

        wp_send_json_success( self::get_all( $args ) );
    }

    public static function ajax_delete(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_attendance' ) || wp_die( -1 );

        $id = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
        self::delete( $id )
            ? wp_send_json_success()
            : wp_send_json_error( [ 'message' => __( 'تعذّر الحذف.', 'rsyi-hr' ) ] );
    }

    public static function ajax_import_csv(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_attendance' ) || wp_die( -1 );

        if ( empty( $_FILES['csv_file']['tmp_name'] ) ) { // phpcs:ignore
            wp_send_json_error( [ 'message' => __( 'لم يتم رفع أي ملف.', 'rsyi-hr' ) ] );
            return;
        }

        $file    = $_FILES['csv_file']['tmp_name']; // phpcs:ignore
        $content = file_get_contents( $file ); // phpcs:ignore
        if ( false === $content ) {
            wp_send_json_error( [ 'message' => __( 'فشل قراءة الملف.', 'rsyi-hr' ) ] );
            return;
        }

        $results = self::import_csv( $content );
        wp_send_json_success( $results );
    }

    public static function ajax_download_template(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_manage_attendance' ) || wp_die( -1 );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="attendance-template.csv"' );

        $rows = [
            [ 'employee_number', 'attendance_date', 'check_in', 'check_out', 'status', 'notes' ],
            [ 'رقم_الموظف', 'YYYY-MM-DD', 'HH:MM', 'HH:MM', 'present/absent/late/leave/holiday', 'ملاحظات' ],
            [ 'EMP-001', '2024-01-01', '08:00', '16:00', 'present', '' ],
            [ 'EMP-002', '2024-01-01', '08:30', '16:00', 'late', 'تأخير 30 دقيقة' ],
            [ 'EMP-003', '2024-01-01', '', '', 'absent', '' ],
        ];

        $output = fopen( 'php://output', 'w' ); // phpcs:ignore
        // BOM for Excel UTF-8
        fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ); // phpcs:ignore
        foreach ( $rows as $row ) {
            fputcsv( $output, $row ); // phpcs:ignore
        }
        fclose( $output ); // phpcs:ignore
        exit;
    }
}
