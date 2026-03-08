<?php
/**
 * Printable Leave Form — نموذج طلب الإجازة للطباعة
 *
 * يُستدعى عبر AJAX: ?action=rsyi_hr_print_leave&nonce=...&id=...
 * يُعيد HTML كاملاً للطباعة مباشرة من المتصفح.
 *
 * يتضمن:
 *   - بيانات الطلب (الاسم، الوظيفة، نوع الإجازة، التواريخ…)
 *   - صور توقيعات (الموظف، المدير المباشر، مدير HR، العميد)
 *
 * @package RSYI_HR
 */
defined( 'ABSPATH' ) || exit;

// يُستدعى من AJAX handler في class-hr-leaves.php
// المتغيرات المتاحة: $leave (array)

/**
 * تنسيق التاريخ بالعربية: "يوم الأحد الموافق 8/3/2026"
 */
function rsyi_hr_format_date_ar( ?string $date ): string {
    if ( empty( $date ) ) {
        return '';
    }
    $ts = strtotime( $date );
    if ( ! $ts ) {
        return esc_html( $date );
    }
    $days_ar = [ 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت' ];
    $day_name = $days_ar[ (int) date( 'w', $ts ) ];
    return 'يوم ' . $day_name . ' الموافق ' . date( 'j/n/Y', $ts );
}

$leave_type_labels = [
    'regular' => 'اعتيادية',
    'sick'    => 'مرضى',
    'casual'  => 'عارضة',
    'unpaid'  => 'بدون مرتب',
];
$selected_type = $leave['leave_type'] ?? 'regular';
?><!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>طلب إجازة — <?php echo esc_html( $leave['employee_name_ar'] ?: $leave['employee_name'] ); ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 14px;
      color: #1a1a1a;
      background: #fff;
      direction: rtl;
      padding: 40px;
  }

  .leave-paper {
      max-width: 680px;
      margin: 0 auto;
      border: 1px solid #ccc;
      padding: 40px 50px;
      background: #fff;
  }

  .leave-title {
      text-align: center;
      font-size: 22px;
      font-weight: bold;
      margin-bottom: 30px;
      letter-spacing: 1px;
  }

  .leave-field {
      display: flex;
      align-items: baseline;
      margin-bottom: 16px;
      border-bottom: 1px solid #999;
      padding-bottom: 2px;
  }

  .leave-field-label {
      font-weight: bold;
      white-space: nowrap;
      min-width: 130px;
  }

  .leave-field-value {
      flex: 1;
      padding: 0 8px;
  }

  .leave-field-note {
      font-size: 12px;
      color: #555;
      white-space: nowrap;
  }

  /* نوع الإجازة — checkboxes */
  .leave-type-row {
      display: flex;
      align-items: center;
      margin-bottom: 16px;
      border-bottom: 1px solid #999;
      padding-bottom: 4px;
  }

  .leave-type-label-main {
      font-weight: bold;
      min-width: 130px;
  }

  .leave-type-checks {
      display: flex;
      gap: 24px;
  }

  .leave-type-check {
      display: flex;
      align-items: center;
      gap: 6px;
  }

  .check-box {
      width: 14px;
      height: 14px;
      border: 1px solid #333;
      display: inline-block;
      text-align: center;
      line-height: 14px;
      font-size: 11px;
  }

  .check-box.checked::after {
      content: '✓';
      font-weight: bold;
      color: #000;
  }

  .leave-divider {
      border: none;
      border-top: 1.5px solid #333;
      margin: 24px 0;
  }

  /* التوقيعات */
  .signatures-section {
      margin-top: 20px;
  }

  .sig-row {
      display: flex;
      align-items: flex-end;
      margin-bottom: 20px;
      border-bottom: 1px solid #999;
      padding-bottom: 4px;
      min-height: 70px;
  }

  .sig-label {
      font-weight: bold;
      min-width: 200px;
  }

  .sig-value {
      flex: 1;
      padding: 0 10px;
  }

  .sig-value img {
      max-height: 60px;
      max-width: 180px;
  }

  /* مربع العميد */
  .dean-box {
      margin-top: 24px;
      border: 2px solid #333;
      padding: 16px 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
  }

  .dean-box p {
      font-size: 13px;
      font-weight: bold;
  }

  .dean-name {
      font-size: 15px;
      font-weight: bold;
      color: #1a1a1a;
  }

  .dean-title {
      font-size: 13px;
      color: #333;
  }

  @media print {
      body { padding: 0; }
      .leave-paper { border: none; box-shadow: none; }
      .no-print { display: none !important; }
  }
</style>
</head>
<body>

<!-- Print button — يختفي عند الطباعة -->
<div class="no-print" style="max-width:680px;margin:0 auto 16px;text-align:left">
    <button onclick="window.print()"
            style="background:#2271b1;color:#fff;border:none;padding:10px 24px;cursor:pointer;border-radius:4px;font-size:14px">
        🖨 طباعة
    </button>
    <button onclick="window.close()"
            style="background:#f0f0f1;color:#1d2327;border:1px solid #ccc;padding:10px 20px;cursor:pointer;border-radius:4px;font-size:14px;margin-right:8px">
        إغلاق
    </button>
</div>

<div class="leave-paper">

    <div class="leave-title">طلب أجازه</div>

    <!-- الاسم -->
    <div class="leave-field">
        <span class="leave-field-label">الاسم:</span>
        <span class="leave-field-value">
            <?php echo esc_html( $leave['employee_name_ar'] ?: $leave['employee_name'] ?: '.........................' ); ?>
        </span>
    </div>

    <!-- الوظيفة -->
    <div class="leave-field">
        <span class="leave-field-label">الوظيفة:</span>
        <span class="leave-field-value">
            <?php echo esc_html( $leave['job_title_name'] ?? '.........................' ); ?>
        </span>
    </div>

    <!-- نوع الإجازة -->
    <div class="leave-type-row">
        <span class="leave-type-label-main">نوع الإجازة:</span>
        <div class="leave-type-checks">
            <?php foreach ( $leave_type_labels as $val => $lbl ) : ?>
            <div class="leave-type-check">
                <span class="check-box <?php echo $selected_type === $val ? 'checked' : ''; ?>"></span>
                <span><?php echo esc_html( $lbl ); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- من يوم -->
    <div class="leave-field">
        <span class="leave-field-label">من يوم</span>
        <span class="leave-field-value"><?php echo esc_html( rsyi_hr_format_date_ar( $leave['from_date'] ?? null ) ); ?></span>
    </div>

    <!-- حتى يوم -->
    <div class="leave-field">
        <span class="leave-field-label">حتى يوم</span>
        <span class="leave-field-value"><?php echo esc_html( rsyi_hr_format_date_ar( $leave['to_date'] ?? null ) ); ?></span>
    </div>

    <!-- عودة إلى العمل -->
    <div class="leave-field">
        <span class="leave-field-label">عودة إلى العمل يوم</span>
        <span class="leave-field-value"><?php echo esc_html( rsyi_hr_format_date_ar( $leave['return_date'] ?? null ) ); ?></span>
    </div>

    <!-- آخر يوم أجازة -->
    <div class="leave-field">
        <span class="leave-field-label">آخر يوم أجازة قمت بها:</span>
        <span class="leave-field-value"><?php echo esc_html( rsyi_hr_format_date_ar( $leave['last_leave_date'] ?? null ) ); ?></span>
    </div>

    <!-- القائم بالعمل -->
    <div class="leave-field">
        <span class="leave-field-label">القائم بالعمل أثناء الإجازة:</span>
        <span class="leave-field-value"><?php echo esc_html( $leave['person_covering'] ?? '' ); ?></span>
        <span class="leave-field-note">-:</span>
    </div>

    <hr class="leave-divider">

    <!-- التوقيعات -->
    <div class="signatures-section">

        <!-- توقيع الموظف -->
        <div class="sig-row">
            <span class="sig-label">توقيع الموظف :</span>
            <span class="sig-value">
                <?php if ( ! empty( $leave['employee_signature_img'] ) ) : ?>
                    <img src="<?php echo esc_url( $leave['employee_signature_img'] ); ?>"
                         alt="توقيع الموظف">
                <?php elseif ( ! empty( $leave['employee_signature'] ) &&
                               str_starts_with( $leave['employee_signature'], 'data:image' ) ) : ?>
                    <img src="<?php echo esc_attr( $leave['employee_signature'] ); ?>"
                         alt="توقيع الموظف">
                <?php else : ?>
                    ......................................................
                <?php endif; ?>
            </span>
            <?php if ( $leave['employee_signed_at'] ) : ?>
            <span style="font-size:12px;color:#555"><?php echo esc_html( $leave['employee_signed_at'] ); ?></span>
            <?php endif; ?>
        </div>

        <!-- توقيع المدير المباشر -->
        <div class="sig-row">
            <span class="sig-label">توقيع المدير المباشر :</span>
            <span class="sig-value">
                <?php if ( ! empty( $leave['manager_signature'] ) &&
                           str_starts_with( $leave['manager_signature'], 'data:image' ) ) : ?>
                    <img src="<?php echo esc_attr( $leave['manager_signature'] ); ?>"
                         alt="توقيع المدير المباشر">
                <?php else : ?>
                    ......................................................
                <?php endif; ?>
            </span>
            <?php if ( $leave['manager_signed_at'] ) : ?>
            <span style="font-size:12px;color:#555"><?php echo esc_html( $leave['manager_signed_at'] ); ?></span>
            <?php endif; ?>
        </div>

        <!-- توقيع مدير الموارد البشرية -->
        <div class="sig-row">
            <span class="sig-label">توقيع مدير الموارد البشرية :</span>
            <span class="sig-value">
                <?php if ( ! empty( $leave['hr_manager_signature'] ) &&
                           str_starts_with( $leave['hr_manager_signature'], 'data:image' ) ) : ?>
                    <img src="<?php echo esc_attr( $leave['hr_manager_signature'] ); ?>"
                         alt="توقيع مدير HR">
                <?php else : ?>
                    ......................................................
                <?php endif; ?>
            </span>
            <?php if ( $leave['hr_manager_signed_at'] ) : ?>
            <span style="font-size:12px;color:#555"><?php echo esc_html( $leave['hr_manager_signed_at'] ); ?></span>
            <?php endif; ?>
        </div>

        <!-- تصديق العميد -->
        <p style="font-weight:bold;margin:16px 0 10px">تصدق؛</p>

        <div class="dean-box">
            <?php if ( $leave['dean_signed_at'] ) : ?>
                <p style="color:green;font-size:13px">✓ تم التصديق بتاريخ: <?php echo esc_html( $leave['dean_signed_at'] ); ?></p>
            <?php else : ?>
                <p style="color:#999;font-size:13px">في انتظار تصديق العميد</p>
            <?php endif; ?>
            <span class="dean-name">لواء بحري/ مصطفى البحيري</span>
            <span class="dean-title">عميد معهد البحر الأحمر لليخوت</span>
        </div>
    </div>

</div><!-- /.leave-paper -->

</body>
</html>
