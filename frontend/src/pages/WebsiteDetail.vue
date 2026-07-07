<template>
  <div class="website-detail">
    <Sidebar />
    <div class="main-content">
      <div class="page-header">
        <h1>{{ website?.name }}</h1>
        <p class="domain">{{ website?.domain }}</p>
      </div>

      <div v-if="loading" class="loading-container">
        <el-skeleton :rows="5" animated />
      </div>

      <div v-else-if="website" class="content">
        <el-tabs v-model="activeTab" class="site-tabs">
          <!-- Overview Tab -->
          <el-tab-pane label="Overview" name="overview">
            <div class="tab-content">
              <!-- Statistic Cards -->
              <el-row :gutter="20" class="stats-row">
                <el-col :span="6">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="Total Displays" :value="analytics.total_displays">
                      <template #prefix>
                        <el-icon :size="24" color="#FF6B35"><View /></el-icon>
                      </template>
                    </el-statistic>
                  </el-card>
                </el-col>
                <el-col :span="6">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="Today" :value="analytics.displays_today">
                      <template #prefix>
                        <el-icon :size="24" color="#22c55e"><Calendar /></el-icon>
                      </template>
                    </el-statistic>
                  </el-card>
                </el-col>
                <el-col :span="6">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="This Week" :value="analytics.displays_this_week">
                      <template #prefix>
                        <el-icon :size="24" color="#a855f7"><Lightning /></el-icon>
                      </template>
                    </el-statistic>
                  </el-card>
                </el-col>
                <el-col :span="6">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="Active Notifications" :value="analytics.notifications.filter(n => n.is_active).length">
                      <template #prefix>
                        <el-icon :size="24" color="#409eff"><Bell /></el-icon>
                      </template>
                    </el-statistic>
                  </el-card>
                </el-col>
              </el-row>

              <!-- Recent Notifications -->
              <el-card class="recent-card" shadow="hover">
                <template #header>
                  <div class="card-header">
                    <span>Recent Notifications</span>
                  </div>
                </template>
                <div class="notifications-list">
                  <el-card v-for="notification in analytics.notifications.slice(0, 5)" :key="notification.id" 
                           class="notification-card" shadow="never">
                    <div class="notification-content">
                      <div class="notification-left">
                        <div class="emoji-circle">{{ notification.emoji }}</div>
                        <div class="notification-text">
                          <h3>{{ notification.message }}</h3>
                          <el-tag :type="getNotificationTypeTag(notification.type)" size="small">{{ notification.type }}</el-tag>
                        </div>
                      </div>
                      <div class="notification-right">
                        <el-tag type="primary">{{ notification.total_displays }} shown</el-tag>
                      </div>
                    </div>
                  </el-card>
                </div>
              </el-card>
            </div>
          </el-tab-pane>

          <!-- Notifications Tab -->
          <el-tab-pane label="Notifications" name="notifications">
            <div class="tab-content">
              <div class="tab-header">
                <h2>Notifications</h2>
                <el-button type="primary" :icon="Plus" @click="showAddModal = true">Create Notification</el-button>
              </div>

              <el-tabs v-model="notificationTab" class="notification-tabs">
                <el-tab-pane label="Auto" name="auto">
                  <el-alert v-if="autoNotifications.length === 0" type="info" :closable="false" class="auto-empty-alert">
                    <div class="auto-empty-content">
                      <p>No automatic notifications yet. Connect your WooCommerce store using the webhook URL below.</p>
                      <div class="webhook-url-box">
                        <el-input v-model="webhookUrl" readonly class="webhook-input" />
                        <el-button type="primary" :icon="copiedWebhook ? Check : DocumentCopy" @click="copyWebhookUrl">
                          {{ copiedWebhook ? 'Copied!' : 'Copy' }}
                        </el-button>
                      </div>
                    </div>
                  </el-alert>

                  <div v-else class="notifications-list">
                    <el-card v-for="notification in autoNotifications" :key="notification.id" 
                             class="notification-card auto-card" shadow="never">
                      <div class="notification-content">
                        <div class="notification-left">
                          <div class="emoji-circle">{{ notification.emoji }}</div>
                          <div class="notification-text">
                            <h3>{{ notification.message }}</h3>
                            <el-tag :type="getSourceTag(notification.source)" size="small">{{ getSourceLabel(notification.source) }}</el-tag>
                            <p class="location">{{ notification.city }}{{ notification.country ? ', ' + notification.country : '' }}</p>
                          </div>
                        </div>
                        <div class="notification-right">
                          <el-tag type="primary">{{ notification.total_displays }} shown</el-tag>
                          <p class="created-time">{{ formatRelativeTime(notification.created_at) }}</p>
                          <div class="notification-actions">
                            <el-button type="danger" size="small" :icon="Delete" @click="deleteNotification(notification.id)">Delete</el-button>
                          </div>
                        </div>
                      </div>
                    </el-card>
                  </div>
                </el-tab-pane>

                <el-tab-pane label="Manual" name="manual">
                  <el-empty v-if="manualNotifications.length === 0" description="No manual notifications yet. Add your first one!">
                    <el-button type="primary" :icon="Plus" @click="showAddModal = true">Add Notification</el-button>
                  </el-empty>

                  <div v-else class="notifications-list">
                    <el-card v-for="notification in manualNotifications" :key="notification.id" 
                             :class="['notification-card', 'border-' + notification.type]" shadow="never">
                      <div class="notification-content">
                        <div class="notification-left">
                          <div class="emoji-circle">{{ notification.emoji }}</div>
                          <div class="notification-text">
                            <h3>{{ notification.message }}</h3>
                            <el-tag :type="getNotificationTypeTag(notification.type)">{{ notification.type }}</el-tag>
                          </div>
                        </div>
                        <div class="notification-right">
                          <el-tag type="primary">{{ notification.total_displays }} shown</el-tag>
                          <p class="last-shown">Last shown: {{ formatLastShown(notification.last_shown) }}</p>
                          <div class="notification-actions">
                            <el-button :type="notification.is_active ? 'success' : 'info'" size="small" @click="toggleNotification(notification)">
                              {{ notification.is_active ? 'Active' : 'Inactive' }}
                            </el-button>
                            <el-button type="danger" size="small" :icon="Delete" @click="deleteNotification(notification.id)">Delete</el-button>
                          </div>
                        </div>
                      </div>
                    </el-card>
                  </div>
                </el-tab-pane>
              </el-tabs>
            </div>
          </el-tab-pane>

          <!-- Sources Tab -->
          <el-tab-pane label="Sources" name="sources">
            <div class="tab-content">
              <div class="tab-header">
                <h2>Sources</h2>
                <p>Connect services that automatically generate notifications.</p>
              </div>

              <el-tabs v-model="sourceTab" class="source-tabs">
                <!-- WooCommerce Tab -->
                <el-tab-pane label="WooCommerce" name="woocommerce">
                  <el-card class="platform-card" shadow="hover">
                    <template #header>
                      <div class="platform-header">
                        <div class="platform-info">
                          <span class="platform-icon">🛒</span>
                          <h3>WooCommerce</h3>
                        </div>
                        <el-tag type="success">Connected</el-tag>
                      </div>
                    </template>
                    <div class="platform-content">
                      <h4>Setup Instructions</h4>
                      <ol class="setup-steps">
                        <li>Go to your WooCommerce dashboard</li>
                        <li>Navigate to Settings → Webhooks</li>
                        <li>Click "Add webhook"</li>
                        <li>Name: SocialPulse Notifications</li>
                        <li>Status: Active</li>
                        <li>Topic: Order created</li>
                        <li>Delivery URL: Copy the URL below</li>
                        <li>Save the webhook</li>
                      </ol>
                      <div class="webhook-section" style="margin-top: 15px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Sales Webhook URL <span style="font-size:12px; font-weight: normal;">(Topic: Order created)</span></label>
                        <div class="webhook-url-box">
                          <el-input v-model="wooCommerceWebhookUrl" readonly class="webhook-input" />
                          <el-button type="primary" :icon="copiedWooCommerce ? Check : DocumentCopy" @click="copyWooCommerceWebhook">
                            {{ copiedWooCommerce ? 'Copied!' : 'Copy' }}
                          </el-button>
                        </div>
                      </div>
                      <div class="webhook-section" style="margin-top: 15px;">
                        <label style="display:block; margin-bottom: 5px; font-weight: 600;">Reviews Webhook URL <span style="font-size:12px; font-weight: normal;">(Topic: Product review created)</span></label>
                        <div class="webhook-url-box">
                          <el-input v-model="wooCommerceReviewWebhookUrl" readonly class="webhook-input" />
                          <el-button type="primary" :icon="copiedWooCommerceReview ? Check : DocumentCopy" @click="copyWooCommerceReviewWebhook">
                            {{ copiedWooCommerceReview ? 'Copied!' : 'Copy' }}
                          </el-button>
                        </div>
                      </div>
                      <el-link href="https://woocommerce.com/document/webhooks/" target="_blank" type="primary">
                        View WooCommerce Documentation →
                      </el-link>
                    </div>
                  </el-card>
                </el-tab-pane>

                <!-- Stripe Tab -->
                <el-tab-pane label="Stripe" name="stripe">
                  <el-card class="platform-card" shadow="hover">
                    <template #header>
                      <div class="platform-header">
                        <div class="platform-info">
                          <span class="platform-icon">💳</span>
                          <h3>Stripe</h3>
                        </div>
                        <el-tag type="success">Connected</el-tag>
                      </div>
                    </template>
                    <div class="platform-content">
                      <h4>Setup Instructions</h4>
                      <ol class="setup-steps">
                        <li>Go to your Stripe dashboard</li>
                        <li>Navigate to Developers → Webhooks</li>
                        <li>Click "Add endpoint"</li>
                        <li>Endpoint URL: Copy the URL below</li>
                        <li>Select events to send: checkout.session.completed, payment_intent.succeeded, charge.succeeded</li>
                        <li>Click "Add endpoint"</li>
                      </ol>
                      <div class="webhook-section">
                        <label>Webhook URL</label>
                        <div class="webhook-url-box">
                          <el-input v-model="stripeWebhookUrl" readonly class="webhook-input" />
                          <el-button type="primary" :icon="copiedStripe ? Check : DocumentCopy" @click="copyStripeWebhook">
                            {{ copiedStripe ? 'Copied!' : 'Copy' }}
                          </el-button>
                        </div>
                      </div>
                      <el-link href="https://stripe.com/docs/webhooks" target="_blank" type="primary">
                        View Stripe Documentation →
                      </el-link>
                    </div>
                  </el-card>
                </el-tab-pane>

                <!-- SureCart Tab -->
                <el-tab-pane label="SureCart" name="surecart">
                  <el-card class="platform-card" shadow="hover">
                    <template #header>
                      <div class="platform-header">
                        <div class="platform-info">
                          <span class="platform-icon">🛍️</span>
                          <h3>SureCart</h3>
                        </div>
                        <el-tag type="info">Coming Soon</el-tag>
                      </div>
                    </template>
                    <div class="platform-content">
                      <el-empty description="SureCart integration coming soon" />
                    </div>
                  </el-card>
                </el-tab-pane>

                <!-- EDD Tab -->
                <el-tab-pane label="EDD" name="edd">
                  <el-card class="platform-card" shadow="hover">
                    <template #header>
                      <div class="platform-header">
                        <div class="platform-info">
                          <span class="platform-icon">📦</span>
                          <h3>Easy Digital Downloads</h3>
                        </div>
                        <el-tag type="info">Coming Soon</el-tag>
                      </div>
                    </template>
                    <div class="platform-content">
                      <el-empty description="Easy Digital Downloads integration coming soon" />
                    </div>
                  </el-card>
                </el-tab-pane>
              </el-tabs>
            </div>
          </el-tab-pane>

          <!-- Widget Tab -->
          <el-tab-pane label="Widget" name="widget">
            <div class="tab-content">
              <div class="tab-header">
                <h2>Widget</h2>
              </div>

              <el-card class="snippet-card" shadow="hover">
                <template #header>
                  <div class="card-header">
                    <span>Installation</span>
                  </div>
                </template>
                <p class="subtitle">Add this snippet to your website to show social proof notifications</p>
                <div class="snippet-box">
                  <el-input v-model="snippet" type="textarea" :rows="3" readonly />
                  <el-button type="primary" :icon="copied ? Check : DocumentCopy" @click="copySnippet">
                    {{ copied ? 'Copied!' : 'Copy' }}
                  </el-button>
                </div>
              </el-card>

              <el-card class="widget-settings-card" shadow="hover">
                <template #header>
                  <div class="card-header">
                    <span>Appearance</span>
                  </div>
                </template>
                <div class="widget-settings">
                  <el-form label-position="top">
                    <el-form-item label="Position">
                      <el-select v-model="widgetSettings.position" style="width: 100%">
                        <el-option label="Bottom Right" value="bottom-right" />
                        <el-option label="Bottom Left" value="bottom-left" />
                        <el-option label="Top Right" value="top-right" />
                        <el-option label="Top Left" value="top-left" />
                      </el-select>
                    </el-form-item>
                    <el-form-item label="Theme">
                      <el-select v-model="widgetSettings.theme" style="width: 100%">
                        <el-option label="Light" value="light" />
                        <el-option label="Dark" value="dark" />
                      </el-select>
                    </el-form-item>
                  </el-form>
                </div>
              </el-card>
            </div>
          </el-tab-pane>

          <!-- Analytics Tab -->
          <el-tab-pane label="Analytics" name="analytics">
            <div class="tab-content">
              <div class="tab-header">
                <h2>Analytics</h2>
              </div>

              <el-row :gutter="20" class="stats-row">
                <el-col :span="4">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="Total Views" :value="analytics.summary?.total_views || 0" />
                  </el-card>
                </el-col>
                <el-col :span="4">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="Total Clicks" :value="analytics.summary?.total_clicks || 0" />
                  </el-card>
                </el-col>
                <el-col :span="4">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="CTR" :value="analytics.summary?.ctr || 0" suffix="%" />
                  </el-card>
                </el-col>
                <el-col :span="4">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="Total Displays" :value="analytics.total_displays" />
                  </el-card>
                </el-col>
                <el-col :span="4">
                  <el-card shadow="hover" class="stat-card live-visitors-card">
                    <el-tooltip content="Visitors active in last 5 minutes" placement="top">
                      <div class="live-visitors-content">
                        <span class="live-dot"></span>
                        <el-statistic title="Live Visitors" :value="analytics.active_visitors || 0" />
                      </div>
                    </el-tooltip>
                  </el-card>
                </el-col>
              </el-row>

              <el-card v-if="analytics.total_displays > 0 && analytics.notifications.length > 0" class="chart-card" shadow="hover">
                <template #header>
                  <div class="card-header">
                    <span>Displays per Notification</span>
                  </div>
                </template>
                <div class="chart-container">
                  <div v-for="notification in analytics.notifications" :key="notification.id" class="chart-bar-wrapper">
                    <div class="chart-bar-label">{{ notification.message.substring(0, 30) }}{{ notification.message.length > 30 ? '...' : '' }}</div>
                    <div class="chart-bar">
                      <el-progress :percentage="getBarWidth(notification.total_displays)" :stroke-width="32" :show-text="false" color="#FF6B35" />
                      <span class="chart-bar-value">{{ notification.total_displays }}</span>
                    </div>
                  </div>
                </div>
              </el-card>
            </div>
          </el-tab-pane>

          <!-- Settings Tab -->
          <el-tab-pane label="Settings" name="settings">
            <div class="tab-content">
              <div class="tab-header">
                <h2>Settings</h2>
              </div>

              <el-tabs v-model="settingsTab" class="settings-tabs">
                <!-- General Tab -->
                <el-tab-pane label="Site Information" name="general">
                  <el-card class="settings-card" shadow="hover">
                    <template #header>
                      <div class="card-header">
                        <span>Site Information</span>
                      </div>
                    </template>
                    <el-descriptions :column="1" border>
                      <el-descriptions-item label="Site Name">{{ website.name }}</el-descriptions-item>
                      <el-descriptions-item label="Website URL">{{ website.domain }}</el-descriptions-item>
                      <el-descriptions-item label="Status">
                        <el-tag :type="website.is_active ? 'success' : 'info'">{{ website.is_active ? 'Active' : 'Disabled' }}</el-tag>
                      </el-descriptions-item>
                      <el-descriptions-item label="Created">{{ formatDate(website.created_at) }}</el-descriptions-item>
                    </el-descriptions>
                  </el-card>

                  <el-card class="danger-card" shadow="hover" style="margin-top: 24px;">
                    <template #header>
                      <div class="card-header">
                        <span style="color: var(--el-color-danger); font-weight: 600;">Danger Zone</span>
                      </div>
                    </template>
                    <p style="margin-bottom: 15px; color: var(--el-text-color-secondary); font-size: 14px;">
                      Once you delete a site, there is no going back. Please be certain.
                    </p>
                    <el-button type="danger" @click="deleteSite">Delete Site</el-button>
                  </el-card>
                </el-tab-pane>

                <!-- Display Rules Tab -->
                <el-tab-pane label="Display Rules" name="display">
                  <el-card class="settings-card" shadow="hover">
                    <el-form label-position="top" :model="displaySettings">
                      <el-form-item label="Display Duration (seconds)">
                        <el-input-number v-model="displaySettings.display_for" :min="1" :max="60" style="width: 100%" />
                        <div class="form-help">How long each notification displays</div>
                      </el-form-item>
                      <el-form-item label="Max Notifications">
                        <el-input-number v-model="displaySettings.display_last" :min="1" :max="50" style="width: 100%" />
                        <div class="form-help">Maximum number of notifications to show</div>
                      </el-form-item>
                      <el-form-item label="Display From Last">
                        <el-row :gutter="10">
                          <el-col :span="8">
                            <el-input-number v-model="displaySettings.display_from_days" :min="0" :max="365" placeholder="Days" style="width: 100%" />
                          </el-col>
                          <el-col :span="8">
                            <el-input-number v-model="displaySettings.display_from_hours" :min="0" :max="23" placeholder="Hours" style="width: 100%" />
                          </el-col>
                          <el-col :span="8">
                            <el-input-number v-model="displaySettings.display_from_minutes" :min="0" :max="59" placeholder="Minutes" style="width: 100%" />
                          </el-col>
                        </el-row>
                        <div class="form-help">Show notifications from the last X days/hours/minutes</div>
                      </el-form-item>
                      <el-form-item label="Loop Notifications">
                        <el-switch v-model="displaySettings.loop" />
                        <div class="form-help">Loop notifications continuously</div>
                      </el-form-item>
                      <el-form-item label="Open Links in New Tab">
                        <el-switch v-model="displaySettings.link_open" />
                      </el-form-item>
                      <el-form-item label="Show For">
                        <el-select v-model="displaySettings.show_on_display" style="width: 100%">
                          <el-option label="Always" value="always" />
                          <el-option label="Logged Out Users" value="logged_out_user" />
                          <el-option label="Logged In Users" value="logged_in_user" />
                        </el-select>
                      </el-form-item>
                      <el-form-item label="Show Close Button">
                        <el-switch v-model="displaySettings.close_button" />
                      </el-form-item>
                      <el-form-item label="Hide on Mobile">
                        <el-switch v-model="displaySettings.hide_on_mobile" />
                      </el-form-item>
                      <el-form-item>
                        <el-button type="primary" :loading="savingDisplaySettings" @click="saveDisplaySettings">Save Display Settings</el-button>
                      </el-form-item>
                    </el-form>
                  </el-card>
                </el-tab-pane>

                <!-- Theme Settings Tab -->
                <el-tab-pane label="Theme Settings" name="theme">
                  <el-card class="settings-card" shadow="hover">
                    <el-form label-position="top" :model="themeSettings">
                      <el-form-item label="Theme">
                        <el-select v-model="themeSettings.theme" style="width: 100%">
                          <el-option label="Light" value="light" />
                          <el-option label="Dark" value="dark" />
                        </el-select>
                        <div class="form-help">Choose light or dark theme</div>
                      </el-form-item>
                      <el-form-item label="Image Shape">
                        <el-select v-model="themeSettings.image_shape" style="width: 100%">
                          <el-option label="Rounded" value="rounded" />
                          <el-option label="Square" value="square" />
                          <el-option label="Circle" value="circle" />
                        </el-select>
                        <div class="form-help">Shape of the emoji/icon</div>
                      </el-form-item>
                      <el-form-item label="Widget Position">
                        <el-select v-model="themeSettings.widget_position" style="width: 100%">
                          <el-option label="Bottom Left" value="bottom-left" />
                          <el-option label="Bottom Right" value="bottom-right" />
                          <el-option label="Top Left" value="top-left" />
                          <el-option label="Top Right" value="top-right" />
                        </el-select>
                        <div class="form-help">Position of the widget on screen</div>
                      </el-form-item>
                      <el-form-item label="Background Color">
                        <el-color-picker v-model="themeSettings.background_color" />
                        <div class="form-help">Widget background color</div>
                      </el-form-item>
                      <el-form-item label="Text Color">
                        <el-color-picker v-model="themeSettings.text_color" />
                        <div class="form-help">Widget text color</div>
                      </el-form-item>
                      <el-form-item label="Accent Color">
                        <el-color-picker v-model="themeSettings.accent_color" />
                        <div class="form-help">Accent color for highlights</div>
                      </el-form-item>
                      <el-form-item label="Custom CSS">
                        <el-input
                          v-model="themeSettings.custom_css"
                          type="textarea"
                          :rows="6"
                          placeholder=".sp-widget-container { border: 1px solid red; }"
                          style="font-family: monospace;"
                        />
                        <div class="form-help">Write custom CSS to override widget styles. Use exactly how NotificationX does it.</div>
                      </el-form-item>
                      <el-form-item>
                        <el-button type="primary" :loading="savingThemeSettings" @click="saveThemeSettings">Save Theme Settings</el-button>
                      </el-form-item>
                    </el-form>
                  </el-card>
                </el-tab-pane>
              </el-tabs>
            </div>
          </el-tab-pane>
        </el-tabs>
      </div>
    </div>

    <!-- Add Notification Dialog -->
    <el-dialog v-model="showAddModal" title="Create Notification" width="500px">
      <el-form @submit.prevent="handleAddNotification" :model="newNotification" label-position="top">
        <el-form-item label="Type">
          <el-select v-model="newNotification.type" required style="width: 100%">
            <el-option label="Purchase" value="purchase" />
            <el-option label="Signup" value="signup" />
            <el-option label="Review" value="review" />
            <el-option label="Top Banner" value="banner" />
          </el-select>
        </el-form-item>
        <el-form-item label="Rating" v-if="newNotification.type === 'review'">
          <el-rate v-model="newNotification.rating" :max="5" />
        </el-form-item>
        <el-form-item label="Button Text" v-if="newNotification.type === 'banner'">
          <el-input v-model="newNotification.button_text" type="text" placeholder="e.g. Buy Now" />
        </el-form-item>
        <el-form-item label="Link URL" v-if="newNotification.type === 'banner' || newNotification.type === 'purchase'">
          <el-input v-model="newNotification.product_url" type="url" placeholder="https://example.com/product" />
        </el-form-item>
        <el-form-item label="Message">
          <el-input v-model="newNotification.message" type="text" required 
                     placeholder="e.g. John from New York just purchased Pro Plan" />
        </el-form-item>
        <el-form-item label="City (optional)">
          <el-input v-model="newNotification.city" type="text" placeholder="New York" />
        </el-form-item>
        <el-form-item label="Country (optional)" v-if="newNotification.type !== 'banner'">
          <el-input v-model="newNotification.country" type="text" placeholder="USA" />
        </el-form-item>
        <el-form-item label="Emoji" v-if="newNotification.type !== 'banner'">
          <el-input v-model="newNotification.emoji" type="text" placeholder="🛒" />
        </el-form-item>
        <el-alert v-if="addError" :title="addError" type="error" :closable="false" style="margin-bottom: 1rem" />
      </el-form>
      <template #footer>
        <el-button @click="showAddModal = false">Cancel</el-button>
        <el-button type="primary" :loading="adding" @click="handleAddNotification">
          {{ adding ? 'Adding...' : 'Save' }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import api from '../services/api'
import echo from '../services/echo'
import Sidebar from '../components/Sidebar.vue'
import { View, Calendar, Lightning, Plus, DocumentCopy, Check, Delete, Bell } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'

export default {
  name: 'WebsiteDetail',
  components: {
    Sidebar,
    View,
    Calendar,
    Lightning,
    Plus,
    DocumentCopy,
    Check,
    Delete,
    Bell
  },
  data() {
    return {
      website: null,
      analytics: {
        total_displays: 0,
        displays_this_week: 0,
        displays_today: 0,
        notifications: []
      },
      snippet: '',
      loading: true,
      showAddModal: false,
      adding: false,
      addError: '',
      copied: false,
      activeTab: this.$route.params.tab || 'overview',
      notificationTab: this.$route.query.nTab || 'auto',
      sourceTab: this.$route.query.sTab || 'woocommerce',
      settingsTab: this.$route.query.setTab || 'general',
      copiedWebhook: false,
      copiedWooCommerce: false,
      copiedWooCommerceReview: false,
      copiedStripe: false,
      wooCommerceWebhookUrl: '',
      wooCommerceReviewWebhookUrl: '',
      stripeWebhookUrl: '',
      widgetSettings: {
        position: 'bottom-right',
        theme: 'light'
      },
      displaySettings: {
        display_for: 5,
        display_last: 20,
        display_from_days: 30,
        display_from_hours: 0,
        display_from_minutes: 0,
        loop: true,
        link_open: false,
        show_on_display: 'always',
        close_button: true,
        hide_on_mobile: false
      },
      savingDisplaySettings: false,
      themeSettings: {
        theme: 'light',
        image_shape: 'rounded',
        widget_position: 'bottom-right',
        background_color: '#ffffff',
        text_color: '#1a1a1a',
        accent_color: '#FF6B35',
        custom_css: ''
      },
      savingThemeSettings: false,
      newNotification: {
        type: 'purchase',
        message: '',
        city: '',
        country: '',
        emoji: '🛒',
        rating: 5,
        button_text: '',
        product_url: ''
      }
    }
  },
  computed: {
    autoNotifications() {
      return this.analytics.notifications.filter(n => n.source === 'woocommerce' || n.source === 'stripe')
    },
    manualNotifications() {
      return this.analytics.notifications.filter(n => n.source === 'manual')
    },
    webhookUrl() {
      if (!this.website) return ''
      return import.meta.env.VITE_API_URL.replace('/api', '') + '/api/webhook/woocommerce/' + this.website.pixel_id
    },
    wooCommerceWebhookUrl() {
      if (!this.website) return ''
      return import.meta.env.VITE_API_URL.replace('/api', '') + '/api/webhook/woocommerce/' + this.website.pixel_id
    },
    wooCommerceReviewWebhookUrl() {
      if (!this.website) return ''
      return import.meta.env.VITE_API_URL.replace('/api', '') + '/api/webhook/woocommerce-review/' + this.website.pixel_id
    },
    stripeWebhookUrl() {
      if (!this.website) return ''
      return import.meta.env.VITE_API_URL.replace('/api', '') + '/api/webhook/stripe/' + this.website.pixel_id
    }
  },
  async mounted() {
    await Promise.all([
      this.fetchWebsite(),
      this.fetchAnalytics(),
      this.fetchSnippet()
    ])
    
    // Start listening to WebSocket events for real-time live visitors
    this.initEcho()
  },
  watch: {
    activeTab(newTab) {
      if (newTab !== this.$route.params.tab) {
        this.$router.replace({ path: `/sites/${this.$route.params.id}/${newTab}`, query: this.$route.query })
      }
    },
    notificationTab(newVal) {
      this.$router.replace({ query: { ...this.$route.query, nTab: newVal } })
    },
    sourceTab(newVal) {
      this.$router.replace({ query: { ...this.$route.query, sTab: newVal } })
    },
    settingsTab(newVal) {
      this.$router.replace({ query: { ...this.$route.query, setTab: newVal } })
    }
  },
  methods: {
    async fetchWebsite() {
      try {
        const response = await api.get(`/websites/${this.$route.params.id}`)
        this.website = response.data
        // Load display settings from website data
        if (this.website.display_for !== undefined) this.displaySettings.display_for = this.website.display_for
        if (this.website.display_last !== undefined) this.displaySettings.display_last = this.website.display_last
        if (this.website.display_from_days !== undefined) this.displaySettings.display_from_days = this.website.display_from_days
        if (this.website.display_from_hours !== undefined) this.displaySettings.display_from_hours = this.website.display_from_hours
        if (this.website.display_from_minutes !== undefined) this.displaySettings.display_from_minutes = this.website.display_from_minutes
        if (this.website.loop !== undefined) this.displaySettings.loop = this.website.loop
        if (this.website.link_open !== undefined) this.displaySettings.link_open = this.website.link_open
        if (this.website.show_on_display !== undefined) this.displaySettings.show_on_display = this.website.show_on_display
        if (this.website.close_button !== undefined) this.displaySettings.close_button = this.website.close_button
        if (this.website.hide_on_mobile !== undefined) this.displaySettings.hide_on_mobile = this.website.hide_on_mobile
        // Load theme settings from website data
        if (this.website.theme !== undefined) this.themeSettings.theme = this.website.theme
        if (this.website.image_shape !== undefined) this.themeSettings.image_shape = this.website.image_shape
        if (this.website.widget_position !== undefined) this.themeSettings.widget_position = this.website.widget_position
        if (this.website.background_color !== undefined) this.themeSettings.background_color = this.website.background_color
        if (this.website.text_color !== undefined) this.themeSettings.text_color = this.website.text_color
        if (this.website.accent_color !== undefined) this.themeSettings.accent_color = this.website.accent_color
        if (this.website.custom_css !== undefined) this.themeSettings.custom_css = this.website.custom_css
      } catch (err) {
        console.error('Failed to fetch website:', err)
      }
    },
    async fetchAnalytics() {
      try {
        const response = await api.get(`/websites/${this.$route.params.id}/analytics`)
        this.analytics = response.data
        
        // Fetch click stats
        try {
          const statsResponse = await api.get(`/websites/${this.$route.params.id}/analytics/stats`)
          this.analytics.summary = statsResponse.data.summary
        } catch (statsErr) {
          console.error('Failed to fetch click stats (migration may not be run):', statsErr)
          this.analytics.summary = { total_views: 0, total_clicks: 0, ctr: 0 }
        }
      } catch (err) {
        console.error('Failed to fetch analytics:', err)
      } finally {
        this.loading = false
      }
    },
    async fetchSnippet() {
      try {
        const response = await api.get(`/websites/${this.$route.params.id}/snippet`)
        this.snippet = response.data.snippet
      } catch (err) {
        console.error('Failed to fetch snippet:', err)
      }
    },
    async handleAddNotification() {
      this.adding = true
      this.addError = ''
      try {
        const response = await api.post(`/websites/${this.$route.params.id}/notifications`, this.newNotification)
        this.showAddModal = false
        this.newNotification = { type: 'purchase', message: '', city: '', country: '', emoji: '🛒', rating: 5, button_text: '', product_url: '' }
        await this.fetchAnalytics()
        ElMessage.success('Notification added!')
      } catch (err) {
        this.addError = err.response?.data?.message || 'Failed to add notification'
      } finally {
        this.adding = false
      }
    },
    async toggleNotification(notification) {
      try {
        await api.patch(`/notifications/${notification.id}/toggle`)
        await this.fetchAnalytics()
      } catch (err) {
        ElMessage.error('Failed to toggle notification')
      }
    },
    async deleteNotification(id) {
      try {
        await ElMessageBox.confirm('Are you sure you want to delete this notification?', 'Confirm Delete', {
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
          type: 'warning'
        })
        await api.delete(`/notifications/${id}`)
        await this.fetchAnalytics()
        ElMessage.success('Notification deleted')
      } catch (err) {
        if (err !== 'cancel') {
          ElMessage.error('Failed to delete notification')
        }
      }
    },
    async deleteSite() {
      try {
        await ElMessageBox.confirm('Are you sure you want to delete this site? This action cannot be undone.', 'Confirm Delete', {
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
          type: 'warning'
        })
        await api.delete(`/websites/${this.$route.params.id}`)
        this.$router.push('/sites')
        ElMessage.success('Site deleted')
      } catch (err) {
        if (err !== 'cancel') {
          ElMessage.error('Failed to delete site')
        }
      }
    },
    async saveDisplaySettings() {
      this.savingDisplaySettings = true
      try {
        await api.patch(`/websites/${this.$route.params.id}`, this.displaySettings)
        ElMessage.success('Display settings saved successfully!')
      } catch (err) {
        ElMessage.error('Failed to save display settings')
      } finally {
        this.savingDisplaySettings = false
      }
    },
    async saveThemeSettings() {
      this.savingThemeSettings = true
      try {
        await api.patch(`/websites/${this.$route.params.id}`, this.themeSettings)
        ElMessage.success('Theme settings saved successfully!')
      } catch (err) {
        ElMessage.error('Failed to save theme settings')
      } finally {
        this.savingThemeSettings = false
      }
    },
    copySnippet() {
      navigator.clipboard.writeText(this.snippet)
      this.copied = true
      setTimeout(() => this.copied = false, 2000)
    },
    copyWebhookUrl() {
      navigator.clipboard.writeText(this.webhookUrl)
      this.copiedWebhook = true
      setTimeout(() => this.copiedWebhook = false, 2000)
    },
    copyWooCommerceWebhook() {
      navigator.clipboard.writeText(this.wooCommerceWebhookUrl)
      this.copiedWooCommerce = true
      setTimeout(() => this.copiedWooCommerce = false, 2000)
    },
    copyStripeWebhook() {
      navigator.clipboard.writeText(this.stripeWebhookUrl)
      this.copiedStripe = true
      setTimeout(() => this.copiedStripe = false, 2000)
    },
    formatLastShown(date) {
      if (!date) return 'Never shown'
      
      const now = new Date()
      const past = new Date(date)
      const diffMs = now - past
      const diffMins = Math.floor(diffMs / 60000)
      const diffHours = Math.floor(diffMs / 3600000)
      const diffDays = Math.floor(diffMs / 86400000)

      if (diffMins < 1) return 'Just now'
      if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`
      if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`
      if (diffDays === 1) return 'Yesterday'
      return `${diffDays} days ago`
    },
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString()
    },
    getBarWidth(displays) {
      const maxDisplays = Math.max(...this.analytics.notifications.map(n => n.total_displays))
      return maxDisplays > 0 ? (displays / maxDisplays) * 100 : 0
    },
    getNotificationTypeTag(type) {
      const tagMap = {
        purchase: 'success',
        signup: 'primary',
        review: 'warning'
      }
      return tagMap[type] || 'info'
    },
    getSourceTag(source) {
      const tagMap = {
        stripe: 'danger',
        woocommerce: 'success',
        surecart: 'primary',
        edd: 'warning',
        manual: 'info'
      }
      return tagMap[source] || 'info'
    },
    getSourceLabel(source) {
      const labelMap = {
        stripe: 'Stripe',
        woocommerce: 'WooCommerce',
        surecart: 'SureCart',
        edd: 'EDD',
        manual: 'Manual'
      }
      return labelMap[source] || source
    },
    formatRelativeTime(date) {
      if (!date) return 'just now'
      
      const now = new Date()
      const past = new Date(date)
      const diffMs = now - past
      const diffMins = Math.floor(diffMs / 60000)
      const diffHours = Math.floor(diffMs / 3600000)
      const diffDays = Math.floor(diffMs / 86400000)

      if (diffMins < 1) return 'just now'
      if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`
      if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`
      if (diffDays === 1) return 'yesterday'
      return `${diffDays} days ago`
    },
    initEcho() {
      if (!this.website || !echo) return

      echo.channel(`website.${this.website.id}.analytics`)
        .listen('ActiveVisitorsUpdated', (e) => {
          if (this.analytics) {
            this.analytics.active_visitors = e.active_visitors
          }
        })
    }
  },
  beforeUnmount() {
    // Disconnect from channel when component is destroyed
    if (this.website && echo) {
      echo.leaveChannel(`website.${this.website.id}.analytics`)
    }
  }
}
</script>

<style scoped>
.website-detail {
  display: flex;
  min-height: 100vh;
  background: var(--el-bg-color-page);
}

.main-content {
  flex: 1;
  padding: 2rem;
}

.page-header {
  margin-bottom: 2rem;
}

.page-header h1 {
  margin: 0 0 0.5rem 0;
  color: var(--el-text-color-primary);
  font-size: 1.75rem;
  font-weight: 700;
}

.domain {
  color: var(--el-text-color-secondary);
  margin: 0;
  font-size: 0.875rem;
}

.loading-container {
  padding: 2rem;
}

.content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.site-tabs {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.tab-content {
  padding: 1rem 0;
}

.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.tab-header h2 {
  margin: 0;
  color: var(--el-text-color-primary);
  font-size: 1.5rem;
  font-weight: 700;
}

.tab-header p {
  margin: 0.5rem 0 0 0;
  color: var(--el-text-color-secondary);
}

.stats-row {
  margin-bottom: 1.5rem;
}

.stat-card {
  text-align: center;
  border-radius: 12px;
}

.recent-card {
  border-radius: 12px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.notification-card {
  border-radius: 8px;
}

.notification-card.border-purchase {
  border-left: 4px solid #67c23a;
}

.notification-card.border-signup {
  border-left: 4px solid #409eff;
}

.notification-card.border-review {
  border-left: 4px solid #e6a23c;
}

.notification-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.notification-left {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex: 1;
}

.emoji-circle {
  width: 48px;
  height: 48px;
  background: var(--el-bg-color-page);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.notification-text h3 {
  margin: 0 0 0.5rem 0;
  color: var(--el-text-color-primary);
  font-weight: 600;
}

.notification-right {
  text-align: right;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: flex-end;
}

.last-shown {
  color: var(--el-text-color-secondary);
  font-size: 0.75rem;
  margin: 0;
}

.notification-actions {
  display: flex;
  gap: 0.5rem;
}

.notification-tabs {
  margin-top: 1rem;
}

.auto-empty-alert {
  margin-bottom: 0;
}

.auto-empty-content p {
  margin: 0 0 1rem 0;
  color: var(--el-text-color-secondary);
}

.webhook-url-box {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.webhook-input {
  flex: 1;
}

.source-tabs {
  margin-top: 1rem;
}

.platform-card {
  border-radius: 12px;
}

.platform-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.platform-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.platform-icon {
  font-size: 1.5rem;
}

.platform-header h3 {
  margin: 0;
  color: var(--el-text-color-primary);
  font-weight: 600;
}

.platform-content {
  padding: 1rem 0;
}

.platform-content h4 {
  margin: 0 0 1rem 0;
  color: var(--el-text-color-primary);
  font-size: 1rem;
  font-weight: 600;
}

.setup-steps {
  margin: 0 0 1.5rem 0;
  padding-left: 1.25rem;
  color: var(--el-text-color-regular);
  line-height: 1.6;
}

.setup-steps li {
  margin-bottom: 0.5rem;
}

.webhook-section {
  margin: 1.5rem 0;
}

.webhook-section label {
  display: block;
  margin-bottom: 0.5rem;
  color: var(--el-text-color-regular);
  font-weight: 500;
}

.live-visitors-card {
  border: 2px solid #22c55e;
}

.live-visitors-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.live-dot {
  width: 8px;
  height: 8px;
  background: #22c55e;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
  }
  70% {
    box-shadow: 0 0 0 6px rgba(34, 197, 94, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
  }
}

.auto-card {
  border-left: 4px solid #67c23a;
}

.location {
  color: var(--el-text-color-secondary);
  font-size: 0.875rem;
  margin: 0.5rem 0 0 0;
}

.created-time {
  color: var(--el-text-color-secondary);
  font-size: 0.75rem;
  margin: 0;
}

.sources-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.source-card {
  border-radius: 12px;
}

.source-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.source-header h3 {
  margin: 0;
  color: var(--el-text-color-primary);
  font-weight: 600;
}

.source-description {
  color: var(--el-text-color-secondary);
  margin: 0 0 1rem 0;
  font-size: 0.875rem;
}

.webhook-section {
  margin-top: 1rem;
}

.webhook-section label {
  display: block;
  margin-bottom: 0.5rem;
  color: var(--el-text-color-regular);
  font-weight: 500;
}

.snippet-card {
  border-radius: 12px;
}

.subtitle {
  color: var(--el-text-color-secondary);
  margin: 0 0 1rem 0;
  font-size: 0.875rem;
}

.snippet-box {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.snippet-box .el-textarea {
  flex: 1;
}

.widget-settings-card {
  border-radius: 12px;
  margin-top: 1.5rem;
}

.widget-settings {
  max-width: 400px;
}

.chart-card {
  border-radius: 12px;
  margin-top: 1.5rem;
}

.chart-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.chart-bar-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.chart-bar-label {
  font-size: 0.875rem;
  color: var(--el-text-color-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chart-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.chart-bar-value {
  font-size: 0.875rem;
  color: var(--el-text-color-secondary);
  font-weight: 500;
  min-width: 40px;
}

.settings-card {
  border-radius: 12px;
}

.form-help {
  font-size: 0.75rem;
  color: var(--el-text-color-secondary);
  margin-top: 0.25rem;
}

.danger-card {
  border-radius: 12px;
  margin-top: 1.5rem;
}

@media (max-width: 768px) {
  .main-content {
    padding: 1rem;
  }
  
  .page-header {
    margin-bottom: 1rem;
  }
  
  .page-header h1 {
    font-size: 1.5rem;
  }
  
  .tab-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .stats-row :deep(.el-col) {
    margin-bottom: 1rem;
  }
  
  .sources-grid {
    grid-template-columns: 1fr;
  }
  
  .notification-content {
    flex-direction: column;
  }
  
  .notification-right {
    align-items: flex-start;
    width: 100%;
  }
}
</style>
