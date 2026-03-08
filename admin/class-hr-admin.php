<?php
/**
 * HR Admin Menu & Pages — v2.1.0
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Admin_Menu {

    public static function init(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
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

        add_submenu_page( 'rsyi-hr',
            __( 'لوحة التحكم', 'rsyi-hr' ), __( 'لوحة التحكم', 'rsyi-hr' ),
            'rsyi_hr_view_employees', 'rsyi-hr',
            [ __CLASS__, 'page_dashboard' ]
        );

        add_submenu_page( 'rsyi-hr',
            __( 'الموظفون', 'rsyi-hr' ), __( 'الموظفون', 'rsyi-hr' ),
            'rsyi_hr_view_employees', 'rsyi-hr-employees',
            [ __CLASS__, 'page_employees' ]
        );

        add_submenu_page( 'rsyi-hr',
            __( 'الأقسام', 'rsyi-hr' ), __( 'الأقسام', 'rsyi-hr' ),
            'rsyi_hr_view_departments', 'rsyi-hr-departments',
            [ __CLASS__, 'page_departments' ]
        );

        add_submenu_page( 'rsyi-hr',
            __( 'التقسيم الوظيفي', 'rsyi-hr' ), __( 'التقسيم الوظيفي', 'rsyi-hr' ),
            'rsyi_hr_view_job_titles', 'rsyi-hr-job-titles',
            [ __CLASS__, 'page_job_titles' ]
        );

        add_submenu_page( 'rsyi-hr',
            __( 'طلبات الإجازة', 'rsyi-hr' ), __( 'طلبات الإجازة', 'rsyi-hr' ),
            'rsyi_hr_manage_leaves', 'rsyi-hr-leaves',
            [ __CLASS__, 'page_leaves' ]
        );

        add_submenu_page( 'rsyi-hr',
            __( 'رصيد الإجازات', 'rsyi-hr' ), __( 'رصيد الإجازات', 'rsyi-hr' ),
            'rsyi_hr_manage_settings', 'rsyi-hr-leave-balance',
            [ __CLASS__, 'page_leave_balance' ]
        );

        add_submenu_page( 'rsyi-hr',
            __( 'العمل الإضافي', 'rsyi-hr' ), __( 'العمل الإضافي', 'rsyi-hr' ),
            'rsyi_hr_manage_overtime', 'rsyi-hr-overtime',
            [ __CLASS__, 'page_overtime' ]
        );

        add_submenu_page( 'rsyi-hr',
            __( 'الحضور والانصراف', 'rsyi-hr' ), __( 'الحضور والانصراف', 'rsyi-hr' ),
            'rsyi_hr_manage_attendance', 'rsyi-hr-attendance',
            [ __CLASS__, 'page_attendance' ]
        );

        add_submenu_page( 'rsyi-hr',
            __( 'المخالفات والجزاءات', 'rsyi-hr' ), __( 'المخالفات والجزاءات', 'rsyi-hr' ),
            'rsyi_hr_manage_violations', 'rsyi-hr-violations',
            [ __CLASS__, 'page_violations' ]
        );

        add_submenu_page( 'rsyi-hr',
            __( 'الصلاحيات', 'rsyi-hr' ), __( 'الصلاحيات', 'rsyi-hr' ),
            'rsyi_hr_manage_settings', 'rsyi-hr-permissions',
            [ __CLASS__, 'page_permissions' ]
        );
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
            'rsyi-hr-admin',
            RSYI_HR_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            RSYI_HR_VERSION,
            true
        );

        // WP Media uploader for signature
        wp_enqueue_media();

        wp_localize_script( 'rsyi-hr-admin', 'rsyiHR', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'rsyi_hr_admin' ),
            'departments' => Departments::get_all( [ 'status' => 'all' ] ),
            'employees'   => Employees::get_all( [ 'status' => 'active' ] ),
            'i18n'        => [
                'confirm_delete'  => __( 'هل أنت متأكد من الحذف؟ / Are you sure to delete?', 'rsyi-hr' ),
                'confirm_approve' => __( 'هل تريد الاعتماد؟ / Approve this request?', 'rsyi-hr' ),
                'saved'           => __( 'تم الحفظ بنجاح. / Saved successfully.', 'rsyi-hr' ),
                'approved'        => __( 'تم الاعتماد بنجاح.', 'rsyi-hr' ),
                'rejected'        => __( 'تم الرفض.', 'rsyi-hr' ),
                'error'           => __( 'حدث خطأ، حاول مجدداً.', 'rsyi-hr' ),
                'required'        => __( 'هذا الحقل مطلوب.', 'rsyi-hr' ),
                'loading'         => __( 'جارٍ التحميل...', 'rsyi-hr' ),
                'no_results'      => __( 'لا توجد نتائج.', 'rsyi-hr' ),
                'add_employee'    => __( 'إضافة موظف', 'rsyi-hr' ),
                'edit_employee'   => __( 'تعديل موظف', 'rsyi-hr' ),
                'edit'            => __( 'تعديل', 'rsyi-hr' ),
                'delete'          => __( 'حذف', 'rsyi-hr' ),
                'approve'         => __( 'اعتماد', 'rsyi-hr' ),
                'reject'          => __( 'رفض', 'rsyi-hr' ),
                'print'           => __( 'طباعة', 'rsyi-hr' ),
                'active'          => __( 'نشط', 'rsyi-hr' ),
                'inactive'        => __( 'غير نشط', 'rsyi-hr' ),
                'on_leave'        => __( 'في إجازة', 'rsyi-hr' ),
                'years'           => __( 'سنة / yrs', 'rsyi-hr' ),
                'import_success'  => __( 'تم الاستيراد بنجاح.', 'rsyi-hr' ),
                'perms_saved'     => __( 'تم حفظ الصلاحيات.', 'rsyi-hr' ),
                'perms_reset'     => __( 'تم إعادة ضبط الصلاحيات.', 'rsyi-hr' ),
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
        $departments = Departments::get_all( [ 'status' => 'all' ] );
        $job_titles  = Departments::get_all_job_titles( [ 'status' => 'all' ] );
        $all_employees = Employees::get_all( [ 'status' => 'active' ] );
        include RSYI_HR_DIR . 'admin/views/employees.php';
    }

    public static function page_departments(): void {
        $departments = Departments::get_all( [ 'status' => 'all' ] );
        $employees   = Employees::get_all( [ 'status' => 'active' ] );
        include RSYI_HR_DIR . 'admin/views/departments.php';
    }

    public static function page_job_titles(): void {
        $job_titles  = Departments::get_all_job_titles( [ 'status' => 'all' ] );
        $departments = Departments::get_all( [ 'status' => 'all' ] );
        include RSYI_HR_DIR . 'admin/views/job-titles.php';
    }

    public static function page_leaves(): void {
        $employees = Employees::get_all( [ 'status' => 'active' ] );
        include RSYI_HR_DIR . 'admin/views/leaves.php';
    }

    public static function page_leave_balance(): void {
        $employees = Employees::get_all( [ 'status' => 'active' ] );
        include RSYI_HR_DIR . 'admin/views/leave-balance.php';
    }

    public static function page_overtime(): void {
        $employees = Employees::get_all( [ 'status' => 'active' ] );
        include RSYI_HR_DIR . 'admin/views/overtime.php';
    }

    public static function page_attendance(): void {
        $employees   = Employees::get_all( [ 'status' => 'active' ] );
        $departments = Departments::get_all( [ 'status' => 'all' ] );
        include RSYI_HR_DIR . 'admin/views/attendance.php';
    }

    public static function page_violations(): void {
        $employees = Employees::get_all( [ 'status' => 'active' ] );
        include RSYI_HR_DIR . 'admin/views/violations.php';
    }

    public static function page_permissions(): void {
        $hr_caps        = Roles::get_hr_caps();
        $definitions    = Roles::get_definitions();
        $extension_caps = Roles::get_extension_caps();
        $all_users      = get_users( [ 'number' => 200 ] );
        $modules        = Permissions_Mgr::get_modules();
        include RSYI_HR_DIR . 'admin/views/permissions.php';
    }
}
