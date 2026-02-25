<?php
/**
 * HR Roles & Capabilities
 *
 * هذا الملف هو المرجع المركزي لجميع الأدوار والصلاحيات في المعهد.
 *
 * المبدأ:
 *   ١. HR Plugin تُعرِّف الأدوار المؤسسية الأساسية (Base Roles).
 *   ٢. كل plugin أخرى (Warehouse, Student Affairs…) تُضيف صلاحياتها
 *      على هذه الأدوار عبر hook: do_action('rsyi_hr_extend_roles').
 *   ٣. لا تنشئ أي plugin أخرى أدواراً جديدة مستقلة — توسِّع الموجودة فقط.
 *
 * التسلسل الهرمي للأدوار (من الأعلى):
 *   rsyi_dean              — عميد / المدير التنفيذي
 *   rsyi_hr_manager        — مدير الموارد البشرية
 *   rsyi_dept_head         — رئيس قسم
 *   rsyi_staff             — موظف
 *   rsyi_readonly          — مشاهد (قراءة فقط)
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Roles {

    /** مفتاح حفظ إصدار الأدوار في wp_options */
    const ROLES_VERSION_OPTION = 'rsyi_hr_roles_version';

    // ─── تعريف الصلاحيات الخاصة بـ HR Plugin ──────────────────────────────

    /**
     * الصلاحيات المرتبطة بالموارد البشرية حصراً.
     * كل plugin تضيف صلاحياتها الخاصة في rsyi_hr_extend_roles hook.
     *
     * @return array<string, string>  cap_key => وصف
     */
    public static function get_hr_caps(): array {
        return [
            // الموظفون
            'rsyi_hr_view_employees'      => 'عرض قائمة الموظفين',
            'rsyi_hr_manage_employees'    => 'إضافة / تعديل / حذف الموظفين',

            // الأقسام
            'rsyi_hr_view_departments'    => 'عرض الأقسام',
            'rsyi_hr_manage_departments'  => 'إضافة / تعديل / حذف الأقسام',

            // التقسيم الوظيفي
            'rsyi_hr_view_job_titles'     => 'عرض التقسيم الوظيفي',
            'rsyi_hr_manage_job_titles'   => 'إضافة / تعديل / حذف الوظائف',

            // طلبات الإجازة
            'rsyi_hr_submit_leave'        => 'رفع طلب إجازة (الموظف)',
            'rsyi_hr_view_leaves'         => 'عرض طلبات الإجازة',
            'rsyi_hr_manage_leaves'       => 'اعتماد / رفض طلبات الإجازة',
            'rsyi_hr_approve_leaves_dean' => 'تصديق الإجازة (العميد)',

            // طلبات الوقت الإضافي
            'rsyi_hr_submit_overtime'     => 'رفع طلب وقت إضافي (الموظف)',
            'rsyi_hr_view_overtime'       => 'عرض طلبات الوقت الإضافي',
            'rsyi_hr_manage_overtime'     => 'اعتماد / رفض الوقت الإضافي',

            // الحضور والانصراف
            'rsyi_hr_view_attendance'     => 'عرض الحضور والانصراف',
            'rsyi_hr_manage_attendance'   => 'تسجيل / تعديل الحضور والانصراف',

            // المخالفات والجزاءات
            'rsyi_hr_view_violations'     => 'عرض المخالفات والجزاءات',
            'rsyi_hr_manage_violations'   => 'إنشاء / تعديل المخالفات',
            'rsyi_hr_approve_violations'  => 'اعتماد المخالفات (العميد)',

            // الصلاحيات
            'rsyi_hr_manage_permissions'  => 'إدارة صلاحيات المستخدمين',

            // البوابة الإلكترونية
            'rsyi_hr_portal_access'       => 'الوصول إلى بوابة الموظف',

            // الإعدادات والتقارير
            'rsyi_hr_manage_settings'     => 'إعدادات نظام الموارد البشرية',
            'rsyi_hr_view_reports'        => 'عرض تقارير الموارد البشرية',
        ];
    }

    // ─── تعريف الأدوار الأساسية ────────────────────────────────────────────

    /**
     * @return array<string, array{label: string, caps: array<string, bool>}>
     */
    private static function base_role_definitions(): array {
        $all_hr = array_fill_keys( array_keys( self::get_hr_caps() ), true );

        return [

            // ── عميد / مدير تنفيذي ───────────────────────────────────────
            'rsyi_dean' => [
                'label' => 'عميد / مدير تنفيذي',
                'caps'  => $all_hr,   // كامل صلاحيات HR + تمتد بها الـ plugins الأخرى
            ],

            // ── مدير الموارد البشرية ──────────────────────────────────────
            'rsyi_hr_manager' => [
                'label' => 'مدير الموارد البشرية',
                'caps'  => $all_hr,
            ],

            // ── رئيس قسم ─────────────────────────────────────────────────
            'rsyi_dept_head' => [
                'label' => 'رئيس قسم',
                'caps'  => [
                    'rsyi_hr_view_employees'    => true,
                    'rsyi_hr_view_departments'  => true,
                    'rsyi_hr_view_job_titles'   => true,
                    'rsyi_hr_view_reports'      => true,
                    'rsyi_hr_view_leaves'       => true,
                    'rsyi_hr_manage_leaves'     => true,
                    'rsyi_hr_view_overtime'     => true,
                    'rsyi_hr_manage_overtime'   => true,
                    'rsyi_hr_view_attendance'   => true,
                    'rsyi_hr_submit_leave'      => true,
                    'rsyi_hr_submit_overtime'   => true,
                    'rsyi_hr_portal_access'     => true,
                ],
            ],

            // ── موظف ─────────────────────────────────────────────────────
            'rsyi_staff' => [
                'label' => 'موظف',
                'caps'  => [
                    'rsyi_hr_view_departments'  => true,
                    'rsyi_hr_view_job_titles'   => true,
                    'rsyi_hr_submit_leave'      => true,
                    'rsyi_hr_submit_overtime'   => true,
                    'rsyi_hr_view_violations'   => true,
                    'rsyi_hr_portal_access'     => true,
                ],
            ],

            // ── مشاهد فقط ────────────────────────────────────────────────
            'rsyi_readonly' => [
                'label' => 'مشاهد فقط',
                'caps'  => [
                    'rsyi_hr_view_employees'    => true,
                    'rsyi_hr_view_departments'  => true,
                    'rsyi_hr_view_job_titles'   => true,
                    'rsyi_hr_portal_access'     => true,
                ],
            ],
        ];
    }

    // ─── إنشاء / مزامنة الأدوار ────────────────────────────────────────────

    /**
     * إنشاء الأدوار عند التفعيل الأول.
     */
    public static function add_roles(): void {
        $definitions = self::base_role_definitions();

        foreach ( $definitions as $slug => $def ) {
            remove_role( $slug );   // تحديث نظيف عند إعادة التفعيل
            add_role( $slug, $def['label'], array_merge( [ 'read' => true ], $def['caps'] ) );
        }

        self::sync_admin_caps( $definitions );

        // أعطِ الـ plugins الأخرى فرصة لتوسيع الأدوار فوراً
        do_action( 'rsyi_hr_extend_roles' );

        update_option( self::ROLES_VERSION_OPTION, RSYI_HR_VERSION );
    }

    /**
     * مزامنة الأدوار بدون deactivate/activate (تُستدعى عند plugins_loaded).
     */
    public static function sync_roles(): void {
        $definitions = self::base_role_definitions();

        foreach ( $definitions as $slug => $def ) {
            $role = get_role( $slug );
            if ( ! $role ) {
                add_role( $slug, $def['label'], array_merge( [ 'read' => true ], $def['caps'] ) );
            } else {
                foreach ( $def['caps'] as $cap => $grant ) {
                    if ( ! isset( $role->capabilities[ $cap ] ) ) {
                        $role->add_cap( $cap, $grant );
                    }
                }
            }
        }

        self::sync_admin_caps( $definitions );

        do_action( 'rsyi_hr_extend_roles' );

        update_option( self::ROLES_VERSION_OPTION, RSYI_HR_VERSION );
    }

    /**
     * منح جميع الصلاحيات لـ administrator تلقائياً.
     */
    private static function sync_admin_caps( array $definitions ): void {
        $admin = get_role( 'administrator' );
        if ( ! $admin ) {
            return;
        }

        $all_caps = [];
        foreach ( $definitions as $def ) {
            $all_caps = array_merge( $all_caps, $def['caps'] );
        }

        foreach ( array_keys( $all_caps ) as $cap ) {
            if ( ! isset( $admin->capabilities[ $cap ] ) ) {
                $admin->add_cap( $cap, true );
            }
        }
    }

    /**
     * حذف الأدوار عند إلغاء التفعيل.
     */
    public static function remove_roles(): void {
        $slugs = array_keys( self::base_role_definitions() );
        foreach ( $slugs as $slug ) {
            remove_role( $slug );
        }

        // إزالة صلاحيات HR من administrator
        $admin    = get_role( 'administrator' );
        $hr_caps  = self::get_hr_caps();
        if ( $admin ) {
            foreach ( array_keys( $hr_caps ) as $cap ) {
                $admin->remove_cap( $cap );
            }
        }
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    /**
     * إرجاع تعريفات الأدوار (للاستخدام في صفحة الصلاحيات).
     */
    public static function get_definitions(): array {
        return self::base_role_definitions();
    }

    /**
     * إرجاع جميع مفاتيح صلاحيات HR.
     */
    public static function get_all_caps(): array {
        return array_keys( self::get_hr_caps() );
    }

    /**
     * التحقق إن كان المستخدم الحالي يملك أحد الأدوار الأساسية.
     */
    public static function current_user_is_hr_user(): bool {
        $hr_roles = array_keys( self::base_role_definitions() );
        $user     = wp_get_current_user();

        return (bool) array_intersect( $hr_roles, (array) $user->roles );
    }
}
