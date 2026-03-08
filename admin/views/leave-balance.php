<?php
/**
 * Admin View — رصيد الإجازات
 *
 * @package RSYI_HR
 */
defined( 'ABSPATH' ) || exit;

$leave_type_labels = [
    'regular' => __( 'اعتيادية',   'rsyi-hr' ),
    'sick'    => __( 'مرضية',      'rsyi-hr' ),
    'casual'  => __( 'عارضة',      'rsyi-hr' ),
    'unpaid'  => __( 'بدون مرتب', 'rsyi-hr' ),
];
$current_year = (int) date( 'Y' );
?>
<div class="wrap rsyi-hr-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-calendar"></span>
        <?php esc_html_e( 'رصيد الإجازات', 'rsyi-hr' ); ?>
    </h1>

    <!-- Toolbar -->
    <div class="rsyi-hr-filters" style="align-items:center">
        <label style="font-weight:bold"><?php esc_html_e( 'السنة:', 'rsyi-hr' ); ?></label>
        <select id="rsyi-hr-balance-year" style="width:100px">
            <?php for ( $y = $current_year + 1; $y >= $current_year - 3; $y-- ) : ?>
                <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $y, $current_year ); ?>>
                    <?php echo esc_html( $y ); ?>
                </option>
            <?php endfor; ?>
        </select>
        <button class="button button-primary" id="rsyi-hr-balance-add">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e( 'تعيين رصيد موظف', 'rsyi-hr' ); ?>
        </button>
    </div>

    <!-- Table -->
    <table class="wp-list-table widefat fixed striped rsyi-hr-table" style="margin-top:16px">
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th><?php esc_html_e( 'الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'نوع الإجازة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'السنة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجمالي الأيام', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'المستخدم', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'المتبقي', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-balance-body">
            <tr><td colspan="8" class="rsyi-hr-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal: تعيين رصيد -->
<div id="rsyi-hr-balance-modal" class="rsyi-hr-modal" style="display:none">
    <div class="rsyi-hr-modal-content" dir="rtl" style="max-width:480px">
        <button class="rsyi-hr-modal-close" type="button">&#x2715;</button>
        <h2><?php esc_html_e( 'تعيين رصيد إجازة', 'rsyi-hr' ); ?></h2>

        <form id="rsyi-hr-balance-form">
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الموظف *', 'rsyi-hr' ); ?></label>
                <select name="employee_id" id="balance-employee" required>
                    <option value="">— <?php esc_html_e( 'اختر موظفاً', 'rsyi-hr' ); ?> —</option>
                    <?php foreach ( $employees as $e ) : ?>
                        <option value="<?php echo esc_attr( $e['id'] ); ?>">
                            <?php echo esc_html( ( $e['full_name_ar'] ?: $e['full_name'] ) . ' — ' . $e['employee_number'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'نوع الإجازة *', 'rsyi-hr' ); ?></label>
                <select name="leave_type" id="balance-type" required>
                    <?php foreach ( $leave_type_labels as $key => $lbl ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $lbl ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'السنة *', 'rsyi-hr' ); ?></label>
                <input type="number" name="year" id="balance-year-input" value="<?php echo esc_attr( $current_year ); ?>" min="2020" max="2099" required>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'إجمالي الأيام المسموح بها *', 'rsyi-hr' ); ?></label>
                <input type="number" name="total_days" id="balance-total" value="0" min="0" max="365" required>
            </div>
            <div class="rsyi-hr-form-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'حفظ', 'rsyi-hr' ); ?></button>
                <button type="button" class="button rsyi-hr-modal-close"><?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
(function ($) {
    'use strict';

    var typeLabels = <?php echo wp_json_encode( $leave_type_labels ); ?>;

    function loadBalances() {
        var year = $('#rsyi-hr-balance-year').val();
        var $tbody = $('#rsyi-hr-balance-body');
        $tbody.html('<tr><td colspan="8" class="rsyi-hr-loading">جارٍ التحميل…</td></tr>');

        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_get_all_balances',
            nonce: rsyiHR.nonce,
            year: year
        }, function (res) {
            if (!res.success || !res.data.length) {
                $tbody.html('<tr><td colspan="8">لا توجد أرصدة مسجلة لهذه السنة.</td></tr>');
                return;
            }
            var html = res.data.map(function (r, i) {
                var empName = r.full_name_ar || r.full_name || '—';
                // compute used & remaining (loaded separately per row for accuracy)
                var remaining = parseInt(r.total_days, 10); // will be refined below
                var color = remaining > 0 ? 'rsyi-hr-badge-active' : 'rsyi-hr-badge-inactive';
                return '<tr data-id="' + r.id + '">' +
                    '<td>' + (i+1) + '</td>' +
                    '<td>' + empName + ' <small style="color:#888">(' + (r.employee_number||'') + ')</small></td>' +
                    '<td>' + (typeLabels[r.leave_type] || r.leave_type) + '</td>' +
                    '<td>' + r.year + '</td>' +
                    '<td>' + r.total_days + '</td>' +
                    '<td class="used-cell-'+r.id+'">—</td>' +
                    '<td class="rem-cell-'+r.id+'">—</td>' +
                    '<td>' +
                        '<button class="button button-small rsyi-hr-balance-edit" ' +
                            'data-emp="' + r.employee_id + '" data-type="' + r.leave_type + '" ' +
                            'data-year="' + r.year + '" data-total="' + r.total_days + '">تعديل</button>' +
                    '</td>' +
                '</tr>';
            }).join('');
            $tbody.html(html);

            // Enrich with used/remaining per employee
            var fetched = {};
            res.data.forEach(function (r) {
                var key = r.employee_id + '_' + r.year;
                if (fetched[key]) return;
                fetched[key] = true;
                $.post(rsyiHR.ajaxUrl, {
                    action: 'rsyi_hr_get_employee_balances',
                    nonce: rsyiHR.nonce,
                    employee_id: r.employee_id,
                    year: r.year
                }, function (resp) {
                    if (!resp.success) return;
                    res.data.filter(function(x){ return x.employee_id === r.employee_id && x.year === r.year; })
                        .forEach(function(x) {
                            var info = resp.data[x.leave_type];
                            if (!info) return;
                            $('.used-cell-' + x.id).text(info.used);
                            $('.rem-cell-' + x.id)
                                .html('<span class="rsyi-hr-badge ' + (info.remaining >= 0 ? 'rsyi-hr-badge-active' : 'rsyi-hr-badge-inactive') + '">' + info.remaining + '</span>');
                        });
                });
            });
        });
    }

    // Open add modal
    $(document).on('click', '#rsyi-hr-balance-add', function () {
        $('#rsyi-hr-balance-form')[0].reset();
        $('#balance-year-input').val($('#rsyi-hr-balance-year').val());
        $('#rsyi-hr-balance-modal').css({display:'flex', opacity:0}).animate({opacity:1}, 150);
    });

    // Open edit modal (pre-fill)
    $(document).on('click', '.rsyi-hr-balance-edit', function () {
        var $btn = $(this);
        $('#balance-employee').val($btn.data('emp'));
        $('#balance-type').val($btn.data('type'));
        $('#balance-year-input').val($btn.data('year'));
        $('#balance-total').val($btn.data('total'));
        $('#rsyi-hr-balance-modal').css({display:'flex', opacity:0}).animate({opacity:1}, 150);
    });

    // Save balance
    $(document).on('submit', '#rsyi-hr-balance-form', function (e) {
        e.preventDefault();
        var data = {};
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        data.action = 'rsyi_hr_set_leave_balance';
        data.nonce  = rsyiHR.nonce;
        $.post(rsyiHR.ajaxUrl, data, function (res) {
            if (!res.success) { alert(rsyiHR.i18n.error); return; }
            alert(rsyiHR.i18n.saved || 'تم الحفظ');
            $('#rsyi-hr-balance-modal').animate({opacity:0}, 150, function() { $(this).css('display','none'); });
            loadBalances();
        });
    });

    $(document).on('change', '#rsyi-hr-balance-year', loadBalances);

    $(function () { loadBalances(); });

    // Modal close
    $(document).on('click', '.rsyi-hr-modal-close', function () {
        $(this).closest('.rsyi-hr-modal').animate({opacity:0}, 150, function () { $(this).css('display','none'); });
    });
}(jQuery));
</script>
