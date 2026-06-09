<template>
  <div class="website-detail">
    <header class="detail-header">
      <router-link to="/" class="back-link">← Back to Dashboard</router-link>
      <h1>{{ website?.name }}</h1>
    </header>

    <div v-if="loading" class="loading">Loading...</div>

    <div v-else-if="website" class="detail-content">
      <div class="snippet-section">
        <h2>Embed Snippet</h2>
        <p>Add this snippet to your website to show social proof notifications:</p>
        <div class="snippet-box">
          <code>{{ snippet }}</code>
          <button @click="copySnippet" class="copy-btn">
            {{ copied ? 'Copied!' : 'Copy' }}
          </button>
        </div>
      </div>

      <div class="notifications-section">
        <div class="section-header">
          <h2>Notifications</h2>
          <button @click="showAddModal = true" class="add-btn">+ Add Notification</button>
        </div>

        <div v-if="notifications.length === 0" class="empty-state">
          <p>No notifications yet. Add your first notification!</p>
        </div>

        <div v-else class="notifications-list">
          <div v-for="notification in notifications" :key="notification.id" class="notification-card">
            <div class="notification-info">
              <span class="emoji">{{ notification.emoji }}</span>
              <div>
                <h3>{{ notification.message }}</h3>
                <p>{{ notification.type }} {{ notification.city ? `• ${notification.city}` : '' }}</p>
              </div>
            </div>
            <div class="notification-actions">
              <button @click="toggleNotification(notification)" class="toggle-btn">
                {{ notification.is_active ? 'Active' : 'Inactive' }}
              </button>
              <button @click="deleteNotification(notification.id)" class="delete-btn">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Notification Modal -->
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
            <input v-model="newNotification.message" type="text" required placeholder="John just purchased Pro Plan" />
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
              {{ adding ? 'Adding...' : 'Add' }}
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
      notifications: [],
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
    await this.fetchWebsite()
    await this.fetchNotifications()
    await this.fetchSnippet()
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
    async fetchNotifications() {
      try {
        const response = await api.get(`/websites/${this.$route.params.id}/notifications`)
        this.notifications = response.data
      } catch (err) {
        console.error('Failed to fetch notifications:', err)
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
        this.notifications.push(response.data)
        this.showAddModal = false
        this.newNotification = { type: 'purchase', message: '', city: '', country: '', emoji: '🛒' }
      } catch (err) {
        this.addError = err.response?.data?.message || 'Failed to add notification'
      } finally {
        this.adding = false
      }
    },
    async toggleNotification(notification) {
      try {
        const response = await api.patch(`/notifications/${notification.id}/toggle`)
        const index = this.notifications.findIndex(n => n.id === notification.id)
        if (index !== -1) {
          this.notifications[index] = response.data
        }
      } catch (err) {
        alert('Failed to toggle notification')
      }
    },
    async deleteNotification(id) {
      if (!confirm('Are you sure you want to delete this notification?')) return
      
      try {
        await api.delete(`/notifications/${id}`)
        this.notifications = this.notifications.filter(n => n.id !== id)
      } catch (err) {
        alert('Failed to delete notification')
      }
    },
    copySnippet() {
      navigator.clipboard.writeText(this.snippet)
      this.copied = true
      setTimeout(() => this.copied = false, 2000)
    }
  }
}
</script>

<style scoped>
.website-detail {
  min-height: 100vh;
  background: #f5f7fa;
}

.detail-header {
  background: white;
  padding: 1.5rem 2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.back-link {
  color: #667eea;
  text-decoration: none;
  display: inline-block;
  margin-bottom: 1rem;
}

.detail-header h1 {
  color: #333;
  margin: 0;
}

.detail-content {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.loading {
  text-align: center;
  padding: 2rem;
  color: #666;
}

.snippet-section {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.snippet-section h2 {
  margin: 0 0 0.5rem 0;
  color: #333;
}

.snippet-section p {
  color: #666;
  margin-bottom: 1rem;
}

.snippet-box {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.snippet-box code {
  flex: 1;
  font-family: monospace;
  color: #333;
  word-break: break-all;
}

.copy-btn {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  white-space: nowrap;
}

.notifications-section h2 {
  color: #333;
  margin: 0 0 1rem 0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.add-btn {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
}

.notifications-list {
  display: grid;
  gap: 1rem;
}

.notification-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.notification-info {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.emoji {
  font-size: 2rem;
}

.notification-info h3 {
  margin: 0 0 0.25rem 0;
  color: #333;
}

.notification-info p {
  margin: 0;
  color: #666;
  font-size: 0.875rem;
}

.notification-actions {
  display: flex;
  gap: 0.5rem;
}

.toggle-btn {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.delete-btn {
  padding: 0.5rem 1rem;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
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
}

.modal h2 {
  margin: 0 0 1.5rem 0;
  color: #333;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
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
}

.submit-btn {
  flex: 1;
  padding: 0.75rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.submit-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.error {
  color: #e74c3c;
  text-align: center;
  margin-top: 1rem;
}
</style>
