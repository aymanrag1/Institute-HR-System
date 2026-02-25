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
 *      أو عبر الـ API الرسمي: RSYI_HR\Roles::register_extension_caps(…)
 *   ٣. لا تنشئ أي plugin أخرى أدواراً جديدة مستقلة — توسِّع الموجودة فقط.
 *
 * التسلسل الهرمي للأدوار (من الأعلى):
 *   rsyi_dean              — عميد / المدير التنفيذي
 *   rsyi_hr_manager        — مدير الموارد البشرية
 *   rsyi_dept_head         — رئيس قسم
 *   rsyi_staff             — موظف
 *   rsyi_readonly          — مشاهد (قراءة فقط)
 *
 * كيف تُسجِّل plugin خارجية صلاحياتها:
 *
 *   add_action( 'rsyi_hr_extend_roles', function() {
 *       RSYI_HR\Roles::register_extension_caps( 'rsyi-warehouse', [
 *           'rsyi_dean'      => [ 'iw_view_warehouse' => true, 'iw_approve_permits' => true ],
 *           'rsyi_dept_head' => [ 'iw_view_warehouse' => true ],
 *           'rsyi_staff'     => [ 'iw_view_warehouse' => true ],
 *       ], 'نظام المستودعات' );
 *   } );
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Roles {

    /** مفتاح حفظ إصدار الأدوار في wp_options */
    const ROLES_VERSION_OPTION = 'rsyi_hr_roles_version';

    /**
     * سجل صلاحيات الـ plugins الخارجية المسجَّلة في الجلسة الحالية.
     * يُستخدم للعرض في صفحة الصلاحيات ولمزامنة الأدمن.
     *
     * @var array<string, array{label: string, caps_by_role: array<string, array<string, bool>>}>
     */
    private static array $extension_caps = [];

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
            'rsyi_hr_view_employees'         => 'عرض قائمة الموظفين',
            'rsyi_hr_manage_employees'        => 'إضافة / تعديل / حذف الموظفين',

            // الأقسام
            'rsyi_hr_view_departments'        => 'عرض الأقسام',
            'rsyi_hr_manage_departments'      => 'إضافة / تعديل / حذف الأقسام',

            // التقسيم الوظيفي
            'rsyi_hr_view_job_titles'         => 'عرض التقسيم الوظيفي',
            'rsyi_hr_manage_job_titles'       => 'إضافة / تعديل / حذف الوظائف',

            // طلبات الإجازة
            'rsyi_hr_manage_leaves'           => 'إدارة طلبات الإجازة',
            'rsyi_hr_approve_leaves_manager'  => 'اعتماد الإجازات (مدير مباشر)',

            // العمل الإضافي
            'rsyi_hr_manage_overtime'         => 'إدارة طلبات العمل الإضافي',

            // الحضور والانصراف
            'rsyi_hr_manage_attendance'       => 'إدارة الحضور والانصراف',

            // المخالفات والجزاءات
            'rsyi_hr_manage_violations'       => 'إدارة المخالفات والجزاءات',

            // اعتماد العميد
            'rsyi_hr_dean_approve'            => 'تصديق واعتماد العميد',

            // الإعدادات والتقارير
            'rsyi_hr_manage_settings'         => 'إعدادات نظام الموارد البشرية',
            'rsyi_hr_view_reports'            => 'عرض تقارير الموارد البشرية',
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
                'caps'  => array_merge( $all_hr, [
                    'rsyi_hr_dean_approve' => false,   // العميد فقط
                ] ),
            ],

            // ── رئيس قسم ─────────────────────────────────────────────────
            'rsyi_dept_head' => [
                'label' => 'رئيس قسم',
                'caps'  => [
                    'rsyi_hr_view_employees'          => true,
                    'rsyi_hr_view_departments'         => true,
                    'rsyi_hr_view_job_titles'          => true,
                    'rsyi_hr_view_reports'             => true,
                    'rsyi_hr_approve_leaves_manager'   => true,
                ],
            ],

            // ── موظف ─────────────────────────────────────────────────────
            'rsyi_staff' => [
                'label' => 'موظف',
                'caps'  => [
                    'rsyi_hr_view_departments' => true,
                    'rsyi_hr_view_job_titles'  => true,
                ],
            ],

            // ── مشاهد فقط ────────────────────────────────────────────────
            'rsyi_readonly' => [
                'label' => 'مشاهد فقط',
                'caps'  => [
                    'rsyi_hr_view_employees'   => true,
                    'rsyi_hr_view_departments' => true,
                    'rsyi_hr_view_job_titles'  => true,
                ],
            ],
        ];
    }

    // ─── Extension Caps API ────────────────────────────────────────────────

    /**
     * تسجيل صلاحيات plugin خارجية على أدوار HR.
     *
     * تُستدعى من داخل rsyi_hr_extend_roles hook:
     *
     *   add_action( 'rsyi_hr_extend_roles', function() {
     *       RSYI_HR\Roles::register_extension_caps( 'rsyi-warehouse', [
     *           'rsyi_dean'      => [ 'iw_view_warehouse' => true, 'iw_approve_permits' => true ],
     *           'rsyi_dept_head' => [ 'iw_view_warehouse' => true ],
     *       ], 'نظام المستودعات' );
     *   } );
     *
     * @param string $plugin_id    مُعرِّف Plugin الخارجية (مثل: 'rsyi-warehouse')
     * @param array  $caps_by_role ['role_slug' => ['cap_key' => true|false]]
     * @param string $label        اسم Plugin للعرض في صفحة الصلاحيات
     */
    public static function register_extension_caps( string $plugin_id, array $caps_by_role, string $label = '' ): void {
        self::$extension_caps[ $plugin_id ] = [
            'label'        => $label ?: $plugin_id,
            'caps_by_role' => $caps_by_role,
        ];

        foreach ( $caps_by_role as $role_slug => $caps ) {
            $role = get_role( $role_slug );
            if ( ! $role ) {
                continue;
            }

            foreach ( $caps as $cap => $grant ) {
                if ( ! isset( $role->capabilities[ $cap ] ) ) {
                    $role->add_cap( $cap, (bool) $grant );
                }
            }
        }
    }

    /**
     * إرجاع صلاحيات الـ plugins الخارجية المسجَّلة في الجلسة الحالية.
     * تُستخدم في صفحة الصلاحيات بلوحة التحكم.
     *
     * @return array<string, array{label: string, caps_by_role: array}>
     */
    public static function get_extension_caps(): array {
        return self::$extension_caps;
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

        // ① أولاً: الـ plugins الأخرى تُسجِّل صلاحياتها على الأدوار
        do_action( 'rsyi_hr_extend_roles' );

        // ② ثانياً: مزامنة الأدمن بعد إضافة جميع الصلاحيات (بما فيها الخارجية)
        self::sync_admin_caps_from_hr_roles();

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

        // ① أولاً: الـ plugins الأخرى تُسجِّل صلاحياتها على الأدوار
        do_action( 'rsyi_hr_extend_roles' );

        // ② ثانياً: مزامنة الأدمن بعد إضافة جميع الصلاحيات (بما فيها الخارجية)
        self::sync_admin_caps_from_hr_roles();

        update_option( self::ROLES_VERSION_OPTION, RSYI_HR_VERSION );
    }

    /**
     * مزامنة الأدمن: يحصل على كل صلاحية موجودة في أي دور HR
     * (تشمل صلاحيات الـ plugins الخارجية المُضافة على الأدوار).
     *
     * تُستدعى بعد do_action('rsyi_hr_extend_roles') مباشرة.
     */
    private static function sync_admin_caps_from_hr_roles(): void {
        $admin = get_role( 'administrator' );
        if ( ! $admin ) {
            return;
        }

        $hr_slugs = array_keys( self::base_role_definitions() );

        foreach ( $hr_slugs as $slug ) {
            $role = get_role( $slug );
            if ( ! $role ) {
                continue;
            }

            foreach ( $role->capabilities as $cap => $grant ) {
                if ( $grant && 'read' !== $cap && ! isset( $admin->capabilities[ $cap ] ) ) {
                    $admin->add_cap( $cap, true );
                }
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
