/* Visitor sign-in page: camera, validation and submit. */
(function () {
  'use strict';

  var $ = function (id) { return document.getElementById(id); };

  var form = $('visitorForm');
  var video = $('video');
  var preview = $('preview');
  var placeholder = $('cameraPlaceholder');
  var btnStart = $('btnStart');
  var btnCapture = $('btnCapture');
  var btnRetake = $('btnRetake');
  var btnFile = $('btnFile');
  var fileInput = $('photoFile');
  var cameraMsg = $('cameraMsg');
  var photoError = $('photoError');
  var formAlert = $('formAlert');
  var btnSubmit = $('btnSubmit');
  var spinner = $('submitSpinner');
  var submitLabel = $('submitLabel');

  var MAX_SIDE = 800;           // photos are shrunk to this size before upload
  var stream = null;
  var photoBlob = null;
  var previewUrl = null;

  var NAME_RE = /^[\p{L}\p{M}][\p{L}\p{M} .'\-]{1,59}$/u;
  var PHONE_RE = /^\+?[0-9]{7,15}$/;

  /* ---------- small UI helpers ---------- */

  function show(el) { el.classList.remove('d-none'); }
  function hide(el) { el.classList.add('d-none'); }

  // state: 'idle' | 'live' | 'captured'
  function setState(state) {
    hide(video); hide(preview); hide(placeholder);
    hide(btnStart); hide(btnCapture); hide(btnRetake);
    if (state === 'live') {
      show(video); show(btnCapture);
    } else if (state === 'captured') {
      show(preview); show(btnRetake);
    } else {
      show(placeholder); show(btnStart);
    }
  }

  function showCameraMsg(text) {
    cameraMsg.textContent = text;
    show(cameraMsg);
  }

  function showPhotoError(text) {
    photoError.textContent = text;
    show(photoError);
  }

  function clearPhotoMessages() {
    hide(photoError); hide(cameraMsg);
  }

  function setFieldError(inputId, errorId, message) {
    var input = $(inputId);
    var box = $(errorId);
    if (message) {
      input.classList.add('is-invalid');
      box.textContent = message;
    } else {
      input.classList.remove('is-invalid');
      box.textContent = '';
    }
  }

  function showFormAlert(text) {
    formAlert.textContent = text;
    show(formAlert);
    formAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  /* ---------- camera ---------- */

  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach(function (t) { t.stop(); });
      stream = null;
    }
    video.srcObject = null;
  }

  function startCamera() {
    clearPhotoMessages();

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      // Happens on plain http:// addresses (other than localhost): browsers block live camera there.
      showCameraMsg('Live camera is not available on this connection. Please tap "Use your phone\'s camera app" below.');
      return;
    }

    navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: { ideal: 1024 }, height: { ideal: 1024 } },
      audio: false
    }).then(function (s) {
      stream = s;
      video.srcObject = s;
      return video.play();
    }).then(function () {
      setState('live');
    }).catch(function (err) {
      stopCamera();
      setState('idle');
      var name = err && err.name;
      if (name === 'NotAllowedError' || name === 'SecurityError') {
        showCameraMsg('Camera permission was blocked. Allow the camera in your browser, or use your phone\'s camera app below.');
      } else if (name === 'NotFoundError' || name === 'OverconstrainedError') {
        showCameraMsg('No camera was found. You can use your phone\'s camera app below.');
      } else {
        showCameraMsg('Could not start the camera. You can use your phone\'s camera app below.');
      }
    });
  }

  function setPhoto(blob) {
    photoBlob = blob;
    if (previewUrl) { URL.revokeObjectURL(previewUrl); }
    previewUrl = URL.createObjectURL(blob);
    preview.src = previewUrl;
    setState('captured');
    clearPhotoMessages();
  }

  function capturePhoto() {
    if (!video.videoWidth) {
      showCameraMsg('The camera is still starting. Try again in a second.');
      return;
    }
    // Crop the centre square of the video
    var side = Math.min(video.videoWidth, video.videoHeight);
    var sx = (video.videoWidth - side) / 2;
    var sy = (video.videoHeight - side) / 2;
    var out = Math.min(side, MAX_SIDE);

    var canvas = document.createElement('canvas');
    canvas.width = out;
    canvas.height = out;
    canvas.getContext('2d').drawImage(video, sx, sy, side, side, 0, 0, out, out);

    canvas.toBlob(function (blob) {
      if (!blob) {
        showPhotoError('Could not take the photo. Please try again.');
        return;
      }
      stopCamera();
      setPhoto(blob);
    }, 'image/jpeg', 0.85);
  }

  function retake() {
    photoBlob = null;
    if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
    preview.removeAttribute('src');
    startCamera();
    setState('idle');
  }

  // Fallback: phone's own camera app (works even without https) or a gallery photo
  function shrinkImageFile(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        var scale = Math.min(1, MAX_SIDE / Math.max(img.naturalWidth, img.naturalHeight));
        var w = Math.max(1, Math.round(img.naturalWidth * scale));
        var h = Math.max(1, Math.round(img.naturalHeight * scale));
        var canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        URL.revokeObjectURL(url);
        canvas.toBlob(function (blob) { blob ? resolve(blob) : reject(new Error('encode')); }, 'image/jpeg', 0.85);
      };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('load')); };
      img.src = url;
    });
  }

  fileInput.addEventListener('change', function () {
    var file = fileInput.files && fileInput.files[0];
    if (!file) { return; }
    clearPhotoMessages();
    if (!/^image\//.test(file.type)) {
      showPhotoError('Please choose a photo (image file).');
      fileInput.value = '';
      return;
    }
    stopCamera();
    shrinkImageFile(file).then(setPhoto).catch(function () {
      showPhotoError('Could not read that photo. Please try another one.');
    });
    fileInput.value = '';
  });

  btnStart.addEventListener('click', startCamera);
  btnCapture.addEventListener('click', capturePhoto);
  btnRetake.addEventListener('click', retake);
  btnFile.addEventListener('click', function () { fileInput.click(); });

  document.addEventListener('visibilitychange', function () {
    if (document.hidden && stream) { stopCamera(); setState('idle'); }
  });
  window.addEventListener('pagehide', stopCamera);

  /* ---------- validation + submit ---------- */

  $('name').addEventListener('input', function () { setFieldError('name', 'nameError', ''); });
  $('phone').addEventListener('input', function () { setFieldError('phone', 'phoneError', ''); });

  function validate() {
    var ok = true;
    var name = $('name').value.trim().replace(/\s+/g, ' ');
    var phone = $('phone').value.trim().replace(/[\s\-().]/g, '');

    if (!name) {
      setFieldError('name', 'nameError', 'Please enter your name.'); ok = false;
    } else if (!NAME_RE.test(name)) {
      setFieldError('name', 'nameError', 'Use 2-60 letters only (spaces, . \' - are fine).'); ok = false;
    } else {
      setFieldError('name', 'nameError', '');
    }

    if (!phone) {
      setFieldError('phone', 'phoneError', 'Please enter your mobile number.'); ok = false;
    } else if (!PHONE_RE.test(phone)) {
      setFieldError('phone', 'phoneError', 'Enter a valid mobile number (7-15 digits, optional + at the start).'); ok = false;
    } else {
      setFieldError('phone', 'phoneError', '');
    }

    if (!photoBlob) {
      showPhotoError('Please take a photo.'); ok = false;
    } else {
      hide(photoError);
    }
    return ok;
  }

  function setBusy(busy) {
    btnSubmit.disabled = busy;
    spinner.classList.toggle('d-none', !busy);
    submitLabel.textContent = busy ? 'Sending...' : 'Submit';
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    hide(formAlert);
    if (!validate()) {
      var firstBad = form.querySelector('.is-invalid');
      if (firstBad) { firstBad.focus(); }
      return;
    }

    var data = new FormData(form);
    data.set('name', $('name').value.trim().replace(/\s+/g, ' '));
    data.set('phone', $('phone').value.trim());
    data.set('photo', photoBlob, 'visitor.jpg');

    setBusy(true);
    fetch('submit.php', { method: 'POST', body: data, headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (res) {
        return res.json().catch(function () { return { ok: false, errors: { general: 'Unexpected reply from the server.' } }; });
      })
      .then(function (json) {
        setBusy(false);
        if (json.ok) {
          $('successName').textContent = json.name || '';
          hide($('formCard'));
          show($('successCard'));
          window.scrollTo({ top: 0, behavior: 'smooth' });
          return;
        }
        var errs = json.errors || {};
        if (errs.name) { setFieldError('name', 'nameError', errs.name); }
        if (errs.phone) { setFieldError('phone', 'phoneError', errs.phone); }
        if (errs.photo) { showPhotoError(errs.photo); }
        if (errs.general) { showFormAlert(errs.general); }
        else if (!errs.name && !errs.phone && !errs.photo) { showFormAlert('Could not submit. Please try again.'); }
      })
      .catch(function () {
        setBusy(false);
        showFormAlert('Could not reach the server. Check your Wi-Fi connection and try again.');
      });
  });

  $('btnAnother').addEventListener('click', function () { window.location.reload(); });

  setState('idle');
})();
