<template>
  <div class="website-detail">
    <el-container class="detail-container">
      <el-header class="detail-header">
        <div class="header-left">
          <router-link to="/">
            <el-button :icon="ArrowLeft">Back to Dashboard</el-button>
          </router-link>
          <div class="header-title">
            <h1>{{ website?.name }}</h1>
            <p class="domain">{{ website?.domain }}</p>
          </div>
        </div>
      </el-header>

      <el-main class="detail-main">
        <div v-if="loading" class="loading-container">
          <el-skeleton :rows="5" animated />
        </div>

        <div v-else-if="website" class="content">
          <!-- Analytics Stats Row -->
          <el-row :gutter="20" class="stats-row">
            <el-col :span="8">
              <el-card shadow="hover" class="stat-card">
                <el-statistic title="Total Displays" :value="analytics.total_displays">
                  <template #prefix>
                    <el-icon :size="24" color="#4f6ef7"><View /></el-icon>
                  </template>
                </el-statistic>
              </el-card>
            </el-col>
            <el-col :span="8">
              <el-card shadow="hover" class="stat-card">
                <el-statistic title="This Week" :value="analytics.displays_this_week">
                  <template #prefix>
                    <el-icon :size="24" color="#22c55e"><Calendar /></el-icon>
                  </template>
                </el-statistic>
              </el-card>
            </el-col>
            <el-col :span="8">
              <el-card shadow="hover" class="stat-card">
                <el-statistic title="Today" :value="analytics.displays_today">
                  <template #prefix>
                    <el-icon :size="24" color="#a855f7"><Lightning /></el-icon>
                  </template>
                </el-statistic>
              </el-card>
            </el-col>
          </el-row>

          <!-- Empty state for zero displays -->
          <el-alert v-if="analytics.total_displays === 0" type="warning" :closable="false" class="no-displays-alert">
            ℹ️ No displays yet — embed the snippet on your website to start tracking
          </el-alert>

          <!-- Simple bar chart -->
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
                  <el-progress :percentage="getBarWidth(notification.total_displays)" :stroke-width="32" :show-text="false" />
                  <span class="chart-bar-value">{{ notification.total_displays }}</span>
                </div>
              </div>
            </div>
          </el-card>

          <!-- Embed Snippet -->
          <el-card class="snippet-card" shadow="hover">
            <template #header>
              <div class="card-header">
                <span>Embed Snippet</span>
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

          <!-- Notifications List -->
          <el-card class="notifications-card" shadow="hover">
            <template #header>
              <div class="card-header">
                <span>Notifications</span>
                <el-button type="primary" :icon="Plus" @click="showAddModal = true">Add Notification</el-button>
              </div>
            </template>

            <el-empty v-if="analytics.notifications.length === 0" description="No notifications yet. Add your first one!">
              <el-button type="primary" :icon="Plus" @click="showAddModal = true">Add Notification</el-button>
            </el-empty>

            <div v-else class="notifications-list">
              <el-card v-for="notification in analytics.notifications" :key="notification.id" 
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
          </el-card>
        </div>
      </el-main>
    </el-container>

    <!-- Add Notification Dialog -->
    <el-dialog v-model="showAddModal" title="Add Notification" width="500px">
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
import { ArrowLeft, View, Calendar, Lightning, Plus, DocumentCopy, Check, Delete } from '@element-plus/icons-vue'

export default {
  name: 'WebsiteDetail',
  components: {
    ArrowLeft,
    View,
    Calendar,
    Lightning,
    Plus,
    DocumentCopy,
    Check,
    Delete
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
      newNotification: {
        type: 'purchase',
        message: '',
        city: '',
        country: '',
        emoji: '🛒'
      }
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
    copySnippet() {
      navigator.clipboard.writeText(this.snippet)
      this.copied = true
      setTimeout(() => this.copied = false, 2000)
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
    }
  }
}
</script>

<style scoped>
.website-detail {
  min-height: 100vh;
  background: var(--el-bg-color-page);
}

.detail-container {
  min-height: 100vh;
}

.detail-header {
  background: white;
  padding: 1rem 2rem;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-bottom: 1px solid var(--el-border-color-light);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.header-title h1 {
  color: var(--el-text-color-primary);
  margin: 0 0 0.25rem 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.domain {
  color: var(--el-text-color-secondary);
  margin: 0;
  font-size: 0.875rem;
}

.detail-main {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.loading-container {
  padding: 2rem;
}

.content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.stats-row {
  margin-bottom: 0;
}

.stat-card {
  text-align: center;
}

.no-displays-alert {
  margin-bottom: 0;
}

.chart-card {
  margin-bottom: 0;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  color: var(--el-text-color-primary);
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

.snippet-card {
  margin-bottom: 0;
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

.notifications-card {
  margin-bottom: 0;
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

@media (max-width: 768px) {
  .detail-header {
    padding: 1rem;
  }
  
  .detail-main {
    padding: 1rem;
  }
  
  .header-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .stats-row :deep(.el-col) {
    margin-bottom: 1rem;
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
