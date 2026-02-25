<?php
/**
 * Admin View — الصلاحيات
 *
 * شاشتان:
 *   ١. مصفوفة صلاحيات لكل مستخدم (بدون صلاحية | عرض فقط | قراءة | قراءة وكتابة)
 *   ٢. جدول صلاحيات الأدوار الوظيفية
 *
 * المتغيرات المتاحة:
 *   $hr_caps        array<cap_key, label>
 *   $definitions    array<role_slug, array{label, caps}>
 *   $extension_caps array<plugin_id, array{label, caps_by_role}>
 *   $all_users      WP_User[]
 *   $modules        array<key, array{ar, en}>
 *
 * @package RSYI_HR
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap rsyi-hr-wrap" dir="rtl">
    <h1>
        <span class="dashicons dashicons-shield"></span>
        <?php esc_html_e( 'الصلاحيات', 'rsyi-hr' ); ?>
    </h1>

    <!-- ───────────────────────────────────────────────
         القسم 1: صلاحيات المستخدم (لكل عنصر في النظام)
         ─────────────────────────────────────────────── -->
    <div style="background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:16px 20px;margin-bottom:24px">
        <p style="margin:0 0 12px;color:#50575e;font-size:13px">
            <?php esc_html_e( 'اختر المستخدم لتحديد صلاحياته لكل عنصر في النظام.', 'rsyi-hr' ); ?>
        </p>
        <table style="border-collapse:collapse">
            <tr>
                <td style="padding-left:12px;font-weight:700"><?php esc_html_e( 'المستخدم', 'rsyi-hr' ); ?></td>
                <td style="padding-left:12px">
                    <select id="rsyi-hr-perm-user" style="min-width:280px">
                        <option value="">— <?php esc_html_e( 'اختر مستخدماً', 'rsyi-hr' ); ?> —</option>
                        <?php foreach ( $all_users as $u ) : ?>
                            <option value="<?php echo esc_attr( $u->ID ); ?>">
                                <?php echo esc_html( $u->display_name . ' (' . $u->user_login . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <button class="button button-primary" id="rsyi-hr-load-perms">
                        <?php esc_html_e( 'تحميل الصلاحيات', 'rsyi-hr' ); ?>
                    </button>
                </td>
            </tr>
        </table>
    </div>

    <!-- مصفوفة الصلاحيات (تظهر بعد تحميل المستخدم) -->
    <div id="rsyi-hr-perms-matrix" style="display:none;margin-bottom:30px">
        <h2><?php esc_html_e( 'مصفوفة الصلاحيات', 'rsyi-hr' ); ?></h2>
        <table class="wp-list-table widefat fixed" dir="rtl" style="border-collapse:collapse">
            <thead>
                <tr>
                    <th style="text-align:right;background:#f6f7f7;border:1px solid #dcdcde;width:220px">
                        <?php esc_html_e( 'البند', 'rsyi-hr' ); ?>
                    </th>
                    <th style="text-align:center;background:#f6f7f7;border:1px solid #dcdcde;width:130px">
                        <?php esc_html_e( 'بدون صلاحية', 'rsyi-hr' ); ?>
                    </th>
                    <th style="text-align:center;background:#f6f7f7;border:1px solid #dcdcde;width:130px">
                        <?php esc_html_e( 'عرض فقط', 'rsyi-hr' ); ?>
                    </th>
                    <th style="text-align:center;background:#f6f7f7;border:1px solid #dcdcde;width:130px">
                        <?php esc_html_e( 'قراءة', 'rsyi-hr' ); ?>
                    </th>
                    <th style="text-align:center;background:#f6f7f7;border:1px solid #dcdcde;width:130px">
                        <?php esc_html_e( 'قراءة وكتابة', 'rsyi-hr' ); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $modules as $mod_key => $mod_labels ) : ?>
                <tr class="rsyi-hr-perm-row" data-module="<?php echo esc_attr( $mod_key ); ?>"
                    style="border-bottom:1px solid #dcdcde">
                    <td style="padding:10px 14px;border-left:1px solid #dcdcde;border-right:1px solid #dcdcde">
                        <strong><?php echo esc_html( $mod_labels['ar'] ); ?></strong>
                        <span style="display:block;color:#72777c;font-size:11px"><?php echo esc_html( $mod_labels['en'] ); ?></span>
                    </td>
                    <?php foreach ( [ 'none', 'view', 'read', 'read_write' ] as $perm_val ) : ?>
                    <td style="text-align:center;border-left:1px solid #dcdcde">
                        <input type="radio"
                               name="perm_<?php echo esc_attr( $mod_key ); ?>"
                               value="<?php echo esc_attr( $perm_val ); ?>"
                               class="rsyi-hr-perm-radio"
                               data-module="<?php echo esc_attr( $mod_key ); ?>"
                               style="width:18px;height:18px;cursor:pointer;accent-color:#2271b1">
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="display:flex;gap:12px;margin-top:16px">
            <button class="button button-primary" id="rsyi-hr-save-perms">
                <?php esc_html_e( 'حفظ الصلاحيات', 'rsyi-hr' ); ?>
            </button>
            <button class="button" id="rsyi-hr-reset-perms" style="color:#cc1818;border-color:#cc1818">
                <?php esc_html_e( 'ضبط الصلاحيات', 'rsyi-hr' ); ?>
            </button>
        </div>
    </div>

    <!-- ───────────────────────────────────────────────
         القسم 2: صلاحيات الأدوار الوظيفية
         ─────────────────────────────────────────────── -->
    <h2><?php esc_html_e( 'صلاحيات الأدوار الوظيفية', 'rsyi-hr' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'الصلاحيات التلقائية المرتبطة بكل دور وظيفي في المعهد.', 'rsyi-hr' ); ?>
    </p>

    <table class="wp-list-table widefat fixed" dir="rtl" style="border-collapse:collapse">
        <thead>
            <tr>
                <th style="text-align:right;width:260px;border:1px solid #dcdcde">
                    <?php esc_html_e( 'الصلاحية', 'rsyi-hr' ); ?>
                </th>
                <?php foreach ( $definitions as $slug => $def ) : ?>
                    <th style="text-align:center;border:1px solid #dcdcde">
                        <?php echo esc_html( $def['label'] ); ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $hr_caps as $cap_key => $cap_label ) : ?>
            <tr style="border-bottom:1px solid #dcdcde">
                <td style="padding:8px 12px;border-left:1px solid #dcdcde;border-right:1px solid #dcdcde">
                    <strong><?php echo esc_html( $cap_label ); ?></strong>
                    <code style="display:block;font-size:11px;color:#72777c"><?php echo esc_html( $cap_key ); ?></code>
                </td>
                <?php foreach ( $definitions as $slug => $def ) :
                    $role    = get_role( $slug );
                    $has_cap = $role && ! empty( $role->capabilities[ $cap_key ] );
                ?>
                    <td style="text-align:center;border-left:1px solid #dcdcde">
                        <?php if ( $has_cap ) : ?>
                            <span class="dashicons dashicons-yes-alt" style="color:#00a32a;font-size:20px"></span>
                        <?php else : ?>
                            <span class="dashicons dashicons-minus" style="color:#c3c4c7;font-size:18px"></span>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- صلاحيات الإضافات الخارجية -->
    <?php if ( ! empty( $extension_caps ) ) : ?>
        <h2 style="margin-top:2rem"><?php esc_html_e( 'توسعات خارجية', 'rsyi-hr' ); ?></h2>
        <?php foreach ( $extension_caps as $plugin_id => $ext ) :
            $all_ext_caps = [];
            foreach ( $ext['caps_by_role'] as $caps ) {
                foreach ( array_keys( $caps ) as $ck ) {
                    $all_ext_caps[ $ck ] = $ck;
                }
            }
        ?>
        <h3><?php echo esc_html( $ext['label'] ); ?></h3>
        <table class="wp-list-table widefat fixed" dir="rtl" style="border-collapse:collapse">
            <thead>
                <tr>
                    <th style="text-align:right;width:200px;border:1px solid #dcdcde">
                        <?php esc_html_e( 'الصلاحية', 'rsyi-hr' ); ?>
                    </th>
                    <?php foreach ( array_keys( $definitions ) as $slug ) : ?>
                        <th style="text-align:center;border:1px solid #dcdcde">
                            <?php echo esc_html( $definitions[ $slug ]['label'] ); ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $all_ext_caps as $cap_key ) : ?>
                <tr style="border-bottom:1px solid #dcdcde">
                    <td style="padding:8px 12px;border-left:1px solid #dcdcde;border-right:1px solid #dcdcde">
                        <code><?php echo esc_html( $cap_key ); ?></code>
                    </td>
                    <?php foreach ( array_keys( $definitions ) as $slug ) :
                        $has = ! empty( $ext['caps_by_role'][ $slug ][ $cap_key ] );
                    ?>
                        <td style="text-align:center;border-left:1px solid #dcdcde">
                            <?php echo $has
                                ? '<span class="dashicons dashicons-yes-alt" style="color:#00a32a;font-size:20px"></span>'
                                : '<span class="dashicons dashicons-minus" style="color:#c3c4c7;font-size:18px"></span>'; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>
    <?php else : ?>
        <p class="description" style="margin-top:1rem">
            <?php esc_html_e( 'لا توجد plugins خارجية مسجَّلة. استخدم RSYI_HR\\Roles::register_extension_caps() لتسجيل صلاحيات plugin خارجية.', 'rsyi-hr' ); ?>
        </p>
    <?php endif; ?>
</div>

<script>
(function ($) {
    'use strict';
    var currentUserId = 0;

    $('#rsyi-hr-load-perms').on('click', function () {
        currentUserId = parseInt($('#rsyi-hr-perm-user').val(), 10);
        if (!currentUserId) { alert('يرجى اختيار مستخدم أولاً.'); return; }

        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_load_user_permissions',
            nonce: rsyiHR.nonce,
            user_id: currentUserId
        }, function (res) {
            if (!res.success) { alert(rsyiHR.i18n.error); return; }
            // Set radio buttons based on loaded permissions
            $.each(res.data.permissions, function (mod, perm) {
                $('[name="perm_'+mod+'"][value="'+perm+'"]').prop('checked', true);
            });
            $('#rsyi-hr-perms-matrix').show();
        });
    });

    $('#rsyi-hr-save-perms').on('click', function () {
        if (!currentUserId) { alert('لم يتم تحديد مستخدم.'); return; }
        var permissions = {};
        $('.rsyi-hr-perm-radio:checked').each(function () {
            permissions[$(this).data('module')] = $(this).val();
        });
        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_save_user_permissions',
            nonce: rsyiHR.nonce,
            user_id: currentUserId,
            permissions: permissions
        }, function (res) {
            var msg = res.success ? (rsyiHR.i18n.perms_saved || 'تم حفظ الصلاحيات') : rsyiHR.i18n.error;
            alert(msg);
        });
    });

    $('#rsyi-hr-reset-perms').on('click', function () {
        if (!currentUserId) return;
        if (!confirm('هل تريد إعادة ضبط كل صلاحيات هذا المستخدم على "بدون صلاحية"؟')) return;
        $.post(rsyiHR.ajaxUrl, {
            action: 'rsyi_hr_reset_user_permissions',
            nonce: rsyiHR.nonce,
            user_id: currentUserId
        }, function (res) {
            if (res.success) {
                $('[value="none"].rsyi-hr-perm-radio').prop('checked', true);
                alert(rsyiHR.i18n.perms_reset || 'تم إعادة الضبط');
            }
        });
    });

    // Zebra striping
    $('.rsyi-hr-perm-row:nth-child(even)').css('background', '#f9f9f9');
}(jQuery));
</script>
