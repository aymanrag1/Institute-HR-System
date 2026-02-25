<?php
/**
 * Job Titles View
 *
 * @package RSYI_HR
 * @var array $job_titles
 * @var array $departments
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap rsyi-hr-wrap">
    <h1>
        <?php esc_html_e( 'التقسيم الوظيفي', 'rsyi-hr' ); ?>
        <?php if ( current_user_can( 'rsyi_hr_manage_job_titles' ) ) : ?>
            <button class="page-title-action rsyi-hr-btn-add-jt">
                <?php esc_html_e( 'إضافة وظيفة', 'rsyi-hr' ); ?>
            </button>
            <button class="page-title-action" id="rsyi-hr-btn-import-jt" style="margin-right:6px">
                <span class="dashicons dashicons-upload" style="vertical-align:middle;margin-top:-2px"></span>
                <?php esc_html_e( 'استيراد CSV', 'rsyi-hr' ); ?>
            </button>
            <a class="page-title-action" style="margin-right:6px"
               href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=rsyi_hr_jt_template' ), 'rsyi_hr_jt_template' ) ); ?>">
                <span class="dashicons dashicons-download" style="vertical-align:middle;margin-top:-2px"></span>
                <?php esc_html_e( 'تحميل النموذج', 'rsyi-hr' ); ?>
            </a>
        <?php endif; ?>
    </h1>

    <!-- ── Import Notice ───────────────────────────────────────────────── -->
    <div id="rsyi-hr-jt-import-notice" style="display:none;margin:10px 0"></div>

    <!-- ── Import Panel ────────────────────────────────────────────────── -->
    <?php if ( current_user_can( 'rsyi_hr_manage_job_titles' ) ) : ?>
    <div id="rsyi-hr-jt-import-panel" style="display:none;background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:16px 20px;margin-bottom:18px">
        <h3 style="margin:0 0 12px;display:flex;align-items:center;gap:8px">
            <span class="dashicons dashicons-media-spreadsheet"></span>
            <?php esc_html_e( 'استيراد الوظائف من ملف CSV', 'rsyi-hr' ); ?>
        </h3>
        <p style="margin:0 0 12px;color:#50575e;font-size:13px">
            <?php esc_html_e( 'ارفع ملف CSV لإضافة أو تحديث الوظائف دفعةً واحدة. إذا كان المسمى الوظيفي موجوداً سيُحدَّث، وإلا سيُضاف جديداً.', 'rsyi-hr' ); ?><br>
            <?php esc_html_e( 'استخدم زر "تحميل النموذج" للحصول على ملف CSV جاهز بالأعمدة الصحيحة.', 'rsyi-hr' ); ?>
        </p>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <input type="file" id="rsyi-hr-jt-csv-file" accept=".csv,text/csv" style="border:1px solid #8c8f94;border-radius:4px;padding:5px 8px">
            <button class="button button-primary" id="rsyi-hr-do-import-jt">
                <span class="dashicons dashicons-upload" style="vertical-align:middle;margin-top:-2px"></span>
                <?php esc_html_e( 'رفع واستيراد', 'rsyi-hr' ); ?>
            </button>
            <button class="button" id="rsyi-hr-cancel-import-jt">
                <?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?>
            </button>
            <span id="rsyi-hr-jt-import-spinner" class="spinner" style="float:none;margin:0"></span>
        </div>
    </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped rsyi-hr-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'الكود', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'المسمى الوظيفي', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'القسم', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الدرجة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $job_titles ) ) : ?>
                <tr>
                    <td colspan="6"><?php esc_html_e( 'لا توجد وظائف بعد.', 'rsyi-hr' ); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $job_titles as $jt ) : ?>
                <tr data-id="<?php echo esc_attr( $jt['id'] ); ?>">
                    <td><?php echo esc_html( $jt['code'] ?? '—' ); ?></td>
                    <td><strong><?php echo esc_html( $jt['title'] ); ?></strong></td>
                    <td><?php echo esc_html( $jt['department_name'] ?? '—' ); ?></td>
                    <td><?php echo esc_html( $jt['grade'] ?? '—' ); ?></td>
                    <td>
                        <span class="rsyi-hr-badge rsyi-hr-badge-<?php echo esc_attr( $jt['status'] ); ?>">
                            <?php echo 'active' === $jt['status']
                                ? esc_html__( 'نشط', 'rsyi-hr' )
                                : esc_html__( 'غير نشط', 'rsyi-hr' ); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ( current_user_can( 'rsyi_hr_manage_job_titles' ) ) : ?>
                            <button class="button button-small rsyi-hr-edit-jt"
                                    data-id="<?php echo esc_attr( $jt['id'] ); ?>">
                                <?php esc_html_e( 'تعديل', 'rsyi-hr' ); ?>
                            </button>
                            <button class="button button-small rsyi-hr-delete-jt"
                                    data-id="<?php echo esc_attr( $jt['id'] ); ?>">
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
    $('#rsyi-hr-btn-import-jt').on('click', function(){
        $('#rsyi-hr-jt-import-panel').slideToggle(200);
    });
    $('#rsyi-hr-cancel-import-jt').on('click', function(){
        $('#rsyi-hr-jt-import-panel').slideUp(200);
        $('#rsyi-hr-jt-csv-file').val('');
        $('#rsyi-hr-jt-import-notice').hide();
    });
    $('#rsyi-hr-do-import-jt').on('click', function(){
        var fi = document.getElementById('rsyi-hr-jt-csv-file');
        if ( ! fi || ! fi.files.length ) {
            $('#rsyi-hr-jt-import-notice')
                .html('<div class="notice notice-warning inline"><p><?php echo esc_js( __( 'الرجاء اختيار ملف CSV أولاً.', 'rsyi-hr' ) ); ?></p></div>').show();
            return;
        }
        var fd = new FormData();
        fd.append('action',   'rsyi_hr_import_job_titles');
        fd.append('nonce',    rsyiHR.nonce);
        fd.append('csv_file', fi.files[0]);

        $('#rsyi-hr-jt-import-spinner').addClass('is-active');
        $('#rsyi-hr-do-import-jt').prop('disabled', true);
        $('#rsyi-hr-jt-import-notice').hide();

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
                $('#rsyi-hr-jt-import-notice').html(html).show();
                if ( res.success ) { location.reload(); }
            },
            error: function(){
                $('#rsyi-hr-jt-import-notice')
                    .html('<div class="notice notice-error inline"><p><?php echo esc_js( __( 'حدث خطأ أثناء الرفع، حاول مجدداً.', 'rsyi-hr' ) ); ?></p></div>').show();
            },
            complete: function(){
                $('#rsyi-hr-jt-import-spinner').removeClass('is-active');
                $('#rsyi-hr-do-import-jt').prop('disabled', false);
            }
        });
    });
})(jQuery);
</script>

<!-- ── Modal ──────────────────────────────────────────────────────────────── -->
<div id="rsyi-hr-jt-modal" class="rsyi-hr-modal" style="display:none;">
    <div class="rsyi-hr-modal-content">
        <button class="rsyi-hr-modal-close">&times;</button>
        <h2 id="rsyi-hr-jt-modal-title"><?php esc_html_e( 'إضافة وظيفة', 'rsyi-hr' ); ?></h2>

        <form id="rsyi-hr-jt-form">
            <input type="hidden" name="id" id="jt-id" value="">

            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'المسمى الوظيفي *', 'rsyi-hr' ); ?></label>
                <input type="text" name="title" id="jt-title" required>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الكود', 'rsyi-hr' ); ?></label>
                <input type="text" name="code" id="jt-code">
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'القسم (اختياري)', 'rsyi-hr' ); ?></label>
                <select name="department_id" id="jt-department">
                    <option value=""><?php esc_html_e( '— عام لكل الأقسام —', 'rsyi-hr' ); ?></option>
                    <?php foreach ( $departments as $d ) : ?>
                        <option value="<?php echo esc_attr( $d['id'] ); ?>">
                            <?php echo esc_html( $d['name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الدرجة الوظيفية', 'rsyi-hr' ); ?></label>
                <input type="text" name="grade" id="jt-grade"
                       placeholder="<?php esc_attr_e( 'مثال: أ، ب، 1، 2...', 'rsyi-hr' ); ?>">
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الوصف', 'rsyi-hr' ); ?></label>
                <textarea name="description" id="jt-description" rows="3"></textarea>
            </div>
            <div class="rsyi-hr-form-row">
                <label><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></label>
                <select name="status" id="jt-status">
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
