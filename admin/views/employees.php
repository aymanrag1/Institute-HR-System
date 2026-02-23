<?php
/**
 * Employees View
 *
 * @package RSYI_HR
 * @var array $departments
 * @var array $job_titles
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap rsyi-hr-wrap">
    <h1>
        <?php esc_html_e( 'الموظفون', 'rsyi-hr' ); ?>
        <?php if ( current_user_can( 'rsyi_hr_manage_employees' ) ) : ?>
            <button class="page-title-action rsyi-hr-btn-add-employee">
                <?php esc_html_e( 'إضافة موظف', 'rsyi-hr' ); ?>
            </button>
        <?php endif; ?>
    </h1>

    <!-- ── فلاتر ──────────────────────────────────────────────────────── -->
    <div class="rsyi-hr-filters">
        <select id="rsyi-hr-filter-dept">
            <option value=""><?php esc_html_e( 'كل الأقسام', 'rsyi-hr' ); ?></option>
            <?php foreach ( $departments as $d ) : ?>
                <option value="<?php echo esc_attr( $d['id'] ); ?>">
                    <?php echo esc_html( $d['name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="rsyi-hr-filter-status">
            <option value="all"><?php esc_html_e( 'كل الحالات', 'rsyi-hr' ); ?></option>
            <option value="active"><?php esc_html_e( 'نشط', 'rsyi-hr' ); ?></option>
            <option value="inactive"><?php esc_html_e( 'غير نشط', 'rsyi-hr' ); ?></option>
            <option value="on_leave"><?php esc_html_e( 'في إجازة', 'rsyi-hr' ); ?></option>
        </select>

        <input type="search" id="rsyi-hr-search-emp"
               placeholder="<?php esc_attr_e( 'بحث بالاسم أو رقم الموظف...', 'rsyi-hr' ); ?>">
    </div>

    <!-- ── جدول الموظفين ───────────────────────────────────────────────── -->
    <table class="wp-list-table widefat fixed striped rsyi-hr-table" id="rsyi-hr-employees-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'رقم الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الاسم الكامل', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'القسم', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الوظيفة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الهاتف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-employees-body">
            <tr><td colspan="7" class="rsyi-hr-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- ── Modal: إضافة / تعديل موظف ─────────────────────────────────────────── -->
<div id="rsyi-hr-employee-modal" class="rsyi-hr-modal" style="display:none;">
    <div class="rsyi-hr-modal-content">
        <button class="rsyi-hr-modal-close">&times;</button>
        <h2 id="rsyi-hr-employee-modal-title"><?php esc_html_e( 'إضافة موظف', 'rsyi-hr' ); ?></h2>

        <form id="rsyi-hr-employee-form">
            <input type="hidden" name="id" id="emp-id" value="">

            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الاسم الكامل *', 'rsyi-hr' ); ?></label>
                <input type="text" name="full_name" id="emp-full-name" required>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'رقم الموظف', 'rsyi-hr' ); ?></label>
                <input type="text" name="employee_number" id="emp-number">
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الرقم الوطني', 'rsyi-hr' ); ?></label>
                <input type="text" name="national_id" id="emp-national-id">
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'القسم', 'rsyi-hr' ); ?></label>
                <select name="department_id" id="emp-department">
                    <option value=""><?php esc_html_e( '— اختر قسماً —', 'rsyi-hr' ); ?></option>
                    <?php foreach ( $departments as $d ) : ?>
                        <option value="<?php echo esc_attr( $d['id'] ); ?>">
                            <?php echo esc_html( $d['name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الوظيفة', 'rsyi-hr' ); ?></label>
                <select name="job_title_id" id="emp-job-title">
                    <option value=""><?php esc_html_e( '— اختر وظيفة —', 'rsyi-hr' ); ?></option>
                    <?php foreach ( $job_titles as $jt ) : ?>
                        <option value="<?php echo esc_attr( $jt['id'] ); ?>">
                            <?php echo esc_html( $jt['title'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الهاتف', 'rsyi-hr' ); ?></label>
                <input type="tel" name="phone" id="emp-phone">
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'البريد الإلكتروني', 'rsyi-hr' ); ?></label>
                <input type="email" name="email" id="emp-email">
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'تاريخ التعيين', 'rsyi-hr' ); ?></label>
                <input type="date" name="hire_date" id="emp-hire-date">
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></label>
                <select name="status" id="emp-status">
                    <option value="active"><?php esc_html_e( 'نشط', 'rsyi-hr' ); ?></option>
                    <option value="inactive"><?php esc_html_e( 'غير نشط', 'rsyi-hr' ); ?></option>
                    <option value="on_leave"><?php esc_html_e( 'في إجازة', 'rsyi-hr' ); ?></option>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'ملاحظات', 'rsyi-hr' ); ?></label>
                <textarea name="notes" id="emp-notes" rows="3"></textarea>
            </div>

            <div class="rsyi-hr-form-actions">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e( 'حفظ', 'rsyi-hr' ); ?>
                </button>
                <button type="button" class="button rsyi-hr-modal-close">
                    <?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?>
                </button>
            </div>
        </form>
    </div>
</div>
