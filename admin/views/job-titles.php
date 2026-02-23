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
        <?php endif; ?>
    </h1>

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
