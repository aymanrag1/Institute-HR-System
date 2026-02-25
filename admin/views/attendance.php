<?php
/**
 * Admin View — الحضور والانصراف
 *
 * @package RSYI_HR
 */
defined( 'ABSPATH' ) || exit;

$att_types = [
    'present'  => __( 'حاضر',      'rsyi-hr' ),
    'absent'   => __( 'غائب',      'rsyi-hr' ),
    'late'     => __( 'متأخر',     'rsyi-hr' ),
    'half_day' => __( 'نصف يوم',   'rsyi-hr' ),
    'holiday'  => __( 'إجازة رسمية','rsyi-hr' ),
    'vacation' => __( 'إجازة',     'rsyi-hr' ),
];
$download_nonce = wp_create_nonce( 'rsyi_hr_admin' );
?>
<div class="wrap rsyi-hr-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-calendar"></span>
        <?php esc_html_e( 'الحضور والانصراف', 'rsyi-hr' ); ?>
    </h1>

    <!-- Filters + Actions -->
    <div class="rsyi-hr-filters" style="flex-wrap:wrap;gap:10px">
        <select id="rsyi-hr-att-emp">
            <option value=""><?php esc_html_e( '— كل الموظفين —', 'rsyi-hr' ); ?></option>
            <?php foreach ( $employees as $e ) : ?>
                <option value="<?php echo esc_attr( $e['id'] ); ?>">
                    <?php echo esc_html( $e['full_name_ar'] ?: $e['full_name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" id="rsyi-hr-att-from" placeholder="<?php esc_attr_e( 'من تاريخ', 'rsyi-hr' ); ?>">
        <input type="date" id="rsyi-hr-att-to"   placeholder="<?php esc_attr_e( 'إلى تاريخ', 'rsyi-hr' ); ?>">
        <select id="rsyi-hr-att-type">
            <option value=""><?php esc_html_e( '— كل الأنواع —', 'rsyi-hr' ); ?></option>
            <?php foreach ( $att_types as $key => $lbl ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $lbl ); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button button-primary rsyi-hr-btn-add-att">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e( 'تسجيل حضور', 'rsyi-hr' ); ?>
        </button>
        <button class="button rsyi-hr-btn-import-att">
            <span class="dashicons dashicons-upload"></span>
            <?php esc_html_e( 'استيراد Excel', 'rsyi-hr' ); ?>
        </button>
        <a class="button" href="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>?action=rsyi_hr_download_att_template&nonce=<?php echo esc_attr( $download_nonce ); ?>">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e( 'تنزيل نموذج Excel', 'rsyi-hr' ); ?>
        </a>
    </div>

    <!-- Import form (hidden) -->
    <div id="rsyi-hr-att-import-box" style="display:none;background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;border-radius:6px" dir="rtl">
        <h3 style="margin-top:0"><?php esc_html_e( 'استيراد ملف CSV للحضور', 'rsyi-hr' ); ?></h3>
        <p><?php esc_html_e( 'قم بتنزيل النموذج أولاً، ثم رفع الملف المكتمل.', 'rsyi-hr' ); ?></p>
        <form id="rsyi-hr-att-import-form" enctype="multipart/form-data">
            <input type="file" name="att_file" accept=".csv" required>
            <button type="submit" class="button button-primary" style="margin-right:10px">
                <?php esc_html_e( 'استيراد', 'rsyi-hr' ); ?>
            </button>
            <button type="button" class="button" id="rsyi-hr-att-cancel-import">
                <?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?>
            </button>
        </form>
        <div id="rsyi-hr-att-import-result"></div>
    </div>

    <table class="wp-list-table widefat fixed striped rsyi-hr-table">
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th><?php esc_html_e( 'الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'رقم الموظف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'التاريخ', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحضور', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الانصراف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'النوع', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'ملاحظات', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-att-body">
            <tr><td colspan="9" class="rsyi-hr-loading"><?php esc_html_e( 'جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- Modal: تسجيل حضور -->
<div id="rsyi-hr-att-modal" class="rsyi-hr-modal" style="display:none">
    <div class="rsyi-hr-modal-content" dir="rtl">
        <button class="rsyi-hr-modal-close" type="button">&#x2715;</button>
        <h2 id="rsyi-hr-att-modal-title"><?php esc_html_e( 'تسجيل حضور / انصراف', 'rsyi-hr' ); ?></h2>
        <form id="rsyi-hr-att-form">
            <input type="hidden" name="id" id="att-id">
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
                    <label><?php esc_html_e( 'التاريخ *', 'rsyi-hr' ); ?></label>
                    <input type="date" name="att_date" required value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'نوع الحضور *', 'rsyi-hr' ); ?></label>
                    <select name="att_type" required>
                        <?php foreach ( $att_types as $key => $lbl ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $lbl ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'وقت الحضور', 'rsyi-hr' ); ?></label>
                    <input type="time" name="check_in">
                </div>
                <div class="rsyi-hr-form-row">
                    <label><?php esc_html_e( 'وقت الانصراف', 'rsyi-hr' ); ?></label>
                    <input type="time" name="check_out">
                </div>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'ملاحظات', 'rsyi-hr' ); ?></label>
                <textarea name="notes" rows="2"></textarea>
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
    var attTypes = <?php echo wp_json_encode( $att_types ); ?>;

    function loadAtt() {
        var $tbody = $('#rsyi-hr-att-body');
        $tbody.html('<tr><td colspan="9" class="rsyi-hr-loading">جارٍ التحميل…</td></tr>');
        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_get_attendance', nonce: rsyiHR.nonce,
            employee_id: $('#rsyi-hr-att-emp').val(),
            date_from: $('#rsyi-hr-att-from').val(),
            date_to: $('#rsyi-hr-att-to').val(),
            att_type: $('#rsyi-hr-att-type').val()
        }, function (res) {
            if (!res.success || !res.data.length) { $tbody.html('<tr><td colspan="9">لا توجد سجلات.</td></tr>'); return; }
            var html = res.data.map(function (r, i) {
                var empName = r.employee_name_ar || r.employee_name || '—';
                var typeClass = r.att_type === 'present' ? 'rsyi-hr-badge-active' : r.att_type === 'absent' ? 'rsyi-hr-badge-inactive' : 'rsyi-hr-badge-pending';
                return '<tr>' +
                    '<td>'+(i+1)+'</td><td>'+empName+'</td><td>'+(r.employee_number||'—')+'</td>' +
                    '<td>'+r.att_date+'</td><td>'+(r.check_in||'—')+'</td><td>'+(r.check_out||'—')+'</td>' +
                    '<td><span class="rsyi-hr-badge '+typeClass+'">'+(attTypes[r.att_type]||r.att_type)+'</span></td>' +
                    '<td>'+(r.notes||'—')+'</td>' +
                    '<td><button class="button button-small rsyi-hr-delete-att" data-id="'+r.id+'">حذف</button></td>' +
                    '</tr>';
            }).join('');
            $tbody.html(html);
        });
    }

    $(document).on('click', '.rsyi-hr-btn-add-att', function () {
        $('#rsyi-hr-att-form')[0].reset();
        $('[name="att_date"]', '#rsyi-hr-att-form').val(new Date().toISOString().slice(0,10));
        $('#rsyi-hr-att-modal').css({display:'flex', opacity:0}).animate({opacity:1}, 150);
    });

    $(document).on('click', '.rsyi-hr-btn-import-att', function () {
        $('#rsyi-hr-att-import-box').toggle();
    });

    $(document).on('click', '#rsyi-hr-att-cancel-import', function () {
        $('#rsyi-hr-att-import-box').hide();
    });

    $(document).on('submit', '#rsyi-hr-att-import-form', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'rsyi_hr_import_attendance');
        formData.append('nonce', rsyiHR.nonce);
        $.ajax({ url: rsyiHR.ajaxUrl, type: 'POST', data: formData, processData: false, contentType: false,
            success: function (res) {
                var msg = res.success
                    ? '✓ تم الاستيراد: ' + res.data.inserted + ' سجل' + (res.data.errors.length ? ' | أخطاء: ' + res.data.errors.join(', ') : '')
                    : '✗ ' + (res.data && res.data.message ? res.data.message : 'خطأ');
                $('#rsyi-hr-att-import-result').html('<p style="color:'+(res.success?'green':'red')+'">' + msg + '</p>');
                if (res.success) loadAtt();
            }
        });
    });

    $(document).on('submit', '#rsyi-hr-att-form', function (e) {
        e.preventDefault();
        var data = { action: 'rsyi_hr_save_attendance', nonce: rsyiHR.nonce };
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        $.post(rsyiHR.ajaxUrl, data, function (res) {
            if (!res.success) { alert(rsyiHR.i18n.error); return; }
            $('#rsyi-hr-att-modal').animate({opacity:0}, 150, function() { $(this).css('display','none'); });
            loadAtt();
        });
    });

    $(document).on('click', '.rsyi-hr-delete-att', function () {
        if (!confirm(rsyiHR.i18n.confirm_delete)) return;
        $.post(rsyiHR.ajaxUrl, { action: 'rsyi_hr_delete_attendance', nonce: rsyiHR.nonce, id: $(this).data('id') }, function () { loadAtt(); });
    });

    $(document).on('change', '#rsyi-hr-att-emp, #rsyi-hr-att-from, #rsyi-hr-att-to, #rsyi-hr-att-type', loadAtt);
    $(function () { loadAtt(); });
}(jQuery));
</script>
