/* Dashboard: photo viewer + "new visitor" alert. */
(function () {
  'use strict';

  // Enlarge photo in the modal
  var modal = document.getElementById('photoModal');
  if (modal) {
    modal.addEventListener('show.bs.modal', function (ev) {
      var btn = ev.relatedTarget;
      if (!btn) { return; }
      var img = document.getElementById('photoModalImg');
      img.src = btn.getAttribute('data-photo');
      img.alt = 'Photo of ' + btn.getAttribute('data-name');
      document.getElementById('photoModalTitle').textContent = btn.getAttribute('data-name');
    });
  }

  // Every 20 seconds, ask the server if someone new has signed in.
  var known = parseInt(document.body.getAttribute('data-latest-id') || '0', 10);
  var banner = document.getElementById('newVisitorBanner');
  var baseTitle = document.title;

  function check() {
    if (document.hidden && !banner) { return; }
    fetch('api_latest.php', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) {
        if (r.status === 401) { window.location.href = 'login.php'; return null; }
        return r.json();
      })
      .then(function (data) {
        if (data && data.ok && data.latest > known) {
          if (banner) { banner.classList.remove('d-none'); }
          document.title = '(New) ' + baseTitle;
        }
      })
      .catch(function () { /* offline for a moment - try again next time */ });
  }

  setInterval(check, 20000);
})();
