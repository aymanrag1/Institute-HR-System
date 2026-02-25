<?php
/**
 * Admin view — بوابة الموظف
 *
 * المتغيرات المُمرَّرة من Admin_Menu::page_portal():
 *   $page_id  int     — معرّف صفحة WordPress للبوابة (0 إذا لم تُنشأ)
 *   $page_url string  — رابط الصفحة (فارغ إذا لم تُنشأ)
 *
 * @package RSYI_HR
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap" dir="rtl" lang="ar">
    <h1><?php esc_html_e( 'بوابة الموظف الإلكترونية', 'rsyi-hr' ); ?></h1>

    <!-- رابط البوابة -->
    <div class="rsyi-hr-card" style="margin-top:20px;">
        <h2 style="margin-top:0;"><?php esc_html_e( 'رابط بوابة الموظف', 'rsyi-hr' ); ?></h2>

        <?php if ( $page_url ) : ?>
            <p><?php esc_html_e( 'صفحة البوابة جاهزة. يمكن للموظفين الوصول إليها من الرابط التالي:', 'rsyi-hr' ); ?></p>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <a href="<?php echo esc_url( $page_url ); ?>"
                   target="_blank"
                   class="button button-primary">
                    🔗 <?php echo esc_html( $page_url ); ?>
                </a>
                <?php if ( $page_id ) : ?>
                    <a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>"
                       class="button">
                        ✏️ <?php esc_html_e( 'تعديل الصفحة', 'rsyi-hr' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <p style="color:#b91c1c;">
                <?php esc_html_e( 'لم يتم إنشاء صفحة البوابة بعد. اضغط على الزر أدناه لإنشائها تلقائياً.', 'rsyi-hr' ); ?>
            </p>
            <button id="rsyi-create-portal-btn" class="button button-primary">
                <?php esc_html_e( 'إنشاء صفحة البوابة', 'rsyi-hr' ); ?>
            </button>
            <span id="rsyi-portal-create-msg" style="margin-right:10px;display:none;"></span>
        <?php endif; ?>
    </div>

    <!-- كيفية الاستخدام اليدوي -->
    <div class="rsyi-hr-card" style="margin-top:20px;">
        <h2 style="margin-top:0;"><?php esc_html_e( 'إضافة البوابة يدوياً', 'rsyi-hr' ); ?></h2>
        <p><?php esc_html_e( 'يمكنك إضافة الكود التالي في أي صفحة WordPress لعرض بوابة الموظف:', 'rsyi-hr' ); ?></p>
        <code style="font-size:16px;padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;display:inline-block;">
            [rsyi_hr_portal]
        </code>
    </div>

    <!-- ميزات البوابة -->
    <div class="rsyi-hr-card" style="margin-top:20px;">
        <h2 style="margin-top:0;"><?php esc_html_e( 'ميزات البوابة', 'rsyi-hr' ); ?></h2>
        <ul style="list-style:disc;margin-right:20px;line-height:2;">
            <li><?php esc_html_e( 'الملف الشخصي — رفع الصورة الشخصية والتوقيع الإلكتروني', 'rsyi-hr' ); ?></li>
            <li><?php esc_html_e( 'طلب إجازة — مع سير الموافقة الكاملة (مدير ← موارد بشرية ← عميد)', 'rsyi-hr' ); ?></li>
            <li><?php esc_html_e( 'طلب وقت إضافي — مع حساب الساعات تلقائياً', 'rsyi-hr' ); ?></li>
            <li><?php esc_html_e( 'عرض سجل الإجازات والوقت الإضافي', 'rsyi-hr' ); ?></li>
            <li><?php esc_html_e( 'عرض المخالفات والجزاءات', 'rsyi-hr' ); ?></li>
        </ul>
    </div>
</div>

<script>
(function($){
    $('#rsyi-create-portal-btn').on('click', function(){
        var $btn = $(this);
        var $msg = $('#rsyi-portal-create-msg');
        $btn.prop('disabled', true).text('<?php esc_html_e( 'جارٍ الإنشاء...', 'rsyi-hr' ); ?>');
        $msg.hide();

        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_create_portal_page',
            nonce:  rsyiHR.nonce
        }, function(res){
            if ( res.success && res.data.url ) {
                $msg.css('color','#16a34a')
                    .html('✅ <?php esc_html_e( 'تم الإنشاء بنجاح! ', 'rsyi-hr' ); ?> <a href="' + res.data.url + '" target="_blank">' + res.data.url + '</a>')
                    .show();
                // Reload page after 2 seconds to show the link section
                setTimeout(function(){ location.reload(); }, 2000);
            } else {
                $msg.css('color','#b91c1c')
                    .text(res.data ? res.data.message : '<?php esc_html_e( 'حدث خطأ.', 'rsyi-hr' ); ?>')
                    .show();
                $btn.prop('disabled', false).text('<?php esc_html_e( 'إنشاء صفحة البوابة', 'rsyi-hr' ); ?>');
            }
        });
    });
})(jQuery);
</script>
