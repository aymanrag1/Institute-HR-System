<?php
/**
 * Attendance Admin View — الحضور والانصراف
 *
 * @package RSYI_HR
 * @var array $employees
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap rsyi-hr-wrap">
    <h1>
        <?php esc_html_e( 'الحضور والانصراف / Attendance', 'rsyi-hr' ); ?>
        <?php if ( current_user_can( 'rsyi_hr_manage_attendance' ) ) : ?>
            <button class="page-title-action rsyi-hr-btn-add-att">
                + <?php esc_html_e( 'إضافة سجل', 'rsyi-hr' ); ?>
            </button>
            <button class="page-title-action" id="rsyi-hr-att-import-btn">
                📥 <?php esc_html_e( 'استيراد CSV', 'rsyi-hr' ); ?>
            </button>
            <button class="page-title-action" id="rsyi-hr-att-template-btn">
                📄 <?php esc_html_e( 'تحميل النموذج', 'rsyi-hr' ); ?>
            </button>
        <?php endif; ?>
    </h1>

    <!-- فلاتر -->
    <div class="rsyi-hr-filters">
        <select id="rsyi-hr-att-filter-emp">
            <option value=""><?php esc_html_e( 'كل الموظفين', 'rsyi-hr' ); ?></option>
            <?php foreach ( $employees as $emp ) : ?>
                <option value="<?php echo esc_attr( $emp['id'] ); ?>">
                    <?php echo esc_html( $emp['full_name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" id="rsyi-hr-att-date-from" placeholder="<?php esc_attr_e( 'من تاريخ', 'rsyi-hr' ); ?>">
        <input type="date" id="rsyi-hr-att-date-to"   placeholder="<?php esc_attr_e( 'إلى تاريخ', 'rsyi-hr' ); ?>">
        <select id="rsyi-hr-att-filter-status">
            <option value=""><?php esc_html_e( 'كل الحالات', 'rsyi-hr' ); ?></option>
            <option value="present"><?php esc_html_e( 'حاضر', 'rsyi-hr' ); ?></option>
            <option value="absent"><?php esc_html_e( 'غائب', 'rsyi-hr' ); ?></option>
            <option value="late"><?php esc_html_e( 'متأخر', 'rsyi-hr' ); ?></option>
            <option value="leave"><?php esc_html_e( 'إجازة', 'rsyi-hr' ); ?></option>
            <option value="holiday"><?php esc_html_e( 'عطلة', 'rsyi-hr' ); ?></option>
        </select>
        <button class="button" id="rsyi-hr-att-search-btn">
            <?php esc_html_e( 'بحث', 'rsyi-hr' ); ?>
        </button>
    </div>

    <table class="wp-list-table widefat fixed striped rsyi-hr-table" id="rsyi-hr-att-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'التاريخ', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'حضور', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'انصراف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'ملاحظات', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-att-body">
            <tr><td colspan="7" class="rsyi-hr-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal: إضافة / تعديل سجل حضور -->
<div id="rsyi-hr-att-modal" class="rsyi-hr-modal" style="display:none;">
    <div class="rsyi-hr-modal-content">
        <button class="rsyi-hr-modal-close">&times;</button>
        <h2 id="rsyi-hr-att-modal-title"><?php esc_html_e( 'إضافة سجل حضور', 'rsyi-hr' ); ?></h2>
        <form id="rsyi-hr-att-form">
            <input type="hidden" name="id" id="att-id">
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الموظف *', 'rsyi-hr' ); ?></label>
                <select name="employee_id" id="att-emp" required>
                    <option value=""><?php esc_html_e( '— اختر موظفاً —', 'rsyi-hr' ); ?></option>
                    <?php foreach ( $employees as $emp ) : ?>
                        <option value="<?php echo esc_attr( $emp['id'] ); ?>">
                            <?php echo esc_html( $emp['full_name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'التاريخ *', 'rsyi-hr' ); ?></label>
                <input type="date" name="attendance_date" id="att-date" required>
            </div>
            <div class="rsyi-hr-form-cols-2">
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'وقت الحضور', 'rsyi-hr' ); ?></label>
                    <input type="time" name="check_in" id="att-checkin">
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'وقت الانصراف', 'rsyi-hr' ); ?></label>
                    <input type="time" name="check_out" id="att-checkout">
                </div>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></label>
                <select name="status" id="att-status">
                    <option value="present"><?php esc_html_e( 'حاضر / Present', 'rsyi-hr' ); ?></option>
                    <option value="absent"><?php esc_html_e( 'غائب / Absent', 'rsyi-hr' ); ?></option>
                    <option value="late"><?php esc_html_e( 'متأخر / Late', 'rsyi-hr' ); ?></option>
                    <option value="leave"><?php esc_html_e( 'إجازة / Leave', 'rsyi-hr' ); ?></option>
                    <option value="holiday"><?php esc_html_e( 'عطلة / Holiday', 'rsyi-hr' ); ?></option>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'ملاحظات', 'rsyi-hr' ); ?></label>
                <textarea name="notes" id="att-notes" rows="2"></textarea>
            </div>
            <div class="rsyi-hr-form-actions">
                <button type="submit" class="button button-primary button-large">
                    <?php esc_html_e( 'حفظ', 'rsyi-hr' ); ?>
                </button>
                <button type="button" class="button button-large rsyi-hr-modal-close">
                    <?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: استيراد CSV -->
<div id="rsyi-hr-att-import-modal" class="rsyi-hr-modal" style="display:none;">
    <div class="rsyi-hr-modal-content">
        <button class="rsyi-hr-modal-close">&times;</button>
        <h2><?php esc_html_e( 'استيراد سجلات الحضور من CSV', 'rsyi-hr' ); ?></h2>
        <p style="color:#50575e;font-size:13px;">
            <?php esc_html_e( 'يرجى رفع ملف CSV بالتنسيق: رقم_الموظف، التاريخ (YYYY-MM-DD)، وقت_الحضور (HH:MM)، وقت_الانصراف، الحالة، ملاحظات', 'rsyi-hr' ); ?>
        </p>
        <form id="rsyi-hr-att-import-form" enctype="multipart/form-data">
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'ملف CSV', 'rsyi-hr' ); ?></label>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>
            <div class="rsyi-hr-form-actions">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e( 'استيراد', 'rsyi-hr' ); ?>
                </button>
            </div>
        </form>
        <div id="rsyi-hr-att-import-result" style="display:none;margin-top:14px;"></div>
    </div>
</div>
