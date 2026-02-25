<?php
/**
 * Departments View
 *
 * @package RSYI_HR
 * @var array $departments
 * @var array $employees
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap rsyi-hr-wrap">
    <h1>
        <?php esc_html_e( 'الأقسام', 'rsyi-hr' ); ?>
        <?php if ( current_user_can( 'rsyi_hr_manage_departments' ) ) : ?>
            <button class="page-title-action rsyi-hr-btn-add-dept">
                <?php esc_html_e( 'إضافة قسم', 'rsyi-hr' ); ?>
            </button>
            <button class="page-title-action" id="rsyi-hr-btn-import-dept" style="margin-right:6px">
                <span class="dashicons dashicons-upload" style="vertical-align:middle;margin-top:-2px"></span>
                <?php esc_html_e( 'استيراد CSV', 'rsyi-hr' ); ?>
            </button>
            <a class="page-title-action" style="margin-right:6px"
               href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=rsyi_hr_dept_template' ), 'rsyi_hr_dept_template' ) ); ?>">
                <span class="dashicons dashicons-download" style="vertical-align:middle;margin-top:-2px"></span>
                <?php esc_html_e( 'تحميل النموذج', 'rsyi-hr' ); ?>
            </a>
        <?php endif; ?>
    </h1>

    <!-- ── Import Notice ───────────────────────────────────────────────── -->
    <div id="rsyi-hr-dept-import-notice" style="display:none;margin:10px 0"></div>

    <!-- ── Import Panel ────────────────────────────────────────────────── -->
    <?php if ( current_user_can( 'rsyi_hr_manage_departments' ) ) : ?>
    <div id="rsyi-hr-dept-import-panel" style="display:none;background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:16px 20px;margin-bottom:18px">
        <h3 style="margin:0 0 12px;display:flex;align-items:center;gap:8px">
            <span class="dashicons dashicons-media-spreadsheet"></span>
            <?php esc_html_e( 'استيراد الأقسام من ملف CSV', 'rsyi-hr' ); ?>
        </h3>
        <p style="margin:0 0 12px;color:#50575e;font-size:13px">
            <?php esc_html_e( 'ارفع ملف CSV لإضافة أو تحديث الأقسام دفعةً واحدة. إذا كان اسم القسم موجوداً سيُحدَّث، وإلا سيُضاف جديداً.', 'rsyi-hr' ); ?><br>
            <?php esc_html_e( 'استخدم زر "تحميل النموذج" للحصول على ملف CSV جاهز بالأعمدة الصحيحة.', 'rsyi-hr' ); ?>
        </p>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <input type="file" id="rsyi-hr-dept-csv-file" accept=".csv,text/csv" style="border:1px solid #8c8f94;border-radius:4px;padding:5px 8px">
            <button class="button button-primary" id="rsyi-hr-do-import-dept">
                <span class="dashicons dashicons-upload" style="vertical-align:middle;margin-top:-2px"></span>
                <?php esc_html_e( 'رفع واستيراد', 'rsyi-hr' ); ?>
            </button>
            <button class="button" id="rsyi-hr-cancel-import-dept">
                <?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?>
            </button>
            <span id="rsyi-hr-dept-import-spinner" class="spinner" style="float:none;margin:0"></span>
        </div>
    </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped rsyi-hr-table" id="rsyi-hr-departments-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'الكود', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'اسم القسم', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'القسم الأعلى', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'المدير المسؤول', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $departments ) ) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e( 'لا توجد أقسام بعد.', 'rsyi-hr' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $departments as $dept ) :
                    $parent = $dept['parent_id']
                        ? RSYI_HR\Departments::get_by_id( (int) $dept['parent_id'] )
                        : null;
                ?>
                <tr data-id="<?php echo esc_attr( $dept['id'] ); ?>">
                    <td><?php echo esc_html( $dept['code'] ?? '—' ); ?></td>
                    <td><strong><?php echo esc_html( $dept['name'] ); ?></strong></td>
                    <td><?php echo $parent ? esc_html( $parent['name'] ) : '—'; ?></td>
                    <td><?php echo esc_html( $dept['manager_name'] ?? '—' ); ?></td>
                    <td>
                        <span class="rsyi-hr-badge rsyi-hr-badge-<?php echo esc_attr( $dept['status'] ); ?>">
                            <?php echo 'active' === $dept['status']
                                ? esc_html__( 'نشط', 'rsyi-hr' )
                                : esc_html__( 'غير نشط', 'rsyi-hr' ); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ( current_user_can( 'rsyi_hr_manage_departments' ) ) : ?>
                            <button class="button button-small rsyi-hr-edit-dept"
                                    data-id="<?php echo esc_attr( $dept['id'] ); ?>">
                                <?php esc_html_e( 'تعديل', 'rsyi-hr' ); ?>
                            </button>
                            <button class="button button-small rsyi-hr-delete-dept"
                                    data-id="<?php echo esc_attr( $dept['id'] ); ?>">
                                <?php esc_html_e( 'حذف', 'rsyi-hr' ); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
(function($){
    $('#rsyi-hr-btn-import-dept').on('click', function(){
        $('#rsyi-hr-dept-import-panel').slideToggle(200);
    });
    $('#rsyi-hr-cancel-import-dept').on('click', function(){
        $('#rsyi-hr-dept-import-panel').slideUp(200);
        $('#rsyi-hr-dept-csv-file').val('');
        $('#rsyi-hr-dept-import-notice').hide();
    });
    $('#rsyi-hr-do-import-dept').on('click', function(){
        var fi = document.getElementById('rsyi-hr-dept-csv-file');
        if ( ! fi || ! fi.files.length ) {
            $('#rsyi-hr-dept-import-notice')
                .html('<div class="notice notice-warning inline"><p><?php echo esc_js( __( 'الرجاء اختيار ملف CSV أولاً.', 'rsyi-hr' ) ); ?></p></div>').show();
            return;
        }
        var fd = new FormData();
        fd.append('action',   'rsyi_hr_import_departments');
        fd.append('nonce',    rsyiHR.nonce);
        fd.append('csv_file', fi.files[0]);

        $('#rsyi-hr-dept-import-spinner').addClass('is-active');
        $('#rsyi-hr-do-import-dept').prop('disabled', true);
        $('#rsyi-hr-dept-import-notice').hide();

        $.ajax({
            url: ajaxurl, method: 'POST', data: fd, processData: false, contentType: false,
            success: function(res){
                var cls  = res.success ? 'notice-success' : 'notice-error';
                var html = '<div class="notice ' + cls + ' inline"><p>' + (res.data.message || '') + '</p>';
                if ( res.success && res.data.errors && res.data.errors.length ) {
                    html += '<ul style="margin:6px 0 0 18px;list-style:disc">';
                    $.each(res.data.errors, function(_,e){ html += '<li>'+e+'</li>'; });
                    html += '</ul>';
                }
                html += '</div>';
                $('#rsyi-hr-dept-import-notice').html(html).show();
                if ( res.success ) { location.reload(); }
            },
            error: function(){
                $('#rsyi-hr-dept-import-notice')
                    .html('<div class="notice notice-error inline"><p><?php echo esc_js( __( 'حدث خطأ أثناء الرفع، حاول مجدداً.', 'rsyi-hr' ) ); ?></p></div>').show();
            },
            complete: function(){
                $('#rsyi-hr-dept-import-spinner').removeClass('is-active');
                $('#rsyi-hr-do-import-dept').prop('disabled', false);
            }
        });
    });
})(jQuery);
</script>

<!-- ── Modal ──────────────────────────────────────────────────────────────── -->
<div id="rsyi-hr-dept-modal" class="rsyi-hr-modal" style="display:none;">
    <div class="rsyi-hr-modal-content">
        <button class="rsyi-hr-modal-close">&times;</button>
        <h2 id="rsyi-hr-dept-modal-title"><?php esc_html_e( 'إضافة قسم', 'rsyi-hr' ); ?></h2>

        <form id="rsyi-hr-dept-form">
            <input type="hidden" name="id" id="dept-id" value="">

            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'اسم القسم *', 'rsyi-hr' ); ?></label>
                <input type="text" name="name" id="dept-name" required>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الكود', 'rsyi-hr' ); ?></label>
                <input type="text" name="code" id="dept-code">
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'القسم الأعلى (اختياري)', 'rsyi-hr' ); ?></label>
                <select name="parent_id" id="dept-parent">
                    <option value=""><?php esc_html_e( '— لا يوجد —', 'rsyi-hr' ); ?></option>
                    <?php foreach ( $departments as $d ) : ?>
                        <option value="<?php echo esc_attr( $d['id'] ); ?>">
                            <?php echo esc_html( $d['name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'المدير المسؤول', 'rsyi-hr' ); ?></label>
                <select name="manager_id" id="dept-manager">
                    <option value=""><?php esc_html_e( '— اختر موظفاً —', 'rsyi-hr' ); ?></option>
                    <?php foreach ( $employees as $emp ) : ?>
                        <option value="<?php echo esc_attr( $emp['id'] ); ?>">
                            <?php echo esc_html( $emp['full_name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الوصف', 'rsyi-hr' ); ?></label>
                <textarea name="description" id="dept-description" rows="3"></textarea>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></label>
                <select name="status" id="dept-status">
                    <option value="active"><?php esc_html_e( 'نشط', 'rsyi-hr' ); ?></option>
                    <option value="inactive"><?php esc_html_e( 'غير نشط', 'rsyi-hr' ); ?></option>
                </select>
            </div>

            <div class="rsyi-hr-form-actions">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e( 'حفظ', 'rsyi-hr' ); ?>
                </button>
                <button type="button" class="button rsyi-hr-modal-close">
                    <?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?>
                </button>
            </div>
        </form>
    </div>
</div>
