<?php
/**
 * HR Public API — WordPress Filters & Actions
 *
 * هذا الملف هو "واجهة" HR Plugin للـ plugins الأخرى.
 * أي plugin تحتاج بيانات موظفين / أقسام / وظائف تستخدم:
 *
 *   $employees   = apply_filters( 'rsyi_hr_get_employees',   [], $args );
 *   $departments = apply_filters( 'rsyi_hr_get_departments', [], $args );
 *   $job_titles  = apply_filters( 'rsyi_hr_get_job_titles',  [], $args );
 *
 * كيف تربط plugin خارجية صلاحياتها بأدوار HR:
 *
 *   add_action( 'rsyi_hr_extend_roles', function() {
 *       $dean = get_role( 'rsyi_dean' );
 *       $dean?->add_cap( 'iw_view_warehouse' );
 *       $dean?->add_cap( 'iw_approve_permits' );
 *   } );
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class API {

    public static function init(): void {
        // ── ربط الـ filters بالـ data classes ─────────────────────────────
        add_filter( 'rsyi_hr_get_employees',            [ __CLASS__, 'filter_get_employees' ],         10, 2 );
        add_filter( 'rsyi_hr_get_employee_by_id',       [ __CLASS__, 'filter_get_employee_by_id' ],    10, 2 );
        add_filter( 'rsyi_hr_get_employee_by_user_id',  [ __CLASS__, 'filter_get_employee_by_user_id' ], 10, 2 );
        add_filter( 'rsyi_hr_get_departments',          [ __CLASS__, 'filter_get_departments' ],        10, 2 );
        add_filter( 'rsyi_hr_get_department_by_id',     [ __CLASS__, 'filter_get_department_by_id' ],   10, 2 );
        add_filter( 'rsyi_hr_get_job_titles',           [ __CLASS__, 'filter_get_job_titles' ],         10, 2 );
        add_filter( 'rsyi_hr_get_job_title_by_id',      [ __CLASS__, 'filter_get_job_title_by_id' ],    10, 2 );
        add_filter( 'rsyi_hr_count_employees',          [ __CLASS__, 'filter_count_employees' ],        10, 2 );
        add_filter( 'rsyi_hr_department_employees',     [ __CLASS__, 'filter_department_employees' ],   10, 2 );
    }

    // ─── Filters ─────────────────────────────────────────────────────────

    /**
     * apply_filters( 'rsyi_hr_get_employees', [], $args )
     *
     * $args مثل:
     *   [ 'status' => 'active', 'department_id' => 3, 'search' => 'أحمد' ]
     */
    public static function filter_get_employees( array $default, array $args = [] ): array {
        return Employees::get_all( $args );
    }

    /**
     * apply_filters( 'rsyi_hr_get_employee_by_id', null, $employee_id )
     */
    public static function filter_get_employee_by_id( $default, int $id ): ?array {
        return Employees::get_by_id( $id );
    }

    /**
     * apply_filters( 'rsyi_hr_get_employee_by_user_id', null, $wp_user_id )
     */
    public static function filter_get_employee_by_user_id( $default, int $user_id ): ?array {
        return Employees::get_by_user_id( $user_id );
    }

    /**
     * apply_filters( 'rsyi_hr_get_departments', [], $args )
     *
     * $args مثل:
     *   [ 'status' => 'active' ]
     */
    public static function filter_get_departments( array $default, array $args = [] ): array {
        return Departments::get_all( $args );
    }

    /**
     * apply_filters( 'rsyi_hr_get_department_by_id', null, $dept_id )
     */
    public static function filter_get_department_by_id( $default, int $id ): ?array {
        return Departments::get_by_id( $id );
    }

    /**
     * apply_filters( 'rsyi_hr_get_job_titles', [], $args )
     */
    public static function filter_get_job_titles( array $default, array $args = [] ): array {
        return Departments::get_all_job_titles( $args );
    }

    /**
     * apply_filters( 'rsyi_hr_get_job_title_by_id', null, $job_title_id )
     */
    public static function filter_get_job_title_by_id( $default, int $id ): ?array {
        return Departments::get_job_title_by_id( $id );
    }

    /**
     * apply_filters( 'rsyi_hr_count_employees', 0, 'active' )
     */
    public static function filter_count_employees( int $default, string $status = 'active' ): int {
        return Employees::count( $status );
    }

    /**
     * apply_filters( 'rsyi_hr_department_employees', [], $department_id )
     * اختصار لجلب موظفي قسم بعينه.
     */
    public static function filter_department_employees( array $default, int $department_id ): array {
        return Employees::get_all( [ 'department_id' => $department_id, 'status' => 'active' ] );
    }
}


// ════════════════════════════════════════════════════════════════════════════
//  Procedural helper functions
//  أي plugin تانية تستخدم هذه الدوال مباشرة بدون الحاجة للـ namespace
// ════════════════════════════════════════════════════════════════════════════

if ( ! function_exists( 'rsyi_hr_get_employees' ) ) {
    /**
     * @param array $args انظر Employees::get_all()
     * @return array
     */
    function rsyi_hr_get_employees( array $args = [] ): array {
        return apply_filters( 'rsyi_hr_get_employees', [], $args );
    }
}

if ( ! function_exists( 'rsyi_hr_get_employee' ) ) {
    function rsyi_hr_get_employee( int $id ): ?array {
        return apply_filters( 'rsyi_hr_get_employee_by_id', null, $id );
    }
}

if ( ! function_exists( 'rsyi_hr_get_employee_by_user' ) ) {
    function rsyi_hr_get_employee_by_user( int $user_id ): ?array {
        return apply_filters( 'rsyi_hr_get_employee_by_user_id', null, $user_id );
    }
}

if ( ! function_exists( 'rsyi_hr_get_departments' ) ) {
    /**
     * @param array $args انظر Departments::get_all()
     * @return array
     */
    function rsyi_hr_get_departments( array $args = [] ): array {
        return apply_filters( 'rsyi_hr_get_departments', [], $args );
    }
}

if ( ! function_exists( 'rsyi_hr_get_department' ) ) {
    function rsyi_hr_get_department( int $id ): ?array {
        return apply_filters( 'rsyi_hr_get_department_by_id', null, $id );
    }
}

if ( ! function_exists( 'rsyi_hr_get_job_titles' ) ) {
    function rsyi_hr_get_job_titles( array $args = [] ): array {
        return apply_filters( 'rsyi_hr_get_job_titles', [], $args );
    }
}

if ( ! function_exists( 'rsyi_hr_department_employees' ) ) {
    function rsyi_hr_department_employees( int $department_id ): array {
        return apply_filters( 'rsyi_hr_department_employees', [], $department_id );
    }
}
