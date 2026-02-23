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
</div>
