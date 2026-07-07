(function () {
  var script = document.currentScript;
  var pixelId = script.getAttribute('data-pixel-id');
  var apiUrl = script.src.replace('/widget.js', '');

  if (!pixelId) return;

  fetch(apiUrl + '/api/widget/' + pixelId)
    .then(function (res) { return res.json(); })
    .then(function (data) {
      // Start visitor ping regardless of notifications
      startVisitorPing(apiUrl, pixelId);
      
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
        '<button class="sp-close-btn" onclick="event.stopPropagation();this.closest(\'div\').parentElement.style.display=\'none\'" style="background:none;border:none;cursor:pointer;color:' + textColor + ';font-size:18px;padding:0;line-height:1;">\u00D7</button>' : '';

      var hasProductUrl = n.product_url && n.product_url.length > 0;
      var cursorStyle = hasProductUrl ? 'cursor:pointer;' : '';

      if (n.type === 'banner') {
        container.style.cssText = 'position:fixed;top:0;left:0;right:0;width:100%;z-index:2147483647;font-family:-apple-system,BlinkMacSystemFont,sans-serif;display:none;';
        
        var buttonHtml = n.button_text ? '<button style="background:' + accentColor + ';color:#fff;border:none;border-radius:4px;padding:6px 12px;font-size:13px;font-weight:600;cursor:pointer;margin-left:15px;">' + n.button_text + '</button>' : '';

        container.innerHTML = 
          '<div style="background:' + backgroundColor + ';padding:10px 20px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 10px rgba(0,0,0,0.1);animation:sp-slide-down 0.3s ease;' + cursorStyle + '" data-notification-id="' + n.id + '">' +
          '<div style="font-size:14px;font-weight:600;color:' + textColor + ';">' + n.message + '</div>' +
          buttonHtml +
          '<div style="position:absolute;right:20px;">' + closeButton + '</div>' +
          '</div>';

        if (!document.getElementById('sp-banner-style')) {
          var s = document.createElement('style');
          s.id = 'sp-banner-style';
          s.textContent = '@keyframes sp-slide-down{from{transform:translateY(-100%);opacity:0}to{transform:translateY(0);opacity:1}}';
          document.head.appendChild(s);
        }
      } else {
        container.style.cssText = 'position:fixed;' + positionStyle + 'z-index:2147483647;font-family:-apple-system,BlinkMacSystemFont,sans-serif;max-width:300px;display:none;';
        var viewProductText = hasProductUrl ? '<div style="font-size:10px;color:' + accentColor + ';margin-top:4px;font-weight:500;">View Product \u2192</div>' : '';

        var reviewStars = '';
        if (n.type === 'review') {
          var rating = n.rating || 5;
          var stars = '';
          for (var i = 1; i <= 5; i++) {
            stars += (i <= rating) ? '<span style="color:#FFB800;">\u2605</span>' : '<span style="color:#ccc;">\u2605</span>';
          }
          reviewStars = '<div style="font-size:14px;margin-top:2px;">' + stars + '</div>';
        }

        container.innerHTML =
          '<div class="sp-notification-box" style="background:' + backgroundColor + ';border-radius:10px;padding:14px 16px;box-shadow:0 4px 20px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;animation:sp-slide-in 0.3s ease;' + cursorStyle + '" data-notification-id="' + n.id + '">' +
          '<span class="sp-emoji" style="font-size:24px;border-radius:' + emojiRadius + ';">' + (n.emoji || '\u{1F6D2}') + '</span>' +
          '<div class="sp-content" style="flex:1;">' +
          '<div class="sp-title" style="font-size:13px;font-weight:600;color:' + textColor + ';line-height:1.4;">' + n.message + '</div>' +
          reviewStars +
          (n.city ? '<div class="sp-subtitle" style="font-size:11px;color:' + textColor + ';opacity:0.7;margin-top:2px;">' + n.city + (n.country ? ', ' + n.country : '') + '</div>' : '') +
          (n.created_at && n.source !== 'manual' ? '<div class="sp-time" style="font-size:10px;color:' + textColor + ';opacity:0.5;margin-top:1px;">' + timeAgo(n.created_at) + '</div>' : '') +
          viewProductText +
          '</div>' +
          closeButton +
          '</div>';
      }

      container.style.display = 'block';

      // Track view to both old and new systems
      fetch(apiUrl + '/api/widget/' + pixelId + '/display', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id: n.id }),
      }).catch(function () {});

      // Track view to new analytics system
      fetch(apiUrl + '/api/analytics/track', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id: n.id, type: 'views' }),
      }).catch(function () {});

      // Track click and handle product URL
      var notificationElement = container.querySelector('[data-notification-id]');
      if (notificationElement) {
        notificationElement.addEventListener('click', function(e) {
          if (e.target.tagName === 'BUTTON') return; // Don't track click on close button
          
          // Track click
          fetch(apiUrl + '/api/analytics/track', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: n.id, type: 'clicks' }),
          }).catch(function () {});

          // Open product URL if exists
          if (hasProductUrl) {
            window.open(n.product_url, '_blank');
          }
        });
      }

      setTimeout(function () {
        container.style.display = 'none';
        if (shouldLoop || index < notifications.length) {
          setTimeout(show, 1000);
        }
      }, displayDuration * 1000);
    }

    var style = document.createElement('style');
    style.textContent = '@keyframes sp-slide-in{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}';
    document.head.appendChild(style);

    if (themeSettings.custom_css && themeSettings.custom_css_active !== false) {
      var customCssStyle = document.createElement('style');
      customCssStyle.id = 'sp-custom-css';
      customCssStyle.textContent = themeSettings.custom_css;
      document.head.appendChild(customCssStyle);
    }

    setTimeout(show, 3000);
  }

  function startVisitorPing(apiUrl, pixelId) {
    var currentPage = window.location.pathname;
    
    function ping() {
      fetch(apiUrl + '/api/visitor/' + pixelId + '/ping', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          page_url: currentPage 
        }),
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.page_visitors > 1) {
          showVisitorCount(data.page_visitors);
        } else {
          hideVisitorCount();
        }
      })
      .catch(function() {});
    }
    
    // Ping immediately then every 30 seconds
    ping();
    setInterval(ping, 30000);
  }

  function showVisitorCount(count) {
    var existing = document.getElementById('sp-visitor-count');
    if (existing) {
      existing.querySelector('.sp-count').textContent = count;
      return;
    }
    
    var el = document.createElement('div');
    el.id = 'sp-visitor-count';
    el.style.cssText = 'position:fixed;bottom:20px;right:20px;' +
        'z-index:2147483647;background:#1e1b4b;color:#fff;' +
        'padding:8px 14px;border-radius:50px;font-size:12px;' +
        'font-family:-apple-system,BlinkMacSystemFont,sans-serif;' +
        'box-shadow:0 4px 12px rgba(0,0,0,0.15);' +
        'display:flex;align-items:center;gap:6px;';
    el.innerHTML = 
        '<span style="width:8px;height:8px;background:#22c55e;' +
        'border-radius:50%;display:inline-block;' +
        'animation:sp-pulse 2s infinite;"></span>' +
        '<span class="sp-count">' + count + '</span>' +
        '<span> people viewing</span>';
    document.body.appendChild(el);
    
    // Add pulse animation
    var style = document.createElement('style');
    style.textContent = 
        '@keyframes sp-pulse{' +
        '0%{box-shadow:0 0 0 0 rgba(34,197,94,0.4)}' +
        '70%{box-shadow:0 0 0 6px rgba(34,197,94,0)}' +
        '100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}}';
    document.head.appendChild(style);
  }

  function hideVisitorCount() {
    var el = document.getElementById('sp-visitor-count');
    if (el) el.style.display = 'none';
  }
})();