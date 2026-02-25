<?php
/**
 * Violations Admin View — المخالفات والجزاءات
 *
 * @package RSYI_HR
 * @var array $employees
 */
defined( 'ABSPATH' ) || exit;

$types    = RSYI_HR\Violations::get_types();
$penalties= RSYI_HR\Violations::get_penalty_types();
?>
<div class="wrap rsyi-hr-wrap">
    <h1>
        <?php esc_html_e( 'المخالفات والجزاءات / Violations', 'rsyi-hr' ); ?>
        <?php if ( current_user_can( 'rsyi_hr_manage_violations' ) ) : ?>
            <button class="page-title-action rsyi-hr-btn-add-violation">
                + <?php esc_html_e( 'إضافة مخالفة', 'rsyi-hr' ); ?>
            </button>
        <?php endif; ?>
    </h1>

    <div class="rsyi-hr-filters">
        <select id="rsyi-hr-filter-vio-status">
            <option value=""><?php esc_html_e( 'كل الحالات', 'rsyi-hr' ); ?></option>
            <option value="pending"><?php esc_html_e( 'قيد الانتظار', 'rsyi-hr' ); ?></option>
            <option value="approved"><?php esc_html_e( 'معتمد', 'rsyi-hr' ); ?></option>
            <option value="rejected"><?php esc_html_e( 'مرفوض', 'rsyi-hr' ); ?></option>
        </select>
        <select id="rsyi-hr-filter-vio-emp">
            <option value=""><?php esc_html_e( 'كل الموظفين', 'rsyi-hr' ); ?></option>
            <?php foreach ( $employees as $emp ) : ?>
                <option value="<?php echo esc_attr( $emp['id'] ); ?>">
                    <?php echo esc_html( $emp['full_name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <table class="wp-list-table widefat fixed striped rsyi-hr-table" id="rsyi-hr-violations-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'نوع المخالفة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الجزاء', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'التاريخ', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-violations-body">
            <tr><td colspan="6" class="rsyi-hr-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal: إضافة مخالفة -->
<div id="rsyi-hr-violation-modal" class="rsyi-hr-modal" style="display:none;">
    <div class="rsyi-hr-modal-content">
        <button class="rsyi-hr-modal-close">&times;</button>
        <h2 id="rsyi-hr-vio-modal-title"><?php esc_html_e( 'إضافة مخالفة', 'rsyi-hr' ); ?></h2>
        <form id="rsyi-hr-vio-form">
            <input type="hidden" name="id" id="vio-id">
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الموظف *', 'rsyi-hr' ); ?></label>
                <select name="employee_id" id="vio-emp" required>
                    <option value=""><?php esc_html_e( '— اختر موظفاً —', 'rsyi-hr' ); ?></option>
                    <?php foreach ( $employees as $emp ) : ?>
                        <option value="<?php echo esc_attr( $emp['id'] ); ?>">
                            <?php echo esc_html( $emp['full_name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'نوع المخالفة *', 'rsyi-hr' ); ?></label>
                <select name="violation_type" id="vio-type" required>
                    <option value=""><?php esc_html_e( '— اختر —', 'rsyi-hr' ); ?></option>
                    <?php foreach ( $types as $val => $label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الوصف', 'rsyi-hr' ); ?></label>
                <textarea name="description" id="vio-desc" rows="3"></textarea>
            </div>
            <div class="rsyi-hr-form-cols-2">
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'نوع الجزاء', 'rsyi-hr' ); ?></label>
                    <select name="penalty_type" id="vio-penalty-type">
                        <option value=""><?php esc_html_e( '— اختر —', 'rsyi-hr' ); ?></option>
                        <?php foreach ( $penalties as $val => $label ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'قيمة الجزاء', 'rsyi-hr' ); ?></label>
                    <input type="text" name="penalty_value" id="vio-penalty-value"
                           placeholder="<?php esc_attr_e( 'مثال: يومان / 10%', 'rsyi-hr' ); ?>">
                </div>
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

<!-- Modal: اعتماد / رفض (العميد) -->
<div id="rsyi-hr-vio-approve-modal" class="rsyi-hr-modal" style="display:none;">
    <div class="rsyi-hr-modal-content">
        <button class="rsyi-hr-modal-close">&times;</button>
        <h2><?php esc_html_e( 'مراجعة المخالفة', 'rsyi-hr' ); ?></h2>
        <div id="rsyi-hr-vio-approve-detail"></div>
        <div style="margin-top:16px;">
            <label><?php esc_html_e( 'ملاحظات العميد', 'rsyi-hr' ); ?></label>
            <textarea id="rsyi-hr-vio-approve-notes" rows="3" class="widefat" style="margin-top:6px;"></textarea>
        </div>
        <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
            <button type="button" id="rsyi-hr-vio-approve-confirm" class="button button-primary">
                ✅ <?php esc_html_e( 'اعتماد', 'rsyi-hr' ); ?>
            </button>
            <button type="button" id="rsyi-hr-vio-reject-confirm" class="button">
                ❌ <?php esc_html_e( 'رفض', 'rsyi-hr' ); ?>
            </button>
        </div>
    </div>
</div>
