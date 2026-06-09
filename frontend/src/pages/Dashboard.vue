<template>
  <div class="dashboard">
    <el-container class="dashboard-container">
      <el-header class="dashboard-header">
        <div class="header-left">
          <div class="logo">
            <svg viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
              <path fill="#667eea" d="M14.747 9.125c.527-1.426 1.736-2.573 3.317-2.573c1.643 0 2.792 1.085 3.318 2.573l6.077 16.867c.186.496.248.931.248 1.147c0 1.209-.992 2.046-2.139 2.046c-1.303 0-1.954-.682-2.264-1.611l-.931-2.915h-8.62l-.93 2.884c-.31.961-.961 1.642-2.232 1.642c-1.24 0-2.294-.93-2.294-2.17c0-.496.155-.868.217-1.023l6.233-16.867zm.34 11.256h5.891l-2.883-8.992h-.062l-2.946 8.992z"/>
            </svg>
          </div>
          <h1>SocialPulse</h1>
        </div>
        <div class="header-actions">
          <router-link to="/settings">
            <el-button :icon="Setting">Settings</el-button>
          </router-link>
          <el-button type="danger" :icon="SwitchButton" @click="handleLogout">Logout</el-button>
        </div>
      </el-header>

      <el-main class="dashboard-main">
        <div class="hero-section">
          <div class="hero-text">
            <span class="eyebrow">Dashboard</span>
            <h1 class="hero-title">Welcome back</h1>
            <p class="hero-sub">Manage your websites and track social proof notifications</p>
          </div>
          <div class="hero-actions">
            <el-button type="primary" :icon="Plus" @click="showAddModal = true">Add Website</el-button>
          </div>
        </div>

        <div v-if="loading" class="loading-container">
          <el-skeleton :rows="3" animated />
        </div>

        <div v-else-if="websites.length === 0" class="empty-state">
          <el-empty description="No websites yet. Add your first website to get started!">
            <el-button type="primary" :icon="Plus" @click="showAddModal = true">Add Website</el-button>
          </el-empty>
        </div>

        <div v-else class="websites-grid">
          <el-card v-for="website in websites" :key="website.id" class="website-card" shadow="hover">
            <template #header>
              <div class="card-header">
                <div class="card-header-left">
                  <el-icon class="website-icon" :size="24" color="#667eea"><Monitor /></el-icon>
                  <h3>{{ website.name }}</h3>
                </div>
                <el-tag type="primary">{{ website.notifications_count }} notifications</el-tag>
              </div>
            </template>
            <div class="card-body">
              <p class="domain">{{ website.domain }}</p>
              <div class="card-actions">
                <router-link :to="`/websites/${website.id}`">
                  <el-button type="primary" :icon="View">View</el-button>
                </router-link>
                <el-button type="danger" :icon="Delete" @click="deleteWebsite(website.id)">Delete</el-button>
              </div>
            </div>
          </el-card>
        </div>
      </el-main>
    </el-container>

    <!-- Add Website Dialog -->
    <el-dialog v-model="showAddModal" title="Add Website" width="500px">
      <el-form @submit.prevent="handleAddWebsite" :model="newWebsite" label-position="top">
        <el-form-item label="Website Name">
          <el-input v-model="newWebsite.name" placeholder="My Website" required />
        </el-form-item>
        <el-form-item label="Domain">
          <el-input v-model="newWebsite.domain" placeholder="example.com" required />
        </el-form-item>
        <el-alert v-if="addError" :title="addError" type="error" :closable="false" style="margin-bottom: 1rem" />
      </el-form>
      <template #footer>
        <el-button @click="showAddModal = false">Cancel</el-button>
        <el-button type="primary" :loading="adding" @click="handleAddWebsite">
          {{ adding ? 'Adding...' : 'Add' }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import api from '../services/api'
import { Setting, SwitchButton, Plus, Monitor, View, Delete } from '@element-plus/icons-vue'

export default {
  name: 'Dashboard',
  components: {
    Setting,
    SwitchButton,
    Plus,
    Monitor,
    View,
    Delete
  },
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
  background: var(--el-bg-color-page);
}

.dashboard-container {
  min-height: 100vh;
}

.dashboard-header {
  background: white;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-bottom: 1px solid var(--el-border-color-light);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo {
  width: 40px;
  height: 40px;
}

.logo svg {
  width: 100%;
  height: 100%;
}

.dashboard-header h1 {
  color: var(--el-text-color-primary);
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.dashboard-main {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.hero-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  color: white;
}

.hero-text {
  flex: 1;
}

.eyebrow {
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  opacity: 0.9;
  margin-bottom: 0.5rem;
  display: block;
}

.hero-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.hero-sub {
  font-size: 1rem;
  opacity: 0.9;
  margin: 0;
}

.hero-actions {
  display: flex;
  gap: 1rem;
}

.loading-container {
  padding: 2rem;
}

.websites-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.website-card {
  border-radius: 12px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.website-card:hover {
  transform: translateY(-2px);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.website-icon {
  flex-shrink: 0;
}

.card-header h3 {
  margin: 0;
  color: var(--el-text-color-primary);
  font-size: 1.125rem;
  font-weight: 600;
}

.card-body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.domain {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 0.875rem;
}

.card-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.card-actions a {
  text-decoration: none;
}

.empty-state {
  padding: 4rem 2rem;
}

@media (max-width: 768px) {
  .hero-section {
    flex-direction: column;
    text-align: center;
    gap: 1.5rem;
  }
  
  .websites-grid {
    grid-template-columns: 1fr;
  }
  
  .dashboard-header {
    padding: 1rem;
  }
  
  .dashboard-main {
    padding: 1rem;
  }
}
</style>
