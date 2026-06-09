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
                            <el-tag type="success" size="small">woocommerce</el-tag>
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

              <div class="sources-grid">
                <el-card class="source-card" shadow="hover">
                  <div class="source-header">
                    <h3>WooCommerce</h3>
                    <el-tag type="success">Connected</el-tag>
                  </div>
                  <p class="source-description">Receive purchase events automatically.</p>
                  <div class="webhook-section">
                    <label>Webhook URL</label>
                    <div class="webhook-url-box">
                      <el-input v-model="webhookUrl" readonly class="webhook-input" />
                      <el-button type="primary" :icon="copiedWebhook ? Check : DocumentCopy" @click="copyWebhookUrl">
                        {{ copiedWebhook ? 'Copied!' : 'Copy' }}
                      </el-button>
                    </div>
                  </div>
                </el-card>

                <el-card class="source-card" shadow="hover">
                  <div class="source-header">
                    <h3>Stripe</h3>
                    <el-tag type="info">Coming Soon</el-tag>
                  </div>
                  <p class="source-description">Receive payment events.</p>
                </el-card>

                <el-card class="source-card" shadow="hover">
                  <div class="source-header">
                    <h3>Shopify</h3>
                    <el-tag type="info">Coming Soon</el-tag>
                  </div>
                  <p class="source-description">Connect your Shopify store.</p>
                </el-card>

                <el-card class="source-card" shadow="hover">
                  <div class="source-header">
                    <h3>Custom Webhook</h3>
                    <el-tag type="info">Coming Soon</el-tag>
                  </div>
                  <p class="source-description">Connect any platform.</p>
                </el-card>
              </div>
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
                <el-col :span="6">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="Total Displays" :value="analytics.total_displays" />
                  </el-card>
                </el-col>
                <el-col :span="6">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="Today's Displays" :value="analytics.displays_today" />
                  </el-card>
                </el-col>
                <el-col :span="6">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="Weekly Displays" :value="analytics.displays_this_week" />
                  </el-card>
                </el-col>
                <el-col :span="6">
                  <el-card shadow="hover" class="stat-card">
                    <el-statistic title="Monthly Displays" :value="analytics.displays_this_week * 4" />
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

              <el-card class="danger-card" shadow="hover">
                <template #header>
                  <div class="card-header">
                    <span>Danger Zone</span>
                  </div>
                </template>
                <el-button type="danger" @click="deleteSite">Delete Site</el-button>
              </el-card>
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
          </el-select>
        </el-form-item>
        <el-form-item label="Message">
          <el-input v-model="newNotification.message" type="text" required 
                     placeholder="e.g. John from New York just purchased Pro Plan" />
        </el-form-item>
        <el-form-item label="City (optional)">
          <el-input v-model="newNotification.city" type="text" placeholder="New York" />
        </el-form-item>
        <el-form-item label="Country (optional)">
          <el-input v-model="newNotification.country" type="text" placeholder="USA" />
        </el-form-item>
        <el-form-item label="Emoji">
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
import Sidebar from '../components/Sidebar.vue'
import { View, Calendar, Lightning, Plus, DocumentCopy, Check, Delete, Bell } from '@element-plus/icons-vue'

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
      activeTab: 'overview',
      notificationTab: 'auto',
      copiedWebhook: false,
      widgetSettings: {
        position: 'bottom-right',
        theme: 'light'
      },
      newNotification: {
        type: 'purchase',
        message: '',
        city: '',
        country: '',
        emoji: '🛒'
      }
    }
  },
  computed: {
    autoNotifications() {
      return this.analytics.notifications.filter(n => n.source === 'woocommerce')
    },
    manualNotifications() {
      return this.analytics.notifications.filter(n => n.source === 'manual')
    },
    webhookUrl() {
      if (!this.website) return ''
      return import.meta.env.VITE_API_URL.replace('/api', '') + '/api/webhook/woocommerce/' + this.website.pixel_id
    }
  },
  async mounted() {
    await Promise.all([
      this.fetchWebsite(),
      this.fetchAnalytics(),
      this.fetchSnippet()
    ])
  },
  methods: {
    async fetchWebsite() {
      try {
        const response = await api.get(`/websites/${this.$route.params.id}`)
        this.website = response.data
      } catch (err) {
        console.error('Failed to fetch website:', err)
      }
    },
    async fetchAnalytics() {
      try {
        const response = await api.get(`/websites/${this.$route.params.id}/analytics`)
        this.analytics = response.data
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
        this.newNotification = { type: 'purchase', message: '', city: '', country: '', emoji: '🛒' }
        await this.fetchAnalytics()
        alert('Notification added!')
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
        alert('Failed to toggle notification')
      }
    },
    async deleteNotification(id) {
      if (!confirm('Are you sure you want to delete this notification?')) return
      
      try {
        await api.delete(`/notifications/${id}`)
        await this.fetchAnalytics()
      } catch (err) {
        alert('Failed to delete notification')
      }
    },
    async deleteSite() {
      if (!confirm('Are you sure you want to delete this site? This action cannot be undone.')) return
      
      try {
        await api.delete(`/websites/${this.$route.params.id}`)
        this.$router.push('/sites')
      } catch (err) {
        alert('Failed to delete site')
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
