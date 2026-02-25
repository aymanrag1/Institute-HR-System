<?php
/**
 * Portal Template — نموذج طلب الإجازة
 *
 * يُعرض عبر شورت-كود: [rsyi_hr_leave_form]
 * أو مضمَّن في employee-dashboard.php
 *
 * @package RSYI_HR
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="rsyi-portal" dir="rtl">
<div class="rsyi-portal-card rsyi-portal-leave-form-card">

    <h2 class="rsyi-portal-form-title">
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php esc_html_e( 'طلب إجازة', 'rsyi-hr' ); ?>
    </h2>

    <?php if ( ! $emp ) : ?>
        <div class="rsyi-portal-notice-error">
            <?php esc_html_e( 'لم يتم ربط حسابك بسجل موظف. تواصل مع مدير الموارد البشرية.', 'rsyi-hr' ); ?>
        </div>
    <?php else : ?>

    <form id="rsyi-portal-leave-form" class="rsyi-portal-form">

        <!-- بيانات الموظف (للعرض فقط) -->
        <div class="rsyi-portal-form-section">
            <div class="rsyi-portal-form-cols-2">
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'الاسم', 'rsyi-hr' ); ?></label>
                    <input type="text" value="<?php echo esc_attr( $emp['full_name_ar'] ?: $emp['full_name'] ); ?>" readonly class="rsyi-portal-readonly">
                </div>
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'الوظيفة', 'rsyi-hr' ); ?></label>
                    <input type="text" value="<?php echo esc_attr( $emp['job_title_name'] ?? '' ); ?>" readonly class="rsyi-portal-readonly">
                </div>
            </div>
        </div>

        <!-- نوع الإجازة -->
        <div class="rsyi-portal-form-section">
            <label class="rsyi-portal-label-block"><?php esc_html_e( 'نوع الإجازة *', 'rsyi-hr' ); ?></label>
            <div class="rsyi-portal-radio-group">
                <?php
                $leave_types = [
                    'regular' => __( 'اعتيادية', 'rsyi-hr' ),
                    'sick'    => __( 'مرضى', 'rsyi-hr' ),
                    'casual'  => __( 'عارضة', 'rsyi-hr' ),
                    'unpaid'  => __( 'بدون مرتب', 'rsyi-hr' ),
                ];
                foreach ( $leave_types as $val => $lbl ) : ?>
                <label class="rsyi-portal-radio-label">
                    <input type="radio" name="leave_type" value="<?php echo esc_attr( $val ); ?>"
                           <?php checked( $val, 'regular' ); ?>>
                    <?php echo esc_html( $lbl ); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- التواريخ -->
        <div class="rsyi-portal-form-section">
            <div class="rsyi-portal-form-cols-2">
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'من يوم *', 'rsyi-hr' ); ?></label>
                    <input type="date" name="from_date" id="rpl-from" required>
                    <small class="rsyi-portal-field-note"><?php esc_html_e( 'الموافق', 'rsyi-hr' ); ?> <span id="rpl-from-hijri"></span></small>
                </div>
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'حتى يوم *', 'rsyi-hr' ); ?></label>
                    <input type="date" name="to_date" id="rpl-to" required>
                    <small class="rsyi-portal-field-note"><?php esc_html_e( 'الموافق', 'rsyi-hr' ); ?> <span id="rpl-to-hijri"></span></small>
                </div>
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'عودة إلى العمل يوم', 'rsyi-hr' ); ?></label>
                    <input type="date" name="return_date" id="rpl-return">
                    <small class="rsyi-portal-field-note"><?php esc_html_e( 'الموافق', 'rsyi-hr' ); ?> <span id="rpl-return-hijri"></span></small>
                </div>
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'عدد الأيام', 'rsyi-hr' ); ?></label>
                    <input type="text" id="rpl-days" readonly class="rsyi-portal-readonly">
                </div>
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'آخر يوم أجازة قمت بها', 'rsyi-hr' ); ?></label>
                    <input type="date" name="last_leave_date">
                </div>
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'القائم بالعمل أثناء الإجازة', 'rsyi-hr' ); ?></label>
                    <input type="text" name="person_covering"
                           placeholder="<?php esc_attr_e( 'اسم الزميل القائم بالعمل', 'rsyi-hr' ); ?>">
                </div>
            </div>
        </div>

        <!-- التوقيع الإلكتروني -->
        <div class="rsyi-portal-form-section rsyi-portal-sig-section">
            <label class="rsyi-portal-label-block">
                <?php esc_html_e( 'التوقيع الإلكتروني للموظف *', 'rsyi-hr' ); ?>
            </label>

            <?php if ( ! empty( $emp['signature_url'] ) ) : ?>
                <!-- استخدام التوقيع المحفوظ -->
                <div class="rsyi-portal-saved-sig">
                    <img src="<?php echo esc_url( $emp['signature_url'] ); ?>"
                         alt="<?php esc_attr_e( 'توقيعك الإلكتروني', 'rsyi-hr' ); ?>"
                         style="max-height:80px;border:1px solid #ccc;padding:6px;border-radius:4px">
                    <p style="color:#50575e;font-size:13px;margin:8px 0 0">
                        <?php esc_html_e( 'سيُستخدم توقيعك الإلكتروني المحفوظ في الطلب.', 'rsyi-hr' ); ?>
                    </p>
                    <input type="hidden" name="employee_signature" value="<?php echo esc_attr( $emp['signature_url'] ); ?>">
                </div>
            <?php else : ?>
                <!-- لوحة التوقيع -->
                <p class="rsyi-portal-notice">
                    <?php esc_html_e( 'لم يتم رفع توقيعك الإلكتروني. يرجى الرسم هنا أو رفع صورة التوقيع من لوحة التحكم.', 'rsyi-hr' ); ?>
                </p>
                <canvas id="rpl-sig-canvas" width="500" height="150"
                        style="border:2px dashed #aaa;border-radius:6px;cursor:crosshair;max-width:100%;touch-action:none;background:#fff"></canvas>
                <div style="margin-top:8px;display:flex;gap:8px">
                    <button type="button" id="rpl-sig-clear" class="rsyi-portal-btn rsyi-portal-btn-sm">
                        <?php esc_html_e( 'مسح التوقيع', 'rsyi-hr' ); ?>
                    </button>
                </div>
                <input type="hidden" name="employee_signature" id="rpl-sig-data">
            <?php endif; ?>
        </div>

        <div id="rsyi-portal-leave-msg" style="margin:12px 0"></div>

        <div class="rsyi-portal-form-actions">
            <button type="submit" class="rsyi-portal-btn rsyi-portal-btn-primary" id="rpl-submit-btn">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e( 'رفع طلب الإجازة', 'rsyi-hr' ); ?>
            </button>
        </div>
    </form>

    <?php endif; // $emp ?>
</div>
</div>

<script>
(function ($) {
    /* ── Signature Canvas ─────────────────────────────────────────────── */
    var canvas = document.getElementById('rpl-sig-canvas');
    if (canvas) {
        var ctx = canvas.getContext('2d');
        var drawing = false;

        function getPos(e) {
            var rect = canvas.getBoundingClientRect();
            var src = e.touches ? e.touches[0] : e;
            return { x: src.clientX - rect.left, y: src.clientY - rect.top };
        }

        canvas.addEventListener('mousedown', function(e) { drawing = true; ctx.beginPath(); var p = getPos(e); ctx.moveTo(p.x, p.y); });
        canvas.addEventListener('mousemove', function(e) { if (!drawing) return; var p = getPos(e); ctx.lineTo(p.x, p.y); ctx.strokeStyle='#1d2327'; ctx.lineWidth=2; ctx.lineCap='round'; ctx.stroke(); });
        canvas.addEventListener('mouseup',   function()  { drawing = false; });
        canvas.addEventListener('mouseleave',function()  { drawing = false; });
        canvas.addEventListener('touchstart', function(e) { e.preventDefault(); drawing = true; ctx.beginPath(); var p = getPos(e); ctx.moveTo(p.x, p.y); });
        canvas.addEventListener('touchmove',  function(e) { e.preventDefault(); if (!drawing) return; var p = getPos(e); ctx.lineTo(p.x, p.y); ctx.strokeStyle='#1d2327'; ctx.lineWidth=2; ctx.lineCap='round'; ctx.stroke(); });
        canvas.addEventListener('touchend',   function()  { drawing = false; });

        document.getElementById('rpl-sig-clear').addEventListener('click', function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            document.getElementById('rpl-sig-data').value = '';
        });
    }

    /* ── Days calculation ─────────────────────────────────────────────── */
    $('#rpl-from, #rpl-to').on('change', function () {
        var f = $('#rpl-from').val(), t = $('#rpl-to').val();
        if (f && t) {
            var days = Math.ceil((new Date(t) - new Date(f)) / 86400000) + 1;
            $('#rpl-days').val(days > 0 ? days + ' <?php echo esc_js( __( 'يوم', 'rsyi-hr' ) ); ?>' : '—');
        }
    });

    /* ── Submit ───────────────────────────────────────────────────────── */
    $('#rsyi-portal-leave-form').on('submit', function (e) {
        e.preventDefault();

        // Capture signature from canvas if used
        if (canvas) {
            var sigData = canvas.toDataURL('image/png');
            // Check if canvas has content
            var blank = document.createElement('canvas');
            blank.width = canvas.width; blank.height = canvas.height;
            if (sigData === blank.toDataURL('image/png')) {
                alert(rsyiHRPortal.i18n.sign_required || 'يجب وضع التوقيع الإلكتروني.');
                return;
            }
            $('#rpl-sig-data').val(sigData);
        }

        if (!confirm(rsyiHRPortal.i18n.confirm_submit || 'هل تريد رفع الطلب؟')) return;

        var fd = new FormData(this);
        fd.append('action', 'rsyi_hr_submit_leave');
        fd.append('nonce', rsyiHRPortal.nonce);

        $('#rpl-submit-btn').prop('disabled', true).text('جارٍ الإرسال...');

        $.ajax({
            url: rsyiHRPortal.ajaxUrl, type: 'POST', data: fd,
            processData: false, contentType: false,
            success: function (res) {
                $('#rpl-submit-btn').prop('disabled', false).text('رفع طلب الإجازة');
                var $msg = $('#rsyi-portal-leave-msg');
                if (res.success) {
                    $msg.html('<div class="rsyi-portal-notice-success">✓ ' + (res.data.message || 'تم رفع الطلب بنجاح.') + '</div>');
                    document.getElementById('rsyi-portal-leave-form').reset();
                    if (canvas) { ctx.clearRect(0, 0, canvas.width, canvas.height); }
                } else {
                    $msg.html('<div class="rsyi-portal-notice-error">✗ ' + (res.data && res.data.message ? res.data.message : 'حدث خطأ.') + '</div>');
                }
            }
        });
    });
}(jQuery));
</script>
