/* global rsyiHRPortal, jQuery, SignaturePad */
(function ($) {
    'use strict';

    var P    = window.rsyiHRPortal || {};
    var ajax = P.ajaxUrl || '';
    var nonce= P.nonce   || '';
    var i18n = P.i18n    || {};

    function post(action, data, cb) {
        $.post(ajax, Object.assign({ action: action, nonce: nonce }, data))
            .done(function (r) {
                r.success ? cb(null, r.data) : cb(r.data && r.data.message ? r.data.message : i18n.error);
            })
            .fail(function () { cb(i18n.error); });
    }

    function notice($el, msg, type) {
        $el.removeClass('rsyi-portal-notice-success rsyi-portal-notice-error')
           .addClass('rsyi-portal-notice-' + (type || 'success'))
           .html(msg).show();
    }

    /* ══ PROFILE — Signature ══════════════════════════════════════════════ */

    var profileSigPad = null;

    if ($('#rsyi-portal-sig-canvas').length) {
        profileSigPad = new SignaturePad(document.getElementById('rsyi-portal-sig-canvas'));

        $('#rsyi-portal-sig-clear').on('click', function () {
            profileSigPad.clear();
        });

        $('#rsyi-portal-sig-save').on('click', function () {
            if (profileSigPad.isEmpty()) {
                $('#rsyi-portal-sig-msg').text(i18n.error).css('color', '#991b1b');
                return;
            }
            var sig = profileSigPad.toDataURL();
            $.post(ajax, { action: 'rsyi_hr_portal_save_signature', nonce: nonce, signature: sig })
                .done(function (r) {
                    if (r.success) {
                        $('#rsyi-portal-sig-msg').text(i18n.saved).css('color', '#065f46');
                    } else {
                        $('#rsyi-portal-sig-msg').text(r.data && r.data.message ? r.data.message : i18n.error).css('color', '#991b1b');
                    }
                });
        });
    }

    /* ── Photo upload ────────────────────────────────────────────────── */
    $('#rsyi-portal-photo-input').on('change', function () {
        var file = this.files[0];
        if (!file) { return; }
        var fd = new FormData();
        fd.append('action', 'rsyi_hr_portal_save_photo');
        fd.append('nonce',  nonce);
        fd.append('photo',  file);

        $.ajax({ url: ajax, type: 'POST', data: fd, processData: false, contentType: false })
            .done(function (r) {
                if (r.success && r.data.url) {
                    var $preview = $('#rsyi-portal-photo-preview');
                    if ($preview.is('img')) {
                        $preview.attr('src', r.data.url);
                    } else {
                        $preview.replaceWith('<img id="rsyi-portal-photo-preview" class="rsyi-portal-photo-img" src="' + r.data.url + '" alt="">');
                    }
                }
            });
    });

    /* ══ LEAVE FORM ═══════════════════════════════════════════════════════ */

    var leaveSigPad = null;

    if ($('#rsyi-portal-leave-canvas').length) {
        leaveSigPad = new SignaturePad(document.getElementById('rsyi-portal-leave-canvas'));

        $('#rsyi-portal-leave-sig-clear').on('click', function () {
            leaveSigPad.clear();
            $('#rsyi-portal-leave-sig-input').val('');
        });
    }

    // Signature choice toggle
    $('input[name="sig_choice"]').on('change', function () {
        if ($(this).val() === 'saved') {
            $('#rsyi-portal-leave-sig-saved').show();
            $('#rsyi-portal-leave-sig-pad').hide();
        } else {
            $('#rsyi-portal-leave-sig-saved').hide();
            $('#rsyi-portal-leave-sig-pad').show();
        }
    });

    // Auto-calculate leave days
    $(document).on('change', '[name="from_date"], [name="to_date"]', function () {
        var from = $('[name="from_date"]').val();
        var to   = $('[name="to_date"]').val();
        if (from && to) {
            var d = Math.round((new Date(to) - new Date(from)) / 86400000) + 1;
            $('#rsyi-portal-leave-days').val(d > 0 ? d : '');
        }
    });

    $('#rsyi-portal-leave-form').on('submit', function (e) {
        e.preventDefault();

        var $notice = $('#rsyi-portal-leave-notice');
        $notice.hide();

        // Build signature
        var sig = '';
        var choice = $('input[name="sig_choice"]:checked').val();
        if (choice === 'new' && leaveSigPad) {
            if (leaveSigPad.isEmpty()) {
                notice($notice, 'يرجى توقيع الطلب أولاً.', 'error'); return;
            }
            sig = leaveSigPad.toDataURL();
        } else {
            sig = $('#rsyi-portal-leave-sig-input').val();
        }

        if (!sig) {
            notice($notice, 'التوقيع مطلوب.', 'error'); return;
        }

        var data = {};
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        data.employee_signature = sig;

        post('rsyi_hr_submit_leave', data, function (err) {
            if (err) { notice($notice, err, 'error'); return; }
            notice($notice, i18n.saved + ' — ' + 'تم رفع طلب الإجازة بنجاح.', 'success');
            setTimeout(function () { window.location.href = P.portalUrl + '?rsyi_portal=leave_list'; }, 1500);
        });
    });

    /* ══ OVERTIME FORM ════════════════════════════════════════════════════ */

    var otSigPad = null;
    if ($('#rsyi-portal-ot-canvas').length) {
        otSigPad = new SignaturePad(document.getElementById('rsyi-portal-ot-canvas'));
        $('#rsyi-portal-ot-sig-clear').on('click', function () {
            otSigPad.clear();
            $('#rsyi-portal-ot-sig-input').val('');
        });
    }

    // Auto-calculate hours
    $(document).on('change', '[name="from_time"], [name="to_time"]', function () {
        var from = $('[name="from_time"]').val();
        var to   = $('[name="to_time"]').val();
        var date = $('[name="work_date"]').val() || '2000-01-01';
        if (from && to) {
            var diff = (new Date(date + 'T' + to) - new Date(date + 'T' + from)) / 3600000;
            $('#rsyi-portal-ot-hours').val(diff > 0 ? diff.toFixed(2) + ' ساعة' : '');
        }
    });

    $('#rsyi-portal-overtime-form').on('submit', function (e) {
        e.preventDefault();

        var $notice = $('#rsyi-portal-ot-notice');
        $notice.hide();

        var sig = '';
        if (otSigPad) {
            if (otSigPad.isEmpty()) {
                notice($notice, 'يرجى توقيع الطلب أولاً.', 'error'); return;
            }
            sig = otSigPad.toDataURL();
        } else {
            sig = $('[name="employee_signature"]').val();
        }

        var data = {};
        $(this).serializeArray().forEach(function (f) { data[f.name] = f.value; });
        data.employee_signature = sig;

        post('rsyi_hr_submit_overtime', data, function (err) {
            if (err) { notice($notice, err, 'error'); return; }
            notice($notice, i18n.saved + ' — تم رفع طلب الوقت الإضافي.', 'success');
            setTimeout(function () { window.location.href = P.portalUrl + '?rsyi_portal=overtime_list'; }, 1500);
        });
    });

}(jQuery));
