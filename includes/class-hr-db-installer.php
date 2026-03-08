<?php
/**
 * HR Database Installer — v2.1.0
 *
 * الجداول المُنشَأة:
 *   rsyi_hr_departments       ← الأقسام
 *   rsyi_hr_job_titles        ← التقسيم الوظيفي
 *   rsyi_hr_employees         ← الموظفون (+ توقيع إلكتروني + مدير مباشر)
 *   rsyi_hr_leaves            ← طلبات الإجازة
 *   rsyi_hr_overtime          ← طلبات العمل الإضافي
 *   rsyi_hr_attendance        ← الحضور والانصراف
 *   rsyi_hr_violations        ← المخالفات والجزاءات
 *   rsyi_hr_user_permissions  ← صلاحيات المستخدمين (لكل عنصر في النظام)
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class DB_Installer {

    const DB_VERSION        = '2.2.0';
    const DB_VERSION_OPTION = 'rsyi_hr_db_version';

    /**
     * إنشاء/ترقية الجداول (آمن للاستدعاء المتكرر عبر dbDelta).
     */
    public static function create_tables(): void {
        global $wpdb;

        $collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── 1. الأقسام ────────────────────────────────────────────────────────
        $departments = $wpdb->prefix . 'rsyi_hr_departments';
        dbDelta( "CREATE TABLE {$departments} (
            id          bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name        varchar(255)        NOT NULL,
            code        varchar(50)         DEFAULT NULL,
            description text,
            parent_id   bigint(20) UNSIGNED DEFAULT NULL COMMENT 'قسم رئيسي → فرعي',
            manager_id  bigint(20) UNSIGNED DEFAULT NULL COMMENT 'employee.id',
            status      enum('active','inactive') NOT NULL DEFAULT 'active',
            created_at  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   code (code),
            KEY parent_id (parent_id),
            KEY manager_id (manager_id),
            KEY status (status)
        ) {$collate};" );

        // ── 2. التقسيم الوظيفي ────────────────────────────────────────────────
        $job_titles = $wpdb->prefix . 'rsyi_hr_job_titles';
        dbDelta( "CREATE TABLE {$job_titles} (
            id            bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title         varchar(255)        NOT NULL,
            code          varchar(50)         DEFAULT NULL,
            department_id bigint(20) UNSIGNED DEFAULT NULL COMMENT 'اختياري: مرتبط بقسم',
            grade         varchar(50)         DEFAULT NULL  COMMENT 'الدرجة الوظيفية',
            description   text,
            status        enum('active','inactive') NOT NULL DEFAULT 'active',
            created_at    datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   code (code),
            KEY department_id (department_id),
            KEY status (status)
        ) {$collate};" );

        // ── 3. الموظفون ────────────────────────────────────────────────────────
        $employees = $wpdb->prefix . 'rsyi_hr_employees';
        dbDelta( "CREATE TABLE {$employees} (
            id               bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id          bigint(20) UNSIGNED DEFAULT NULL  COMMENT 'wp_users.ID إذا كان له حساب',
            employee_number  varchar(50)         DEFAULT NULL,
            full_name        varchar(255)        NOT NULL      COMMENT 'الاسم بالإنجليزية',
            full_name_ar     varchar(255)        DEFAULT NULL  COMMENT 'الاسم بالعربية',
            national_id      varchar(50)         DEFAULT NULL  COMMENT 'الرقم القومي',
            date_of_birth    date                DEFAULT NULL  COMMENT 'تاريخ الميلاد',
            department_id    bigint(20) UNSIGNED DEFAULT NULL,
            job_title_id     bigint(20) UNSIGNED DEFAULT NULL,
            manager_id       bigint(20) UNSIGNED DEFAULT NULL  COMMENT 'المدير المباشر employee.id',
            is_manager       tinyint(1)          NOT NULL DEFAULT 0 COMMENT '1 = يُعامَل كمدير',
            grade            varchar(50)         DEFAULT NULL  COMMENT 'الدرجة الوظيفية',
            phone            varchar(50)         DEFAULT NULL  COMMENT 'رقم الهاتف',
            email            varchar(255)        DEFAULT NULL,
            hire_date        date                DEFAULT NULL  COMMENT 'تاريخ التعيين',
            contract_start   date                DEFAULT NULL  COMMENT 'بداية العقد',
            contract_end     date                DEFAULT NULL  COMMENT 'نهاية العقد',
            contract_type    varchar(100)        DEFAULT NULL  COMMENT 'نوع العقد',
            marital_status   varchar(50)         DEFAULT NULL  COMMENT 'الحالة الاجتماعية',
            religion         varchar(100)        DEFAULT NULL  COMMENT 'الديانة',
            housing          varchar(255)        DEFAULT NULL  COMMENT 'السكن',
            insurance_number varchar(100)        DEFAULT NULL  COMMENT 'الرقم التأميني',
            military_status  varchar(100)        DEFAULT NULL  COMMENT 'موقف التجنيد',
            education        varchar(255)        DEFAULT NULL  COMMENT 'المؤهل الدراسي',
            bank_name        varchar(255)        DEFAULT NULL  COMMENT 'اسم البنك',
            bank_account     varchar(100)        DEFAULT NULL  COMMENT 'رقم الحساب البنكي',
            signature_url    varchar(500)        DEFAULT NULL  COMMENT 'رابط صورة التوقيع الإلكتروني',
            status           enum('active','inactive','on_leave') NOT NULL DEFAULT 'active',
            notes            text,
            created_at       datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at       datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   employee_number (employee_number),
            KEY user_id (user_id),
            KEY department_id (department_id),
            KEY job_title_id (job_title_id),
            KEY manager_id (manager_id),
            KEY status (status),
            KEY date_of_birth (date_of_birth)
        ) {$collate};" );

        // ── 4. طلبات الإجازة ──────────────────────────────────────────────────
        $leaves = $wpdb->prefix . 'rsyi_hr_leaves';
        dbDelta( "CREATE TABLE {$leaves} (
            id                       bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            employee_id              bigint(20) UNSIGNED NOT NULL,
            leave_type               enum('regular','sick','casual','unpaid') NOT NULL DEFAULT 'regular',
            from_date                date        NOT NULL,
            to_date                  date        NOT NULL,
            days_count               int(11)     NOT NULL DEFAULT 1,
            return_date              date        DEFAULT NULL,
            last_leave_date          date        DEFAULT NULL  COMMENT 'آخر يوم أجازة قمت بها',
            person_covering          varchar(255) DEFAULT NULL COMMENT 'القائم بالعمل أثناء الإجازة',
            reason                   text,
            employee_signature       text        COMMENT 'base64 التوقيع الإلكتروني للموظف',
            employee_signed_at       datetime    DEFAULT NULL,
            manager_id               bigint(20) UNSIGNED DEFAULT NULL,
            manager_notes            text,
            manager_signature        text        COMMENT 'توقيع المدير المباشر',
            manager_signed_at        datetime    DEFAULT NULL,
            hr_manager_id            bigint(20) UNSIGNED DEFAULT NULL,
            hr_manager_notes         text,
            hr_manager_signature     text        COMMENT 'توقيع مدير الموارد البشرية',
            hr_manager_signed_at     datetime    DEFAULT NULL,
            dean_notes               text,
            dean_signed_at           datetime    DEFAULT NULL,
            status                   enum('draft','pending_manager','pending_hr','pending_dean','approved','rejected') NOT NULL DEFAULT 'draft',
            rejected_by              varchar(50) DEFAULT NULL,
            rejection_reason         text,
            created_at               datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at               datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY employee_id (employee_id),
            KEY manager_id (manager_id),
            KEY hr_manager_id (hr_manager_id),
            KEY status (status),
            KEY from_date (from_date),
            KEY to_date (to_date)
        ) {$collate};" );

        // ── 5. طلبات العمل الإضافي ────────────────────────────────────────────
        $overtime = $wpdb->prefix . 'rsyi_hr_overtime';
        dbDelta( "CREATE TABLE {$overtime} (
            id                    bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            employee_id           bigint(20) UNSIGNED NOT NULL,
            work_date             date        NOT NULL,
            from_time             time        NOT NULL,
            to_time               time        NOT NULL,
            hours_count           decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'يُحسب تلقائياً',
            reason                text,
            employee_signature    text        COMMENT 'توقيع الموظف الإلكتروني',
            employee_signed_at    datetime    DEFAULT NULL,
            manager_id            bigint(20) UNSIGNED DEFAULT NULL,
            manager_notes         text,
            manager_signature     text,
            manager_signed_at     datetime    DEFAULT NULL,
            hr_manager_id         bigint(20) UNSIGNED DEFAULT NULL,
            hr_manager_notes      text,
            hr_manager_signed_at  datetime    DEFAULT NULL,
            status                enum('draft','pending_manager','pending_hr','approved','rejected') NOT NULL DEFAULT 'draft',
            rejection_reason      text,
            created_at            datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at            datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY employee_id (employee_id),
            KEY manager_id (manager_id),
            KEY status (status),
            KEY work_date (work_date)
        ) {$collate};" );

        // ── 6. الحضور والانصراف ───────────────────────────────────────────────
        $attendance = $wpdb->prefix . 'rsyi_hr_attendance';
        dbDelta( "CREATE TABLE {$attendance} (
            id            bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            employee_id   bigint(20) UNSIGNED NOT NULL,
            att_date      date        NOT NULL,
            check_in      time        DEFAULT NULL,
            check_out     time        DEFAULT NULL,
            att_type      enum('present','absent','late','half_day','holiday','vacation') NOT NULL DEFAULT 'present',
            notes         text,
            recorded_by   bigint(20) UNSIGNED DEFAULT NULL COMMENT 'مدير الموارد البشرية',
            created_at    datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   emp_date (employee_id, att_date),
            KEY employee_id (employee_id),
            KEY att_date (att_date),
            KEY att_type (att_type),
            KEY recorded_by (recorded_by)
        ) {$collate};" );

        // ── 7. المخالفات والجزاءات ────────────────────────────────────────────
        $violations = $wpdb->prefix . 'rsyi_hr_violations';
        dbDelta( "CREATE TABLE {$violations} (
            id                   bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            employee_id          bigint(20) UNSIGNED NOT NULL,
            violation_type       varchar(255) NOT NULL,
            violation_date       date         NOT NULL,
            description          text,
            penalty_type         varchar(255) DEFAULT NULL COMMENT 'نوع الجزاء',
            penalty_value        varchar(255) DEFAULT NULL COMMENT 'قيمة الجزاء',
            hr_manager_id        bigint(20) UNSIGNED DEFAULT NULL,
            hr_manager_notes     text,
            hr_applied_at        datetime    DEFAULT NULL,
            dean_notes           text,
            dean_signed_at       datetime    DEFAULT NULL,
            status               enum('draft','pending_dean','approved','rejected') NOT NULL DEFAULT 'draft',
            rejection_reason     text,
            created_at           datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at           datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY employee_id (employee_id),
            KEY hr_manager_id (hr_manager_id),
            KEY status (status),
            KEY violation_date (violation_date)
        ) {$collate};" );

        // ── 8. رصيد الإجازات ──────────────────────────────────────────────────
        $leave_balances = $wpdb->prefix . 'rsyi_hr_leave_balances';
        dbDelta( "CREATE TABLE {$leave_balances} (
            id          bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            employee_id bigint(20) UNSIGNED NOT NULL,
            leave_type  enum('regular','sick','casual','unpaid') NOT NULL DEFAULT 'regular',
            year        smallint(4) UNSIGNED NOT NULL,
            total_days  int(11) NOT NULL DEFAULT 0,
            updated_at  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   emp_type_year (employee_id, leave_type, year),
            KEY employee_id (employee_id),
            KEY year (year)
        ) {$collate};" );

        // ── 9. صلاحيات المستخدمين (لكل عنصر في النظام) ───────────────────────
        $user_perms = $wpdb->prefix . 'rsyi_hr_user_permissions';
        dbDelta( "CREATE TABLE {$user_perms} (
            id         bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id    bigint(20) UNSIGNED NOT NULL,
            module     varchar(100)        NOT NULL  COMMENT 'اسم الوحدة: employees, leaves, …',
            permission enum('none','view','read','read_write') NOT NULL DEFAULT 'none',
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   user_module (user_id, module),
            KEY user_id (user_id)
        ) {$collate};" );

        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * يُستدعى عند تفعيل البلجن.
     */
    public static function activate(): void {
        self::create_tables();
        Roles::add_roles();
        flush_rewrite_rules();
    }

    /**
     * يُستدعى عند إلغاء تفعيل البلجن (لا يحذف الجداول حفاظاً على البيانات).
     */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    /**
     * حذف الجداول عند إزالة البلجن نهائياً (uninstall.php).
     */
    public static function drop_tables(): void {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'rsyi_hr_user_permissions',
            $wpdb->prefix . 'rsyi_hr_leave_balances',
            $wpdb->prefix . 'rsyi_hr_violations',
            $wpdb->prefix . 'rsyi_hr_attendance',
            $wpdb->prefix . 'rsyi_hr_overtime',
            $wpdb->prefix . 'rsyi_hr_leaves',
            $wpdb->prefix . 'rsyi_hr_employees',
            $wpdb->prefix . 'rsyi_hr_job_titles',
            $wpdb->prefix . 'rsyi_hr_departments',
        ];

        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore
        }

        delete_option( self::DB_VERSION_OPTION );
    }
}
