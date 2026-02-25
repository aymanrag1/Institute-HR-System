<?php
/**
 * Dashboard View
 *
 * @package RSYI_HR
 * @var int   $total_active
 * @var int   $total_inactive
 * @var int   $total_leave
 * @var array $departments
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap rsyi-hr-wrap">
    <h1><?php esc_html_e( 'لوحة تحكم الموارد البشرية', 'rsyi-hr' ); ?></h1>

    <div class="rsyi-hr-stats">
        <div class="rsyi-hr-stat-card">
            <span class="dashicons dashicons-businessman"></span>
            <div>
                <h3><?php echo esc_html( $total_active ); ?></h3>
                <p><?php esc_html_e( 'موظف نشط', 'rsyi-hr' ); ?></p>
            </div>
        </div>
        <div class="rsyi-hr-stat-card">
            <span class="dashicons dashicons-building"></span>
            <div>
                <h3><?php echo esc_html( count( $departments ) ); ?></h3>
                <p><?php esc_html_e( 'قسم', 'rsyi-hr' ); ?></p>
            </div>
        </div>
        <div class="rsyi-hr-stat-card">
            <span class="dashicons dashicons-clock"></span>
            <div>
                <h3><?php echo esc_html( $total_leave ); ?></h3>
                <p><?php esc_html_e( 'في إجازة', 'rsyi-hr' ); ?></p>
            </div>
        </div>
        <div class="rsyi-hr-stat-card rsyi-hr-stat-inactive">
            <span class="dashicons dashicons-no-alt"></span>
            <div>
                <h3><?php echo esc_html( $total_inactive ); ?></h3>
                <p><?php esc_html_e( 'غير نشط', 'rsyi-hr' ); ?></p>
            </div>
        </div>
    </div>

    <div class="rsyi-hr-quick-links">
        <h2><?php esc_html_e( 'روابط سريعة', 'rsyi-hr' ); ?></h2>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-hr-employees&action=new' ) ); ?>" class="button button-primary">
            <?php esc_html_e( '+ إضافة موظف', 'rsyi-hr' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-hr-departments&action=new' ) ); ?>" class="button">
            <?php esc_html_e( '+ إضافة قسم', 'rsyi-hr' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-hr-job-titles&action=new' ) ); ?>" class="button">
            <?php esc_html_e( '+ إضافة وظيفة', 'rsyi-hr' ); ?>
        </a>
    </div>

    <!-- ── إعداد حماية البيانات ────────────────────────────────────────── -->
    <?php if ( current_user_can( 'administrator' ) ) : ?>
    <div style="margin-top:30px;background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:16px 20px" dir="rtl">
        <h3 style="margin:0 0 10px;color:#856404;display:flex;align-items:center;gap:8px">
            <span class="dashicons dashicons-shield"></span>
            <?php esc_html_e( 'حماية البيانات عند حذف البلجن', 'rsyi-hr' ); ?>
        </h3>
        <p style="color:#856404;margin:0 0 12px">
            <?php esc_html_e( 'تحذير: إذا فعّلت هذا الخيار وقمت بحذف البلجن من WordPress — ستُحذف كل بيانات الموارد البشرية نهائياً. اتركه مُعطَّلاً للحفاظ على بياناتك.', 'rsyi-hr' ); ?>
        </p>
        <?php
        $delete_data = get_option( 'rsyi_hr_delete_data_on_uninstall', '0' );

        if ( isset( $_POST['rsyi_hr_save_uninstall_setting'] ) && check_admin_referer( 'rsyi_hr_dashboard' ) ) {
            $new_val = isset( $_POST['rsyi_hr_delete_data'] ) ? '1' : '0';
            update_option( 'rsyi_hr_delete_data_on_uninstall', $new_val );
            $delete_data = $new_val;
            echo '<div class="notice notice-success inline"><p>' . esc_html__( 'تم حفظ الإعداد.', 'rsyi-hr' ) . '</p></div>';
        }
        ?>
        <form method="post" style="display:inline-flex;align-items:center;gap:12px">
            <?php wp_nonce_field( 'rsyi_hr_dashboard' ); ?>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;color:#856404;font-weight:600">
                <input type="checkbox" name="rsyi_hr_delete_data" value="1"
                       <?php checked( $delete_data, '1' ); ?>>
                <?php esc_html_e( 'حذف كل البيانات عند إزالة البلجن', 'rsyi-hr' ); ?>
            </label>
            <button type="submit" name="rsyi_hr_save_uninstall_setting" class="button"
                    style="<?php echo '1' === $delete_data ? 'border-color:#cc1818;color:#cc1818' : ''; ?>">
                <?php esc_html_e( 'حفظ', 'rsyi-hr' ); ?>
            </button>
        </form>
        <p style="margin:10px 0 0;font-size:12px;color:#856404">
            <?php esc_html_e( 'الحالة الحالية:', 'rsyi-hr' ); ?>
            <strong style="color:<?php echo '1' === $delete_data ? '#cc1818' : '#00a32a'; ?>">
                <?php echo '1' === $delete_data
                    ? esc_html__( '⚠ الحذف مفعَّل — بياناتك في خطر عند الإزالة', 'rsyi-hr' )
                    : esc_html__( '✓ البيانات محمية — لن تُحذف عند إزالة البلجن', 'rsyi-hr' ); ?>
            </strong>
        </p>
    </div>
    <?php endif; ?>
</div>
