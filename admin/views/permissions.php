<?php
/**
 * Permissions Matrix View
 *
 * يعرض مصفوفة الصلاحيات لكل الأدوار (HR + plugins خارجية).
 *
 * المتغيرات المتاحة (من Admin_Menu::page_permissions):
 *   $hr_caps        array<cap_key, label>
 *   $definitions    array<role_slug, array{label, caps}>
 *   $extension_caps array<plugin_id, array{label, caps_by_role}>
 *
 * @package RSYI_HR
 */

defined( 'ABSPATH' ) || exit;

$role_slugs  = array_keys( $definitions );
$role_labels = array_column( $definitions, 'label' );
?>
<div class="wrap rsyi-permissions-wrap" dir="rtl">
    <h1><?php esc_html_e( 'مصفوفة الصلاحيات', 'rsyi-hr' ); ?></h1>
    <p class="description">
        <?php esc_html_e( 'نظرة عامة على الصلاحيات الممنوحة لكل دور. الصلاحيات المضافة من plugins خارجية تظهر في أقسام منفصلة.', 'rsyi-hr' ); ?>
    </p>

    <?php
    // ── دالة مساعدة لرسم جدول مجموعة من الصلاحيات ──────────────────────
    $render_caps_table = function ( string $group_label, array $caps_map ) use ( $role_slugs, $definitions ): void {
        // $caps_map: ['cap_key' => 'label'] أو ['cap_key' => ['role_slug' => bool]]
        // نحوّل للشكل الموحَّد: cap_key => label, والقيمة الفعلية من get_role()
        ?>
        <h2><?php echo esc_html( $group_label ); ?></h2>
        <table class="widefat fixed rsyi-perm-table" cellspacing="0">
            <thead>
                <tr>
                    <th class="rsyi-cap-col"><?php esc_html_e( 'الصلاحية', 'rsyi-hr' ); ?></th>
                    <?php foreach ( $role_slugs as $i => $slug ) : ?>
                        <th class="rsyi-role-col"><?php echo esc_html( $definitions[ $slug ]['label'] ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $caps_map as $cap_key => $cap_label ) : ?>
                    <tr>
                        <td class="rsyi-cap-key">
                            <strong><?php echo esc_html( is_string( $cap_label ) ? $cap_label : $cap_key ); ?></strong>
                            <code><?php echo esc_html( $cap_key ); ?></code>
                        </td>
                        <?php foreach ( $role_slugs as $slug ) :
                            $role      = get_role( $slug );
                            $has_cap   = $role && ! empty( $role->capabilities[ $cap_key ] );
                        ?>
                            <td class="rsyi-perm-cell <?php echo $has_cap ? 'has-cap' : 'no-cap'; ?>">
                                <?php if ( $has_cap ) : ?>
                                    <span class="dashicons dashicons-yes-alt" title="<?php esc_attr_e( 'ممنوحة', 'rsyi-hr' ); ?>"></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-minus" title="<?php esc_attr_e( 'غير ممنوحة', 'rsyi-hr' ); ?>"></span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    };

    // ── ١. صلاحيات HR الأساسية ───────────────────────────────────────────
    $render_caps_table( __( 'الموارد البشرية (HR)', 'rsyi-hr' ), $hr_caps );

    // ── ٢. صلاحيات الـ plugins الخارجية ────────────────────────────────
    if ( ! empty( $extension_caps ) ) :
        foreach ( $extension_caps as $plugin_id => $ext ) :
            // نجمع كل cap_keys من هذه الـ plugin
            $all_ext_caps = [];
            foreach ( $ext['caps_by_role'] as $caps ) {
                foreach ( array_keys( $caps ) as $cap_key ) {
                    $all_ext_caps[ $cap_key ] = $cap_key;   // label = key (لا وصف متوفر)
                }
            }

            $render_caps_table(
                /* translators: %s: plugin label */
                sprintf( __( 'توسعة: %s', 'rsyi-hr' ), esc_html( $ext['label'] ) ),
                $all_ext_caps
            );
        endforeach;
    else :
        ?>
        <h2><?php esc_html_e( 'توسعات خارجية', 'rsyi-hr' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'لا توجد plugins خارجية مسجَّلة حالياً. استخدم RSYI_HR\\Roles::register_extension_caps() لتسجيل صلاحيات plugin خارجية.', 'rsyi-hr' ); ?>
        </p>
        <pre class="rsyi-code-example"><code>add_action( 'rsyi_hr_extend_roles', function() {
    RSYI_HR\Roles::register_extension_caps( 'my-plugin-id', [
        'rsyi_dean'      => [ 'my_view_cap' => true, 'my_manage_cap' => true ],
        'rsyi_dept_head' => [ 'my_view_cap' => true ],
        'rsyi_staff'     => [ 'my_view_cap' => true ],
    ], 'اسم نظامي للعرض' );
} );</code></pre>
    <?php endif; ?>

</div>

<style>
.rsyi-permissions-wrap { max-width: 100%; }
.rsyi-permissions-wrap h2 { margin-top: 2rem; }
.rsyi-perm-table { border-collapse: collapse; }
.rsyi-perm-table th,
.rsyi-perm-table td { text-align: center; padding: 8px 10px; }
.rsyi-perm-table .rsyi-cap-col { text-align: right; width: 260px; }
.rsyi-perm-table .rsyi-cap-key { text-align: right; }
.rsyi-perm-table .rsyi-cap-key code { display: block; font-size: 11px; color: #666; }
.rsyi-perm-table .has-cap .dashicons { color: #00a32a; font-size: 20px; }
.rsyi-perm-table .no-cap .dashicons  { color: #ccc; font-size: 18px; }
.rsyi-code-example {
    background: #f6f7f7;
    border: 1px solid #ddd;
    padding: 12px 16px;
    font-size: 13px;
    direction: ltr;
    text-align: left;
    overflow-x: auto;
}
</style>
