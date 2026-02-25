<?php
/**
 * Admin View — طلبات العمل الإضافي
 *
 * @package RSYI_HR
 */
defined( 'ABSPATH' ) || exit;

$status_labels = [
    'draft'           => 'مسودة',
    'pending_manager' => 'بانتظار المدير',
    'pending_hr'      => 'بانتظار HR',
    'approved'        => 'معتمد',
    'rejected'        => 'مرفوض',
];
?>
<div class="wrap rsyi-hr-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-clock"></span>
        <?php esc_html_e( 'طلبات العمل الإضافي', 'rsyi-hr' ); ?>
    </h1>

    <div class="rsyi-hr-filters">
        <select id="rsyi-hr-filter-ot-status">
            <option value=""><?php esc_html_e( '— كل الحالات —', 'rsyi-hr' ); ?></option>
            <?php foreach ( $status_labels as $key => $lbl ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $lbl ); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="rsyi-hr-filter-ot-emp">
            <option value=""><?php esc_html_e( '— كل الموظفين —', 'rsyi-hr' ); ?></option>
            <?php foreach ( $employees as $e ) : ?>
                <option value="<?php echo esc_attr( $e['id'] ); ?>">
                    <?php echo esc_html( $e['full_name_ar'] ?: $e['full_name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="button button-primary rsyi-hr-btn-add-ot">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e( 'إضافة طلب', 'rsyi-hr' ); ?>
        </button>
    </div>

    <table class="wp-list-table widefat fixed striped rsyi-hr-table">
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th><?php esc_html_e( 'الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'تاريخ العمل', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'من', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إلى', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'ساعات', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-ot-body">
            <tr><td colspan="8" class="rsyi-hr-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal: إضافة طلب -->
<div id="rsyi-hr-ot-modal" class="rsyi-hr-modal" style="display:none">
    <div class="rsyi-hr-modal-content" dir="rtl">
        <button class="rsyi-hr-modal-close" type="button">&#x2715;</button>
        <h2><?php esc_html_e( 'طلب عمل إضافي', 'rsyi-hr' ); ?></h2>

        <form id="rsyi-hr-ot-form">
            <input type="hidden" name="id" id="ot-id">
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الموظف *', 'rsyi-hr' ); ?></label>
                <select name="employee_id" required>
                    <option value="">— <?php esc_html_e( 'اختر موظفاً', 'rsyi-hr' ); ?> —</option>
                    <?php foreach ( $employees as $e ) : ?>
                        <option value="<?php echo esc_attr( $e['id'] ); ?>">
                            <?php echo esc_html( ( $e['full_name_ar'] ?: $e['full_name'] ) . ' — ' . $e['employee_number'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-cols-3">
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'تاريخ العمل *', 'rsyi-hr' ); ?></label>
                    <input type="date" name="work_date" id="ot-work-date" required>
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'من الساعة *', 'rsyi-hr' ); ?></label>
                    <input type="time" name="from_time" id="ot-from" required>
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'إلى الساعة *', 'rsyi-hr' ); ?></label>
                    <input type="time" name="to_time" id="ot-to" required>
                </div>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'عدد الساعات (محسوب)', 'rsyi-hr' ); ?></label>
                <input type="text" id="ot-hours-display" readonly class="rsyi-hr-readonly">
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'سبب الطلب', 'rsyi-hr' ); ?></label>
                <textarea name="reason" rows="3"></textarea>
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
    var statusLabels = <?php echo wp_json_encode( $status_labels ); ?>;

    function loadOT() {
        var $tbody = $('#rsyi-hr-ot-body');
        $tbody.html('<tr><td colspan="8" class="rsyi-hr-loading">جارٍ التحميل…</td></tr>');
        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_get_overtime_list', nonce: rsyiHR.nonce,
            status: $('#rsyi-hr-filter-ot-status').val(),
            employee_id: $('#rsyi-hr-filter-ot-emp').val()
        }, function (res) {
            if (!res.success || !res.data.length) { $tbody.html('<tr><td colspan="8">لا توجد طلبات.</td></tr>'); return; }
            var html = res.data.map(function (r, i) {
                var empName = r.employee_name_ar || r.employee_name || '—';
                var statusClass = r.status === 'approved' ? 'rsyi-hr-badge-active' : r.status === 'rejected' ? 'rsyi-hr-badge-inactive' : 'rsyi-hr-badge-pending';
                return '<tr>' +
                    '<td>'+(i+1)+'</td><td>'+empName+'</td><td>'+(r.work_date||'—')+'</td>' +
                    '<td>'+(r.from_time||'—')+'</td><td>'+(r.to_time||'—')+'</td>' +
                    '<td><strong>'+(r.hours_count||'0')+'</strong> ساعة</td>' +
                    '<td><span class="rsyi-hr-badge '+statusClass+'">'+(statusLabels[r.status]||r.status)+'</span></td>' +
                    '<td>' +
                    (r.status === 'pending_manager' ? '<button class="button button-small rsyi-hr-ot-approve" data-id="'+r.id+'" data-stage="manager">اعتماد مدير</button> ' : '') +
                    (r.status === 'pending_hr'      ? '<button class="button button-small rsyi-hr-ot-approve" data-id="'+r.id+'" data-stage="hr_manager">اعتماد HR</button> ' : '') +
                    '<button class="button button-small rsyi-hr-delete-ot" data-id="'+r.id+'">حذف</button>' +
                    '</td></tr>';
            }).join('');
            $tbody.html(html);
        });
    }

    $(document).on('change', '#ot-from, #ot-to', function () {
        var f = $('#ot-from').val(), t = $('#ot-to').val();
        if (f && t) {
            var diff = (new Date('1970-01-01T'+t) - new Date('1970-01-01T'+f)) / 3600000;
            $('#ot-hours-display').val(diff > 0 ? diff.toFixed(2) + ' ساعة' : '—');
        }
    });

    $(document).on('click', '.rsyi-hr-btn-add-ot', function () {
        $('#rsyi-hr-ot-form')[0].reset();
        $('#ot-id, #ot-hours-display').val('');
        $('#rsyi-hr-ot-modal').css({display:'flex', opacity:0}).animate({opacity:1}, 150);
    });

    $(document).on('submit', '#rsyi-hr-ot-form', function (e) {
        e.preventDefault();
        var data = { action: 'rsyi_hr_save_overtime', nonce: rsyiHR.nonce };
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        $.post(rsyiHR.ajaxUrl, data, function (res) {
            if (!res.success) { alert(rsyiHR.i18n.error); return; }
            $('#rsyi-hr-ot-modal').animate({opacity:0}, 150, function() { $(this).css('display','none'); });
            loadOT();
        });
    });

    $(document).on('click', '.rsyi-hr-ot-approve', function () {
        if (!confirm('هل تريد الاعتماد؟')) return;
        var id = $(this).data('id'), stage = $(this).data('stage');
        $.post(rsyiHR.ajaxUrl, { action: 'rsyi_hr_approve_overtime', nonce: rsyiHR.nonce, id: id, stage: stage }, function () { loadOT(); });
    });

    $(document).on('click', '.rsyi-hr-delete-ot', function () {
        if (!confirm(rsyiHR.i18n.confirm_delete)) return;
        $.post(rsyiHR.ajaxUrl, { action: 'rsyi_hr_delete_overtime', nonce: rsyiHR.nonce, id: $(this).data('id') }, function () { loadOT(); });
    });

    $(document).on('change', '#rsyi-hr-filter-ot-status, #rsyi-hr-filter-ot-emp', loadOT);
    $(function () { loadOT(); });
}(jQuery));
</script>
