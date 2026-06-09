<template>
  <div class="settings">
    <header class="settings-header">
      <router-link to="/" class="back-link">← Back to Dashboard</router-link>
      <h1>Settings</h1>
    </header>

    <div v-if="loading" class="loading">Loading...</div>

    <div v-else-if="user" class="settings-content">
      <div class="profile-section">
        <h2>Profile</h2>
        <div class="profile-info">
          <div class="info-item">
            <label>Name</label>
            <p>{{ user.name }}</p>
          </div>
          <div class="info-item">
            <label>Email</label>
            <p>{{ user.email }}</p>
          </div>
        </div>
      </div>

      <div class="plan-section">
        <h2>Plan</h2>
        <div class="plan-info">
          <div class="info-item">
            <label>Current Plan</label>
            <p class="plan-badge">{{ user.plan.toUpperCase() }}</p>
          </div>
          <div class="info-item" v-if="user.plan === 'trial'">
            <label>Trial Ends</label>
            <p>{{ formatDate(user.trial_ends_at) }}</p>
          </div>
          <div class="info-item">
            <label>Status</label>
            <p>{{ user.is_on_trial ? 'On Trial' : (user.is_paid ? 'Paid' : 'Free') }}</p>
          </div>
        </div>
      </div>

      <div class="limits-section">
        <h2>Usage Limits</h2>
        <div class="limits-info">
          <div class="info-item">
            <label>Websites</label>
            <p>{{ websitesCount }} / {{ getWebsiteLimit() }}</p>
          </div>
        </div>
      </div>

      <div class="actions-section">
        <button @click="handleLogout" class="logout-btn">Logout</button>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api'

export default {
  name: 'Settings',
  data() {
    return {
      user: null,
      websitesCount: 0,
      loading: true
    }
  },
  async mounted() {
    await this.fetchUser()
    await this.fetchWebsitesCount()
  },
  methods: {
    async fetchUser() {
      try {
        const response = await api.get('/me')
        this.user = response.data
      } catch (err) {
        console.error('Failed to fetch user:', err)
      } finally {
        this.loading = false
      }
    },
    async fetchWebsitesCount() {
      try {
        const response = await api.get('/websites')
        this.websitesCount = response.data.length
      } catch (err) {
        console.error('Failed to fetch websites:', err)
      }
    },
    getWebsiteLimit() {
      const limits = { trial: 1, starter: 1, pro: 5 }
      return limits[this.user?.plan] || 0
    },
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString()
    },
    handleLogout() {
      localStorage.removeItem('token')
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
.settings {
  min-height: 100vh;
  background: #f5f7fa;
}

.settings-header {
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

.settings-header h1 {
  color: #333;
  margin: 0;
}

.settings-content {
  padding: 2rem;
  max-width: 800px;
  margin: 0 auto;
}

.loading {
  text-align: center;
  padding: 2rem;
  color: #666;
}

.profile-section,
.plan-section,
.limits-section {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.profile-section h2,
.plan-section h2,
.limits-section h2 {
  margin: 0 0 1.5rem 0;
  color: #333;
}

.profile-info,
.plan-info,
.limits-info {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-item label {
  display: block;
  color: #666;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.info-item p {
  color: #333;
  font-size: 1.125rem;
  margin: 0;
}

.plan-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border-radius: 12px;
  font-weight: 600;
}

.actions-section {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.logout-btn {
  width: 100%;
  padding: 0.75rem;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
}

.logout-btn:hover {
  background: #c0392b;
}
</style>
