<?php
/**
 * HR Database Installer
 *
 * ينشئ 3 جداول مشتركة تُستخدَم من جميع الـ plugins:
 *   rsyi_hr_departments  ← الأقسام
 *   rsyi_hr_job_titles   ← التقسيم الوظيفي
 *   rsyi_hr_employees    ← الموظفون
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class DB_Installer {

    const DB_VERSION        = '2.0.0';
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
            grade         varchar(50)         DEFAULT NULL COMMENT 'الدرجة الوظيفية',
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
            status           enum('active','inactive','on_leave') NOT NULL DEFAULT 'active',
            notes            text,
            created_at       datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at       datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   employee_number (employee_number),
            KEY user_id (user_id),
            KEY department_id (department_id),
            KEY job_title_id (job_title_id),
            KEY status (status),
            KEY date_of_birth (date_of_birth)
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
