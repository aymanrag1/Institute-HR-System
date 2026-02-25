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

    function openModal(id) { $(id).fadeIn(150); }
    function closeModal(id) { $(id).fadeOut(150); }

    $(document).on('click', '.rsyi-hr-modal-close', function () {
        $(this).closest('.rsyi-hr-modal').fadeOut(150);
    });
    $(document).on('click', '.rsyi-hr-modal', function (e) {
        if ($(e.target).hasClass('rsyi-hr-modal')) { $(this).fadeOut(150); }
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

    // فتح Modal إضافة موظف
    $(document).on('click', '.rsyi-hr-btn-add-employee', function () {
        $('#rsyi-hr-employee-form')[0].reset();
        $('#emp-id').val('');
        $('#emp-age-display, #emp-birth-year, #emp-birth-month, #emp-birth-day, #emp-total-years').val('');
        $('#rsyi-hr-employee-modal-title').text(i18n.add_employee || 'Add Employee');
        openModal('#rsyi-hr-employee-modal');
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

            // Work
            $('#emp-department').val(emp.department_id);
            $('#emp-job-title').val(emp.job_title_id);
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
        var $row = $(this).closest('tr');
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

    /* ══ LEAVES ══════════════════════════════════════════════════════════════ */

    var leaveSigPad = null;
    var currentLeaveId = null;

    var statusBadgeLeave = {
        pending:          '<span class="rsyi-hr-badge" style="background:#fef3c7;color:#92400e;">' + (i18n.pending          || 'قيد الانتظار') + '</span>',
        manager_approved: '<span class="rsyi-hr-badge" style="background:#dbeafe;color:#1e40af;">' + (i18n.manager_approved || 'اعتمد المدير') + '</span>',
        hr_approved:      '<span class="rsyi-hr-badge" style="background:#e0f2fe;color:#0369a1;">' + (i18n.hr_approved      || 'اعتمد الموارد البشرية') + '</span>',
        dean_approved:    '<span class="rsyi-hr-badge rsyi-hr-badge-active">'                       + (i18n.dean_approved    || 'معتمد نهائياً') + '</span>',
        rejected:         '<span class="rsyi-hr-badge rsyi-hr-badge-inactive">'                     + (i18n.rejected         || 'مرفوض') + '</span>',
    };
    var leaveTypeLabels = { annual: 'اعتيادية', emergency: 'عارضة', sick: 'مرضى', unpaid: 'بدون مرتب' };

    function loadLeaves() {
        var $tbody = $('#rsyi-hr-leaves-body');
        if (!$tbody.length) { return; }
        $tbody.html('<tr><td colspan="7" class="rsyi-hr-loading">' + (i18n.loading || 'جارٍ التحميل...') + '</td></tr>');
        ajax('rsyi_hr_get_leaves', {
            status:      $('#rsyi-hr-filter-leave-status').val() || '',
            employee_id: $('#rsyi-hr-filter-leave-emp').val()    || 0,
        }, function (err, rows) {
            if (err) { notice(err, 'error'); return; }
            if (!rows || !rows.length) { $tbody.html('<tr><td colspan="7">' + (i18n.no_results || 'لا توجد نتائج.') + '</td></tr>'); return; }
            var html = rows.map(function (r) {
                return '<tr>' +
                    '<td>' + esc(r.full_name) + '</td>' +
                    '<td>' + (leaveTypeLabels[r.leave_type] || r.leave_type) + '</td>' +
                    '<td>' + esc(r.from_date) + '</td>' +
                    '<td>' + esc(r.to_date) + '</td>' +
                    '<td>' + (r.total_days || '—') + '</td>' +
                    '<td>' + (statusBadgeLeave[r.status] || r.status) + '</td>' +
                    '<td><button class="button button-small rsyi-hr-view-leave" data-id="' + r.id + '">' + (i18n.approve || 'مراجعة') + '</button></td>' +
                '</tr>';
            }).join('');
            $tbody.html(html);
        });
    }

    $(document).on('change', '#rsyi-hr-filter-leave-status, #rsyi-hr-filter-leave-emp', loadLeaves);

    $(document).on('click', '.rsyi-hr-view-leave', function () {
        currentLeaveId = $(this).data('id');
        ajax('rsyi_hr_get_leave', { id: currentLeaveId }, function (err, leave) {
            if (err) { notice(err, 'error'); return; }

            var typeLabel  = leaveTypeLabels[leave.leave_type] || leave.leave_type;
            var statusInfo = statusBadgeLeave[leave.status] || leave.status;

            var detail = '<table class="widefat" style="margin-bottom:12px;">' +
                '<tr><th>الموظف</th><td>' + esc(leave.full_name) + (leave.full_name_ar ? ' / ' + esc(leave.full_name_ar) : '') + '</td></tr>' +
                '<tr><th>الوظيفة</th><td>' + esc(leave.job_title_name || '—') + '</td></tr>' +
                '<tr><th>نوع الإجازة</th><td>' + typeLabel + '</td></tr>' +
                '<tr><th>من</th><td>' + esc(leave.from_date) + '</td></tr>' +
                '<tr><th>حتى</th><td>' + esc(leave.to_date) + '</td></tr>' +
                '<tr><th>عدد الأيام</th><td>' + (leave.total_days || '—') + '</td></tr>' +
                '<tr><th>عودة للعمل</th><td>' + (leave.return_date || '—') + '</td></tr>' +
                '<tr><th>القائم بالعمل</th><td>' + esc(leave.substitute_name || '—') + '</td></tr>' +
                '<tr><th>الحالة</th><td>' + statusInfo + '</td></tr>' +
                '</table>';

            if (leave.employee_signature) {
                detail += '<p><strong>توقيع الموظف:</strong><br><img src="' + leave.employee_signature + '" style="max-width:200px;border:1px solid #ddd;background:#fff;"></p>';
            }
            if (leave.manager_signature) {
                detail += '<p><strong>توقيع المدير:</strong><br><img src="' + leave.manager_signature + '" style="max-width:200px;border:1px solid #ddd;background:#fff;"></p>';
            }
            if (leave.hr_signature) {
                detail += '<p><strong>توقيع مدير الموارد البشرية:</strong><br><img src="' + leave.hr_signature + '" style="max-width:200px;border:1px solid #ddd;background:#fff;"></p>';
            }
            if (leave.dean_signature) {
                detail += '<p><strong>توقيع العميد (تصديق):</strong><br><img src="' + leave.dean_signature + '" style="max-width:200px;border:1px solid #ddd;background:#fff;"></p>';
            }

            $('#rsyi-hr-leave-detail').html(detail);

            var canApprove = (
                (leave.status === 'pending'          && i18n.can_manage_leaves === '1') ||
                (leave.status === 'manager_approved' && i18n.can_manage_leaves === '1') ||
                (leave.status === 'hr_approved'      && i18n.can_approve_dean  === '1')
            );
            $('#rsyi-hr-leave-approve-section').toggle(canApprove);
            $('#rsyi-hr-leave-print-section').toggle(leave.status === 'dean_approved');
            $('#rsyi-hr-leave-reject-form').hide();

            // Init signature pad
            if (canApprove) {
                if (!leaveSigPad) {
                    leaveSigPad = new SignaturePad(document.getElementById('rsyi-hr-leave-approve-canvas'));
                } else {
                    leaveSigPad.clear();
                }
            }

            // Store leave for print
            $('#rsyi-hr-leave-modal').data('leave', leave);
            openModal('#rsyi-hr-leave-modal');
        });
    });

    $(document).on('click', '#rsyi-hr-leave-sig-clear', function () {
        if (leaveSigPad) { leaveSigPad.clear(); }
    });

    $(document).on('click', '#rsyi-hr-leave-approve-btn', function () {
        if (!currentLeaveId) { return; }
        var sig = (leaveSigPad && !leaveSigPad.isEmpty()) ? leaveSigPad.toDataURL() : '';
        ajax('rsyi_hr_approve_leave', { id: currentLeaveId, signature: sig }, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-leave-modal');
            notice(i18n.saved);
            loadLeaves();
        });
    });

    $(document).on('click', '#rsyi-hr-leave-reject-btn', function () {
        $('#rsyi-hr-leave-reject-form').slideToggle();
    });

    $(document).on('click', '#rsyi-hr-leave-reject-confirm', function () {
        if (!currentLeaveId) { return; }
        var reason = $('#rsyi-hr-leave-reject-reason').val();
        ajax('rsyi_hr_reject_leave', { id: currentLeaveId, reason: reason }, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-leave-modal');
            notice(i18n.saved);
            loadLeaves();
        });
    });

    // Print leave form
    $(document).on('click', '#rsyi-hr-leave-print-btn', function () {
        var leave = $('#rsyi-hr-leave-modal').data('leave');
        if (!leave) { return; }

        var typeLabels = { annual: 'اعتيادية', emergency: 'عارضة', sick: 'مرضى', unpaid: 'بدون مرتب' };

        var sigEmp  = leave.employee_signature ? '<img src="' + leave.employee_signature + '" class="rsyi-leave-print-sig-img">' : '......................';
        var sigMgr  = leave.manager_signature  ? '<img src="' + leave.manager_signature  + '" class="rsyi-leave-print-sig-img">' : '......................';
        var sigHR   = leave.hr_signature       ? '<img src="' + leave.hr_signature       + '" class="rsyi-leave-print-sig-img">' : '......................';
        var sigDean = leave.dean_signature     ? '<img src="' + leave.dean_signature     + '" class="rsyi-leave-print-sig-img">' : '';

        var html = '<div class="rsyi-leave-print-wrapper">' +
            '<div class="rsyi-leave-print-title">طلب أجازه</div>' +
            '<div class="rsyi-leave-print-row"><span class="rsyi-leave-print-label">الاسم:</span><span class="rsyi-leave-print-dots"></span><span>' + esc(leave.full_name) + '</span></div>' +
            '<div class="rsyi-leave-print-row"><span class="rsyi-leave-print-label">الوظيفة:</span><span class="rsyi-leave-print-dots"></span><span>' + esc(leave.job_title_name || '') + '</span></div>' +
            '<div class="rsyi-leave-print-row"><span class="rsyi-leave-print-label">نوع الإجازة:</span><span class="rsyi-leave-print-dots"></span><span>' + (typeLabels[leave.leave_type] || leave.leave_type) + '</span></div>' +
            '<div class="rsyi-leave-print-row"><span class="rsyi-leave-print-label">من يوم:</span><span class="rsyi-leave-print-dots"></span><span>' + esc(leave.from_date) + '</span></div>' +
            '<div class="rsyi-leave-print-row"><span class="rsyi-leave-print-label">حتى يوم:</span><span class="rsyi-leave-print-dots"></span><span>' + esc(leave.to_date) + '</span></div>' +
            '<div class="rsyi-leave-print-row"><span class="rsyi-leave-print-label">عودة إلى العمل يوم:</span><span class="rsyi-leave-print-dots"></span><span>' + esc(leave.return_date || '') + '</span></div>' +
            '<div class="rsyi-leave-print-row"><span class="rsyi-leave-print-label">آخر يوم إجازة:</span><span class="rsyi-leave-print-dots"></span><span>' + esc(leave.last_day_before || '') + '</span></div>' +
            '<div class="rsyi-leave-print-row"><span class="rsyi-leave-print-label">القائم بالعمل:</span><span class="rsyi-leave-print-dots"></span><span>' + esc(leave.substitute_name || '') + '</span></div>' +
            '<div class="rsyi-leave-print-sigs">' +
            '<div class="rsyi-leave-print-sig-row"><span style="min-width:220px;font-weight:700;">توقيع الموظف:</span>' + sigEmp + '</div>' +
            '<div class="rsyi-leave-print-sig-row"><span style="min-width:220px;font-weight:700;">توقيع المدير المباشر:</span>' + sigMgr + '</div>' +
            '<div class="rsyi-leave-print-sig-row"><span style="min-width:220px;font-weight:700;">توقيع مدير الموارد البشرية:</span>' + sigHR + '</div>' +
            '<div class="rsyi-leave-print-dean">تصديق؛<br><br>' + sigDean + '</div>' +
            '</div></div>';

        var win = window.open('', '_blank');
        win.document.write('<html><head><title>طلب إجازة</title><style>body{direction:rtl;font-family:Arial,sans-serif;} .rsyi-leave-print-wrapper{padding:40px;max-width:800px;margin:0 auto;} .rsyi-leave-print-title{text-align:center;font-size:22px;font-weight:700;margin-bottom:30px;border-bottom:2px solid #000;padding-bottom:10px;} .rsyi-leave-print-row{display:flex;align-items:baseline;gap:8px;margin-bottom:12px;font-size:15px;} .rsyi-leave-print-label{font-weight:700;min-width:180px;flex-shrink:0;} .rsyi-leave-print-dots{flex:1;border-bottom:1px dotted #555;} .rsyi-leave-print-sigs{margin-top:40px;border-top:1px solid #000;padding-top:20px;} .rsyi-leave-print-sig-row{display:flex;align-items:flex-end;gap:8px;margin-bottom:30px;font-size:14px;} .rsyi-leave-print-sig-img{max-width:200px;max-height:60px;} .rsyi-leave-print-dean{text-align:center;font-size:14px;font-weight:700;border-top:1px solid #555;padding-top:16px;margin-top:20px;}</style></head><body>' + html + '</body></html>');
        win.document.close();
        setTimeout(function () { win.print(); }, 400);
    });

    /* ══ OVERTIME ════════════════════════════════════════════════════════════ */

    var currentOtId = null;

    function loadOvertimeRequests() {
        var $tbody = $('#rsyi-hr-ot-body');
        if (!$tbody.length) { return; }
        $tbody.html('<tr><td colspan="7" class="rsyi-hr-loading">' + (i18n.loading || 'جارٍ التحميل...') + '</td></tr>');
        ajax('rsyi_hr_get_overtime_requests', {
            status:      $('#rsyi-hr-filter-ot-status').val() || '',
            employee_id: $('#rsyi-hr-filter-ot-emp').val()    || 0,
        }, function (err, rows) {
            if (err) { notice(err, 'error'); return; }
            if (!rows || !rows.length) { $tbody.html('<tr><td colspan="7">' + (i18n.no_results || 'لا توجد نتائج.') + '</td></tr>'); return; }
            var stMap = { pending: 'rsyi-hr-badge-on_leave', manager_approved: '', hr_approved: 'rsyi-hr-badge-active', rejected: 'rsyi-hr-badge-inactive' };
            var html = rows.map(function (r) {
                return '<tr>' +
                    '<td>' + esc(r.full_name) + '</td>' +
                    '<td>' + esc(r.work_date) + '</td>' +
                    '<td>' + (r.from_time || '').substring(0,5) + '</td>' +
                    '<td>' + (r.to_time   || '').substring(0,5) + '</td>' +
                    '<td>' + (r.total_hours || '—') + '</td>' +
                    '<td><span class="rsyi-hr-badge ' + (stMap[r.status] || '') + '">' + (i18n[r.status] || r.status) + '</span></td>' +
                    '<td><button class="button button-small rsyi-hr-view-ot" data-id="' + r.id + '">' + (i18n.approve || 'مراجعة') + '</button></td>' +
                '</tr>';
            }).join('');
            $tbody.html(html);
        });
    }

    $(document).on('change', '#rsyi-hr-filter-ot-status, #rsyi-hr-filter-ot-emp', loadOvertimeRequests);

    $(document).on('click', '.rsyi-hr-view-ot', function () {
        currentOtId = $(this).data('id');
        ajax('rsyi_hr_get_overtime', { id: currentOtId }, function (err, ot) {
            if (err) { notice(err, 'error'); return; }
            var detail = '<table class="widefat">' +
                '<tr><th>الموظف</th><td>' + esc(ot.full_name) + '</td></tr>' +
                '<tr><th>التاريخ</th><td>' + esc(ot.work_date) + '</td></tr>' +
                '<tr><th>من</th><td>' + (ot.from_time || '').substring(0,5) + '</td></tr>' +
                '<tr><th>إلى</th><td>' + (ot.to_time   || '').substring(0,5) + '</td></tr>' +
                '<tr><th>الساعات</th><td>' + (ot.total_hours || '—') + '</td></tr>' +
                '<tr><th>السبب</th><td>' + esc(ot.reason || '—') + '</td></tr>' +
                '<tr><th>الحالة</th><td>' + (i18n[ot.status] || ot.status) + '</td></tr>' +
                '</table>';
            if (ot.employee_signature) {
                detail += '<p><strong>توقيع الموظف:</strong><br><img src="' + ot.employee_signature + '" style="max-width:200px;border:1px solid #ddd;background:#fff;"></p>';
            }
            $('#rsyi-hr-ot-detail').html(detail);
            var canApprove = (ot.status === 'pending' || ot.status === 'manager_approved') && i18n.can_manage_ot === '1';
            $('#rsyi-hr-ot-approve-section').toggle(canApprove);
            $('#rsyi-hr-ot-reject-form').hide();
            openModal('#rsyi-hr-ot-modal');
        });
    });

    $(document).on('click', '#rsyi-hr-ot-approve-btn', function () {
        ajax('rsyi_hr_approve_overtime', { id: currentOtId }, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-ot-modal');
            notice(i18n.saved);
            loadOvertimeRequests();
        });
    });

    $(document).on('click', '#rsyi-hr-ot-reject-btn', function () {
        $('#rsyi-hr-ot-reject-form').slideToggle();
    });

    $(document).on('click', '#rsyi-hr-ot-reject-confirm', function () {
        var reason = $('#rsyi-hr-ot-reject-reason').val();
        ajax('rsyi_hr_reject_overtime', { id: currentOtId, reason: reason }, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-ot-modal');
            notice(i18n.saved);
            loadOvertimeRequests();
        });
    });

    /* ══ ATTENDANCE ══════════════════════════════════════════════════════════ */

    var attStatusLabels = { present: 'حاضر', absent: 'غائب', late: 'متأخر', leave: 'إجازة', holiday: 'عطلة' };

    function loadAttendance() {
        var $tbody = $('#rsyi-hr-att-body');
        if (!$tbody.length) { return; }
        $tbody.html('<tr><td colspan="7" class="rsyi-hr-loading">' + (i18n.loading || 'جارٍ التحميل...') + '</td></tr>');
        ajax('rsyi_hr_get_attendance', {
            employee_id: $('#rsyi-hr-att-filter-emp').val()    || 0,
            date_from:   $('#rsyi-hr-att-date-from').val()     || '',
            date_to:     $('#rsyi-hr-att-date-to').val()       || '',
            status:      $('#rsyi-hr-att-filter-status').val() || '',
        }, function (err, rows) {
            if (err) { notice(err, 'error'); return; }
            if (!rows || !rows.length) { $tbody.html('<tr><td colspan="7">' + (i18n.no_results || 'لا توجد نتائج.') + '</td></tr>'); return; }
            var html = rows.map(function (r) {
                return '<tr>' +
                    '<td>' + esc(r.full_name) + '</td>' +
                    '<td>' + esc(r.attendance_date) + '</td>' +
                    '<td>' + (r.check_in  ? r.check_in.substring(0,5)  : '—') + '</td>' +
                    '<td>' + (r.check_out ? r.check_out.substring(0,5) : '—') + '</td>' +
                    '<td>' + (attStatusLabels[r.status] || r.status) + '</td>' +
                    '<td>' + esc(r.notes || '') + '</td>' +
                    '<td><button class="button button-small rsyi-hr-edit-att" data-id="' + r.id + '" data-row=\'' + JSON.stringify(r) + '\'>' + (i18n.edit || 'تعديل') + '</button> ' +
                        '<button class="button button-small rsyi-hr-delete-att" data-id="' + r.id + '">' + (i18n.delete || 'حذف') + '</button></td>' +
                '</tr>';
            }).join('');
            $tbody.html(html);
        });
    }

    $(document).on('click', '#rsyi-hr-att-search-btn', loadAttendance);

    $(document).on('click', '.rsyi-hr-btn-add-att', function () {
        $('#rsyi-hr-att-form')[0].reset();
        $('#att-id').val('');
        $('#rsyi-hr-att-modal-title').text('إضافة سجل حضور');
        openModal('#rsyi-hr-att-modal');
    });

    $(document).on('click', '.rsyi-hr-edit-att', function () {
        var r = $(this).data('row');
        if (typeof r === 'string') { r = JSON.parse(r); }
        $('#att-id').val(r.id);
        $('#att-emp').val(r.employee_id);
        $('#att-date').val(r.attendance_date);
        $('#att-checkin').val(r.check_in  ? r.check_in.substring(0,5)  : '');
        $('#att-checkout').val(r.check_out ? r.check_out.substring(0,5) : '');
        $('#att-status').val(r.status);
        $('#att-notes').val(r.notes || '');
        $('#rsyi-hr-att-modal-title').text('تعديل سجل حضور');
        openModal('#rsyi-hr-att-modal');
    });

    $(document).on('submit', '#rsyi-hr-att-form', function (e) {
        e.preventDefault();
        var data = {};
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        ajax('rsyi_hr_save_attendance', data, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-att-modal');
            notice(i18n.saved);
            loadAttendance();
        });
    });

    $(document).on('click', '.rsyi-hr-delete-att', function () {
        if (!confirm(i18n.confirm_delete)) { return; }
        ajax('rsyi_hr_delete_attendance', { id: $(this).data('id') }, function (err) {
            if (err) { notice(err, 'error'); return; }
            loadAttendance();
        });
    });

    $(document).on('click', '#rsyi-hr-att-import-btn', function () {
        openModal('#rsyi-hr-att-import-modal');
    });

    $(document).on('click', '#rsyi-hr-att-template-btn', function () {
        ajax('rsyi_hr_download_att_template', {}, function () {});
        // Direct download via form
        var form = $('<form method="POST" action="' + ajaxUrl + '" style="display:none">' +
            '<input name="action" value="rsyi_hr_download_att_template">' +
            '<input name="nonce" value="' + nonce + '">' +
            '</form>');
        $('body').append(form);
        form.submit().remove();
    });

    $(document).on('submit', '#rsyi-hr-att-import-form', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        fd.append('action', 'rsyi_hr_import_attendance_csv');
        fd.append('nonce',  nonce);
        $.ajax({ url: ajaxUrl, type: 'POST', data: fd, processData: false, contentType: false })
            .done(function (r) {
                var $result = $('#rsyi-hr-att-import-result').show();
                if (r.success) {
                    var msg = (i18n.import_success || 'تم استيراد %d سجل.').replace('%d', r.data.success);
                    if (r.data.errors && r.data.errors.length) {
                        msg += '<br><strong>' + (i18n.import_errors || 'أخطاء:') + '</strong><ul>' +
                            r.data.errors.map(function (e) { return '<li>' + esc(e) + '</li>'; }).join('') + '</ul>';
                    }
                    $result.html(msg).css('color', '#065f46');
                    loadAttendance();
                } else {
                    $result.html(r.data && r.data.message ? r.data.message : i18n.error).css('color', '#991b1b');
                }
            });
    });

    /* ══ VIOLATIONS ══════════════════════════════════════════════════════════ */

    var currentVioId = null;
    var vioTypeLabels = { late: 'تأخير', absent: 'غياب', misconduct: 'سلوك مخالف', negligence: 'إهمال', policy_breach: 'مخالفة لائحة', other: 'أخرى' };
    var vioPenaltyLabels = { warning: 'إنذار', deduction: 'خصم', suspension: 'إيقاف', demotion: 'تنزيل درجة', termination: 'إنهاء خدمة' };

    function loadViolations() {
        var $tbody = $('#rsyi-hr-violations-body');
        if (!$tbody.length) { return; }
        $tbody.html('<tr><td colspan="6" class="rsyi-hr-loading">' + (i18n.loading || 'جارٍ التحميل...') + '</td></tr>');
        ajax('rsyi_hr_get_violations', {
            status:      $('#rsyi-hr-filter-vio-status').val() || '',
            employee_id: $('#rsyi-hr-filter-vio-emp').val()    || 0,
        }, function (err, rows) {
            if (err) { notice(err, 'error'); return; }
            if (!rows || !rows.length) { $tbody.html('<tr><td colspan="6">' + (i18n.no_results || 'لا توجد نتائج.') + '</td></tr>'); return; }
            var stBadge = { pending: 'rsyi-hr-badge-on_leave', approved: 'rsyi-hr-badge-active', rejected: 'rsyi-hr-badge-inactive' };
            var html = rows.map(function (r) {
                var btns = '<button class="button button-small rsyi-hr-edit-vio" data-id="' + r.id + '">' + (i18n.edit || 'تعديل') + '</button> ';
                if (i18n.can_approve_vio === '1' && r.status === 'pending') {
                    btns += '<button class="button button-small rsyi-hr-approve-vio" data-id="' + r.id + '">' + (i18n.approve || 'اعتماد') + '</button> ';
                }
                btns += '<button class="button button-small rsyi-hr-delete-vio" data-id="' + r.id + '">' + (i18n.delete || 'حذف') + '</button>';
                return '<tr>' +
                    '<td>' + esc(r.full_name) + '</td>' +
                    '<td>' + (vioTypeLabels[r.violation_type] || r.violation_type) + '</td>' +
                    '<td>' + (vioPenaltyLabels[r.penalty_type] || r.penalty_type || '—') + '</td>' +
                    '<td>' + esc((r.created_at || '').substring(0,10)) + '</td>' +
                    '<td><span class="rsyi-hr-badge ' + (stBadge[r.status] || '') + '">' + (i18n[r.status] || r.status) + '</span></td>' +
                    '<td>' + btns + '</td>' +
                '</tr>';
            }).join('');
            $tbody.html(html);
        });
    }

    $(document).on('change', '#rsyi-hr-filter-vio-status, #rsyi-hr-filter-vio-emp', loadViolations);

    $(document).on('click', '.rsyi-hr-btn-add-violation', function () {
        $('#rsyi-hr-vio-form')[0].reset();
        $('#vio-id').val('');
        $('#rsyi-hr-vio-modal-title').text('إضافة مخالفة');
        openModal('#rsyi-hr-violation-modal');
    });

    $(document).on('click', '.rsyi-hr-edit-vio', function () {
        var id = $(this).data('id');
        ajax('rsyi_hr_get_violation', { id: id }, function (err, v) {
            if (err) { notice(err, 'error'); return; }
            $('#vio-id').val(v.id);
            $('#vio-emp').val(v.employee_id);
            $('#vio-type').val(v.violation_type);
            $('#vio-desc').val(v.description);
            $('#vio-penalty-type').val(v.penalty_type);
            $('#vio-penalty-value').val(v.penalty_value);
            $('#rsyi-hr-vio-modal-title').text('تعديل مخالفة');
            openModal('#rsyi-hr-violation-modal');
        });
    });

    $(document).on('submit', '#rsyi-hr-vio-form', function (e) {
        e.preventDefault();
        var data = {};
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        ajax('rsyi_hr_save_violation', data, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-violation-modal');
            notice(i18n.saved);
            loadViolations();
        });
    });

    $(document).on('click', '.rsyi-hr-delete-vio', function () {
        if (!confirm(i18n.confirm_delete)) { return; }
        ajax('rsyi_hr_delete_violation', { id: $(this).data('id') }, function (err) {
            if (err) { notice(err, 'error'); return; }
            loadViolations();
        });
    });

    $(document).on('click', '.rsyi-hr-approve-vio', function () {
        currentVioId = $(this).data('id');
        ajax('rsyi_hr_get_violation', { id: currentVioId }, function (err, v) {
            if (err) { notice(err, 'error'); return; }
            var detail = '<p><strong>الموظف:</strong> ' + esc(v.full_name) + '</p>' +
                         '<p><strong>المخالفة:</strong> ' + (vioTypeLabels[v.violation_type] || v.violation_type) + '</p>' +
                         '<p><strong>الجزاء:</strong> ' + (vioPenaltyLabels[v.penalty_type] || v.penalty_type || '—') + '</p>' +
                         '<p><strong>الوصف:</strong> ' + esc(v.description || '') + '</p>';
            $('#rsyi-hr-vio-approve-detail').html(detail);
            openModal('#rsyi-hr-vio-approve-modal');
        });
    });

    $(document).on('click', '#rsyi-hr-vio-approve-confirm', function () {
        var notes = $('#rsyi-hr-vio-approve-notes').val();
        ajax('rsyi_hr_approve_violation', { id: currentVioId, notes: notes }, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-vio-approve-modal');
            notice(i18n.saved);
            loadViolations();
        });
    });

    $(document).on('click', '#rsyi-hr-vio-reject-confirm', function () {
        var notes = $('#rsyi-hr-vio-approve-notes').val();
        ajax('rsyi_hr_reject_violation', { id: currentVioId, notes: notes }, function (err) {
            if (err) { notice(err, 'error'); return; }
            closeModal('#rsyi-hr-vio-approve-modal');
            notice(i18n.saved);
            loadViolations();
        });
    });

    /* ══ PERMISSIONS ═════════════════════════════════════════════════════════ */

    $(document).on('click', '#rsyi-hr-perm-load', function () {
        var userId = $('#rsyi-hr-perm-user').val();
        if (!userId) { return; }
        ajax('rsyi_hr_get_user_permissions', { user_id: userId }, function (err, perms) {
            if (err) { notice(err, 'error'); return; }
            // Set radio buttons
            Object.keys(perms).forEach(function (module) {
                $('input[name="perm[' + module + ']"][value="' + perms[module] + '"]').prop('checked', true);
            });
            $('#rsyi-hr-perm-matrix').show();
        });
    });

    $(document).on('click', '#rsyi-hr-perm-save', function () {
        var userId = $('#rsyi-hr-perm-user').val();
        if (!userId) { return; }
        var permissions = {};
        $('input.rsyi-hr-perm-radio:checked').each(function () {
            permissions[$(this).data('module')] = $(this).val();
        });
        ajax('rsyi_hr_save_user_permissions', { user_id: userId, permissions: permissions }, function (err) {
            if (err) {
                $('#rsyi-hr-perm-notice').text(err).css('color', '#991b1b');
                return;
            }
            $('#rsyi-hr-perm-notice').text(i18n.saved).css('color', '#065f46');
        });
    });

    /* ══ Utility ══════════════════════════════════════════════════════════════ */

    function esc(str) {
        if (!str) { return ''; }
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ══ Init ════════════════════════════════════════════════════════════════ */
    $(function () {
        loadEmployees();
        loadLeaves();
        loadOvertimeRequests();
        loadAttendance();
        loadViolations();
    });

}(jQuery));
