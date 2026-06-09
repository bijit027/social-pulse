<template>
  <div class="dashboard">
    <header class="dashboard-header">
      <h1>SocialPulse Dashboard</h1>
      <div class="header-actions">
        <router-link to="/settings" class="settings-link">Settings</router-link>
        <button @click="handleLogout" class="logout-btn">Logout</button>
      </div>
    </header>

    <div class="dashboard-content">
      <div class="websites-section">
        <div class="section-header">
          <h2>Your Websites</h2>
          <button @click="showAddModal = true" class="add-btn">+ Add Website</button>
        </div>

        <div v-if="loading" class="loading">Loading...</div>

        <div v-else-if="websites.length === 0" class="empty-state">
          <p>No websites yet. Add your first website to get started!</p>
        </div>

        <div v-else class="websites-list">
          <div v-for="website in websites" :key="website.id" class="website-card">
            <div class="website-info">
              <h3>{{ website.name }}</h3>
              <p>{{ website.domain }}</p>
              <span class="badge">{{ website.notifications_count }} notifications</span>
            </div>
            <div class="website-actions">
              <router-link :to="`/websites/${website.id}`" class="view-btn">View</router-link>
              <button @click="deleteWebsite(website.id)" class="delete-btn">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Website Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
      <div class="modal">
        <h2>Add Website</h2>
        <form @submit.prevent="handleAddWebsite">
          <div class="form-group">
            <label>Website Name</label>
            <input v-model="newWebsite.name" type="text" required />
          </div>
          <div class="form-group">
            <label>Domain</label>
            <input v-model="newWebsite.domain" type="text" required placeholder="example.com" />
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
  name: 'Dashboard',
  data() {
    return {
      websites: [],
      loading: true,
      showAddModal: false,
      adding: false,
      addError: '',
      newWebsite: {
        name: '',
        domain: ''
      }
    }
  },
  async mounted() {
    await this.fetchWebsites()
  },
  methods: {
    async fetchWebsites() {
      try {
        const response = await api.get('/websites')
        this.websites = response.data
      } catch (err) {
        console.error('Failed to fetch websites:', err)
      } finally {
        this.loading = false
      }
    },
    async handleAddWebsite() {
      this.adding = true
      this.addError = ''
      try {
        const response = await api.post('/websites', this.newWebsite)
        this.websites.push(response.data)
        this.showAddModal = false
        this.newWebsite = { name: '', domain: '' }
      } catch (err) {
        this.addError = err.response?.data?.message || 'Failed to add website'
      } finally {
        this.adding = false
      }
    },
    async deleteWebsite(id) {
      if (!confirm('Are you sure you want to delete this website?')) return
      
      try {
        await api.delete(`/websites/${id}`)
        this.websites = this.websites.filter(w => w.id !== id)
      } catch (err) {
        alert('Failed to delete website')
      }
    },
    handleLogout() {
      localStorage.removeItem('token')
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  background: #f5f7fa;
}

.dashboard-header {
  background: white;
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.dashboard-header h1 {
  color: #333;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.settings-link {
  color: #667eea;
  text-decoration: none;
}

.logout-btn {
  padding: 0.5rem 1rem;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.dashboard-content {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h2 {
  color: #333;
  margin: 0;
}

.add-btn {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.loading {
  text-align: center;
  padding: 2rem;
  color: #666;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
}

.websites-list {
  display: grid;
  gap: 1rem;
}

.website-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.website-info h3 {
  margin: 0 0 0.5rem 0;
  color: #333;
}

.website-info p {
  margin: 0 0 0.5rem 0;
  color: #666;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #667eea;
  color: white;
  border-radius: 12px;
  font-size: 0.875rem;
}

.website-actions {
  display: flex;
  gap: 0.5rem;
}

.view-btn {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  text-decoration: none;
  border-radius: 6px;
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

.form-group input {
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
