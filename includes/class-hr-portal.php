<?php
/**
 * HR Employee Portal — بوابة الموظف
 *
 * Shortcodes:
 *   [rsyi_hr_portal]         — لوحة تحكم الموظف الكاملة
 *   [rsyi_hr_leave_form]     — نموذج طلب الإجازة
 *   [rsyi_hr_overtime_form]  — نموذج طلب العمل الإضافي
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Portal {

    public static function init(): void {
        add_shortcode( 'rsyi_hr_portal',        [ __CLASS__, 'shortcode_portal' ] );
        add_shortcode( 'rsyi_hr_leave_form',    [ __CLASS__, 'shortcode_leave_form' ] );
        add_shortcode( 'rsyi_hr_overtime_form', [ __CLASS__, 'shortcode_overtime_form' ] );

        add_action( 'wp_enqueue_scripts',           [ __CLASS__, 'enqueue_portal_assets' ] );
        add_action( 'wp_ajax_rsyi_hr_upload_signature', [ __CLASS__, 'ajax_upload_signature' ] );
        add_action( 'wp_ajax_rsyi_hr_get_my_profile',   [ __CLASS__, 'ajax_get_my_profile' ] );
    }

    public static function enqueue_portal_assets(): void {
        if ( ! is_user_logged_in() ) {
            return;
        }

        wp_enqueue_style(
            'rsyi-hr-portal',
            RSYI_HR_URL . 'assets/css/portal.css',
            [],
            RSYI_HR_VERSION
        );

        wp_enqueue_script(
            'rsyi-hr-portal',
            RSYI_HR_URL . 'assets/js/portal.js',
            [ 'jquery' ],
            RSYI_HR_VERSION,
            true
        );

        wp_localize_script( 'rsyi-hr-portal', 'rsyiHRPortal', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'rsyi_hr_portal' ),
            'userId'  => get_current_user_id(),
            'i18n'    => [
                'loading'        => __( 'جارٍ التحميل...', 'rsyi-hr' ),
                'saved'          => __( 'تم الحفظ بنجاح.', 'rsyi-hr' ),
                'error'          => __( 'حدث خطأ، حاول مجدداً.', 'rsyi-hr' ),
                'confirm_submit' => __( 'هل تريد رفع الطلب؟ لن يمكن تعديله بعد ذلك.', 'rsyi-hr' ),
                'clear_sig'      => __( 'هل تريد مسح التوقيع؟', 'rsyi-hr' ),
                'sign_required'  => __( 'يجب وضع التوقيع الإلكتروني قبل الإرسال.', 'rsyi-hr' ),
            ],
        ] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  Shortcodes
    // ═══════════════════════════════════════════════════════════════════════

    public static function shortcode_portal( array $atts ): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="rsyi-portal-notice">' .
                   esc_html__( 'يجب تسجيل الدخول للوصول إلى بوابة الموظف.', 'rsyi-hr' ) .
                   '</div>';
        }

        $user_id = get_current_user_id();
        $emp     = Employees::get_by_user_id( $user_id );

        ob_start();
        include RSYI_HR_DIR . 'portal/employee-dashboard.php';
        return ob_get_clean();
    }

    public static function shortcode_leave_form( array $atts ): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="rsyi-portal-notice">' .
                   esc_html__( 'يجب تسجيل الدخول.', 'rsyi-hr' ) .
                   '</div>';
        }

        $user_id = get_current_user_id();
        $emp     = Employees::get_by_user_id( $user_id );

        ob_start();
        include RSYI_HR_DIR . 'portal/leave-form.php';
        return ob_get_clean();
    }

    public static function shortcode_overtime_form( array $atts ): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="rsyi-portal-notice">' .
                   esc_html__( 'يجب تسجيل الدخول.', 'rsyi-hr' ) .
                   '</div>';
        }

        $user_id = get_current_user_id();
        $emp     = Employees::get_by_user_id( $user_id );

        ob_start();
        include RSYI_HR_DIR . 'portal/overtime-form.php';
        return ob_get_clean();
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * رفع صورة التوقيع الإلكتروني.
     */
    public static function ajax_upload_signature(): void {
        check_ajax_referer( 'rsyi_hr_portal', 'nonce' );
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error( [ 'message' => __( 'غير مصرح.', 'rsyi-hr' ) ] );
        }

        $emp = Employees::get_by_user_id( $user_id );
        if ( ! $emp ) {
            wp_send_json_error( [ 'message' => __( 'لم يتم ربط حسابك بسجل موظف.', 'rsyi-hr' ) ] );
        }

        if ( empty( $_FILES['signature_file'] ) || UPLOAD_ERR_OK !== $_FILES['signature_file']['error'] ) { // phpcs:ignore
            wp_send_json_error( [ 'message' => __( 'لم يتم رفع الملف.', 'rsyi-hr' ) ] );
        }

        $file     = $_FILES['signature_file']; // phpcs:ignore
        $ext      = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        $allowed  = [ 'png', 'jpg', 'jpeg', 'gif' ];

        if ( ! in_array( $ext, $allowed, true ) ) {
            wp_send_json_error( [ 'message' => __( 'صيغة الملف غير مدعومة. (PNG/JPG فقط)', 'rsyi-hr' ) ] );
        }

        // استخدام wp_handle_upload
        $overrides = [ 'test_form' => false ];
        $uploaded  = wp_handle_upload( $file, $overrides );

        if ( isset( $uploaded['error'] ) ) {
            wp_send_json_error( [ 'message' => $uploaded['error'] ] );
        }

        // حفظ URL التوقيع في سجل الموظف
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_hr_employees',
            [ 'signature_url' => esc_url_raw( $uploaded['url'] ) ],
            [ 'id'            => $emp['id'] ]
        );

        wp_send_json_success( [ 'url' => $uploaded['url'], 'message' => __( 'تم رفع التوقيع بنجاح.', 'rsyi-hr' ) ] );
    }

    public static function ajax_get_my_profile(): void {
        check_ajax_referer( 'rsyi_hr_portal', 'nonce' );
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error();
        }
        $emp = Employees::get_by_user_id( $user_id );
        $emp ? wp_send_json_success( $emp ) : wp_send_json_error();
    }
}
