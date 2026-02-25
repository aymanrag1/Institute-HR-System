<?php
/**
 * Portal Template — لوحة تحكم الموظف
 *
 * يُعرض عبر شورت-كود: [rsyi_hr_portal]
 *
 * المتغيرات المتاحة:
 *   $emp  array|null  — سجل الموظف الحالي
 *
 * @package RSYI_HR
 */
defined( 'ABSPATH' ) || exit;

$user    = wp_get_current_user();
$emp_name = $emp
    ? ( $emp['full_name_ar'] ?: $emp['full_name'] )
    : $user->display_name;
?>
<div class="rsyi-portal" dir="rtl">

    <!-- رأس الصفحة -->
    <div class="rsyi-portal-header">
        <div class="rsyi-portal-avatar">
            <?php echo get_avatar( $user->ID, 72 ); ?>
        </div>
        <div class="rsyi-portal-user-info">
            <h2><?php echo esc_html( $emp_name ); ?></h2>
            <?php if ( $emp ) : ?>
                <p>
                    <?php echo esc_html( $emp['job_title_name'] ?? '' ); ?>
                    <?php if ( $emp['employee_number'] ) : ?>
                        &nbsp;|&nbsp; <?php esc_html_e( 'رقم الموظف', 'rsyi-hr' ); ?>: <strong><?php echo esc_html( $emp['employee_number'] ); ?></strong>
                    <?php endif; ?>
                </p>
            <?php else : ?>
                <p class="rsyi-portal-notice"><?php esc_html_e( 'حسابك غير مرتبط بسجل موظف. تواصل مع مدير الموارد البشرية.', 'rsyi-hr' ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( $emp ) : ?>

    <!-- التوقيع الإلكتروني -->
    <div class="rsyi-portal-card">
        <h3 class="rsyi-portal-card-title">
            <span class="dashicons dashicons-edit"></span>
            <?php esc_html_e( 'التوقيع الإلكتروني', 'rsyi-hr' ); ?>
        </h3>
        <div class="rsyi-portal-signature-area">
            <?php if ( ! empty( $emp['signature_url'] ) ) : ?>
                <img src="<?php echo esc_url( $emp['signature_url'] ); ?>"
                     alt="<?php esc_attr_e( 'التوقيع الإلكتروني', 'rsyi-hr' ); ?>"
                     class="rsyi-portal-sig-img">
                <p class="rsyi-portal-sig-note"><?php esc_html_e( 'يمكنك تحديث توقيعك الإلكتروني بتحميل صورة جديدة.', 'rsyi-hr' ); ?></p>
            <?php else : ?>
                <p class="rsyi-portal-notice"><?php esc_html_e( 'لم يتم رفع توقيعك الإلكتروني بعد.', 'rsyi-hr' ); ?></p>
            <?php endif; ?>
            <form id="rsyi-portal-sig-form" enctype="multipart/form-data">
                <label class="rsyi-portal-upload-label">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e( 'رفع صورة التوقيع (PNG/JPG)', 'rsyi-hr' ); ?>
                    <input type="file" name="signature_file" accept="image/png,image/jpeg" id="rsyi-portal-sig-file" style="display:none">
                </label>
                <button type="submit" class="rsyi-portal-btn rsyi-portal-btn-primary" id="rsyi-portal-sig-submit">
                    <?php esc_html_e( 'رفع التوقيع', 'rsyi-hr' ); ?>
                </button>
            </form>
            <div id="rsyi-portal-sig-msg"></div>
        </div>
    </div>

    <!-- الإجازات -->
    <div class="rsyi-portal-card">
        <div class="rsyi-portal-card-header">
            <h3 class="rsyi-portal-card-title">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e( 'طلبات الإجازة', 'rsyi-hr' ); ?>
            </h3>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'leave-request' ) ) ?: '#' ); ?>"
               class="rsyi-portal-btn rsyi-portal-btn-primary" id="rsyi-portal-new-leave-btn">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'طلب إجازة جديد', 'rsyi-hr' ); ?>
            </a>
        </div>
        <div id="rsyi-portal-leaves-list" class="rsyi-portal-list">
            <p class="rsyi-portal-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></p>
        </div>
    </div>

    <!-- العمل الإضافي -->
    <div class="rsyi-portal-card">
        <div class="rsyi-portal-card-header">
            <h3 class="rsyi-portal-card-title">
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e( 'طلبات العمل الإضافي', 'rsyi-hr' ); ?>
            </h3>
            <button class="rsyi-portal-btn rsyi-portal-btn-primary" id="rsyi-portal-new-ot-btn">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'طلب عمل إضافي', 'rsyi-hr' ); ?>
            </button>
        </div>
        <div id="rsyi-portal-ot-list" class="rsyi-portal-list">
            <p class="rsyi-portal-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></p>
        </div>
    </div>

    <!-- المخالفات والجزاءات -->
    <div class="rsyi-portal-card">
        <h3 class="rsyi-portal-card-title">
            <span class="dashicons dashicons-warning"></span>
            <?php esc_html_e( 'المخالفات والجزاءات', 'rsyi-hr' ); ?>
        </h3>
        <div id="rsyi-portal-viol-list" class="rsyi-portal-list">
            <p class="rsyi-portal-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></p>
        </div>
    </div>

    <!-- نموذج طلب عمل إضافي (inline) -->
    <div id="rsyi-portal-ot-form-card" class="rsyi-portal-card" style="display:none">
        <h3 class="rsyi-portal-card-title">
            <span class="dashicons dashicons-clock"></span>
            <?php esc_html_e( 'نموذج طلب العمل الإضافي', 'rsyi-hr' ); ?>
        </h3>
        <?php include __DIR__ . '/overtime-form.php'; ?>
    </div>

    <?php endif; // $emp ?>

</div>

<script>
(function ($) {
    var nonce = rsyiHRPortal.nonce;
    var ajaxUrl = rsyiHRPortal.ajaxUrl;

    var leaveStatusLabels = {
        draft: 'مسودة', pending_manager: 'بانتظار المدير',
        pending_hr: 'بانتظار HR', pending_dean: 'بانتظار العميد',
        approved: 'معتمد', rejected: 'مرفوض'
    };
    var leaveTypeLabels = { regular:'اعتيادية', sick:'مرضية', casual:'عارضة', unpaid:'بدون مرتب' };
    var otStatusLabels  = { draft:'مسودة', pending_manager:'بانتظار المدير', pending_hr:'بانتظار HR', approved:'معتمد', rejected:'مرفوض' };

    function statusBadge(status, map) {
        var label = (map[status] || status);
        var cls = status === 'approved' ? 'rsyi-portal-badge-green' : status === 'rejected' ? 'rsyi-portal-badge-red' : 'rsyi-portal-badge-yellow';
        return '<span class="rsyi-portal-badge '+cls+'">'+label+'</span>';
    }

    // Load leaves
    function loadLeaves() {
        $.post(ajaxUrl, { action: 'rsyi_hr_my_leaves', nonce: nonce }, function (res) {
            var $div = $('#rsyi-portal-leaves-list');
            if (!res.success || !res.data.length) { $div.html('<p>لا توجد طلبات إجازة.</p>'); return; }
            var html = '<table class="rsyi-portal-table"><thead><tr><th>النوع</th><th>من</th><th>إلى</th><th>أيام</th><th>الحالة</th><th></th></tr></thead><tbody>';
            res.data.forEach(function (r) {
                html += '<tr><td>'+(leaveTypeLabels[r.leave_type]||r.leave_type)+'</td><td>'+r.from_date+'</td><td>'+r.to_date+'</td><td>'+r.days_count+'</td><td>'+statusBadge(r.status, leaveStatusLabels)+'</td>';
                html += '<td>';
                if (r.status === 'approved') {
                    html += '<a href="' + ajaxUrl + '?action=rsyi_hr_print_leave&nonce=' + nonce + '&id=' + r.id + '" target="_blank" class="rsyi-portal-btn-sm">طباعة</a>';
                }
                html += '</td></tr>';
            });
            html += '</tbody></table>';
            $div.html(html);
        });
    }

    // Load overtime
    function loadOT() {
        $.post(ajaxUrl, { action: 'rsyi_hr_my_overtime', nonce: nonce }, function (res) {
            var $div = $('#rsyi-portal-ot-list');
            if (!res.success || !res.data.length) { $div.html('<p>لا توجد طلبات عمل إضافي.</p>'); return; }
            var html = '<table class="rsyi-portal-table"><thead><tr><th>التاريخ</th><th>من</th><th>إلى</th><th>ساعات</th><th>الحالة</th></tr></thead><tbody>';
            res.data.forEach(function (r) {
                html += '<tr><td>'+r.work_date+'</td><td>'+r.from_time+'</td><td>'+r.to_time+'</td><td>'+r.hours_count+'</td><td>'+statusBadge(r.status, otStatusLabels)+'</td></tr>';
            });
            html += '</tbody></table>';
            $div.html(html);
        });
    }

    // Load violations
    function loadViolations() {
        $.post(ajaxUrl, { action: 'rsyi_hr_my_violations', nonce: nonce }, function (res) {
            var $div = $('#rsyi-portal-viol-list');
            if (!res.success || !res.data.length) { $div.html('<p>لا توجد مخالفات مسجلة.</p>'); return; }
            var html = '<table class="rsyi-portal-table"><thead><tr><th>المخالفة</th><th>التاريخ</th><th>الجزاء</th></tr></thead><tbody>';
            res.data.forEach(function (r) {
                html += '<tr><td>'+r.violation_type+'</td><td>'+r.violation_date+'</td><td>'+(r.penalty_type||'—')+'</td></tr>';
            });
            html += '</tbody></table>';
            $div.html(html);
        });
    }

    // Signature upload
    $('#rsyi-portal-sig-form').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('action', 'rsyi_hr_upload_signature');
        fd.append('nonce', nonce);
        $('#rsyi-portal-sig-msg').html('<em>جارٍ الرفع...</em>');
        $.ajax({ url: ajaxUrl, type: 'POST', data: fd, processData: false, contentType: false,
            success: function (res) {
                if (res.success) {
                    $('#rsyi-portal-sig-msg').html('<span style="color:green">✓ تم رفع التوقيع بنجاح.</span>');
                    if (res.data && res.data.url) {
                        var $img = $('.rsyi-portal-sig-img');
                        if ($img.length) { $img.attr('src', res.data.url); }
                        else { $('.rsyi-portal-signature-area').prepend('<img src="'+res.data.url+'" class="rsyi-portal-sig-img" style="max-height:80px;border:1px solid #ccc;padding:4px;margin-bottom:8px">'); }
                    }
                } else {
                    $('#rsyi-portal-sig-msg').html('<span style="color:red">✗ '+(res.data && res.data.message ? res.data.message : 'خطأ')+'</span>');
                }
            }
        });
    });

    // Toggle OT form
    $('#rsyi-portal-new-ot-btn').on('click', function () {
        $('#rsyi-portal-ot-form-card').toggle();
    });

    $(function () {
        loadLeaves();
        loadOT();
        loadViolations();
    });
}(jQuery));
</script>
