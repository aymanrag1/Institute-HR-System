<?php
/**
 * Admin View — المخالفات والجزاءات
 *
 * @package RSYI_HR
 */
defined( 'ABSPATH' ) || exit;

$status_labels = [
    'draft'        => 'مسودة',
    'pending_dean' => 'بانتظار اعتماد العميد',
    'approved'     => 'معتمد',
    'rejected'     => 'مرفوض',
];

$violation_types = [
    'تأخر متكرر',
    'غياب بدون إذن',
    'إهمال في العمل',
    'مخالفة اللوائح',
    'سلوك غير لائق',
    'تسريب معلومات',
    'أخرى',
];

$penalty_types = [
    'إنذار شفهي',
    'إنذار كتابي',
    'خصم من الراتب',
    'إيقاف عن العمل',
    'فصل',
];
?>
<div class="wrap rsyi-hr-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-warning"></span>
        <?php esc_html_e( 'المخالفات والجزاءات', 'rsyi-hr' ); ?>
    </h1>

    <div class="rsyi-hr-filters">
        <select id="rsyi-hr-filter-viol-status">
            <option value=""><?php esc_html_e( '— كل الحالات —', 'rsyi-hr' ); ?></option>
            <?php foreach ( $status_labels as $key => $lbl ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $lbl ); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="rsyi-hr-filter-viol-emp">
            <option value=""><?php esc_html_e( '— كل الموظفين —', 'rsyi-hr' ); ?></option>
            <?php foreach ( $employees as $e ) : ?>
                <option value="<?php echo esc_attr( $e['id'] ); ?>">
                    <?php echo esc_html( $e['full_name_ar'] ?: $e['full_name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ( current_user_can( 'rsyi_hr_manage_violations' ) ) : ?>
        <button class="button button-primary rsyi-hr-btn-add-viol">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e( 'إضافة مخالفة', 'rsyi-hr' ); ?>
        </button>
        <?php endif; ?>
    </div>

    <table class="wp-list-table widefat fixed striped rsyi-hr-table">
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th><?php esc_html_e( 'الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'المخالفة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'التاريخ', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الجزاء', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-viol-body">
            <tr><td colspan="7" class="rsyi-hr-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal: إضافة / تعديل مخالفة -->
<div id="rsyi-hr-viol-modal" class="rsyi-hr-modal" style="display:none">
    <div class="rsyi-hr-modal-content" dir="rtl">
        <button class="rsyi-hr-modal-close" type="button">&#x2715;</button>
        <h2><?php esc_html_e( 'تسجيل مخالفة وجزاء', 'rsyi-hr' ); ?></h2>

        <form id="rsyi-hr-viol-form">
            <input type="hidden" name="id" id="viol-id">
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
            <div class="rsyi-hr-form-cols-2">
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'نوع المخالفة *', 'rsyi-hr' ); ?></label>
                    <select name="violation_type" required>
                        <option value="">— <?php esc_html_e( 'اختر', 'rsyi-hr' ); ?> —</option>
                        <?php foreach ( $violation_types as $t ) : ?>
                            <option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( $t ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'تاريخ المخالفة *', 'rsyi-hr' ); ?></label>
                    <input type="date" name="violation_date" required value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'نوع الجزاء', 'rsyi-hr' ); ?></label>
                    <select name="penalty_type">
                        <option value="">— <?php esc_html_e( 'اختر', 'rsyi-hr' ); ?> —</option>
                        <?php foreach ( $penalty_types as $p ) : ?>
                            <option value="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'قيمة الجزاء', 'rsyi-hr' ); ?></label>
                    <input type="text" name="penalty_value" placeholder="<?php esc_attr_e( 'مثال: يوم واحد', 'rsyi-hr' ); ?>">
                </div>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'تفاصيل المخالفة', 'rsyi-hr' ); ?></label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'ملاحظات مدير HR', 'rsyi-hr' ); ?></label>
                <textarea name="hr_manager_notes" rows="2"></textarea>
            </div>
            <div class="rsyi-hr-form-actions">
                <button type="submit" name="action_type" value="draft" class="button">
                    <?php esc_html_e( 'حفظ مسودة', 'rsyi-hr' ); ?>
                </button>
                <button type="submit" name="action_type" value="submit_dean" class="button button-primary">
                    <?php esc_html_e( 'رفع لاعتماد العميد', 'rsyi-hr' ); ?>
                </button>
                <button type="button" class="button rsyi-hr-modal-close"><?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: اعتماد العميد -->
<div id="rsyi-hr-viol-dean-modal" class="rsyi-hr-modal" style="display:none">
    <div class="rsyi-hr-modal-content" dir="rtl">
        <button class="rsyi-hr-modal-close" type="button">&#x2715;</button>
        <h2><?php esc_html_e( 'تصديق العميد', 'rsyi-hr' ); ?></h2>
        <div id="rsyi-hr-viol-dean-info"></div>
        <div class="rsyi-hr-form-row">
            <label><?php esc_html_e( 'ملاحظات العميد', 'rsyi-hr' ); ?></label>
            <textarea id="rsyi-hr-viol-dean-notes" rows="3" style="width:100%"></textarea>
        </div>
        <div class="rsyi-hr-form-actions">
            <button class="button button-primary" id="rsyi-hr-viol-approve-btn"><?php esc_html_e( 'اعتماد وتصديق', 'rsyi-hr' ); ?></button>
            <button class="button" id="rsyi-hr-viol-reject-btn"><?php esc_html_e( 'رفض', 'rsyi-hr' ); ?></button>
        </div>
    </div>
</div>

<script>
(function ($) {
    var statusLabels = <?php echo wp_json_encode( $status_labels ); ?>;
    var currentViolId = 0;

    function loadViols() {
        var $tbody = $('#rsyi-hr-viol-body');
        $tbody.html('<tr><td colspan="7" class="rsyi-hr-loading">جارٍ التحميل…</td></tr>');
        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_get_violations', nonce: rsyiHR.nonce,
            status: $('#rsyi-hr-filter-viol-status').val(),
            employee_id: $('#rsyi-hr-filter-viol-emp').val()
        }, function (res) {
            if (!res.success || !res.data.length) { $tbody.html('<tr><td colspan="7">لا توجد مخالفات.</td></tr>'); return; }
            var html = res.data.map(function (r, i) {
                var empName = r.employee_name_ar || r.employee_name || '—';
                var statusClass = r.status === 'approved' ? 'rsyi-hr-badge-active' : r.status === 'rejected' ? 'rsyi-hr-badge-inactive' : 'rsyi-hr-badge-pending';
                var actions = '';
                if (r.status === 'pending_dean') {
                    actions += '<button class="button button-small rsyi-hr-viol-dean-btn" data-id="'+r.id+'" data-info=\''+JSON.stringify({name:empName,type:r.violation_type,date:r.violation_date})+'\'>تصديق العميد</button> ';
                }
                actions += '<button class="button button-small rsyi-hr-delete-viol" data-id="'+r.id+'">حذف</button>';
                return '<tr>' +
                    '<td>'+(i+1)+'</td><td>'+empName+'</td>' +
                    '<td>'+(r.violation_type||'—')+'</td><td>'+(r.violation_date||'—')+'</td>' +
                    '<td>'+(r.penalty_type||'—')+(r.penalty_value?' ('+r.penalty_value+')':'')+'</td>' +
                    '<td><span class="rsyi-hr-badge '+statusClass+'">'+(statusLabels[r.status]||r.status)+'</span></td>' +
                    '<td>'+actions+'</td></tr>';
            }).join('');
            $tbody.html(html);
        });
    }

    $(document).on('click', '.rsyi-hr-btn-add-viol', function () {
        $('#rsyi-hr-viol-form')[0].reset();
        $('[name="violation_date"]').val(new Date().toISOString().slice(0,10));
        $('#rsyi-hr-viol-modal').css({display:'flex', opacity:0}).animate({opacity:1}, 150);
    });

    $(document).on('submit', '#rsyi-hr-viol-form', function (e) {
        e.preventDefault();
        var actionType = document.activeElement.value || 'draft';
        var data = { action: 'rsyi_hr_save_violation', nonce: rsyiHR.nonce };
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        if (actionType === 'submit_dean') data.submit_for_dean = 1;
        $.post(rsyiHR.ajaxUrl, data, function (res) {
            if (!res.success) { alert(rsyiHR.i18n.error); return; }
            $('#rsyi-hr-viol-modal').animate({opacity:0}, 150, function() { $(this).css('display','none'); });
            loadViols();
        });
    });

    $(document).on('click', '.rsyi-hr-viol-dean-btn', function () {
        currentViolId = $(this).data('id');
        var info = $(this).data('info');
        $('#rsyi-hr-viol-dean-info').html('<p><strong>الموظف:</strong> '+info.name+' | <strong>المخالفة:</strong> '+info.type+' | <strong>التاريخ:</strong> '+info.date+'</p>');
        $('#rsyi-hr-viol-dean-notes').val('');
        $('#rsyi-hr-viol-dean-modal').css({display:'flex', opacity:0}).animate({opacity:1}, 150);
    });

    $('#rsyi-hr-viol-approve-btn').on('click', function () {
        if (!currentViolId) return;
        $.post(rsyiHR.ajaxUrl, { action: 'rsyi_hr_approve_violation', nonce: rsyiHR.nonce, id: currentViolId, notes: $('#rsyi-hr-viol-dean-notes').val() }, function (res) {
            if (!res.success) { alert(rsyiHR.i18n.error); return; }
            $('#rsyi-hr-viol-dean-modal').css('display','none');
            loadViols();
        });
    });

    $('#rsyi-hr-viol-reject-btn').on('click', function () {
        var reason = prompt('سبب الرفض:');
        if (reason === null) return;
        $.post(rsyiHR.ajaxUrl, { action: 'rsyi_hr_reject_violation', nonce: rsyiHR.nonce, id: currentViolId, reason: reason }, function () {
            $('#rsyi-hr-viol-dean-modal').css('display','none');
            loadViols();
        });
    });

    $(document).on('click', '.rsyi-hr-delete-viol', function () {
        if (!confirm(rsyiHR.i18n.confirm_delete)) return;
        $.post(rsyiHR.ajaxUrl, { action: 'rsyi_hr_delete_violation', nonce: rsyiHR.nonce, id: $(this).data('id') }, function () { loadViols(); });
    });

    $(document).on('change', '#rsyi-hr-filter-viol-status, #rsyi-hr-filter-viol-emp', loadViols);
    $(function () { loadViols(); });
}(jQuery));
</script>
