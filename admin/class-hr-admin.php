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

        add_submenu_page(
            'rsyi-hr',
            __( 'الموظفون', 'rsyi-hr' ),
            __( 'الموظفون', 'rsyi-hr' ),
            'rsyi_hr_view_employees',
            'rsyi-hr-employees',
            [ __CLASS__, 'page_employees' ]
        );

        add_submenu_page(
            'rsyi-hr',
            __( 'الأقسام', 'rsyi-hr' ),
            __( 'الأقسام', 'rsyi-hr' ),
            'rsyi_hr_view_departments',
            'rsyi-hr-departments',
            [ __CLASS__, 'page_departments' ]
        );

        add_submenu_page(
            'rsyi-hr',
            __( 'التقسيم الوظيفي', 'rsyi-hr' ),
            __( 'التقسيم الوظيفي', 'rsyi-hr' ),
            'rsyi_hr_view_job_titles',
            'rsyi-hr-job-titles',
            [ __CLASS__, 'page_job_titles' ]
        );

        add_submenu_page(
            'rsyi-hr',
            __( 'الصلاحيات', 'rsyi-hr' ),
            __( 'الصلاحيات', 'rsyi-hr' ),
            'rsyi_hr_manage_settings',
            'rsyi-hr-permissions',
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

        wp_localize_script( 'rsyi-hr-admin', 'rsyiHR', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'rsyi_hr_admin' ),
            'departments' => Departments::get_all( [ 'status' => 'all' ] ),
            'i18n'        => [
                'confirm_delete' => __( 'Are you sure you want to delete? / هل أنت متأكد من الحذف؟', 'rsyi-hr' ),
                'saved'          => __( 'Saved successfully. / تم الحفظ بنجاح.', 'rsyi-hr' ),
                'error'          => __( 'An error occurred, please try again. / حدث خطأ، حاول مجدداً.', 'rsyi-hr' ),
                'required'       => __( 'This field is required. / هذا الحقل مطلوب.', 'rsyi-hr' ),
                'loading'        => __( 'Loading... / جارٍ التحميل...', 'rsyi-hr' ),
                'no_results'     => __( 'No results found. / لا توجد نتائج.', 'rsyi-hr' ),
                'add_employee'   => __( 'Add Employee / إضافة موظف', 'rsyi-hr' ),
                'edit_employee'  => __( 'Edit Employee / تعديل موظف', 'rsyi-hr' ),
                'edit'           => __( 'Edit / تعديل', 'rsyi-hr' ),
                'delete'         => __( 'Delete / حذف', 'rsyi-hr' ),
                'active'         => __( 'Active / نشط', 'rsyi-hr' ),
                'inactive'       => __( 'Inactive / غير نشط', 'rsyi-hr' ),
                'on_leave'       => __( 'On Leave / في إجازة', 'rsyi-hr' ),
                'years'          => __( 'yrs / سنة', 'rsyi-hr' ),
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

    public static function page_permissions(): void {
        $hr_caps       = Roles::get_hr_caps();
        $definitions   = Roles::get_definitions();
        $extension_caps = Roles::get_extension_caps();
        include RSYI_HR_DIR . 'admin/views/permissions.php';
    }
}
