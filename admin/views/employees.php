<?php
/**
 * Employees View
 *
 * @package RSYI_HR
 * @var array $departments
 * @var array $job_titles
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap rsyi-hr-wrap">
    <h1>
        <?php esc_html_e( 'Employees / الموظفون', 'rsyi-hr' ); ?>
        <?php if ( current_user_can( 'rsyi_hr_manage_employees' ) ) : ?>
            <button class="page-title-action rsyi-hr-btn-add-employee">
                + <?php esc_html_e( 'Add Employee / إضافة موظف', 'rsyi-hr' ); ?>
            </button>
        <?php endif; ?>
    </h1>

    <!-- ── Filters / فلاتر ──────────────────────────────────────────────── -->
    <div class="rsyi-hr-filters">
        <select id="rsyi-hr-filter-dept">
            <option value=""><?php esc_html_e( 'All Departments / كل الأقسام', 'rsyi-hr' ); ?></option>
            <?php foreach ( $departments as $d ) : ?>
                <option value="<?php echo esc_attr( $d['id'] ); ?>">
                    <?php echo esc_html( $d['name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="rsyi-hr-filter-status">
            <option value="all"><?php esc_html_e( 'All Status / كل الحالات', 'rsyi-hr' ); ?></option>
            <option value="active"><?php esc_html_e( 'Active / نشط', 'rsyi-hr' ); ?></option>
            <option value="inactive"><?php esc_html_e( 'Inactive / غير نشط', 'rsyi-hr' ); ?></option>
            <option value="on_leave"><?php esc_html_e( 'On Leave / في إجازة', 'rsyi-hr' ); ?></option>
        </select>

        <input type="search" id="rsyi-hr-search-emp"
               placeholder="<?php esc_attr_e( 'Search name, ID, national ID... / بحث...', 'rsyi-hr' ); ?>">
    </div>

    <!-- ── Employees Table / جدول الموظفين ─────────────────────────────── -->
    <table class="wp-list-table widefat fixed striped rsyi-hr-table" id="rsyi-hr-employees-table">
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th><?php esc_html_e( 'Emp. ID', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'Name / الاسم', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'Department / القسم', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'Position / الوظيفة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'Status / الحالة', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'Phone / الهاتف', 'rsyi-hr' ); ?></th>
                <th><?php esc_html_e( 'Actions / إجراءات', 'rsyi-hr' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-hr-employees-body">
            <tr><td colspan="8" class="rsyi-hr-loading"><?php esc_html_e( 'Loading... / جارٍ التحميل...', 'rsyi-hr' ); ?></td></tr>
        </tbody>
    </table>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- Modal: Add / Edit Employee                                                -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="rsyi-hr-employee-modal" class="rsyi-hr-modal" style="display:none;">
    <div class="rsyi-hr-modal-content rsyi-hr-modal-wide">
        <button class="rsyi-hr-modal-close" title="<?php esc_attr_e( 'Close', 'rsyi-hr' ); ?>">&times;</button>
        <h2 id="rsyi-hr-employee-modal-title"><?php esc_html_e( 'Add Employee / إضافة موظف', 'rsyi-hr' ); ?></h2>

        <form id="rsyi-hr-employee-form" autocomplete="off">
            <input type="hidden" name="id" id="emp-id" value="">

            <!-- ══ Section 1: Identity / بيانات الهوية ══════════════════════ -->
            <div class="rsyi-hr-form-section">
                <h3 class="rsyi-hr-section-title">
                    <span class="dashicons dashicons-id-alt"></span>
                    <?php esc_html_e( 'Identity / بيانات الهوية', 'rsyi-hr' ); ?>
                </h3>
                <div class="rsyi-hr-form-cols-3">
                    <div class="rsyi-hr-form-row">
                        <label for="emp-number"><?php esc_html_e( 'Emp. ID', 'rsyi-hr' ); ?></label>
                        <input type="text" name="employee_number" id="emp-number" placeholder="EMP-001">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-full-name"><?php esc_html_e( 'Name (EN) *', 'rsyi-hr' ); ?></label>
                        <input type="text" name="full_name" id="emp-full-name" required
                               placeholder="<?php esc_attr_e( 'Full name in English', 'rsyi-hr' ); ?>">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-full-name-ar"><?php esc_html_e( 'الاسم (AR)', 'rsyi-hr' ); ?></label>
                        <input type="text" name="full_name_ar" id="emp-full-name-ar"
                               placeholder="الاسم بالعربية" dir="rtl">
                    </div>
                </div>

                <div class="rsyi-hr-form-cols-3">
                    <div class="rsyi-hr-form-row">
                        <label for="emp-national-id">
                            <?php esc_html_e( 'National ID / الرقم القومي', 'rsyi-hr' ); ?>
                        </label>
                        <input type="text" name="national_id" id="emp-national-id" maxlength="20">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-dob">
                            <?php esc_html_e( 'Date of Birth / تاريخ الميلاد', 'rsyi-hr' ); ?>
                        </label>
                        <input type="date" name="date_of_birth" id="emp-dob">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'Age / السن', 'rsyi-hr' ); ?></label>
                        <input type="text" id="emp-age-display" readonly
                               class="rsyi-hr-readonly"
                               placeholder="<?php esc_attr_e( 'Auto-calculated / تلقائي', 'rsyi-hr' ); ?>">
                    </div>
                </div>

                <!-- Birth breakdown (auto-filled) -->
                <div class="rsyi-hr-form-cols-3">
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'Year / سنة الميلاد', 'rsyi-hr' ); ?></label>
                        <input type="text" id="emp-birth-year" readonly class="rsyi-hr-readonly">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'Month / الشهر', 'rsyi-hr' ); ?></label>
                        <input type="text" id="emp-birth-month" readonly class="rsyi-hr-readonly">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'Day / اليوم', 'rsyi-hr' ); ?></label>
                        <input type="text" id="emp-birth-day" readonly class="rsyi-hr-readonly">
                    </div>
                </div>
            </div>

            <!-- ══ Section 2: Work / بيانات العمل ═══════════════════════════ -->
            <div class="rsyi-hr-form-section">
                <h3 class="rsyi-hr-section-title">
                    <span class="dashicons dashicons-building"></span>
                    <?php esc_html_e( 'Work Info / بيانات العمل', 'rsyi-hr' ); ?>
                </h3>

                <div class="rsyi-hr-form-cols-3">
                    <div class="rsyi-hr-form-row">
                        <label for="emp-department"><?php esc_html_e( 'Department / القسم', 'rsyi-hr' ); ?></label>
                        <select name="department_id" id="emp-department">
                            <option value=""><?php esc_html_e( '— Select / اختر —', 'rsyi-hr' ); ?></option>
                            <?php foreach ( $departments as $d ) : ?>
                                <option value="<?php echo esc_attr( $d['id'] ); ?>">
                                    <?php echo esc_html( $d['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-job-title"><?php esc_html_e( 'Position / الوظيفة', 'rsyi-hr' ); ?></label>
                        <select name="job_title_id" id="emp-job-title">
                            <option value=""><?php esc_html_e( '— Select / اختر —', 'rsyi-hr' ); ?></option>
                            <?php foreach ( $job_titles as $jt ) : ?>
                                <option value="<?php echo esc_attr( $jt['id'] ); ?>">
                                    <?php echo esc_html( $jt['title'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-grade"><?php esc_html_e( 'Grade / الدرجة الوظيفية', 'rsyi-hr' ); ?></label>
                        <input type="text" name="grade" id="emp-grade"
                               placeholder="<?php esc_attr_e( 'e.g. Grade 5', 'rsyi-hr' ); ?>">
                    </div>
                </div>

                <div class="rsyi-hr-form-cols-3">
                    <div class="rsyi-hr-form-row">
                        <label for="emp-hire-date"><?php esc_html_e( 'Hire Date / تاريخ التعيين', 'rsyi-hr' ); ?></label>
                        <input type="date" name="hire_date" id="emp-hire-date">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-contract-start"><?php esc_html_e( 'Contract Start / بداية العقد', 'rsyi-hr' ); ?></label>
                        <input type="date" name="contract_start" id="emp-contract-start">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-contract-end"><?php esc_html_e( 'Contract End / نهاية العقد', 'rsyi-hr' ); ?></label>
                        <input type="date" name="contract_end" id="emp-contract-end">
                    </div>
                </div>

                <div class="rsyi-hr-form-cols-3">
                    <div class="rsyi-hr-form-row">
                        <label for="emp-contract-type"><?php esc_html_e( 'Contract Type / نوع العقد', 'rsyi-hr' ); ?></label>
                        <select name="contract_type" id="emp-contract-type">
                            <option value=""><?php esc_html_e( '— Select / اختر —', 'rsyi-hr' ); ?></option>
                            <option value="permanent"><?php esc_html_e( 'Permanent / دائم', 'rsyi-hr' ); ?></option>
                            <option value="temporary"><?php esc_html_e( 'Temporary / مؤقت', 'rsyi-hr' ); ?></option>
                            <option value="part_time"><?php esc_html_e( 'Part-time / جزء وقت', 'rsyi-hr' ); ?></option>
                            <option value="project"><?php esc_html_e( 'Project / مشروع', 'rsyi-hr' ); ?></option>
                        </select>
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label><?php esc_html_e( 'Total Years / إجمالي السنوات', 'rsyi-hr' ); ?></label>
                        <input type="text" id="emp-total-years" readonly class="rsyi-hr-readonly"
                               placeholder="<?php esc_attr_e( 'Auto-calculated / تلقائي', 'rsyi-hr' ); ?>">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-status"><?php esc_html_e( 'Status / الحالة', 'rsyi-hr' ); ?></label>
                        <select name="status" id="emp-status">
                            <option value="active"><?php esc_html_e( 'Active / نشط', 'rsyi-hr' ); ?></option>
                            <option value="inactive"><?php esc_html_e( 'Inactive / غير نشط', 'rsyi-hr' ); ?></option>
                            <option value="on_leave"><?php esc_html_e( 'On Leave / في إجازة', 'rsyi-hr' ); ?></option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ══ Section 3: Personal / المعلومات الشخصية ══════════════════ -->
            <div class="rsyi-hr-form-section">
                <h3 class="rsyi-hr-section-title">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php esc_html_e( 'Personal Info / المعلومات الشخصية', 'rsyi-hr' ); ?>
                </h3>

                <div class="rsyi-hr-form-cols-3">
                    <div class="rsyi-hr-form-row">
                        <label for="emp-marital"><?php esc_html_e( 'Marital Status / الحالة الاجتماعية', 'rsyi-hr' ); ?></label>
                        <select name="marital_status" id="emp-marital">
                            <option value=""><?php esc_html_e( '— Select / اختر —', 'rsyi-hr' ); ?></option>
                            <option value="single"><?php esc_html_e( 'Single / أعزب', 'rsyi-hr' ); ?></option>
                            <option value="married"><?php esc_html_e( 'Married / متزوج', 'rsyi-hr' ); ?></option>
                            <option value="divorced"><?php esc_html_e( 'Divorced / مطلق', 'rsyi-hr' ); ?></option>
                            <option value="widowed"><?php esc_html_e( 'Widowed / أرمل', 'rsyi-hr' ); ?></option>
                        </select>
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-religion"><?php esc_html_e( 'Religion / الديانة', 'rsyi-hr' ); ?></label>
                        <select name="religion" id="emp-religion">
                            <option value=""><?php esc_html_e( '— Select / اختر —', 'rsyi-hr' ); ?></option>
                            <option value="muslim"><?php esc_html_e( 'Muslim / مسلم', 'rsyi-hr' ); ?></option>
                            <option value="christian"><?php esc_html_e( 'Christian / مسيحي', 'rsyi-hr' ); ?></option>
                            <option value="other"><?php esc_html_e( 'Other / أخرى', 'rsyi-hr' ); ?></option>
                        </select>
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-military"><?php esc_html_e( 'Military Status / موقف التجنيد', 'rsyi-hr' ); ?></label>
                        <select name="military_status" id="emp-military">
                            <option value=""><?php esc_html_e( '— Select / اختر —', 'rsyi-hr' ); ?></option>
                            <option value="completed"><?php esc_html_e( 'Completed / أتم الخدمة', 'rsyi-hr' ); ?></option>
                            <option value="exempt"><?php esc_html_e( 'Exempt / معفى', 'rsyi-hr' ); ?></option>
                            <option value="pending"><?php esc_html_e( 'Pending / لم يؤدِ', 'rsyi-hr' ); ?></option>
                            <option value="not_applicable"><?php esc_html_e( 'N/A / لا ينطبق', 'rsyi-hr' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="rsyi-hr-form-cols-3">
                    <div class="rsyi-hr-form-row">
                        <label for="emp-education"><?php esc_html_e( 'Education / المؤهل الدراسي', 'rsyi-hr' ); ?></label>
                        <select name="education" id="emp-education">
                            <option value=""><?php esc_html_e( '— Select / اختر —', 'rsyi-hr' ); ?></option>
                            <option value="elementary"><?php esc_html_e( 'Elementary / ابتدائي', 'rsyi-hr' ); ?></option>
                            <option value="middle"><?php esc_html_e( 'Middle School / إعدادي', 'rsyi-hr' ); ?></option>
                            <option value="high_school"><?php esc_html_e( 'High School / ثانوي', 'rsyi-hr' ); ?></option>
                            <option value="diploma"><?php esc_html_e( 'Diploma / دبلوم', 'rsyi-hr' ); ?></option>
                            <option value="bachelor"><?php esc_html_e( 'Bachelor / بكالوريوس', 'rsyi-hr' ); ?></option>
                            <option value="master"><?php esc_html_e( 'Master / ماجستير', 'rsyi-hr' ); ?></option>
                            <option value="doctorate"><?php esc_html_e( 'Doctorate / دكتوراه', 'rsyi-hr' ); ?></option>
                        </select>
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-phone"><?php esc_html_e( 'Telephone No. / الهاتف', 'rsyi-hr' ); ?></label>
                        <input type="tel" name="phone" id="emp-phone">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-email"><?php esc_html_e( 'Email / البريد الإلكتروني', 'rsyi-hr' ); ?></label>
                        <input type="email" name="email" id="emp-email">
                    </div>
                </div>

                <div class="rsyi-hr-form-cols-2">
                    <div class="rsyi-hr-form-row">
                        <label for="emp-housing"><?php esc_html_e( 'Housing / السكن', 'rsyi-hr' ); ?></label>
                        <input type="text" name="housing" id="emp-housing"
                               placeholder="<?php esc_attr_e( 'Housing type or address', 'rsyi-hr' ); ?>">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-insurance"><?php esc_html_e( 'Insurance No. / الرقم التأميني', 'rsyi-hr' ); ?></label>
                        <input type="text" name="insurance_number" id="emp-insurance">
                    </div>
                </div>
            </div>

            <!-- ══ Section 4: Banking / البيانات البنكية ═════════════════════ -->
            <div class="rsyi-hr-form-section">
                <h3 class="rsyi-hr-section-title">
                    <span class="dashicons dashicons-money-alt"></span>
                    <?php esc_html_e( 'Banking / البيانات البنكية', 'rsyi-hr' ); ?>
                </h3>

                <div class="rsyi-hr-form-cols-2">
                    <div class="rsyi-hr-form-row">
                        <label for="emp-bank-name"><?php esc_html_e( 'Bank Name / اسم البنك', 'rsyi-hr' ); ?></label>
                        <input type="text" name="bank_name" id="emp-bank-name">
                    </div>
                    <div class="rsyi-hr-form-row">
                        <label for="emp-bank-account"><?php esc_html_e( 'Bank Account No. / رقم الحساب', 'rsyi-hr' ); ?></label>
                        <input type="text" name="bank_account" id="emp-bank-account">
                    </div>
                </div>
            </div>

            <!-- ══ Section 5: Notes / ملاحظات ═══════════════════════════════ -->
            <div class="rsyi-hr-form-section">
                <div class="rsyi-hr-form-row">
                    <label for="emp-notes"><?php esc_html_e( 'Notes / ملاحظات', 'rsyi-hr' ); ?></label>
                    <textarea name="notes" id="emp-notes" rows="3"></textarea>
                </div>
            </div>

            <div class="rsyi-hr-form-actions">
                <button type="submit" class="button button-primary button-large">
                    <?php esc_html_e( 'Save / حفظ', 'rsyi-hr' ); ?>
                </button>
                <button type="button" class="button button-large rsyi-hr-modal-close">
                    <?php esc_html_e( 'Cancel / إلغاء', 'rsyi-hr' ); ?>
                </button>
            </div>
        </form>
    </div>
</div>
