<?php
/**
 * Permissions Matrix View — صلاحيات المستخدمين
 *
 * @package RSYI_HR
 * @var WP_User[] $users
 */
defined( 'ABSPATH' ) || exit;

$modules = RSYI_HR\Permissions_Manager::get_modules();
$levels  = RSYI_HR\Permissions_Manager::get_levels();
?>
<div class="wrap rsyi-hr-wrap">
    <h1><?php esc_html_e( 'الصلاحيات / Permissions', 'rsyi-hr' ); ?></h1>

    <div class="rsyi-hr-perm-user-select" style="margin-bottom:20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <label style="font-weight:600;font-size:14px;"><?php esc_html_e( 'المستخدم / User:', 'rsyi-hr' ); ?></label>
        <select id="rsyi-hr-perm-user" style="min-width:260px;">
            <option value=""><?php esc_html_e( '— اختر مستخدماً —', 'rsyi-hr' ); ?></option>
            <?php foreach ( $users as $user ) : ?>
                <option value="<?php echo esc_attr( $user->ID ); ?>">
                    <?php echo esc_html( $user->display_name . ' (' . $user->user_login . ')' ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="button" id="rsyi-hr-perm-load">
            <?php esc_html_e( 'تحميل الصلاحيات', 'rsyi-hr' ); ?>
        </button>
    </div>

    <div id="rsyi-hr-perm-matrix" style="display:none;">
        <table class="wp-list-table widefat fixed striped" id="rsyi-hr-perm-table">
            <thead>
                <tr>
                    <th style="width:200px;"><?php esc_html_e( 'البند', 'rsyi-hr' ); ?></th>
                    <?php foreach ( $levels as $lv => $lname ) : ?>
                        <th style="text-align:center;"><?php echo esc_html( $lname ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $modules as $module => $module_label ) : ?>
                <tr>
                    <td style="font-weight:600;"><?php echo esc_html( $module_label ); ?></td>
                    <?php foreach ( $levels as $lv => $lname ) : ?>
                    <td style="text-align:center;">
                        <input type="radio"
                               name="perm[<?php echo esc_attr( $module ); ?>]"
                               value="<?php echo esc_attr( $lv ); ?>"
                               data-module="<?php echo esc_attr( $module ); ?>"
                               class="rsyi-hr-perm-radio">
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:16px;display:flex;gap:10px;">
            <button class="button button-primary button-large" id="rsyi-hr-perm-save">
                <?php esc_html_e( 'حفظ الصلاحيات', 'rsyi-hr' ); ?>
            </button>
        </div>
        <div id="rsyi-hr-perm-notice" style="margin-top:10px;font-size:13px;"></div>
    </div>
</div>
