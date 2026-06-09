<template>
  <div class="website-detail">
    <div class="page-container">
      <!-- Section 1: Page Header -->
      <header class="page-header">
        <router-link to="/" class="back-link">← Back to Dashboard</router-link>
        <h1>{{ website?.name }}</h1>
        <p class="domain">{{ website?.domain }}</p>
      </header>

      <div v-if="loading" class="loading">
        <div class="skeleton-header"></div>
        <div class="skeleton-stats"></div>
        <div class="skeleton-section"></div>
      </div>

      <div v-else-if="website" class="content">
        <!-- Section 2: Analytics Stats Row -->
        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-icon">👁️</div>
            <div class="stat-value" :style="{ color: '#4f6ef7' }">{{ analytics.total_displays }}</div>
            <div class="stat-label">Total Displays</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-value" :style="{ color: '#22c55e' }">{{ analytics.displays_this_week }}</div>
            <div class="stat-label">This Week</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-value" :style="{ color: '#a855f7' }">{{ analytics.displays_today }}</div>
            <div class="stat-label">Today</div>
          </div>
        </div>

        <!-- Empty state for zero displays -->
        <div v-if="analytics.total_displays === 0" class="no-displays-box">
          ℹ️ No displays yet — embed the snippet on your website to start tracking
        </div>

        <!-- Simple bar chart -->
        <div v-if="analytics.total_displays > 0 && analytics.notifications.length > 0" class="chart-section">
          <h3>Displays per Notification</h3>
          <div class="chart-container">
            <div v-for="notification in analytics.notifications" :key="notification.id" class="chart-bar-wrapper">
              <div class="chart-bar-label">{{ notification.message.substring(0, 20) }}{{ notification.message.length > 20 ? '...' : '' }}</div>
              <div class="chart-bar">
                <div class="chart-bar-fill" 
                     :style="{ width: getBarWidth(notification.total_displays) + '%' }"
                     :title="notification.total_displays + ' displays'">
                </div>
                <span class="chart-bar-value">{{ notification.total_displays }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 3: Embed Snippet -->
        <div class="snippet-section">
          <h2>Embed Snippet</h2>
          <p class="subtitle">Add this snippet to your website to show social proof notifications</p>
          <div class="snippet-box">
            <code>{{ snippet }}</code>
            <button @click="copySnippet" class="copy-btn">
              {{ copied ? 'Copied!' : 'Copy' }}
            </button>
          </div>
        </div>

        <!-- Section 4: Notifications List -->
        <div class="notifications-section">
          <div class="section-header">
            <h2>Notifications</h2>
            <button @click="showAddModal = true" class="add-btn">+ Add Notification</button>
          </div>

          <div v-if="analytics.notifications.length === 0" class="empty-state">
            <p>No notifications yet. Add your first one!</p>
            <button @click="showAddModal = true" class="add-btn-empty">+</button>
          </div>

          <div v-else class="notifications-list">
            <div v-for="notification in analytics.notifications" :key="notification.id" 
                 :class="['notification-card', 'border-' + notification.type]">
              <div class="notification-left">
                <div class="emoji-circle">{{ notification.emoji }}</div>
                <div class="notification-text">
                  <h3>{{ notification.message }}</h3>
                  <p>{{ notification.type }}</p>
                </div>
              </div>
              <div class="notification-right">
                <div class="display-badge">{{ notification.total_displays }} shown</div>
                <p class="last-shown">Last shown: {{ formatLastShown(notification.last_shown) }}</p>
                <div class="notification-actions">
                  <button @click="toggleNotification(notification)" 
                          :class="['toggle-btn', notification.is_active ? 'active' : 'inactive']">
                    {{ notification.is_active ? 'Active' : 'Inactive' }}
                  </button>
                  <button @click="deleteNotification(notification.id)" class="delete-btn">Delete</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 5: Add Notification Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
      <div class="modal">
        <h2>Add Notification</h2>
        <form @submit.prevent="handleAddNotification">
          <div class="form-group">
            <label>Type</label>
            <select v-model="newNotification.type" required>
              <option value="purchase">Purchase</option>
              <option value="signup">Signup</option>
              <option value="review">Review</option>
            </select>
          </div>
          <div class="form-group">
            <label>Message</label>
            <input v-model="newNotification.message" type="text" required 
                   placeholder="e.g. John from New York just purchased Pro Plan" />
          </div>
          <div class="form-group">
            <label>City (optional)</label>
            <input v-model="newNotification.city" type="text" placeholder="New York" />
          </div>
          <div class="form-group">
            <label>Country (optional)</label>
            <input v-model="newNotification.country" type="text" placeholder="USA" />
          </div>
          <div class="form-group">
            <label>Emoji</label>
            <input v-model="newNotification.emoji" type="text" placeholder="🛒" />
          </div>
          <div class="modal-actions">
            <button type="button" @click="showAddModal = false" class="cancel-btn">Cancel</button>
            <button type="submit" :disabled="adding" class="submit-btn">
              <span v-if="adding" class="spinner"></span>
              <span v-else>Save</span>
            </button>
          </div>
          <p v-if="addError" class="error">{{ addError }}</p>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api'

export default {
  name: 'WebsiteDetail',
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
    }
  }
}
</script>

<style scoped>
.website-detail {
  min-height: 100vh;
  background: #f8fafc;
}

.page-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem;
}

/* Page Header */
.page-header {
  margin-bottom: 2rem;
}

.back-link {
  color: #4f6ef7;
  text-decoration: none;
  display: inline-block;
  margin-bottom: 1rem;
  font-weight: 500;
}

.page-header h1 {
  color: #1a1a1a;
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
  font-weight: 700;
}

.domain {
  color: #666;
  margin: 0;
  font-size: 1rem;
}

/* Loading Skeleton */
.loading {
  padding: 2rem 0;
}

.skeleton-header {
  height: 80px;
  background: #e2e8f0;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  animation: pulse 1.5s ease-in-out infinite;
}

.skeleton-stats {
  height: 120px;
  background: #e2e8f0;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  animation: pulse 1.5s ease-in-out infinite;
}

.skeleton-section {
  height: 200px;
  background: #e2e8f0;
  border-radius: 8px;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Stats Row */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.stat-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.stat-label {
  color: #666;
  font-size: 0.875rem;
}

.no-displays-box {
  background: #fef9c3;
  border: 1px solid #fde047;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 2rem;
  color: #854d0e;
  font-size: 0.875rem;
  text-align: center;
}

.chart-section {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.chart-section h3 {
  color: #1a1a1a;
  margin: 0 0 1.5rem 0;
  font-weight: 700;
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
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chart-bar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.chart-bar-fill {
  height: 32px;
  background: #4f6ef7;
  border-radius: 4px;
  min-width: 4px;
  transition: width 0.3s ease;
}

.chart-bar-value {
  font-size: 0.875rem;
  color: #666;
  font-weight: 500;
  min-width: 30px;
}

/* Snippet Section */
.snippet-section {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.snippet-section h2 {
  color: #1a1a1a;
  margin: 0 0 0.5rem 0;
  font-weight: 700;
}

.subtitle {
  color: #666;
  margin: 0 0 1rem 0;
  font-size: 0.875rem;
}

.snippet-box {
  background: #f1f5f9;
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.snippet-box code {
  flex: 1;
  font-family: monospace;
  color: #333;
  font-size: 0.875rem;
  word-break: break-all;
}

.copy-btn {
  padding: 0.5rem 1rem;
  background: #4f6ef7;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  white-space: nowrap;
}

/* Notifications Section */
.notifications-section {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h2 {
  color: #1a1a1a;
  margin: 0;
  font-weight: 700;
}

.add-btn {
  padding: 0.5rem 1rem;
  background: #4f6ef7;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
}

.add-btn-empty {
  margin-top: 1rem;
  width: 50px;
  height: 50px;
  font-size: 1.5rem;
  background: #4f6ef7;
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
}

.notifications-list {
  display: grid;
  gap: 1rem;
}

.notification-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.notification-card.border-purchase {
  border-left: 4px solid #22c55e;
}

.notification-card.border-signup {
  border-left: 4px solid #4f6ef7;
}

.notification-card.border-review {
  border-left: 4px solid #f97316;
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
  background: #f1f5f9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.notification-text h3 {
  margin: 0 0 0.25rem 0;
  color: #1a1a1a;
  font-weight: 600;
}

.notification-text p {
  margin: 0;
  color: #666;
  font-size: 0.875rem;
}

.notification-right {
  text-align: right;
  flex-shrink: 0;
}

.display-badge {
  background: #4f6ef7;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
  display: inline-block;
}

.last-shown {
  color: #666;
  font-size: 0.75rem;
  margin: 0 0 0.75rem 0;
}

.notification-actions {
  display: flex;
  gap: 0.5rem;
}

.toggle-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
}

.toggle-btn.active {
  background: #22c55e;
  color: white;
}

.toggle-btn.inactive {
  background: #ccc;
  color: white;
}

.delete-btn {
  padding: 0.5rem 1rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.modal h2 {
  margin: 0 0 1.5rem 0;
  color: #1a1a1a;
  font-weight: 700;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
  font-weight: 500;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.cancel-btn {
  flex: 1;
  padding: 0.75rem;
  background: #ccc;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.submit-btn {
  flex: 1;
  padding: 0.75rem;
  background: #4f6ef7;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.submit-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error {
  color: #ef4444;
  text-align: center;
  margin-top: 1rem;
  font-size: 0.875rem;
}
</style>
