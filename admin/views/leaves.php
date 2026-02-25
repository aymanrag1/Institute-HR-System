<?php
/**
 * Leaves Admin View — إدارة طلبات الإجازة
 *
 * @package RSYI_HR
 * @var array $employees
 */
defined( 'ABSPATH' ) || exit;

$status_labels = [
    'pending'          => [ 'label' => __( 'قيد الانتظار', 'rsyi-hr' ),        'badge' => 'rsyi-hr-badge-pending' ],
    'manager_approved' => [ 'label' => __( 'اعتمد المدير', 'rsyi-hr' ),         'badge' => 'rsyi-hr-badge-info' ],
    'hr_approved'      => [ 'label' => __( 'اعتمد الموارد البشرية', 'rsyi-hr' ),'badge' => 'rsyi-hr-badge-info2' ],
    'dean_approved'    => [ 'label' => __( 'معتمد نهائياً', 'rsyi-hr' ),        'badge' => 'rsyi-hr-badge-active' ],
    'rejected'         => [ 'label' => __( 'مرفوض', 'rsyi-hr' ),                'badge' => 'rsyi-hr-badge-inactive' ],
];
$type_labels = [
    'annual'    => __( 'اعتيادية', 'rsyi-hr' ),
    'emergency' => __( 'عارضة',   'rsyi-hr' ),
    'sick'      => __( 'مرضى',    'rsyi-hr' ),
    'unpaid'    => __( 'بدون مرتب','rsyi-hr' ),
];
?>
<div class="wrap rsyi-hr-wrap">
    <h1><?php esc_html_e( 'طلبات الإجازة / Leave Requests', 'rsyi-hr' ); ?></h1>

    <div class="rsyi-hr-filters">
        <select id="rsyi-hr-filter-leave-status">
            <option value=""><?php esc_html_e( 'كل الحالات', 'rsyi-hr' ); ?></option>
            <?php foreach ( $status_labels as $val => $sl ) : ?>
                <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $sl['label'] ); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="rsyi-hr-filter-leave-emp">
            <option value=""><?php esc_html_e( 'كل الموظفين', 'rsyi-hr' ); ?></option>
            <?php foreach ( $employees as $emp ) : ?>
                <option value="<?php echo esc_attr( $emp['id'] ); ?>">
                    <?php echo esc_html( $emp['full_name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <table class="wp-list-table widefat fixed striped rsyi-hr-table" id="rsyi-hr-leaves-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'النوع', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'من', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'حتى', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الأيام', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-leaves-body">
            <tr><td colspan="7" class="rsyi-hr-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal: تفاصيل الإجازة / الاعتماد -->
<div id="rsyi-hr-leave-modal" class="rsyi-hr-modal" style="display:none;">
    <div class="rsyi-hr-modal-content rsyi-hr-modal-wide">
        <button class="rsyi-hr-modal-close">&times;</button>
        <h2 id="rsyi-hr-leave-modal-title"><?php esc_html_e( 'تفاصيل طلب الإجازة', 'rsyi-hr' ); ?></h2>
        <div id="rsyi-hr-leave-detail"></div>

        <div id="rsyi-hr-leave-approve-section" style="display:none;margin-top:20px;padding-top:16px;border-top:1px solid #ddd;">
            <h3><?php esc_html_e( 'التوقيع الإلكتروني للاعتماد', 'rsyi-hr' ); ?></h3>
            <canvas id="rsyi-hr-leave-approve-canvas" width="400" height="120"
                    style="border:2px solid #2271b1;border-radius:4px;background:#fff;touch-action:none;cursor:crosshair;"></canvas>
            <br>
            <button type="button" id="rsyi-hr-leave-sig-clear" class="button" style="margin-top:6px;">
                <?php esc_html_e( 'مسح التوقيع', 'rsyi-hr' ); ?>
            </button>
            <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" id="rsyi-hr-leave-approve-btn" class="button button-primary">
                    ✅ <?php esc_html_e( 'اعتماد', 'rsyi-hr' ); ?>
                </button>
                <button type="button" id="rsyi-hr-leave-reject-btn" class="button">
                    ❌ <?php esc_html_e( 'رفض', 'rsyi-hr' ); ?>
                </button>
            </div>
            <div id="rsyi-hr-leave-reject-form" style="display:none;margin-top:10px;">
                <textarea id="rsyi-hr-leave-reject-reason" rows="3" class="widefat"
                          placeholder="<?php esc_attr_e( 'سبب الرفض...', 'rsyi-hr' ); ?>"></textarea>
                <button type="button" id="rsyi-hr-leave-reject-confirm" class="button button-primary" style="margin-top:6px;">
                    <?php esc_html_e( 'تأكيد الرفض', 'rsyi-hr' ); ?>
                </button>
            </div>
        </div>

        <!-- زر الطباعة -->
        <div id="rsyi-hr-leave-print-section" style="display:none;margin-top:14px;">
            <button type="button" id="rsyi-hr-leave-print-btn" class="button">
                🖨️ <?php esc_html_e( 'طباعة النموذج', 'rsyi-hr' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- نموذج الطباعة المخفي -->
<div id="rsyi-hr-leave-printable" style="display:none;"></div>
