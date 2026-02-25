/* global rsyiHR, jQuery */
(function ($) {
    'use strict';

    const HR = window.rsyiHR || {};
    const ajaxUrl = HR.ajaxUrl || '';
    const nonce   = HR.nonce   || '';
    const i18n    = HR.i18n   || {};

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    function ajax(action, data, callback) {
        $.post(ajaxUrl, Object.assign({ action: action, nonce: nonce }, data))
            .done(function (res) {
                if (res.success) {
                    callback(null, res.data);
                } else {
                    callback(res.data && res.data.message ? res.data.message : i18n.error);
                }
            })
            .fail(function () { callback(i18n.error); });
    }

    function notice(msg, type) {
        type = type || 'success';
        var $n = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + msg + '</p></div>');
        $('.rsyi-hr-wrap h1').after($n);
        setTimeout(function () { $n.fadeOut(400, function () { $(this).remove(); }); }, 3500);
    }

    function statusBadge(status) {
        var labels = { active: i18n.active || 'نشط', inactive: i18n.inactive || 'غير نشط', on_leave: i18n.on_leave || 'في إجازة' };
        return '<span class="rsyi-hr-badge rsyi-hr-badge-' + status + '">' + (labels[status] || status) + '</span>';
    }

    /* ══ MODAL HELPERS ══════════════════════════════════════════════════════ */

    /**
     * فتح الـ modal مع الحفاظ على display: flex للتوسيط الصحيح.
     * jQuery's fadeIn() يضع display: block افتراضياً مما يكسر التوسيط.
     */
    function openModal(id) {
        $(id).css({ display: 'flex', opacity: 0 }).animate({ opacity: 1 }, 150);
    }

    function closeModal(id) {
        $(id).animate({ opacity: 0 }, 150, function () {
            $(this).css('display', 'none');
        });
    }

    $(document).on('click', '.rsyi-hr-modal-close', function () {
        closeModal('#' + $(this).closest('.rsyi-hr-modal').attr('id'));
    });
    $(document).on('click', '.rsyi-hr-modal', function (e) {
        if ($(e.target).hasClass('rsyi-hr-modal')) {
            closeModal('#' + $(this).attr('id'));
        }
    });

    /* ══ EMPLOYEES — Helpers ════════════════════════════════════════════════ */

    /** Calculate age in years from a date string (YYYY-MM-DD) */
    function calcAge(dobStr) {
        if (!dobStr) return '';
        var dob   = new Date(dobStr);
        var today = new Date();
        var age   = today.getFullYear() - dob.getFullYear();
        var m     = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) { age--; }
        return age >= 0 ? age : '';
    }

    /** Calculate total years of service from hire_date */
    function calcTotalYears(hireDateStr) {
        if (!hireDateStr) return '';
        var hire  = new Date(hireDateStr);
        var today = new Date();
        var yrs   = today.getFullYear() - hire.getFullYear();
        var m     = today.getMonth() - hire.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < hire.getDate())) { yrs--; }
        return yrs >= 0 ? yrs : 0;
    }

    /** Fill age + birth breakdown fields from DOB input */
    function updateDobFields(dobStr) {
        var age = calcAge(dobStr);
        $('#emp-age-display').val(age !== '' ? age + ' ' + (i18n.years || 'yrs') : '');
        if (dobStr) {
            var parts = dobStr.split('-');
            $('#emp-birth-year').val(parts[0] || '');
            $('#emp-birth-month').val(parts[1] || '');
            $('#emp-birth-day').val(parts[2] || '');
        } else {
            $('#emp-birth-year, #emp-birth-month, #emp-birth-day').val('');
        }
    }

    /** Fill total years from hire date */
    function updateHireFields(hireDateStr) {
        var yrs = calcTotalYears(hireDateStr);
        $('#emp-total-years').val(yrs !== '' ? yrs + ' ' + (i18n.years || 'yrs') : '');
    }

    /** placeholder option مشترك */
    var PLACEHOLDER_OPTION = '<option value="">\u2014 Select / \u0627\u062e\u062a\u0631 \u2014</option>';

    /**
     * تعبئة قائمة الأقسام من البيانات المحقونة في الصفحة (بدون AJAX).
     * البيانات تُحمَّل مرة واحدة مع الصفحة عبر wp_localize_script
     * فلا توجد أي صلاحيات أو طلبات شبكة قد تفشل.
     *
     * @param {Function} [callback] تُستدعى بعد تحديث القائمة
     */
    function reloadDepartments(callback) {
        var $dept = $('#emp-department');
        if (!$dept.length) { if (callback) { callback(); } return; }

        var rows = HR.departments || [];

        // فقط نكتب فوق الـ options لو عندنا بيانات فعلية
        if (rows.length > 0) {
            var opts = PLACEHOLDER_OPTION + rows.map(function (d) {
                return '<option value="' + d.id + '">' + d.name + '</option>';
            }).join('');
            $dept.html(opts);
        }
        // لو HR.departments فارغة، نحتفظ بالـ options اللي رسمها PHP

        if (callback) { callback(); }
    }

    /**
     * تحميل الوظائف ديناميكياً حسب القسم المختار.
     * - deptId = 0  → كل الوظائف النشطة
     * - deptId > 0  → وظائف القسم + الوظائف غير المرتبطة بقسم
     * @param {number}   deptId    معرِّف القسم (0 = كل الأقسام)
     * @param {Function} [callback] تُستدعى بعد تحديث القائمة
     */
    function reloadJobTitles(deptId, callback) {
        var $jt = $('#emp-job-title');
        if (!$jt.length) { if (callback) { callback(); } return; }

        ajax('rsyi_hr_get_job_titles', { department_id: deptId || 0, status: 'active' }, function (err, rows) {
            if (err) { if (callback) { callback(); } return; }

            var opts = PLACEHOLDER_OPTION + (rows || []).map(function (jt) {
                return '<option value="' + jt.id + '">' + jt.title + '</option>';
            }).join('');

            $jt.html(opts);
            if (callback) { callback(); }
        });
    }

    /* ══ EMPLOYEES ══════════════════════════════════════════════════════════ */

    function loadEmployees() {
        var $tbody = $('#rsyi-hr-employees-body');
        if (!$tbody.length) return;

        $tbody.html('<tr><td colspan="8" class="rsyi-hr-loading">' + (i18n.loading || 'Loading…') + '</td></tr>');

        ajax('rsyi_hr_get_employees', {
            status:        $('#rsyi-hr-filter-status').val() || 'all',
            department_id: $('#rsyi-hr-filter-dept').val()   || 0,
            search:        $('#rsyi-hr-search-emp').val()    || '',
        }, function (err, rows) {
            if (err) { notice(err, 'error'); return; }
            if (!rows || !rows.length) {
                $tbody.html('<tr><td colspan="8">' + (i18n.no_results || 'No results.') + '</td></tr>');
                return;
            }
            var html = rows.map(function (r, idx) {
                var nameDisplay = r.full_name || '';
                if (r.full_name_ar) { nameDisplay += '<br><small style="color:#666">' + r.full_name_ar + '</small>'; }
                return '<tr>' +
                    '<td>' + (idx + 1) + '</td>' +
                    '<td>' + (r.employee_number || '—') + '</td>' +
                    '<td>' + nameDisplay + '</td>' +
                    '<td>' + (r.department_name || '—') + '</td>' +
                    '<td>' + (r.job_title_name  || '—') + '</td>' +
                    '<td>' + statusBadge(r.status) + '</td>' +
                    '<td>' + (r.phone || '—') + '</td>' +
                    '<td>' +
                        '<button class="button button-small rsyi-hr-edit-emp" data-id="' + r.id + '">' + (i18n.edit || 'Edit') + '</button> ' +
                        '<button class="button button-small rsyi-hr-delete-emp" data-id="' + r.id + '">' + (i18n.delete || 'Delete') + '</button>' +
                    '</td>' +
                '</tr>';
            }).join('');
            $tbody.html(html);
        });
    }

    // Live DOB calculation
    $(document).on('change', '#emp-dob', function () {
        updateDobFields($(this).val());
    });

    // Live hire date total-years calculation
    $(document).on('change', '#emp-hire-date', function () {
        updateHireFields($(this).val());
    });

    // عند تغيير القسم → أعد تحميل قائمة الوظائف المرتبطة به
    $(document).on('change', '#emp-department', function () {
        reloadJobTitles($(this).val());
    });

    // فتح Modal إضافة موظف
    $(document).on('click', '.rsyi-hr-btn-add-employee', function () {
        $('#rsyi-hr-employee-form')[0].reset();
        $('#emp-id').val('');
        $('#emp-age-display, #emp-birth-year, #emp-birth-month, #emp-birth-day, #emp-total-years').val('');
        $('#rsyi-hr-employee-modal-title').text(i18n.add_employee || 'Add Employee');
        openModal('#rsyi-hr-employee-modal');
        // تحميل الأقسام والوظائف من DB مباشرة عند فتح الـ modal
        reloadDepartments();
        reloadJobTitles(0);
    });

    // فتح Modal تعديل موظف
    $(document).on('click', '.rsyi-hr-edit-emp', function () {
        var id = $(this).data('id');
        ajax('rsyi_hr_get_employee', { id: id }, function (err, emp) {
            if (err) { notice(err, 'error'); return; }

            // Identity
            $('#emp-id').val(emp.id);
            $('#emp-number').val(emp.employee_number);
            $('#emp-full-name').val(emp.full_name);
            $('#emp-full-name-ar').val(emp.full_name_ar);
            $('#emp-national-id').val(emp.national_id);
            $('#emp-dob').val(emp.date_of_birth);
            updateDobFields(emp.date_of_birth);

            // Work الأخرى (غير القسم والوظيفة)
            $('#emp-grade').val(emp.grade);
            $('#emp-hire-date').val(emp.hire_date);
            updateHireFields(emp.hire_date);
            $('#emp-contract-start').val(emp.contract_start);
            $('#emp-contract-end').val(emp.contract_end);
            $('#emp-contract-type').val(emp.contract_type);
            $('#emp-status').val(emp.status);

            // Personal
            $('#emp-marital').val(emp.marital_status);
            $('#emp-religion').val(emp.religion);
            $('#emp-military').val(emp.military_status);
            $('#emp-education').val(emp.education);
            $('#emp-phone').val(emp.phone);
            $('#emp-email').val(emp.email);
            $('#emp-housing').val(emp.housing);
            $('#emp-insurance').val(emp.insurance_number);

            // Banking
            $('#emp-bank-name').val(emp.bank_name);
            $('#emp-bank-account').val(emp.bank_account);

            // Notes
            $('#emp-notes').val(emp.notes);

            $('#rsyi-hr-employee-modal-title').text(i18n.edit_employee || 'Edit Employee');
            openModal('#rsyi-hr-employee-modal');

            // تحميل الأقسام من DB أولاً ثم تحديد القسم، ثم تحميل الوظائف وتحديدها
            reloadDepartments(function () {
                $('#emp-department').val(emp.department_id);
                reloadJobTitles(emp.department_id, function () {
                    $('#emp-job-title').val(emp.job_title_id);
                });
            });
        });
    });

    // حفظ موظف
    $(document).on('submit', '#rsyi-hr-employee-form', function (e) {
        e.preventDefault();
        var data = {};
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        ajax('rsyi_hr_save_employee', data, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-employee-modal');
            notice(i18n.saved || 'تم الحفظ بنجاح.');
            loadEmployees();
        });
    });

    // حذف موظف
    $(document).on('click', '.rsyi-hr-delete-emp', function () {
        if (!confirm(i18n.confirm_delete)) return;
        ajax('rsyi_hr_delete_employee', { id: $(this).data('id') }, function (err) {
            if (err) { notice(err, 'error'); return; }
            loadEmployees();
        });
    });

    // فلاتر الموظفين
    $(document).on('change', '#rsyi-hr-filter-status, #rsyi-hr-filter-dept', loadEmployees);
    var empSearchTimer;
    $(document).on('input', '#rsyi-hr-search-emp', function () {
        clearTimeout(empSearchTimer);
        empSearchTimer = setTimeout(loadEmployees, 400);
    });

    /* ══ DEPARTMENTS ════════════════════════════════════════════════════════ */

    // فتح Modal إضافة قسم
    $(document).on('click', '.rsyi-hr-btn-add-dept', function () {
        $('#rsyi-hr-dept-form')[0].reset();
        $('#dept-id').val('');
        $('#rsyi-hr-dept-modal-title').text('إضافة قسم');
        openModal('#rsyi-hr-dept-modal');
    });

    // تعديل قسم
    $(document).on('click', '.rsyi-hr-edit-dept', function () {
        var id   = $(this).data('id');
        ajax('rsyi_hr_get_departments', {}, function (err, rows) {
            if (err) { notice(err, 'error'); return; }
            var dept = rows.find(function (r) { return String(r.id) === String(id); });
            if (!dept) return;
            $('#dept-id').val(dept.id);
            $('#dept-name').val(dept.name);
            $('#dept-code').val(dept.code);
            $('#dept-parent').val(dept.parent_id);
            $('#dept-manager').val(dept.manager_id);
            $('#dept-description').val(dept.description);
            $('#dept-status').val(dept.status);
            $('#rsyi-hr-dept-modal-title').text('تعديل قسم');
            openModal('#rsyi-hr-dept-modal');
        });
    });

    // حفظ قسم
    $(document).on('submit', '#rsyi-hr-dept-form', function (e) {
        e.preventDefault();
        var data = {};
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        ajax('rsyi_hr_save_department', data, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-dept-modal');
            notice(i18n.saved || 'تم الحفظ بنجاح.');
            setTimeout(function () { location.reload(); }, 800);
        });
    });

    // حذف قسم
    $(document).on('click', '.rsyi-hr-delete-dept', function () {
        if (!confirm(i18n.confirm_delete)) return;
        ajax('rsyi_hr_delete_department', { id: $(this).data('id') }, function (err) {
            if (err) { notice(err, 'error'); return; }
            setTimeout(function () { location.reload(); }, 400);
        });
    });

    /* ══ JOB TITLES ═════════════════════════════════════════════════════════ */

    // فتح Modal إضافة وظيفة
    $(document).on('click', '.rsyi-hr-btn-add-jt', function () {
        $('#rsyi-hr-jt-form')[0].reset();
        $('#jt-id').val('');
        $('#rsyi-hr-jt-modal-title').text('إضافة وظيفة');
        openModal('#rsyi-hr-jt-modal');
    });

    // تعديل وظيفة
    $(document).on('click', '.rsyi-hr-edit-jt', function () {
        var id = $(this).data('id');
        ajax('rsyi_hr_get_job_titles', {}, function (err, rows) {
            if (err) { notice(err, 'error'); return; }
            var jt = rows.find(function (r) { return String(r.id) === String(id); });
            if (!jt) return;
            $('#jt-id').val(jt.id);
            $('#jt-title').val(jt.title);
            $('#jt-code').val(jt.code);
            $('#jt-department').val(jt.department_id);
            $('#jt-grade').val(jt.grade);
            $('#jt-description').val(jt.description);
            $('#jt-status').val(jt.status);
            $('#rsyi-hr-jt-modal-title').text('تعديل وظيفة');
            openModal('#rsyi-hr-jt-modal');
        });
    });

    // حفظ وظيفة
    $(document).on('submit', '#rsyi-hr-jt-form', function (e) {
        e.preventDefault();
        var data = {};
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        ajax('rsyi_hr_save_job_title', data, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-jt-modal');
            notice(i18n.saved || 'تم الحفظ بنجاح.');
            setTimeout(function () { location.reload(); }, 800);
        });
    });

    // حذف وظيفة
    $(document).on('click', '.rsyi-hr-delete-jt', function () {
        if (!confirm(i18n.confirm_delete)) return;
        ajax('rsyi_hr_delete_job_title', { id: $(this).data('id') }, function (err) {
            if (err) { notice(err, 'error'); return; }
            setTimeout(function () { location.reload(); }, 400);
        });
    });

    /* ══ Init ════════════════════════════════════════════════════════════════ */
    $(function () {
        loadEmployees();
    });

}(jQuery));
