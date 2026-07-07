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

    // Check GDPR consent
    var gdprConsent = localStorage.getItem('sp_gdpr_consent_' + pixelId);
    
    // Filter out GDPR notifications if already consented or declined
    if (gdprConsent) {
      notifications = notifications.filter(function(n) {
        return n.type !== 'gdpr';
      });
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
      var closeBtnStyle = n.type === 'banner' ? 
        'background:none;border:none;cursor:pointer;color:' + textColor + ';font-size:18px;padding:0;line-height:1;' :
        'position:absolute;top:8px;right:8px;background:none;border:none;cursor:pointer;color:' + textColor + ';font-size:18px;padding:0;line-height:1;';

      var closeButton = showCloseButton ? 
        '<button class="sp-close-btn" onclick="event.stopPropagation();this.closest(\'div\').parentElement.style.display=\'none\'" style="' + closeBtnStyle + '">\u00D7</button>' : '';

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
      } else if (n.type === 'gdpr') {
        var m = n.metadata || {};
        var acceptText = m.accept_button_text || 'Accept';
        var declineText = m.decline_button_text || 'Decline';
        var policyUrl = m.policy_url || '#';
        
        container.style.cssText = 'position:fixed;bottom:20px;left:20px;right:20px;z-index:2147483647;font-family:-apple-system,BlinkMacSystemFont,sans-serif;display:none;';
        
        container.innerHTML = 
          '<div style="background:' + backgroundColor + ';border-radius:12px;padding:20px 24px;box-shadow:0 10px 30px rgba(0,0,0,0.15);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;animation:sp-slide-in 0.3s ease;" data-notification-id="' + n.id + '">' +
          '<div style="flex:1;min-width:250px;">' +
          '<div style="font-size:14px;color:' + textColor + ';line-height:1.5;">' + n.message + ' <a href="' + policyUrl + '" style="color:' + accentColor + ';text-decoration:none;font-weight:500;">Learn more</a></div>' +
          '</div>' +
          '<div style="display:flex;gap:10px;">' +
          '<button class="sp-gdpr-decline" style="background:transparent;border:1px solid ' + textColor + '40;color:' + textColor + ';border-radius:6px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;">' + declineText + '</button>' +
          '<button class="sp-gdpr-accept" style="background:' + accentColor + ';border:none;color:#fff;border-radius:6px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;">' + acceptText + '</button>' +
          '</div>' +
          '</div>';
          
        setTimeout(function() {
          var acceptBtn = container.querySelector('.sp-gdpr-accept');
          var declineBtn = container.querySelector('.sp-gdpr-decline');
          if (acceptBtn) acceptBtn.onclick = function() { 
            localStorage.setItem('sp_gdpr_consent_' + pixelId, 'accepted');
            container.style.display = 'none'; 
          };
          if (declineBtn) declineBtn.onclick = function() { 
            localStorage.setItem('sp_gdpr_consent_' + pixelId, 'declined');
            container.style.display = 'none'; 
          };
        }, 100);
      } else if (n.type === 'video') {
        container.style.cssText = 'position:fixed;' + positionStyle + 'z-index:2147483647;font-family:-apple-system,BlinkMacSystemFont,sans-serif;width:320px;display:none;';
        
        var videoEmbedUrl = n.product_url || '';
        if (videoEmbedUrl.includes('youtube.com/watch?v=')) {
          videoEmbedUrl = videoEmbedUrl.replace('watch?v=', 'embed/');
        }
        
        container.innerHTML =
          '<div style="background:' + backgroundColor + ';border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.15);animation:sp-slide-in 0.3s ease;" data-notification-id="' + n.id + '">' +
          '<div style="position:relative;width:100%;padding-top:56.25%;">' +
          '<iframe src="' + videoEmbedUrl + '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allow="autoplay; encrypted-media" allowfullscreen></iframe>' +
          '</div>' +
          '<div style="padding:12px 16px;position:relative;">' +
          '<div style="font-size:14px;font-weight:600;color:' + textColor + ';">' + n.message + '</div>' +
          closeButton +
          '</div>' +
          '</div>';
      } else if (n.type === 'email_subscription') {
        var m = n.metadata || {};
        var placeholder = m.placeholder_text || 'Enter your email...';
        var btnText = m.submit_button_text || 'Subscribe';
        
        container.style.cssText = 'position:fixed;' + positionStyle + 'z-index:2147483647;font-family:-apple-system,BlinkMacSystemFont,sans-serif;width:300px;display:none;';
        
        container.innerHTML =
          '<div style="position:relative;background:' + backgroundColor + ';border-radius:12px;padding:20px;box-shadow:0 8px 24px rgba(0,0,0,0.15);animation:sp-slide-in 0.3s ease;" data-notification-id="' + n.id + '">' +
          '<div style="font-size:16px;font-weight:bold;color:' + textColor + ';margin-bottom:8px;">' + n.message + '</div>' +
          '<form class="sp-email-form" style="display:flex;flex-direction:column;gap:10px;" onsubmit="event.preventDefault();">' +
          '<input type="email" placeholder="' + placeholder + '" required style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;box-sizing:border-box;" />' +
          '<button type="submit" style="width:100%;background:' + accentColor + ';color:#fff;border:none;padding:10px;border-radius:6px;font-weight:600;cursor:pointer;">' + btnText + '</button>' +
          '</form>' +
          closeButton +
          '</div>';
          
        setTimeout(function() {
          var form = container.querySelector('.sp-email-form');
          if (form) {
            form.onsubmit = function(e) {
              e.preventDefault();
              var emailInput = form.querySelector('input[type="email"]');
              var email = emailInput.value;
              var btn = form.querySelector('button');
              
              btn.textContent = 'Subscribing...';
              btn.disabled = true;

              fetch(apiUrl + '/api/widget/' + pixelId + '/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
              })
              .then(function(res) { return res.json(); })
              .then(function(data) {
                btn.textContent = 'Subscribed!';
                btn.style.background = '#22c55e';
                setTimeout(function() { container.style.display = 'none'; }, 2000);
              })
              .catch(function() {
                btn.textContent = 'Error. Try again';
                btn.disabled = false;
              });
            };
          }
        }, 100);
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

        var iconHtml = '';
        var m = n.metadata || {};
        if (n.type === 'page_analytics') {
           var liveCount = window.spLiveVisitors || 1;
           var count = (parseInt(m.base_count) || 0) + liveCount;
           iconHtml = '<div style="width:40px;height:40px;background:' + accentColor + '20;border-radius:' + emojiRadius + ';display:flex;align-items:center;justify-content:center;color:' + accentColor + ';font-weight:bold;font-size:16px;">' + count + '</div>';
        } else if (n.emoji && (n.emoji.startsWith('http://') || n.emoji.startsWith('https://') || n.emoji.startsWith('/'))) {
           iconHtml = '<img src="' + n.emoji + '" style="width:40px;height:40px;object-fit:cover;border-radius:' + emojiRadius + ';" />';
        } else {
           iconHtml = '<span class="sp-emoji" style="font-size:24px;display:flex;align-items:center;justify-content:center;width:40px;height:40px;background:#f1f5f9;border-radius:' + emojiRadius + ';">' + (n.emoji || '\u{1F6D2}') + '</span>';
        }

        container.innerHTML =
          '<div class="sp-notification-box" style="position:relative;background:' + backgroundColor + ';border-radius:10px;padding:14px 16px;box-shadow:0 4px 20px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;animation:sp-slide-in 0.3s ease;' + cursorStyle + '" data-notification-id="' + n.id + '">' +
          iconHtml +
          '<div class="sp-content" style="flex:1;padding-right:16px;">' +
          '<div class="sp-title" style="font-size:13px;font-weight:600;color:' + textColor + ';line-height:1.4;">' + n.message + '</div>' +
          reviewStars +
          (n.city && n.type !== 'page_analytics' ? '<div class="sp-subtitle" style="font-size:11px;color:' + textColor + ';opacity:0.7;margin-top:2px;">' + n.city + (n.country ? ', ' + n.country : '') + '</div>' : '') +
          (n.created_at && n.source !== 'manual' && n.type !== 'page_analytics' ? '<div class="sp-time" style="font-size:10px;color:' + textColor + ';opacity:0.5;margin-top:1px;">' + timeAgo(n.created_at) + '</div>' : '') +
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
          window.spLiveVisitors = data.page_visitors;
          showVisitorCount(data.page_visitors);
        } else {
          window.spLiveVisitors = 1;
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