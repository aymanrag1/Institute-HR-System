/**
 * RSYI Signature Pad — لوحة التوقيع الإلكتروني
 * Simple canvas-based signature pad (mouse + touch)
 */
(function () {
    'use strict';

    function SignaturePad(canvas, options) {
        this.canvas  = canvas;
        this.ctx     = canvas.getContext('2d');
        this.options = Object.assign({ penColor: '#000033', lineWidth: 2 }, options || {});
        this._empty  = true;
        this._down   = false;
        this._init();
    }

    SignaturePad.prototype._init = function () {
        var self = this;
        var ctx  = this.ctx;
        var cvs  = this.canvas;

        ctx.strokeStyle = this.options.penColor;
        ctx.lineWidth   = this.options.lineWidth;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';

        cvs.addEventListener('mousedown', function (e) {
            self._down = true;
            var p = self._pos(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        });

        cvs.addEventListener('mousemove', function (e) {
            if (!self._down) { return; }
            var p = self._pos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            self._empty = false;
        });

        document.addEventListener('mouseup', function () { self._down = false; });

        cvs.addEventListener('touchstart', function (e) {
            e.preventDefault();
            self._down = true;
            var p = self._pos(e.touches[0]);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        }, { passive: false });

        cvs.addEventListener('touchmove', function (e) {
            e.preventDefault();
            if (!self._down) { return; }
            var p = self._pos(e.touches[0]);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            self._empty = false;
        }, { passive: false });

        cvs.addEventListener('touchend', function () { self._down = false; });
    };

    SignaturePad.prototype._pos = function (e) {
        var r = this.canvas.getBoundingClientRect();
        return {
            x: (e.clientX - r.left) * (this.canvas.width  / r.width),
            y: (e.clientY - r.top)  * (this.canvas.height / r.height)
        };
    };

    SignaturePad.prototype.clear = function () {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this._empty = true;
    };

    SignaturePad.prototype.isEmpty = function () { return this._empty; };

    SignaturePad.prototype.toDataURL = function (type) {
        return this.canvas.toDataURL(type || 'image/png');
    };

    window.SignaturePad = SignaturePad;
}());
