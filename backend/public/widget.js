(function () {
  var script = document.currentScript;
  var pixelId = script.getAttribute('data-pixel-id');
  var apiUrl = script.src.replace('/widget.js', '');

  if (!pixelId) return;

  fetch(apiUrl + '/api/widget/' + pixelId)
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (!data.notifications || !data.notifications.length) return;
      initWidget(data.notifications, apiUrl, pixelId);
    })
    .catch(function () {});

  function initWidget(notifications, apiUrl, pixelId) {
    var index = 0;

    var container = document.createElement('div');
    container.style.cssText = 'position:fixed;bottom:20px;left:20px;z-index:2147483647;font-family:-apple-system,BlinkMacSystemFont,sans-serif;max-width:300px;display:none;';
    document.body.appendChild(container);

    function show() {
      var n = notifications[index % notifications.length];
      index++;

      container.innerHTML =
        '<div style="background:#fff;border-radius:10px;padding:14px 16px;box-shadow:0 4px 20px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;animation:sp-slide-in 0.3s ease;">' +
        '<span style="font-size:24px;">' + (n.emoji || '\u{1F6D2}') + '</span>' +
        '<div style="flex:1;">' +
        '<div style="font-size:13px;font-weight:600;color:#1a1a1a;line-height:1.4;">' + n.message + '</div>' +
        (n.city ? '<div style="font-size:11px;color:#888;margin-top:2px;">' + n.city + (n.country ? ', ' + n.country : '') + '</div>' : '') +
        '</div>' +
        '<button onclick="this.closest(\'div\').parentElement.style.display=\'none\'" style="background:none;border:none;cursor:pointer;color:#ccc;font-size:18px;padding:0;line-height:1;">\u00D7</button>' +
        '</div>';

      container.style.display = 'block';

      fetch(apiUrl + '/api/widget/' + pixelId + '/display', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id: n.id }),
      }).catch(function () {});

      setTimeout(function () {
        container.style.display = 'none';
        setTimeout(show, 8000);
      }, 5000);
    }

    var style = document.createElement('style');
    style.textContent = '@keyframes sp-slide-in{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}';
    document.head.appendChild(style);

    setTimeout(show, 3000);
  }
})();