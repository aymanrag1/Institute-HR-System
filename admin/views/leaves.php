<?php
/**
 * Admin View — طلبات الإجازة
 *
 * @package RSYI_HR
 */
defined( 'ABSPATH' ) || exit;

$status_labels = [
    'draft'          => [ 'ar' => 'مسودة',               'class' => 'rsyi-hr-badge-draft' ],
    'pending_manager'=> [ 'ar' => 'بانتظار المدير',       'class' => 'rsyi-hr-badge-pending' ],
    'pending_hr'     => [ 'ar' => 'بانتظار HR',           'class' => 'rsyi-hr-badge-pending' ],
    'pending_dean'   => [ 'ar' => 'بانتظار العميد',       'class' => 'rsyi-hr-badge-pending' ],
    'approved'       => [ 'ar' => 'معتمد',                'class' => 'rsyi-hr-badge-active' ],
    'rejected'       => [ 'ar' => 'مرفوض',               'class' => 'rsyi-hr-badge-inactive' ],
];

$leave_type_labels = [
    'regular' => __( 'اعتيادية',    'rsyi-hr' ),
    'sick'    => __( 'مرضية',       'rsyi-hr' ),
    'casual'  => __( 'عارضة',       'rsyi-hr' ),
    'unpaid'  => __( 'بدون مرتب',  'rsyi-hr' ),
];
?>
<div class="wrap rsyi-hr-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php esc_html_e( 'طلبات الإجازة', 'rsyi-hr' ); ?>
    </h1>

    <!-- Filters -->
    <div class="rsyi-hr-filters">
        <select id="rsyi-hr-filter-leave-status">
            <option value=""><?php esc_html_e( '— كل الحالات —', 'rsyi-hr' ); ?></option>
            <?php foreach ( $status_labels as $key => $lbl ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $lbl['ar'] ); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="rsyi-hr-filter-leave-emp">
            <option value=""><?php esc_html_e( '— كل الموظفين —', 'rsyi-hr' ); ?></option>
            <?php foreach ( $employees as $e ) : ?>
                <option value="<?php echo esc_attr( $e['id'] ); ?>">
                    <?php echo esc_html( $e['full_name_ar'] ?: $e['full_name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="button button-primary rsyi-hr-btn-add-leave">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e( 'إضافة طلب إجازة', 'rsyi-hr' ); ?>
        </button>
    </div>

    <!-- Table -->
    <table class="wp-list-table widefat fixed striped rsyi-hr-table">
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th><?php esc_html_e( 'الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'نوع الإجازة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'من', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إلى', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'أيام', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-leaves-body">
            <tr><td colspan="8" class="rsyi-hr-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal: إضافة / تعديل طلب إجازة -->
<div id="rsyi-hr-leave-modal" class="rsyi-hr-modal" style="display:none">
    <div class="rsyi-hr-modal-content rsyi-hr-modal-wide" dir="rtl">
        <button class="rsyi-hr-modal-close" type="button">&#x2715;</button>
        <h2 id="rsyi-hr-leave-modal-title"><?php esc_html_e( 'طلب إجازة', 'rsyi-hr' ); ?></h2>

        <form id="rsyi-hr-leave-form">
            <input type="hidden" name="id" id="leave-id">

            <div class="rsyi-hr-form-section">
                <h3 class="rsyi-hr-section-title">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php esc_html_e( 'بيانات الموظف', 'rsyi-hr' ); ?>
                </h3>
                <div class="rsyi-hr-form-cols-2">
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'الموظف *', 'rsyi-hr' ); ?></label>
                        <select name="employee_id" id="leave-employee" required>
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
                        <select name="leave_type" id="leave-type" required>
                            <?php foreach ( $leave_type_labels as $key => $lbl ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $lbl ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rsyi-hr-form-section">
                <h3 class="rsyi-hr-section-title">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e( 'تفاصيل الإجازة', 'rsyi-hr' ); ?>
                </h3>
                <div class="rsyi-hr-form-cols-3">
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'من يوم *', 'rsyi-hr' ); ?></label>
                        <input type="date" name="from_date" id="leave-from" required>
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'حتى يوم *', 'rsyi-hr' ); ?></label>
                        <input type="date" name="to_date" id="leave-to" required>
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'عدد الأيام', 'rsyi-hr' ); ?></label>
                        <input type="text" id="leave-days-display" readonly class="rsyi-hr-readonly">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'عودة للعمل يوم', 'rsyi-hr' ); ?></label>
                        <input type="date" name="return_date" id="leave-return">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'آخر يوم أجازة قمت بها', 'rsyi-hr' ); ?></label>
                        <input type="date" name="last_leave_date" id="leave-last">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'القائم بالعمل أثناء الإجازة', 'rsyi-hr' ); ?></label>
                        <input type="text" name="person_covering" id="leave-covering"
                               placeholder="<?php esc_attr_e( 'اسم القائم بالعمل', 'rsyi-hr' ); ?>">
                    </div>
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'السبب / الملاحظات', 'rsyi-hr' ); ?></label>
                    <textarea name="reason" id="leave-reason" rows="3"></textarea>
                </div>
            </div>

            <div class="rsyi-hr-form-actions">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e( 'حفظ الطلب', 'rsyi-hr' ); ?>
                </button>
                <button type="button" class="button rsyi-hr-modal-close">
                    <?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: تفاصيل طلب إجازة (للاعتماد / الرفض / الطباعة) -->
<div id="rsyi-hr-leave-detail-modal" class="rsyi-hr-modal" style="display:none">
    <div class="rsyi-hr-modal-content rsyi-hr-modal-wide" dir="rtl">
        <button class="rsyi-hr-modal-close" type="button">&#x2715;</button>
        <h2><?php esc_html_e( 'تفاصيل طلب الإجازة', 'rsyi-hr' ); ?></h2>
        <div id="rsyi-hr-leave-detail-body"></div>
        <div class="rsyi-hr-form-actions" id="rsyi-hr-leave-detail-actions"></div>
    </div>
</div>

<script>
(function ($) {
    'use strict';

    var statusLabels = <?php echo wp_json_encode( array_map( fn($l) => $l['ar'], $status_labels ) ); ?>;
    var leaveTypeLabels = <?php echo wp_json_encode( $leave_type_labels ); ?>;

    function loadLeaves() {
        var $tbody = $('#rsyi-hr-leaves-body');
        $tbody.html('<tr><td colspan="8" class="rsyi-hr-loading">جارٍ التحميل…</td></tr>');

        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_get_leaves',
            nonce: rsyiHR.nonce,
            status: $('#rsyi-hr-filter-leave-status').val(),
            employee_id: $('#rsyi-hr-filter-leave-emp').val()
        }, function (res) {
            if (!res.success || !res.data.length) {
                $tbody.html('<tr><td colspan="8">لا توجد طلبات.</td></tr>');
                return;
            }
            var html = res.data.map(function (r, i) {
                var empName = r.employee_name_ar || r.employee_name || '—';
                var status  = statusLabels[r.status] || r.status;
                var ltype   = leaveTypeLabels[r.leave_type] || r.leave_type;
                return '<tr>' +
                    '<td>' + (i+1) + '</td>' +
                    '<td>' + empName + '</td>' +
                    '<td>' + ltype + '</td>' +
                    '<td>' + (r.from_date || '—') + '</td>' +
                    '<td>' + (r.to_date   || '—') + '</td>' +
                    '<td>' + (r.days_count || '—') + '</td>' +
                    '<td><span class="rsyi-hr-badge ' + (r.status === 'approved' ? 'rsyi-hr-badge-active' : r.status === 'rejected' ? 'rsyi-hr-badge-inactive' : 'rsyi-hr-badge-pending') + '">' + status + '</span></td>' +
                    '<td>' +
                        '<button class="button button-small rsyi-hr-view-leave" data-id="' + r.id + '">عرض</button> ' +
                        '<button class="button button-small rsyi-hr-print-leave" data-id="' + r.id + '">' + (rsyiHR.i18n.print || 'طباعة') + '</button> ' +
                        '<button class="button button-small rsyi-hr-delete-leave" data-id="' + r.id + '">' + (rsyiHR.i18n.delete || 'حذف') + '</button>' +
                    '</td>' +
                '</tr>';
            }).join('');
            $tbody.html(html);
        });
    }

    // Auto-fill last leave date when employee is selected
    $(document).on('change', '#leave-employee', function () {
        var empId = $(this).val();
        if (!empId) {
            $('#leave-last').val('');
            $('#rsyi-hr-leave-balance-info').remove();
            return;
        }
        // Fetch last approved leave date
        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_get_last_leave_date',
            nonce: rsyiHR.nonce,
            employee_id: empId
        }, function (res) {
            if (res.success && res.data.last_leave_date) {
                $('#leave-last').val(res.data.last_leave_date);
            }
        });
        // Fetch leave balance for current year
        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_get_employee_balances',
            nonce: rsyiHR.nonce,
            employee_id: empId,
            year: new Date().getFullYear()
        }, function (res) {
            $('#rsyi-hr-leave-balance-info').remove();
            if (!res.success) return;
            var b = res.data;
            var typeLabels = {regular: 'اعتيادية', sick: 'مرضية', casual: 'عارضة', unpaid: 'بدون مرتب'};
            var html = '<div id="rsyi-hr-leave-balance-info" style="background:#f0f6fc;border:1px solid #c5d9ed;border-radius:4px;padding:10px 14px;margin:8px 0;font-size:13px">' +
                '<strong>رصيد الإجازات (' + new Date().getFullYear() + '):</strong> ';
            var parts = [];
            $.each(b, function(type, info) {
                if (info.total > 0 || info.used > 0) {
                    parts.push(typeLabels[type] + ': <span style="color:' + (info.remaining > 0 ? '#2e7d32' : '#c62828') + '">' + info.remaining + '</span>/' + info.total + ' يوم');
                }
            });
            if (parts.length) {
                html += parts.join(' &nbsp;|&nbsp; ');
            } else {
                html += '<span style="color:#888">لم يُحدَّد رصيد لهذا الموظف</span>';
            }
            html += '</div>';
            $('#leave-employee').closest('.rsyi-hr-form-cols-2').after(html);
        });
    });

    // Calculate days between dates
    $(document).on('change', '#leave-from, #leave-to', function () {
        var f = $('#leave-from').val(), t = $('#leave-to').val();
        if (f && t) {
            var days = Math.ceil((new Date(t) - new Date(f)) / 86400000) + 1;
            $('#leave-days-display').val(days > 0 ? days : '—');
        }
    });

    // Add leave
    $(document).on('click', '.rsyi-hr-btn-add-leave', function () {
        $('#rsyi-hr-leave-form')[0].reset();
        $('#leave-id').val('');
        $('#leave-days-display').val('');
        $('#rsyi-hr-leave-modal-title').text('إضافة طلب إجازة');
        $('#rsyi-hr-leave-modal').css({display:'flex', opacity:0}).animate({opacity:1}, 150);
    });

    // Save leave
    $(document).on('submit', '#rsyi-hr-leave-form', function (e) {
        e.preventDefault();
        var data = {};
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        data.action = 'rsyi_hr_save_leave';
        data.nonce  = rsyiHR.nonce;
        $.post(rsyiHR.ajaxUrl, data, function (res) {
            if (!res.success) { alert(rsyiHR.i18n.error); return; }
            $('#rsyi-hr-leave-modal').animate({opacity:0}, 150, function() { $(this).css('display','none'); });
            loadLeaves();
        });
    });

    // View leave details
    $(document).on('click', '.rsyi-hr-view-leave', function () {
        var id = $(this).data('id');
        $.post(rsyiHR.ajaxUrl, { action: 'rsyi_hr_get_leave', nonce: rsyiHR.nonce, id: id }, function (res) {
            if (!res.success) return;
            var r = res.data;
            var empName = r.employee_name_ar || r.employee_name || '—';
            var leaveTypeName = leaveTypeLabels[r.leave_type] || r.leave_type;
            var html = '<table class="widefat" style="margin-bottom:16px">' +
                '<tr><th>الموظف</th><td>' + empName + '</td><th>الوظيفة</th><td>' + (r.job_title_name||'—') + '</td></tr>' +
                '<tr><th>نوع الإجازة</th><td>' + leaveTypeName + '</td><th>عدد الأيام</th><td>' + (r.days_count||'—') + '</td></tr>' +
                '<tr><th>من يوم</th><td>' + (r.from_date||'—') + '</td><th>حتى يوم</th><td>' + (r.to_date||'—') + '</td></tr>' +
                '<tr><th>عودة للعمل</th><td>' + (r.return_date||'—') + '</td><th>آخر إجازة</th><td>' + (r.last_leave_date||'—') + '</td></tr>' +
                '<tr><th>القائم بالعمل</th><td colspan="3">' + (r.person_covering||'—') + '</td></tr>' +
                '<tr><th>الحالة</th><td colspan="3"><strong>' + (statusLabels[r.status]||r.status) + '</strong></td></tr>' +
                '</table>';

            // Signatures
            html += '<div style="border-top:1px solid #ddd;padding-top:12px;margin-top:8px">';
            html += '<h3>التوقيعات</h3>';
            if (r.employee_signature_img) {
                html += '<p><strong>توقيع الموظف:</strong> <img src="' + r.employee_signature_img + '" style="max-height:60px;border:1px solid #ccc;padding:4px"></p>';
            }
            if (r.manager_signed_at) {
                html += '<p><strong>توقيع المدير المباشر:</strong> <span style="color:green">✓</span> ' + r.manager_signed_at + '</p>';
            }
            if (r.hr_manager_signed_at) {
                html += '<p><strong>توقيع مدير الموارد البشرية:</strong> <span style="color:green">✓</span> ' + r.hr_manager_signed_at + '</p>';
            }
            if (r.dean_signed_at) {
                html += '<p><strong>تصديق العميد:</strong> <span style="color:green">✓</span> ' + r.dean_signed_at + '</p>';
            }
            html += '</div>';

            $('#rsyi-hr-leave-detail-body').html(html);

            // Action buttons by status
            var actions = '';
            if (r.status === 'pending_manager') {
                actions += '<button class="button button-primary rsyi-hr-do-approve-leave" data-id="'+r.id+'" data-stage="manager">اعتماد المدير المباشر</button> ';
            }
            if (r.status === 'pending_hr') {
                actions += '<button class="button button-primary rsyi-hr-do-approve-leave" data-id="'+r.id+'" data-stage="hr_manager">اعتماد مدير HR</button> ';
            }
            if (r.status === 'pending_dean') {
                actions += '<button class="button button-primary rsyi-hr-do-approve-leave" data-id="'+r.id+'" data-stage="dean">تصديق العميد</button> ';
            }
            if (['pending_manager','pending_hr','pending_dean'].indexOf(r.status) !== -1) {
                actions += '<button class="button rsyi-hr-do-reject-leave" data-id="'+r.id+'">رفض</button> ';
            }
            actions += '<a class="button" href="' + rsyiHR.ajaxUrl + '?action=rsyi_hr_print_leave&nonce=' + rsyiHR.nonce + '&id=' + r.id + '" target="_blank">طباعة</a>';
            $('#rsyi-hr-leave-detail-actions').html(actions);

            $('#rsyi-hr-leave-detail-modal').css({display:'flex', opacity:0}).animate({opacity:1}, 150);
        });
    });

    // Approve leave
    $(document).on('click', '.rsyi-hr-do-approve-leave', function () {
        if (!confirm(rsyiHR.i18n.confirm_approve || 'هل تريد الاعتماد؟')) return;
        var id = $(this).data('id'), stage = $(this).data('stage');
        $.post(rsyiHR.ajaxUrl, { action: 'rsyi_hr_approve_leave', nonce: rsyiHR.nonce, id: id, stage: stage }, function (res) {
            if (!res.success) { alert(rsyiHR.i18n.error); return; }
            alert(rsyiHR.i18n.approved || 'تم الاعتماد');
            $('#rsyi-hr-leave-detail-modal').css('display','none');
            loadLeaves();
        });
    });

    // Reject leave
    $(document).on('click', '.rsyi-hr-do-reject-leave', function () {
        var reason = prompt('سبب الرفض:');
        if (reason === null) return;
        var id = $(this).data('id');
        $.post(rsyiHR.ajaxUrl, { action: 'rsyi_hr_reject_leave', nonce: rsyiHR.nonce, id: id, reason: reason, rejected_by: 'admin' }, function () {
            $('#rsyi-hr-leave-detail-modal').css('display','none');
            loadLeaves();
        });
    });

    // Print leave
    $(document).on('click', '.rsyi-hr-print-leave', function () {
        var id = $(this).data('id');
        window.open(rsyiHR.ajaxUrl + '?action=rsyi_hr_print_leave&nonce=' + rsyiHR.nonce + '&id=' + id, '_blank');
    });

    // Delete leave
    $(document).on('click', '.rsyi-hr-delete-leave', function () {
        if (!confirm(rsyiHR.i18n.confirm_delete)) return;
        var id = $(this).data('id');
        $.post(rsyiHR.ajaxUrl, { action: 'rsyi_hr_delete_leave', nonce: rsyiHR.nonce, id: id }, function () { loadLeaves(); });
    });

    $(document).on('change', '#rsyi-hr-filter-leave-status, #rsyi-hr-filter-leave-emp', loadLeaves);

    $(function () { loadLeaves(); });
}(jQuery));
</script>
