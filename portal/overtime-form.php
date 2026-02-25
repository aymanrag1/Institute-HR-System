<?php
/**
 * Portal Template — نموذج طلب العمل الإضافي
 *
 * يُعرض عبر شورت-كود: [rsyi_hr_overtime_form]
 * أو مضمَّن في employee-dashboard.php
 *
 * @package RSYI_HR
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="rsyi-portal" dir="rtl">
<div class="rsyi-portal-card">

    <?php if ( ! isset( $emp ) || ! $emp ) : ?>
        <div class="rsyi-portal-notice-error">
            <?php esc_html_e( 'لم يتم ربط حسابك بسجل موظف.', 'rsyi-hr' ); ?>
        </div>
    <?php else : ?>

    <form id="rsyi-portal-ot-form" class="rsyi-portal-form">

        <div class="rsyi-portal-form-cols-3">
            <div class="rsyi-portal-form-row">
                <label><?php esc_html_e( 'تاريخ العمل الإضافي *', 'rsyi-hr' ); ?></label>
                <input type="date" name="work_date" required>
            </div>
            <div class="rsyi-portal-form-row">
                <label><?php esc_html_e( 'من الساعة *', 'rsyi-hr' ); ?></label>
                <input type="time" name="from_time" id="rpt-from" required>
            </div>
            <div class="rsyi-portal-form-row">
                <label><?php esc_html_e( 'إلى الساعة *', 'rsyi-hr' ); ?></label>
                <input type="time" name="to_time" id="rpt-to" required>
            </div>
        </div>

        <div class="rsyi-portal-form-row">
            <label><?php esc_html_e( 'عدد الساعات (محسوب)', 'rsyi-hr' ); ?></label>
            <input type="text" id="rpt-hours" readonly class="rsyi-portal-readonly">
        </div>

        <div class="rsyi-portal-form-row">
            <label><?php esc_html_e( 'سبب العمل الإضافي *', 'rsyi-hr' ); ?></label>
            <textarea name="reason" rows="3" required></textarea>
        </div>

        <!-- التوقيع الإلكتروني -->
        <div class="rsyi-portal-form-section rsyi-portal-sig-section">
            <label class="rsyi-portal-label-block">
                <?php esc_html_e( 'التوقيع الإلكتروني *', 'rsyi-hr' ); ?>
            </label>
            <?php if ( ! empty( $emp['signature_url'] ) ) : ?>
                <img src="<?php echo esc_url( $emp['signature_url'] ); ?>"
                     style="max-height:70px;border:1px solid #ccc;padding:4px;border-radius:4px">
                <input type="hidden" name="employee_signature" value="<?php echo esc_attr( $emp['signature_url'] ); ?>">
            <?php else : ?>
                <canvas id="rpt-sig-canvas" width="500" height="120"
                        style="border:2px dashed #aaa;border-radius:6px;cursor:crosshair;max-width:100%;touch-action:none;background:#fff"></canvas>
                <button type="button" id="rpt-sig-clear" class="rsyi-portal-btn rsyi-portal-btn-sm" style="margin-top:6px">
                    <?php esc_html_e( 'مسح', 'rsyi-hr' ); ?>
                </button>
                <input type="hidden" name="employee_signature" id="rpt-sig-data">
            <?php endif; ?>
        </div>

        <div id="rsyi-portal-ot-msg" style="margin:10px 0"></div>

        <div class="rsyi-portal-form-actions">
            <button type="submit" class="rsyi-portal-btn rsyi-portal-btn-primary" id="rpt-submit-btn">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e( 'رفع طلب العمل الإضافي', 'rsyi-hr' ); ?>
            </button>
        </div>
    </form>

    <?php endif; ?>
</div>
</div>

<script>
(function ($) {
    var canvas = document.getElementById('rpt-sig-canvas');
    if (canvas) {
        var ctx = canvas.getContext('2d'), drawing = false;
        function getPos(e) { var r = canvas.getBoundingClientRect(), s = e.touches ? e.touches[0] : e; return {x: s.clientX - r.left, y: s.clientY - r.top}; }
        canvas.addEventListener('mousedown',  function(e){ drawing=true; ctx.beginPath(); var p=getPos(e); ctx.moveTo(p.x,p.y); });
        canvas.addEventListener('mousemove',  function(e){ if(!drawing)return; var p=getPos(e); ctx.lineTo(p.x,p.y); ctx.strokeStyle='#1d2327'; ctx.lineWidth=2; ctx.lineCap='round'; ctx.stroke(); });
        canvas.addEventListener('mouseup',    function(){ drawing=false; });
        canvas.addEventListener('mouseleave', function(){ drawing=false; });
        canvas.addEventListener('touchstart', function(e){ e.preventDefault(); drawing=true; ctx.beginPath(); var p=getPos(e); ctx.moveTo(p.x,p.y); });
        canvas.addEventListener('touchmove',  function(e){ e.preventDefault(); if(!drawing)return; var p=getPos(e); ctx.lineTo(p.x,p.y); ctx.strokeStyle='#1d2327'; ctx.lineWidth=2; ctx.lineCap='round'; ctx.stroke(); });
        canvas.addEventListener('touchend',   function(){ drawing=false; });
        document.getElementById('rpt-sig-clear').addEventListener('click', function(){ ctx.clearRect(0,0,canvas.width,canvas.height); document.getElementById('rpt-sig-data').value=''; });
    }

    $('#rpt-from, #rpt-to').on('change', function() {
        var f = $('#rpt-from').val(), t = $('#rpt-to').val();
        if (f && t) {
            var diff = (new Date('1970-01-01T'+t) - new Date('1970-01-01T'+f)) / 3600000;
            $('#rpt-hours').val(diff > 0 ? diff.toFixed(2) + ' ساعة' : '—');
        }
    });

    $('#rsyi-portal-ot-form').on('submit', function(e) {
        e.preventDefault();
        if (canvas) {
            var blank = document.createElement('canvas'); blank.width=canvas.width; blank.height=canvas.height;
            if (canvas.toDataURL() === blank.toDataURL()) { alert('يجب وضع التوقيع.'); return; }
            document.getElementById('rpt-sig-data').value = canvas.toDataURL('image/png');
        }
        if (!confirm(rsyiHRPortal.i18n.confirm_submit || 'هل تريد رفع الطلب؟')) return;
        var fd = new FormData(this);
        fd.append('action', 'rsyi_hr_submit_overtime');
        fd.append('nonce', rsyiHRPortal.nonce);
        $('#rpt-submit-btn').prop('disabled', true).text('جارٍ الإرسال...');
        $.ajax({ url: rsyiHRPortal.ajaxUrl, type:'POST', data:fd, processData:false, contentType:false,
            success: function(res) {
                $('#rpt-submit-btn').prop('disabled', false).text('رفع طلب العمل الإضافي');
                var msg = res.success ? '<div class="rsyi-portal-notice-success">✓ تم رفع الطلب بنجاح.</div>' : '<div class="rsyi-portal-notice-error">✗ '+(res.data&&res.data.message?res.data.message:'خطأ')+'</div>';
                $('#rsyi-portal-ot-msg').html(msg);
                if (res.success) { document.getElementById('rsyi-portal-ot-form').reset(); if(canvas) ctx.clearRect(0,0,canvas.width,canvas.height); }
            }
        });
    });
}(jQuery));
</script>
