/* QR page: draws the QR code on a canvas using the offline qrcode.js library. */
(function () {
  'use strict';

  var canvas = document.getElementById('qrCanvas');
  var input = document.getElementById('qrUrl');
  var urlText = document.getElementById('qrUrlText');
  var ctx = canvas.getContext('2d');

  function draw(text) {
    var value = text.trim();
    if (!value) {
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      urlText.textContent = '';
      return;
    }
    var qr = qrcode(0, 'M');           // auto size, medium error correction
    qr.addData(value);
    qr.make();

    var count = qr.getModuleCount();
    var quiet = 4;                      // white border (modules) so phones can scan it
    var cell = Math.max(1, Math.floor(800 / (count + quiet * 2)));
    var size = cell * (count + quiet * 2);

    canvas.width = size;
    canvas.height = size;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);
    ctx.fillStyle = '#12263a';
    for (var r = 0; r < count; r++) {
      for (var c = 0; c < count; c++) {
        if (qr.isDark(r, c)) {
          ctx.fillRect((c + quiet) * cell, (r + quiet) * cell, cell, cell);
        }
      }
    }
    urlText.textContent = value;
  }

  function safeDraw() {
    try { draw(input.value); } catch (e) { urlText.textContent = 'That address is too long for a QR code.'; }
  }

  input.addEventListener('input', safeDraw);

  document.getElementById('btnDownload').addEventListener('click', function () {
    var a = document.createElement('a');
    a.href = canvas.toDataURL('image/png');
    a.download = 'visitor-signin-qr.png';
    document.body.appendChild(a);
    a.click();
    a.remove();
  });

  document.getElementById('btnPrint').addEventListener('click', function () { window.print(); });

  safeDraw();
})();
