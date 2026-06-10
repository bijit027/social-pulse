(function () {
  var script = document.currentScript;
  var pixelId = script.getAttribute('data-pixel-id');
  var apiUrl = script.src.replace('/widget.js', '');

  if (!pixelId) return;

  fetch(apiUrl + '/api/widget/' + pixelId)
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (!data.notifications || !data.notifications.length) return;
      initWidget(data.notifications, data.display_settings || {}, data.theme_settings || {}, apiUrl, pixelId);
    })
    .catch(function () {});

  function timeAgo(dateString) {
    var now = new Date();
    var date = new Date(dateString);
    var seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    return Math.floor(seconds / 86400) + ' days ago';
  }

  function filterNotificationsByTime(notifications, settings) {
    if (!settings.display_from_days && !settings.display_from_hours && !settings.display_from_minutes) {
      return notifications;
    }

    var now = new Date();
    var cutoffDate = new Date(now);
    
    if (settings.display_from_days) {
      cutoffDate.setDate(cutoffDate.getDate() - settings.display_from_days);
    }
    if (settings.display_from_hours) {
      cutoffDate.setHours(cutoffDate.getHours() - settings.display_from_hours);
    }
    if (settings.display_from_minutes) {
      cutoffDate.setMinutes(cutoffDate.getMinutes() - settings.display_from_minutes);
    }

    return notifications.filter(function(n) {
      var notificationDate = new Date(n.created_at);
      return notificationDate >= cutoffDate;
    });
  }

  function shouldShowNotification(settings) {
    // Check show_on_display setting
    if (settings.show_on_display === 'logged_out_user') {
      // Check if user is logged in (you may need to implement this based on your auth system)
      // For now, we'll assume user is not logged in
      return true;
    }
    if (settings.show_on_display === 'logged_in_user') {
      // Check if user is logged in
      // For now, we'll assume user is not logged in
      return false;
    }
    return true; // 'always'
  }

  function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  }

  function initWidget(notifications, settings, themeSettings, apiUrl, pixelId) {
    // Check if should hide on mobile
    var hideOnMobile = settings.hide_on_mobile === true || settings.hide_on_mobile === 1 || settings.hide_on_mobile === '1';
    if (hideOnMobile && isMobile()) {
      return;
    }

    // Check if should show based on user type
    if (!shouldShowNotification(settings)) {
      return;
    }

    // Filter notifications by time
    notifications = filterNotificationsByTime(notifications, settings);

    // Limit notifications
    if (settings.display_last) {
      notifications = notifications.slice(0, settings.display_last);
    }

    if (!notifications.length) return;

    var index = 0;
    var displayDuration = settings.display_for || 5; // default 5 seconds
    var shouldLoop = settings.loop !== false && settings.loop !== 0 && settings.loop !== '0';

    // Theme settings
    var theme = themeSettings.theme || 'light';
    var imageShape = themeSettings.image_shape || 'rounded';
    var widgetPosition = themeSettings.widget_position || 'bottom-right';
    var backgroundColor = themeSettings.background_color || '#ffffff';
    var textColor = themeSettings.text_color || '#1a1a1a';
    var accentColor = themeSettings.accent_color || '#FF6B35';

    // Calculate position styles
    var positionStyles = {
      'bottom-left': 'bottom:20px;left:20px;',
      'bottom-right': 'bottom:20px;right:20px;',
      'top-left': 'top:20px;left:20px;',
      'top-right': 'top:20px;right:20px;'
    };
    var positionStyle = positionStyles[widgetPosition] || positionStyles['bottom-right'];

    // Calculate image shape
    var imageRadius = {
      'rounded': '10px',
      'square': '0px',
      'circle': '50%'
    };
    var emojiRadius = imageRadius[imageShape] || imageRadius['rounded'];

    // Dark theme adjustments
    if (theme === 'dark') {
      backgroundColor = themeSettings.background_color || '#1a1a1a';
      textColor = themeSettings.text_color || '#ffffff';
    }

    var container = document.createElement('div');
    container.style.cssText = 'position:fixed;' + positionStyle + 'z-index:2147483647;font-family:-apple-system,BlinkMacSystemFont,sans-serif;max-width:300px;display:none;';
    document.body.appendChild(container);

    function show() {
      var n = notifications[index % notifications.length];
      index++;

      // Handle close button - check for false (boolean, string, or integer 0)
      var showCloseButton = settings.close_button !== false && 
                           settings.close_button !== 'false' && 
                           settings.close_button !== 0 && 
                           settings.close_button !== '0';
      var closeButton = showCloseButton ? 
        '<button onclick="this.closest(\'div\').parentElement.style.display=\'none\'" style="background:none;border:none;cursor:pointer;color:' + textColor + ';font-size:18px;padding:0;line-height:1;">\u00D7</button>' : '';

      container.innerHTML =
        '<div style="background:' + backgroundColor + ';border-radius:10px;padding:14px 16px;box-shadow:0 4px 20px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;animation:sp-slide-in 0.3s ease;">' +
        '<span style="font-size:24px;border-radius:' + emojiRadius + ';">' + (n.emoji || '\u{1F6D2}') + '</span>' +
        '<div style="flex:1;">' +
        '<div style="font-size:13px;font-weight:600;color:' + textColor + ';line-height:1.4;">' + n.message + '</div>' +
        (n.city ? '<div style="font-size:11px;color:' + textColor + ';opacity:0.7;margin-top:2px;">' + n.city + (n.country ? ', ' + n.country : '') + '</div>' : '') +
        (n.created_at ? '<div style="font-size:10px;color:' + textColor + ';opacity:0.5;margin-top:1px;">' + timeAgo(n.created_at) + '</div>' : '') +
        '</div>' +
        closeButton +
        '</div>';

      container.style.display = 'block';

      fetch(apiUrl + '/api/widget/' + pixelId + '/display', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id: n.id }),
      }).catch(function () {});

      setTimeout(function () {
        container.style.display = 'none';
        if (shouldLoop) {
          setTimeout(show, 1000);
        }
      }, displayDuration * 1000);
    }

    var style = document.createElement('style');
    style.textContent = '@keyframes sp-slide-in{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}';
    document.head.appendChild(style);

    setTimeout(show, 3000);
  }
})();