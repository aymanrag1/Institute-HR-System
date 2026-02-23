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

    /* ══ EMPLOYEES ══════════════════════════════════════════════════════════ */

    function loadEmployees() {
        var $tbody = $('#rsyi-hr-employees-body');
        if (!$tbody.length) return;

        $tbody.html('<tr><td colspan="7" class="rsyi-hr-loading">جارٍ التحميل…</td></tr>');

        ajax('rsyi_hr_get_employees', {
            status:        $('#rsyi-hr-filter-status').val() || 'all',
            department_id: $('#rsyi-hr-filter-dept').val()   || 0,
            search:        $('#rsyi-hr-search-emp').val()    || '',
        }, function (err, rows) {
            if (err) { notice(err, 'error'); return; }
            if (!rows || !rows.length) {
                $tbody.html('<tr><td colspan="7">لا توجد نتائج.</td></tr>');
                return;
            }
            var html = rows.map(function (r) {
                return '<tr>' +
                    '<td>' + (r.employee_number || '—') + '</td>' +
                    '<td><strong>' + r.full_name + '</strong></td>' +
                    '<td>' + (r.department_name || '—') + '</td>' +
                    '<td>' + (r.job_title_name  || '—') + '</td>' +
                    '<td>' + (r.phone           || '—') + '</td>' +
                    '<td>' + statusBadge(r.status) + '</td>' +
                    '<td>' +
                        '<button class="button button-small rsyi-hr-edit-emp" data-id="' + r.id + '">تعديل</button> ' +
                        '<button class="button button-small rsyi-hr-delete-emp" data-id="' + r.id + '">حذف</button>' +
                    '</td>' +
                '</tr>';
            }).join('');
            $tbody.html(html);
        });
    }

    // فتح Modal إضافة موظف
    $(document).on('click', '.rsyi-hr-btn-add-employee', function () {
        $('#rsyi-hr-employee-form')[0].reset();
        $('#emp-id').val('');
        $('#rsyi-hr-employee-modal-title').text('إضافة موظف');
        openModal('#rsyi-hr-employee-modal');
    });

    // فتح Modal تعديل موظف
    $(document).on('click', '.rsyi-hr-edit-emp', function () {
        var id = $(this).data('id');
        ajax('rsyi_hr_get_employee', { id: id }, function (err, emp) {
            if (err) { notice(err, 'error'); return; }
            $('#emp-id').val(emp.id);
            $('#emp-full-name').val(emp.full_name);
            $('#emp-number').val(emp.employee_number);
            $('#emp-national-id').val(emp.national_id);
            $('#emp-department').val(emp.department_id);
            $('#emp-job-title').val(emp.job_title_id);
            $('#emp-phone').val(emp.phone);
            $('#emp-email').val(emp.email);
            $('#emp-hire-date').val(emp.hire_date);
            $('#emp-status').val(emp.status);
            $('#emp-notes').val(emp.notes);
            $('#rsyi-hr-employee-modal-title').text('تعديل موظف');
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

    /* ══ Init ════════════════════════════════════════════════════════════════ */
    $(function () {
        loadEmployees();
    });

}(jQuery));
