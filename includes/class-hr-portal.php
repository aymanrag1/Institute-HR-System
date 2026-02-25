<?php
/**
 * HR Employee Portal — بوابة الموظف الإلكترونية
 *
 * الاستخدام: ضع الـ shortcode [rsyi_hr_portal] على أي صفحة WordPress
 *
 * المسارات عبر ?rsyi_portal=XXX :
 *   dashboard     — الرئيسية
 *   profile       — الملف الشخصي
 *   leave_new     — طلب إجازة جديد
 *   leave_list    — سجل الإجازات
 *   overtime_new  — طلب وقت إضافي
 *   overtime_list — سجل الوقت الإضافي
 *   violations    — المخالفات
 *
 * @package RSYI_HR
 */

namespace RSYI_HR;

defined( 'ABSPATH' ) || exit;

class Portal {

    public static function init(): void {
        add_shortcode( 'rsyi_hr_portal', [ __CLASS__, 'render_portal' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_rsyi_hr_portal_save_signature',  [ __CLASS__, 'ajax_save_signature' ] );
        add_action( 'wp_ajax_rsyi_hr_portal_save_photo',      [ __CLASS__, 'ajax_save_photo' ] );
        add_action( 'wp_ajax_rsyi_hr_portal_get_employee',    [ __CLASS__, 'ajax_get_employee' ] );
    }

    /** تحميل الأصول الخاصة بالبوابة */
    public static function enqueue_assets(): void {
        if ( ! has_shortcode( get_post()->post_content ?? '', 'rsyi_hr_portal' ) ) {
            return;
        }

        wp_enqueue_style(
            'rsyi-hr-portal',
            RSYI_HR_URL . 'assets/css/portal.css',
            [],
            RSYI_HR_VERSION
        );

        wp_enqueue_script(
            'rsyi-hr-signature-pad',
            RSYI_HR_URL . 'assets/js/signature-pad.js',
            [],
            RSYI_HR_VERSION,
            true
        );

        wp_enqueue_script(
            'rsyi-hr-portal',
            RSYI_HR_URL . 'assets/js/portal.js',
            [ 'jquery', 'rsyi-hr-signature-pad' ],
            RSYI_HR_VERSION,
            true
        );

        wp_localize_script( 'rsyi-hr-portal', 'rsyiHRPortal', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'rsyi_hr_admin' ),
            'portalUrl'=> get_permalink(),
            'i18n'     => [
                'saved'   => __( 'تم الحفظ بنجاح.', 'rsyi-hr' ),
                'error'   => __( 'حدث خطأ، حاول مجدداً.', 'rsyi-hr' ),
                'confirm' => __( 'هل أنت متأكد؟', 'rsyi-hr' ),
                'loading' => __( 'جارٍ التحميل...', 'rsyi-hr' ),
                'sign_here' => __( 'وقّع هنا / Sign Here', 'rsyi-hr' ),
                'clear'   => __( 'مسح / Clear', 'rsyi-hr' ),
            ],
        ] );
    }

    /** الـ shortcode الرئيسي */
    public static function render_portal( array $atts = [] ): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="rsyi-portal-login-notice"><p>' .
                sprintf(
                    /* translators: %s: login URL */
                    __( 'يجب <a href="%s">تسجيل الدخول</a> للوصول إلى بوابة الموظف.', 'rsyi-hr' ),
                    esc_url( wp_login_url( get_permalink() ) )
                ) .
                '</p></div>';
        }

        if ( ! current_user_can( 'rsyi_hr_portal_access' ) ) {
            return '<div class="rsyi-portal-error"><p>' .
                esc_html__( 'ليس لديك صلاحية الوصول إلى بوابة الموظف.', 'rsyi-hr' ) .
                '</p></div>';
        }

        $emp = Employees::get_by_user_id( get_current_user_id() );
        if ( ! $emp ) {
            return '<div class="rsyi-portal-error"><p>' .
                esc_html__( 'لا يوجد سجل موظف مرتبط بحسابك. يرجى التواصل مع إدارة الموارد البشرية.', 'rsyi-hr' ) .
                '</p></div>';
        }

        $page = sanitize_key( $_GET['rsyi_portal'] ?? 'dashboard' ); // phpcs:ignore

        ob_start();
        self::render_nav( $emp, $page );

        switch ( $page ) {
            case 'profile':
                self::render_profile( $emp );
                break;
            case 'leave_new':
                self::render_leave_form( $emp );
                break;
            case 'leave_list':
                self::render_leave_list( $emp );
                break;
            case 'overtime_new':
                self::render_overtime_form( $emp );
                break;
            case 'overtime_list':
                self::render_overtime_list( $emp );
                break;
            case 'violations':
                self::render_violations( $emp );
                break;
            default:
                self::render_dashboard( $emp );
        }

        return ob_get_clean();
    }

    // ─── شريط التنقل ──────────────────────────────────────────────────────
    private static function render_nav( array $emp, string $active ): void {
        $base = get_permalink();
        $name = $emp['full_name_ar'] ?: $emp['full_name'];
        ?>
        <div class="rsyi-portal-wrap" dir="rtl">
        <nav class="rsyi-portal-nav">
            <div class="rsyi-portal-nav-user">
                <?php if ( $emp['photo'] ) : ?>
                    <img src="<?php echo esc_url( $emp['photo'] ); ?>" alt="" class="rsyi-portal-avatar">
                <?php else : ?>
                    <span class="rsyi-portal-avatar-icon">👤</span>
                <?php endif; ?>
                <span><?php echo esc_html( $name ); ?></span>
            </div>
            <ul class="rsyi-portal-nav-links">
                <li <?php echo 'dashboard' === $active ? 'class="active"' : ''; ?>>
                    <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'dashboard', $base ) ); ?>">
                        🏠 <?php esc_html_e( 'الرئيسية', 'rsyi-hr' ); ?>
                    </a>
                </li>
                <li <?php echo 'profile' === $active ? 'class="active"' : ''; ?>>
                    <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'profile', $base ) ); ?>">
                        👤 <?php esc_html_e( 'ملفي الشخصي', 'rsyi-hr' ); ?>
                    </a>
                </li>
                <?php if ( current_user_can( 'rsyi_hr_submit_leave' ) ) : ?>
                <li <?php echo in_array( $active, [ 'leave_new', 'leave_list' ], true ) ? 'class="active"' : ''; ?>>
                    <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'leave_list', $base ) ); ?>">
                        🗓️ <?php esc_html_e( 'الإجازات', 'rsyi-hr' ); ?>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ( current_user_can( 'rsyi_hr_submit_overtime' ) ) : ?>
                <li <?php echo in_array( $active, [ 'overtime_new', 'overtime_list' ], true ) ? 'class="active"' : ''; ?>>
                    <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'overtime_list', $base ) ); ?>">
                        ⏰ <?php esc_html_e( 'الوقت الإضافي', 'rsyi-hr' ); ?>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ( current_user_can( 'rsyi_hr_view_violations' ) ) : ?>
                <li <?php echo 'violations' === $active ? 'class="active"' : ''; ?>>
                    <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'violations', $base ) ); ?>">
                        ⚠️ <?php esc_html_e( 'المخالفات', 'rsyi-hr' ); ?>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">
                        🚪 <?php esc_html_e( 'تسجيل الخروج', 'rsyi-hr' ); ?>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="rsyi-portal-content">
        <?php
    }

    // ─── لوحة التحكم ──────────────────────────────────────────────────────
    private static function render_dashboard( array $emp ): void {
        $base    = get_permalink();
        $pending = Leaves::get_all( [ 'employee_id' => (int) $emp['id'] ] );
        $total   = count( $pending );
        $approved = count( array_filter( $pending, fn( $l ) => 'dean_approved' === $l['status'] ) );
        ?>
        <div class="rsyi-portal-page">
            <h2><?php esc_html_e( 'مرحباً بك في بوابة الموظف', 'rsyi-hr' ); ?></h2>
            <div class="rsyi-portal-cards">
                <div class="rsyi-portal-card">
                    <span class="rsyi-portal-card-num"><?php echo esc_html( $total ); ?></span>
                    <span><?php esc_html_e( 'إجمالي الإجازات', 'rsyi-hr' ); ?></span>
                </div>
                <div class="rsyi-portal-card rsyi-portal-card-green">
                    <span class="rsyi-portal-card-num"><?php echo esc_html( $approved ); ?></span>
                    <span><?php esc_html_e( 'إجازات معتمدة', 'rsyi-hr' ); ?></span>
                </div>
                <div class="rsyi-portal-card rsyi-portal-card-orange">
                    <span class="rsyi-portal-card-num"><?php echo esc_html( $total - $approved ); ?></span>
                    <span><?php esc_html_e( 'قيد الانتظار', 'rsyi-hr' ); ?></span>
                </div>
            </div>
            <div class="rsyi-portal-actions">
                <?php if ( current_user_can( 'rsyi_hr_submit_leave' ) ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'leave_new', $base ) ); ?>"
                   class="rsyi-portal-btn">
                    + <?php esc_html_e( 'طلب إجازة جديد', 'rsyi-hr' ); ?>
                </a>
                <?php endif; ?>
                <?php if ( current_user_can( 'rsyi_hr_submit_overtime' ) ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'overtime_new', $base ) ); ?>"
                   class="rsyi-portal-btn rsyi-portal-btn-secondary">
                    + <?php esc_html_e( 'طلب وقت إضافي', 'rsyi-hr' ); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        </div></div>
        <?php
    }

    // ─── الملف الشخصي ────────────────────────────────────────────────────
    private static function render_profile( array $emp ): void {
        ?>
        <div class="rsyi-portal-page">
            <h2><?php esc_html_e( 'الملف الشخصي', 'rsyi-hr' ); ?></h2>

            <!-- صورة الموظف -->
            <div class="rsyi-portal-profile-photo">
                <?php if ( $emp['photo'] ) : ?>
                    <img src="<?php echo esc_url( $emp['photo'] ); ?>" alt="" id="rsyi-portal-photo-preview" class="rsyi-portal-photo-img">
                <?php else : ?>
                    <div class="rsyi-portal-photo-placeholder" id="rsyi-portal-photo-preview">
                        <?php esc_html_e( 'لا توجد صورة', 'rsyi-hr' ); ?>
                    </div>
                <?php endif; ?>
                <label class="rsyi-portal-btn rsyi-portal-btn-sm" style="cursor:pointer;display:inline-block;margin-top:8px;">
                    <?php esc_html_e( 'رفع صورة', 'rsyi-hr' ); ?>
                    <input type="file" id="rsyi-portal-photo-input" accept="image/*" style="display:none">
                </label>
            </div>

            <!-- بيانات الموظف -->
            <div class="rsyi-portal-info-grid">
                <div class="rsyi-portal-info-item">
                    <label><?php esc_html_e( 'الاسم', 'rsyi-hr' ); ?></label>
                    <span><?php echo esc_html( $emp['full_name_ar'] ?: $emp['full_name'] ); ?></span>
                </div>
                <div class="rsyi-portal-info-item">
                    <label><?php esc_html_e( 'رقم الموظف', 'rsyi-hr' ); ?></label>
                    <span><?php echo esc_html( $emp['employee_number'] ?: '—' ); ?></span>
                </div>
                <div class="rsyi-portal-info-item">
                    <label><?php esc_html_e( 'الوظيفة', 'rsyi-hr' ); ?></label>
                    <span><?php echo esc_html( $emp['job_title_name'] ?? '—' ); ?></span>
                </div>
                <div class="rsyi-portal-info-item">
                    <label><?php esc_html_e( 'القسم', 'rsyi-hr' ); ?></label>
                    <span><?php echo esc_html( $emp['department_name'] ?? '—' ); ?></span>
                </div>
                <div class="rsyi-portal-info-item">
                    <label><?php esc_html_e( 'تاريخ التعيين', 'rsyi-hr' ); ?></label>
                    <span><?php echo esc_html( $emp['hire_date'] ?: '—' ); ?></span>
                </div>
                <div class="rsyi-portal-info-item">
                    <label><?php esc_html_e( 'الهاتف', 'rsyi-hr' ); ?></label>
                    <span><?php echo esc_html( $emp['phone'] ?: '—' ); ?></span>
                </div>
            </div>

            <!-- التوقيع الإلكتروني -->
            <div class="rsyi-portal-signature-section">
                <h3><?php esc_html_e( 'توقيعي الإلكتروني', 'rsyi-hr' ); ?></h3>
                <?php if ( $emp['signature'] ) : ?>
                    <div class="rsyi-portal-sig-saved">
                        <p><?php esc_html_e( 'التوقيع المحفوظ:', 'rsyi-hr' ); ?></p>
                        <img src="<?php echo esc_attr( $emp['signature'] ); ?>" alt="signature" style="border:1px solid #ddd;max-width:300px;background:#fff;">
                    </div>
                <?php endif; ?>
                <p><?php esc_html_e( 'رسم توقيع جديد:', 'rsyi-hr' ); ?></p>
                <canvas id="rsyi-portal-sig-canvas" width="400" height="150"
                        style="border:2px solid #2271b1;border-radius:4px;background:#fff;touch-action:none;cursor:crosshair;"></canvas>
                <br>
                <button type="button" id="rsyi-portal-sig-clear" class="rsyi-portal-btn rsyi-portal-btn-sm rsyi-portal-btn-secondary">
                    <?php esc_html_e( 'مسح', 'rsyi-hr' ); ?>
                </button>
                <button type="button" id="rsyi-portal-sig-save" class="rsyi-portal-btn rsyi-portal-btn-sm">
                    <?php esc_html_e( 'حفظ التوقيع', 'rsyi-hr' ); ?>
                </button>
                <span id="rsyi-portal-sig-msg" style="margin-right:10px;font-size:13px;color:#065f46;"></span>
            </div>
        </div>
        </div></div>
        <?php
    }

    // ─── نموذج طلب إجازة ─────────────────────────────────────────────────
    private static function render_leave_form( array $emp ): void {
        $base = get_permalink();
        ?>
        <div class="rsyi-portal-page">
            <h2><?php esc_html_e( 'طلب إجازة جديد', 'rsyi-hr' ); ?></h2>

            <form id="rsyi-portal-leave-form" class="rsyi-portal-form" autocomplete="off">
                <?php wp_nonce_field( 'rsyi_hr_admin', 'rsyi_nonce' ); ?>

                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'الاسم', 'rsyi-hr' ); ?></label>
                    <input type="text" value="<?php echo esc_attr( $emp['full_name_ar'] ?: $emp['full_name'] ); ?>" readonly class="rsyi-portal-readonly">
                </div>
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'الوظيفة', 'rsyi-hr' ); ?></label>
                    <input type="text" value="<?php echo esc_attr( $emp['job_title_name'] ?? '' ); ?>" readonly class="rsyi-portal-readonly">
                </div>

                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'نوع الإجازة *', 'rsyi-hr' ); ?></label>
                    <div class="rsyi-portal-radio-group">
                        <label><input type="radio" name="leave_type" value="annual" checked> <?php esc_html_e( 'اعتيادية', 'rsyi-hr' ); ?></label>
                        <label><input type="radio" name="leave_type" value="emergency"> <?php esc_html_e( 'عارضة', 'rsyi-hr' ); ?></label>
                        <label><input type="radio" name="leave_type" value="sick"> <?php esc_html_e( 'مرضى', 'rsyi-hr' ); ?></label>
                        <label><input type="radio" name="leave_type" value="unpaid"> <?php esc_html_e( 'بدون مرتب', 'rsyi-hr' ); ?></label>
                    </div>
                </div>

                <div class="rsyi-portal-form-cols-2">
                    <div class="rsyi-portal-form-row">
                        <label><?php esc_html_e( 'من يوم *', 'rsyi-hr' ); ?></label>
                        <input type="date" name="from_date" required>
                    </div>
                    <div class="rsyi-portal-form-row">
                        <label><?php esc_html_e( 'حتى يوم *', 'rsyi-hr' ); ?></label>
                        <input type="date" name="to_date" required>
                    </div>
                    <div class="rsyi-portal-form-row">
                        <label><?php esc_html_e( 'عودة إلى العمل يوم', 'rsyi-hr' ); ?></label>
                        <input type="date" name="return_date">
                    </div>
                    <div class="rsyi-portal-form-row">
                        <label><?php esc_html_e( 'عدد الأيام', 'rsyi-hr' ); ?></label>
                        <input type="text" id="rsyi-portal-leave-days" readonly class="rsyi-portal-readonly" placeholder="<?php esc_attr_e( 'تلقائي', 'rsyi-hr' ); ?>">
                    </div>
                </div>

                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'آخر إجازة قمت بها', 'rsyi-hr' ); ?></label>
                    <input type="date" name="last_day_before">
                </div>
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'القائم بالعمل أثناء الإجازة', 'rsyi-hr' ); ?></label>
                    <input type="text" name="substitute_name">
                </div>

                <!-- التوقيع الإلكتروني -->
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'توقيع الموظف *', 'rsyi-hr' ); ?></label>
                    <?php if ( $emp['signature'] ) : ?>
                        <div class="rsyi-portal-sig-choice">
                            <label>
                                <input type="radio" name="sig_choice" value="saved" checked>
                                <?php esc_html_e( 'استخدام توقيعي المحفوظ', 'rsyi-hr' ); ?>
                            </label>
                            <label>
                                <input type="radio" name="sig_choice" value="new">
                                <?php esc_html_e( 'رسم توقيع جديد', 'rsyi-hr' ); ?>
                            </label>
                            <div id="rsyi-portal-leave-sig-saved">
                                <img src="<?php echo esc_attr( $emp['signature'] ); ?>" alt="sig" style="border:1px solid #ddd;max-width:250px;background:#fff;">
                            </div>
                        </div>
                    <?php endif; ?>
                    <div id="rsyi-portal-leave-sig-pad" <?php echo $emp['signature'] ? 'style="display:none"' : ''; ?>>
                        <canvas id="rsyi-portal-leave-canvas" width="400" height="120"
                                style="border:2px solid #2271b1;border-radius:4px;background:#fff;touch-action:none;cursor:crosshair;"></canvas>
                        <br>
                        <button type="button" id="rsyi-portal-leave-sig-clear"
                                class="rsyi-portal-btn rsyi-portal-btn-sm rsyi-portal-btn-secondary" style="margin-top:6px;">
                            <?php esc_html_e( 'مسح التوقيع', 'rsyi-hr' ); ?>
                        </button>
                    </div>
                    <input type="hidden" name="employee_signature" id="rsyi-portal-leave-sig-input"
                           value="<?php echo $emp['signature'] ? esc_attr( $emp['signature'] ) : ''; ?>">
                </div>

                <div class="rsyi-portal-form-actions">
                    <button type="submit" class="rsyi-portal-btn">
                        <?php esc_html_e( 'رفع الطلب', 'rsyi-hr' ); ?>
                    </button>
                    <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'leave_list', $base ) ); ?>"
                       class="rsyi-portal-btn rsyi-portal-btn-secondary">
                        <?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?>
                    </a>
                </div>
                <div id="rsyi-portal-leave-notice" class="rsyi-portal-notice" style="display:none;"></div>
            </form>
        </div>
        </div></div>
        <?php
    }

    // ─── قائمة الإجازات ───────────────────────────────────────────────────
    private static function render_leave_list( array $emp ): void {
        $base   = get_permalink();
        $leaves = Leaves::get_all( [ 'employee_id' => (int) $emp['id'] ] );

        $status_labels = [
            'pending'          => [ 'label' => __( 'قيد الانتظار', 'rsyi-hr' ),     'class' => 'rsyi-badge-pending' ],
            'manager_approved' => [ 'label' => __( 'اعتمد المدير', 'rsyi-hr' ),      'class' => 'rsyi-badge-info' ],
            'hr_approved'      => [ 'label' => __( 'اعتمد الموارد البشرية', 'rsyi-hr' ), 'class' => 'rsyi-badge-info' ],
            'dean_approved'    => [ 'label' => __( 'معتمد نهائياً', 'rsyi-hr' ),     'class' => 'rsyi-badge-approved' ],
            'rejected'         => [ 'label' => __( 'مرفوض', 'rsyi-hr' ),             'class' => 'rsyi-badge-rejected' ],
        ];
        $type_labels = [
            'annual'    => __( 'اعتيادية', 'rsyi-hr' ),
            'emergency' => __( 'عارضة',   'rsyi-hr' ),
            'sick'      => __( 'مرضى',    'rsyi-hr' ),
            'unpaid'    => __( 'بدون مرتب','rsyi-hr' ),
        ];
        ?>
        <div class="rsyi-portal-page">
            <div class="rsyi-portal-page-header">
                <h2><?php esc_html_e( 'طلبات الإجازة', 'rsyi-hr' ); ?></h2>
                <?php if ( current_user_can( 'rsyi_hr_submit_leave' ) ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'leave_new', $base ) ); ?>"
                   class="rsyi-portal-btn">+ <?php esc_html_e( 'طلب جديد', 'rsyi-hr' ); ?></a>
                <?php endif; ?>
            </div>

            <?php if ( empty( $leaves ) ) : ?>
                <p class="rsyi-portal-empty"><?php esc_html_e( 'لا توجد طلبات إجازة بعد.', 'rsyi-hr' ); ?></p>
            <?php else : ?>
                <div class="rsyi-portal-table-wrap">
                <table class="rsyi-portal-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'النوع', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'من', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'حتى', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'الأيام', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'إجراءات', 'rsyi-hr' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $leaves as $l ) :
                            $sl = $status_labels[ $l['status'] ] ?? [ 'label' => $l['status'], 'class' => '' ];
                        ?>
                        <tr>
                            <td><?php echo esc_html( $type_labels[ $l['leave_type'] ] ?? $l['leave_type'] ); ?></td>
                            <td><?php echo esc_html( $l['from_date'] ); ?></td>
                            <td><?php echo esc_html( $l['to_date'] ); ?></td>
                            <td><?php echo esc_html( $l['total_days'] ); ?></td>
                            <td><span class="rsyi-portal-badge <?php echo esc_attr( $sl['class'] ); ?>"><?php echo esc_html( $sl['label'] ); ?></span></td>
                            <td>
                                <?php if ( 'dean_approved' === $l['status'] ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( [ 'rsyi_portal' => 'leave_print', 'leave_id' => $l['id'] ], $base ) ); ?>"
                                   target="_blank" class="rsyi-portal-btn rsyi-portal-btn-sm">
                                    🖨️ <?php esc_html_e( 'طباعة', 'rsyi-hr' ); ?>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        </div></div>
        <?php
    }

    // ─── نموذج الوقت الإضافي ─────────────────────────────────────────────
    private static function render_overtime_form( array $emp ): void {
        $base = get_permalink();
        ?>
        <div class="rsyi-portal-page">
            <h2><?php esc_html_e( 'طلب وقت إضافي', 'rsyi-hr' ); ?></h2>

            <form id="rsyi-portal-overtime-form" class="rsyi-portal-form" autocomplete="off">
                <?php wp_nonce_field( 'rsyi_hr_admin', 'rsyi_nonce' ); ?>

                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'الاسم', 'rsyi-hr' ); ?></label>
                    <input type="text" value="<?php echo esc_attr( $emp['full_name_ar'] ?: $emp['full_name'] ); ?>" readonly class="rsyi-portal-readonly">
                </div>

                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'تاريخ العمل الإضافي *', 'rsyi-hr' ); ?></label>
                    <input type="date" name="work_date" required>
                </div>

                <div class="rsyi-portal-form-cols-2">
                    <div class="rsyi-portal-form-row">
                        <label><?php esc_html_e( 'من الساعة *', 'rsyi-hr' ); ?></label>
                        <input type="time" name="from_time" required>
                    </div>
                    <div class="rsyi-portal-form-row">
                        <label><?php esc_html_e( 'إلى الساعة *', 'rsyi-hr' ); ?></label>
                        <input type="time" name="to_time" required>
                    </div>
                </div>

                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'إجمالي الساعات', 'rsyi-hr' ); ?></label>
                    <input type="text" id="rsyi-portal-ot-hours" readonly class="rsyi-portal-readonly" placeholder="<?php esc_attr_e( 'تلقائي', 'rsyi-hr' ); ?>">
                </div>

                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'سبب الطلب *', 'rsyi-hr' ); ?></label>
                    <textarea name="reason" rows="3" required></textarea>
                </div>

                <!-- التوقيع -->
                <div class="rsyi-portal-form-row">
                    <label><?php esc_html_e( 'توقيع الموظف *', 'rsyi-hr' ); ?></label>
                    <?php if ( $emp['signature'] ) : ?>
                        <img src="<?php echo esc_attr( $emp['signature'] ); ?>" alt="sig" style="border:1px solid #ddd;max-width:250px;display:block;background:#fff;margin-bottom:8px;">
                        <input type="hidden" name="employee_signature" value="<?php echo esc_attr( $emp['signature'] ); ?>">
                    <?php else : ?>
                        <canvas id="rsyi-portal-ot-canvas" width="400" height="120"
                                style="border:2px solid #2271b1;border-radius:4px;background:#fff;touch-action:none;cursor:crosshair;"></canvas>
                        <br>
                        <button type="button" id="rsyi-portal-ot-sig-clear"
                                class="rsyi-portal-btn rsyi-portal-btn-sm rsyi-portal-btn-secondary" style="margin-top:6px;">
                            <?php esc_html_e( 'مسح', 'rsyi-hr' ); ?>
                        </button>
                        <input type="hidden" name="employee_signature" id="rsyi-portal-ot-sig-input">
                    <?php endif; ?>
                </div>

                <div class="rsyi-portal-form-actions">
                    <button type="submit" class="rsyi-portal-btn">
                        <?php esc_html_e( 'رفع الطلب', 'rsyi-hr' ); ?>
                    </button>
                    <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'overtime_list', $base ) ); ?>"
                       class="rsyi-portal-btn rsyi-portal-btn-secondary">
                        <?php esc_html_e( 'إلغاء', 'rsyi-hr' ); ?>
                    </a>
                </div>
                <div id="rsyi-portal-ot-notice" class="rsyi-portal-notice" style="display:none;"></div>
            </form>
        </div>
        </div></div>
        <?php
    }

    // ─── قائمة الوقت الإضافي ─────────────────────────────────────────────
    private static function render_overtime_list( array $emp ): void {
        $base = get_permalink();
        $list = Overtime::get_all( [ 'employee_id' => (int) $emp['id'] ] );

        $status_labels = [
            'pending'          => [ 'label' => __( 'قيد الانتظار', 'rsyi-hr' ), 'class' => 'rsyi-badge-pending' ],
            'manager_approved' => [ 'label' => __( 'اعتمد المدير', 'rsyi-hr' ), 'class' => 'rsyi-badge-info' ],
            'hr_approved'      => [ 'label' => __( 'معتمد', 'rsyi-hr' ),        'class' => 'rsyi-badge-approved' ],
            'rejected'         => [ 'label' => __( 'مرفوض', 'rsyi-hr' ),        'class' => 'rsyi-badge-rejected' ],
        ];
        ?>
        <div class="rsyi-portal-page">
            <div class="rsyi-portal-page-header">
                <h2><?php esc_html_e( 'طلبات الوقت الإضافي', 'rsyi-hr' ); ?></h2>
                <?php if ( current_user_can( 'rsyi_hr_submit_overtime' ) ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'rsyi_portal', 'overtime_new', $base ) ); ?>"
                   class="rsyi-portal-btn">+ <?php esc_html_e( 'طلب جديد', 'rsyi-hr' ); ?></a>
                <?php endif; ?>
            </div>

            <?php if ( empty( $list ) ) : ?>
                <p class="rsyi-portal-empty"><?php esc_html_e( 'لا توجد طلبات وقت إضافي.', 'rsyi-hr' ); ?></p>
            <?php else : ?>
                <div class="rsyi-portal-table-wrap">
                <table class="rsyi-portal-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'التاريخ', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'من', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'إلى', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'الساعات', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'الحالة', 'rsyi-hr' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $list as $ot ) :
                            $sl = $status_labels[ $ot['status'] ] ?? [ 'label' => $ot['status'], 'class' => '' ];
                        ?>
                        <tr>
                            <td><?php echo esc_html( $ot['work_date'] ); ?></td>
                            <td><?php echo esc_html( substr( $ot['from_time'], 0, 5 ) ); ?></td>
                            <td><?php echo esc_html( substr( $ot['to_time'],   0, 5 ) ); ?></td>
                            <td><?php echo esc_html( $ot['total_hours'] ); ?></td>
                            <td><span class="rsyi-portal-badge <?php echo esc_attr( $sl['class'] ); ?>"><?php echo esc_html( $sl['label'] ); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        </div></div>
        <?php
    }

    // ─── المخالفات ───────────────────────────────────────────────────────
    private static function render_violations( array $emp ): void {
        $violations = Violations::get_all( [ 'employee_id' => (int) $emp['id'], 'status' => 'approved' ] );
        $types      = Violations::get_types();
        $penalties  = Violations::get_penalty_types();
        ?>
        <div class="rsyi-portal-page">
            <h2><?php esc_html_e( 'المخالفات والجزاءات', 'rsyi-hr' ); ?></h2>

            <?php if ( empty( $violations ) ) : ?>
                <p class="rsyi-portal-empty" style="color:#065f46;">✅ <?php esc_html_e( 'لا توجد مخالفات مسجلة بحقك.', 'rsyi-hr' ); ?></p>
            <?php else : ?>
                <div class="rsyi-portal-table-wrap">
                <table class="rsyi-portal-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'المخالفة', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'الوصف', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'الجزاء', 'rsyi-hr' ); ?></th>
                            <th><?php esc_html_e( 'التاريخ', 'rsyi-hr' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $violations as $v ) : ?>
                        <tr>
                            <td><?php echo esc_html( $types[ $v['violation_type'] ] ?? $v['violation_type'] ); ?></td>
                            <td><?php echo esc_html( $v['description'] ); ?></td>
                            <td><?php echo esc_html( ( $penalties[ $v['penalty_type'] ] ?? $v['penalty_type'] ) . ( $v['penalty_value'] ? ' — ' . $v['penalty_value'] : '' ) ); ?></td>
                            <td><?php echo esc_html( substr( $v['created_at'], 0, 10 ) ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        </div></div>
        <?php
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════════════

    public static function ajax_save_signature(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_portal_access' ) || wp_die( -1 );

        $signature = isset( $_POST['signature'] ) ? wp_unslash( $_POST['signature'] ) : ''; // phpcs:ignore
        if ( ! str_starts_with( $signature, 'data:image/png;base64,' ) ) {
            wp_send_json_error( [ 'message' => __( 'توقيع غير صالح.', 'rsyi-hr' ) ] );
            return;
        }

        $emp = Employees::get_by_user_id( get_current_user_id() );
        if ( ! $emp ) {
            wp_send_json_error( [ 'message' => __( 'لا يوجد سجل موظف.', 'rsyi-hr' ) ] );
            return;
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_hr_employees',
            [ 'signature' => $signature ],
            [ 'id' => (int) $emp['id'] ]
        );

        wp_send_json_success();
    }

    public static function ajax_save_photo(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_portal_access' ) || wp_die( -1 );

        $emp = Employees::get_by_user_id( get_current_user_id() );
        if ( ! $emp ) {
            wp_send_json_error( [ 'message' => __( 'لا يوجد سجل موظف.', 'rsyi-hr' ) ] );
            return;
        }

        if ( empty( $_FILES['photo']['tmp_name'] ) ) { // phpcs:ignore
            wp_send_json_error( [ 'message' => __( 'لم يتم رفع أي صورة.', 'rsyi-hr' ) ] );
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload( 'photo', 0 );
        if ( is_wp_error( $attachment_id ) ) {
            wp_send_json_error( [ 'message' => $attachment_id->get_error_message() ] );
            return;
        }

        $photo_url = wp_get_attachment_url( $attachment_id );

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_hr_employees',
            [ 'photo' => $photo_url ],
            [ 'id' => (int) $emp['id'] ]
        );

        wp_send_json_success( [ 'url' => $photo_url ] );
    }

    public static function ajax_get_employee(): void {
        check_ajax_referer( 'rsyi_hr_admin', 'nonce' );
        current_user_can( 'rsyi_hr_portal_access' ) || wp_die( -1 );

        $emp = Employees::get_by_user_id( get_current_user_id() );
        $emp ? wp_send_json_success( $emp )
             : wp_send_json_error( [ 'message' => __( 'لا يوجد سجل موظف.', 'rsyi-hr' ) ] );
    }
}
