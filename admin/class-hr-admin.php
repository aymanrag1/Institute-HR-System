<?php
/**
 * HR Admin Menu & Pages
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Admin_Menu {

    public static function init(): void {
        add_action( 'admin_menu',             [ __CLASS__, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts',  [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_rsyi_hr_create_portal_page', [ __CLASS__, 'ajax_create_portal_page' ] );
    }

    public static function register_menus(): void {
        add_menu_page(
            __( 'الموارد البشرية', 'rsyi-hr' ),
            __( 'الموارد البشرية', 'rsyi-hr' ),
            'rsyi_hr_view_employees',
            'rsyi-hr',
            [ __CLASS__, 'page_dashboard' ],
            'dashicons-groups',
            25
        );

        add_submenu_page( 'rsyi-hr', __( 'لوحة التحكم', 'rsyi-hr' ), __( 'لوحة التحكم', 'rsyi-hr' ),
            'rsyi_hr_view_employees', 'rsyi-hr', [ __CLASS__, 'page_dashboard' ] );

        add_submenu_page( 'rsyi-hr', __( 'الموظفون', 'rsyi-hr' ), __( 'الموظفون', 'rsyi-hr' ),
            'rsyi_hr_view_employees', 'rsyi-hr-employees', [ __CLASS__, 'page_employees' ] );

        add_submenu_page( 'rsyi-hr', __( 'الأقسام', 'rsyi-hr' ), __( 'الأقسام', 'rsyi-hr' ),
            'rsyi_hr_view_departments', 'rsyi-hr-departments', [ __CLASS__, 'page_departments' ] );

        add_submenu_page( 'rsyi-hr', __( 'التقسيم الوظيفي', 'rsyi-hr' ), __( 'التقسيم الوظيفي', 'rsyi-hr' ),
            'rsyi_hr_view_job_titles', 'rsyi-hr-job-titles', [ __CLASS__, 'page_job_titles' ] );

        add_submenu_page( 'rsyi-hr', __( 'طلبات الإجازة', 'rsyi-hr' ), __( 'طلبات الإجازة', 'rsyi-hr' ),
            'rsyi_hr_view_leaves', 'rsyi-hr-leaves', [ __CLASS__, 'page_leaves' ] );

        add_submenu_page( 'rsyi-hr', __( 'الوقت الإضافي', 'rsyi-hr' ), __( 'الوقت الإضافي', 'rsyi-hr' ),
            'rsyi_hr_view_overtime', 'rsyi-hr-overtime', [ __CLASS__, 'page_overtime' ] );

        add_submenu_page( 'rsyi-hr', __( 'الحضور والانصراف', 'rsyi-hr' ), __( 'الحضور والانصراف', 'rsyi-hr' ),
            'rsyi_hr_view_attendance', 'rsyi-hr-attendance', [ __CLASS__, 'page_attendance' ] );

        add_submenu_page( 'rsyi-hr', __( 'المخالفات والجزاءات', 'rsyi-hr' ), __( 'المخالفات', 'rsyi-hr' ),
            'rsyi_hr_view_violations', 'rsyi-hr-violations', [ __CLASS__, 'page_violations' ] );

        add_submenu_page( 'rsyi-hr', __( 'الصلاحيات', 'rsyi-hr' ), __( 'الصلاحيات', 'rsyi-hr' ),
            'rsyi_hr_manage_permissions', 'rsyi-hr-permissions', [ __CLASS__, 'page_permissions' ] );

        add_submenu_page( 'rsyi-hr', __( 'بوابة الموظف', 'rsyi-hr' ), __( 'بوابة الموظف', 'rsyi-hr' ),
            'rsyi_hr_view_employees', 'rsyi-hr-portal', [ __CLASS__, 'page_portal' ] );
    }

    public static function enqueue_assets( string $hook ): void {
        if ( false === strpos( $hook, 'rsyi-hr' ) ) {
            return;
        }

        wp_enqueue_style(
            'rsyi-hr-admin',
            RSYI_HR_URL . 'assets/css/admin.css',
            [],
            RSYI_HR_VERSION
        );

        wp_enqueue_script(
            'rsyi-hr-signature-pad',
            RSYI_HR_URL . 'assets/js/signature-pad.js',
            [],
            RSYI_HR_VERSION,
            true
        );

        wp_enqueue_script(
            'rsyi-hr-admin',
            RSYI_HR_URL . 'assets/js/admin.js',
            [ 'jquery', 'rsyi-hr-signature-pad' ],
            RSYI_HR_VERSION,
            true
        );

        wp_localize_script( 'rsyi-hr-admin', 'rsyiHR', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'rsyi_hr_admin' ),
            'i18n'    => [
                'confirm_delete'    => __( 'هل أنت متأكد من الحذف؟ / Are you sure?', 'rsyi-hr' ),
                'saved'             => __( 'تم الحفظ بنجاح. / Saved successfully.', 'rsyi-hr' ),
                'error'             => __( 'حدث خطأ، حاول مجدداً. / An error occurred.', 'rsyi-hr' ),
                'required'          => __( 'هذا الحقل مطلوب.', 'rsyi-hr' ),
                'loading'           => __( 'جارٍ التحميل... / Loading…', 'rsyi-hr' ),
                'no_results'        => __( 'لا توجد نتائج. / No results.', 'rsyi-hr' ),
                'add_employee'      => __( 'إضافة موظف / Add Employee', 'rsyi-hr' ),
                'edit_employee'     => __( 'تعديل موظف / Edit Employee', 'rsyi-hr' ),
                'edit'              => __( 'تعديل / Edit', 'rsyi-hr' ),
                'delete'            => __( 'حذف / Delete', 'rsyi-hr' ),
                'active'            => __( 'نشط / Active', 'rsyi-hr' ),
                'inactive'          => __( 'غير نشط / Inactive', 'rsyi-hr' ),
                'on_leave'          => __( 'في إجازة / On Leave', 'rsyi-hr' ),
                'years'             => __( 'سنة / yrs', 'rsyi-hr' ),
                'approve'           => __( 'اعتماد / Approve', 'rsyi-hr' ),
                'reject'            => __( 'رفض / Reject', 'rsyi-hr' ),
                'pending'           => __( 'قيد الانتظار / Pending', 'rsyi-hr' ),
                'approved'          => __( 'معتمد / Approved', 'rsyi-hr' ),
                'rejected'          => __( 'مرفوض / Rejected', 'rsyi-hr' ),
                'manager_approved'  => __( 'اعتمد المدير / Manager Approved', 'rsyi-hr' ),
                'hr_approved'       => __( 'اعتمد الموارد البشرية / HR Approved', 'rsyi-hr' ),
                'dean_approved'     => __( 'معتمد نهائياً / Dean Approved', 'rsyi-hr' ),
                'present'           => __( 'حاضر / Present', 'rsyi-hr' ),
                'absent'            => __( 'غائب / Absent', 'rsyi-hr' ),
                'late'              => __( 'متأخر / Late', 'rsyi-hr' ),
                'leave'             => __( 'إجازة / Leave', 'rsyi-hr' ),
                'holiday'           => __( 'عطلة / Holiday', 'rsyi-hr' ),
                'import_success'    => __( 'تم استيراد %d سجل بنجاح.', 'rsyi-hr' ),
                'import_errors'     => __( 'أخطاء:', 'rsyi-hr' ),
                'can_manage_leaves' => current_user_can( 'rsyi_hr_manage_leaves' )     ? '1' : '0',
                'can_approve_dean'  => current_user_can( 'rsyi_hr_approve_leaves_dean' ) ? '1' : '0',
                'can_manage_ot'     => current_user_can( 'rsyi_hr_manage_overtime' )    ? '1' : '0',
                'can_approve_vio'   => current_user_can( 'rsyi_hr_approve_violations' ) ? '1' : '0',
            ],
        ] );
    }

    // ─── Pages ─────────────────────────────────────────────────────────────

    public static function page_dashboard(): void {
        $total_active   = Employees::count( 'active' );
        $total_inactive = Employees::count( 'inactive' );
        $total_leave    = Employees::count( 'on_leave' );
        $departments    = Departments::get_all();
        include RSYI_HR_DIR . 'admin/views/dashboard.php';
    }

    public static function page_employees(): void {
        $departments = Departments::get_all();
        $job_titles  = Departments::get_all_job_titles();
        include RSYI_HR_DIR . 'admin/views/employees.php';
    }

    public static function page_departments(): void {
        $departments = Departments::get_all( [ 'status' => 'all' ] );
        $employees   = Employees::get_all( [ 'status' => 'active' ] );
        include RSYI_HR_DIR . 'admin/views/departments.php';
    }

    public static function page_job_titles(): void {
        $job_titles  = Departments::get_all_job_titles( [ 'status' => 'all' ] );
        $departments = Departments::get_all();
        include RSYI_HR_DIR . 'admin/views/job-titles.php';
    }

    public static function page_leaves(): void {
        $employees = Employees::get_all( [ 'status' => 'all' ] );
        include RSYI_HR_DIR . 'admin/views/leaves.php';
    }

    public static function page_overtime(): void {
        $employees = Employees::get_all( [ 'status' => 'all' ] );
        include RSYI_HR_DIR . 'admin/views/overtime.php';
    }

    public static function page_attendance(): void {
        $employees = Employees::get_all( [ 'status' => 'all' ] );
        include RSYI_HR_DIR . 'admin/views/attendance.php';
    }

    public static function page_violations(): void {
        $employees = Employees::get_all( [ 'status' => 'all' ] );
        include RSYI_HR_DIR . 'admin/views/violations.php';
    }

    public static function page_permissions(): void {
        $users = get_users( [
            'role__in' => [ 'rsyi_dean', 'rsyi_hr_manager', 'rsyi_dept_head', 'rsyi_staff', 'rsyi_readonly', 'administrator' ],
            'orderby'  => 'display_name',
        ] );
        include RSYI_HR_DIR . 'admin/views/permissions.php';
    }

    public static function page_portal(): void {
        $page_id  = (int) get_option( 'rsyi_hr_portal_page_id', 0 );
        $page_url = ( $page_id && 'publish' === get_post_status( $page_id ) )
                    ? get_permalink( $page_id ) : '';
        include RSYI_HR_DIR . 'admin/views/portal-page.php';
    }

    public static function ajax_create_portal_page(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Forbidden' ] );
        }
        // Force-reset option so create_portal_page() will create a new page
        delete_option( 'rsyi_hr_portal_page_id' );
        DB_Installer::create_portal_page();
        $page_id  = (int) get_option( 'rsyi_hr_portal_page_id', 0 );
        $page_url = $page_id ? get_permalink( $page_id ) : '';
        if ( $page_url ) {
            wp_send_json_success( [ 'url' => $page_url ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'فشل إنشاء الصفحة.', 'rsyi-hr' ) ] );
        }
    }
}
