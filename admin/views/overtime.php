<?php
/**
 * Overtime Admin View — إدارة طلبات الوقت الإضافي
 *
 * @package RSYI_HR
 * @var array $employees
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap rsyi-hr-wrap">
    <h1><?php esc_html_e( 'الوقت الإضافي / Overtime Requests', 'rsyi-hr' ); ?></h1>

    <div class="rsyi-hr-filters">
        <select id="rsyi-hr-filter-ot-status">
            <option value=""><?php esc_html_e( 'كل الحالات', 'rsyi-hr' ); ?></option>
            <option value="pending"><?php esc_html_e( 'قيد الانتظار', 'rsyi-hr' ); ?></option>
            <option value="manager_approved"><?php esc_html_e( 'اعتمد المدير', 'rsyi-hr' ); ?></option>
            <option value="hr_approved"><?php esc_html_e( 'معتمد', 'rsyi-hr' ); ?></option>
            <option value="rejected"><?php esc_html_e( 'مرفوض', 'rsyi-hr' ); ?></option>
        </select>
        <select id="rsyi-hr-filter-ot-emp">
            <option value=""><?php esc_html_e( 'كل الموظفين', 'rsyi-hr' ); ?></option>
            <?php foreach ( $employees as $emp ) : ?>
                <option value="<?php echo esc_attr( $emp['id'] ); ?>">
                    <?php echo esc_html( $emp['full_name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <table class="wp-list-table widefat fixed striped rsyi-hr-table" id="rsyi-hr-ot-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'التاريخ', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'من', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إلى', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الساعات', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-ot-body">
            <tr><td colspan="7" class="rsyi-hr-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal: تفاصيل الوقت الإضافي -->
<div id="rsyi-hr-ot-modal" class="rsyi-hr-modal" style="display:none;">
    <div class="rsyi-hr-modal-content">
        <button class="rsyi-hr-modal-close">&times;</button>
        <h2><?php esc_html_e( 'تفاصيل طلب الوقت الإضافي', 'rsyi-hr' ); ?></h2>
        <div id="rsyi-hr-ot-detail"></div>
        <div id="rsyi-hr-ot-approve-section" style="display:none;margin-top:16px;padding-top:14px;border-top:1px solid #ddd;">
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" id="rsyi-hr-ot-approve-btn" class="button button-primary">
                    ✅ <?php esc_html_e( 'اعتماد', 'rsyi-hr' ); ?>
                </button>
                <button type="button" id="rsyi-hr-ot-reject-btn" class="button">
                    ❌ <?php esc_html_e( 'رفض', 'rsyi-hr' ); ?>
                </button>
            </div>
            <div id="rsyi-hr-ot-reject-form" style="display:none;margin-top:10px;">
                <textarea id="rsyi-hr-ot-reject-reason" rows="3" class="widefat"
                          placeholder="<?php esc_attr_e( 'سبب الرفض...', 'rsyi-hr' ); ?>"></textarea>
                <button type="button" id="rsyi-hr-ot-reject-confirm" class="button button-primary" style="margin-top:6px;">
                    <?php esc_html_e( 'تأكيد الرفض', 'rsyi-hr' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>
