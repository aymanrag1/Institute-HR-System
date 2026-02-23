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
        <?php endif; ?>
    </h1>

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
