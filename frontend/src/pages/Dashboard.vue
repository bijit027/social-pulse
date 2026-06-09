<template>
  <div class="dashboard">
    <Sidebar />
    <div class="main-content">
      <div class="page-header">
        <h1>Welcome Back 👋</h1>
        <p class="subtitle">Manage social proof notifications across all your websites.</p>
      </div>

      <div v-if="loading" class="loading-container">
        <el-skeleton :rows="5" animated />
      </div>

      <div v-else class="content">
        <!-- Statistics Cards -->
        <el-row :gutter="20" class="stats-row">
          <el-col :span="6">
            <el-card shadow="hover" class="stat-card">
              <el-statistic title="Total Sites" :value="websites.length">
                <template #prefix>
                  <el-icon :size="24" color="#FF6B35"><Monitor /></el-icon>
                </template>
              </el-statistic>
              <p class="stat-description">Connected websites</p>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card shadow="hover" class="stat-card">
              <el-statistic title="Active Notifications" :value="totalNotifications">
                <template #prefix>
                  <el-icon :size="24" color="#22c55e"><Bell /></el-icon>
                </template>
              </el-statistic>
              <p class="stat-description">Currently running notifications</p>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card shadow="hover" class="stat-card">
              <el-statistic title="Total Displays" :value="totalDisplays">
                <template #prefix>
                  <el-icon :size="24" color="#409eff"><View /></el-icon>
                </template>
              </el-statistic>
              <p class="stat-description">All-time widget displays</p>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card shadow="hover" class="stat-card">
              <el-statistic title="Today's Displays" :value="todayDisplays">
                <template #prefix>
                  <el-icon :size="24" color="#a855f7"><Calendar /></el-icon>
                </template>
              </el-statistic>
              <p class="stat-description">Displays in the last 24 hours</p>
            </el-card>
          </el-col>
        </el-row>

        <!-- Recent Activity -->
        <el-card class="recent-activity-card" shadow="hover">
          <template #header>
            <div class="card-header">
              <span>Recent Activity</span>
            </div>
          </template>
          <div class="activity-list">
            <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
              <el-icon class="activity-icon" color="#22c55e"><Check /></el-icon>
              <span>{{ activity.message }}</span>
            </div>
          </div>
        </el-card>

        <!-- Website List -->
        <el-card class="websites-card" shadow="hover">
          <template #header>
            <div class="card-header">
              <span>Websites</span>
              <el-button type="primary" :icon="Plus" @click="showAddModal = true">Add Website</el-button>
            </div>
          </template>

          <el-empty v-if="websites.length === 0" description="No websites yet. Create your first website to start displaying notifications.">
            <el-button type="primary" :icon="Plus" @click="showAddModal = true">Add Website</el-button>
          </el-empty>

          <div v-else class="websites-list">
            <el-card v-for="website in websites" :key="website.id" class="website-card" shadow="never">
              <div class="website-content">
                <div class="website-info">
                  <h3>{{ website.name }}</h3>
                  <p class="domain">{{ website.domain }}</p>
                  <el-tag :type="website.is_active ? 'success' : 'info'" size="small">
                    {{ website.is_active ? 'Active' : 'Disabled' }}
                  </el-tag>
                  <p class="notification-count">{{ website.notifications_count || 0 }} Notifications</p>
                </div>
                <div class="website-actions">
                  <el-button type="primary" size="small" @click="goToSite(website.id)">Open</el-button>
                  <el-button size="small" @click="goToSiteSettings(website.id)">Settings</el-button>
                  <el-button type="danger" size="small" :icon="Delete" @click="deleteWebsite(website.id)">Delete</el-button>
                </div>
              </div>
            </el-card>
          </div>
        </el-card>
      </div>
    </div>

    <!-- Add Website Dialog -->
    <el-dialog v-model="showAddModal" title="Add Website" width="500px">
      <el-form @submit.prevent="handleAddWebsite" :model="newWebsite" label-position="top">
        <el-form-item label="Site Name">
          <el-input v-model="newWebsite.name" placeholder="My Website" required />
        </el-form-item>
        <el-form-item label="Website URL">
          <el-input v-model="newWebsite.domain" placeholder="https://example.com" required />
        </el-form-item>
        <el-alert v-if="addError" :title="addError" type="error" :closable="false" style="margin-bottom: 1rem" />
      </el-form>
      <template #footer>
        <el-button @click="showAddModal = false">Cancel</el-button>
        <el-button type="primary" :loading="adding" @click="handleAddWebsite">
          {{ adding ? 'Adding...' : 'Create Site' }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import api from '../services/api'
import Sidebar from '../components/Sidebar.vue'
import { Plus, Monitor, View, Delete, Bell, Calendar, Check } from '@element-plus/icons-vue'

export default {
  name: 'Dashboard',
  components: {
    Sidebar,
    Plus,
    Monitor,
    View,
    Delete,
    Bell,
    Calendar,
    Check
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
      },
      recentActivities: []
    }
  },
  computed: {
    totalNotifications() {
      return this.websites.reduce((sum, site) => sum + (site.notifications_count || 0), 0)
    },
    totalDisplays() {
      return 8432 // Placeholder - would need to fetch from API
    },
    todayDisplays() {
      return 245 // Placeholder - would need to fetch from API
    }
  },
  async mounted() {
    await this.fetchWebsites()
    this.generateRecentActivities()
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
    generateRecentActivities() {
      this.recentActivities = [
        { id: 1, message: 'Purchase received from WooCommerce' },
        { id: 2, message: 'Manual notification created' },
        { id: 3, message: 'Widget installed' }
      ]
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
    goToSite(id) {
      this.$router.push(`/sites/${id}`)
    },
    goToSiteSettings(id) {
      this.$router.push(`/sites/${id}/settings`)
    }
  }
}
</script>

<style scoped>
.dashboard {
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

.subtitle {
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

.stats-row {
  margin-bottom: 0;
}

.stat-card {
  text-align: center;
  border-radius: 12px;
}

.stat-description {
  color: var(--el-text-color-secondary);
  margin: 0.5rem 0 0 0;
  font-size: 0.875rem;
}

.recent-activity-card {
  border-radius: 12px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: var(--el-bg-color-page);
  border-radius: 6px;
}

.activity-icon {
  flex-shrink: 0;
}

.websites-card {
  border-radius: 12px;
}

.websites-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.website-card {
  border-radius: 8px;
}

.website-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.website-info {
  flex: 1;
}

.website-info h3 {
  margin: 0 0 0.5rem 0;
  color: var(--el-text-color-primary);
  font-weight: 600;
}

.domain {
  color: var(--el-text-color-secondary);
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
}

.notification-count {
  color: var(--el-text-color-secondary);
  margin: 0.5rem 0 0 0;
  font-size: 0.875rem;
}

.website-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .main-content {
    padding: 1rem;
  }
  
  .page-header h1 {
    font-size: 1.5rem;
  }
  
  .stats-row :deep(.el-col) {
    margin-bottom: 1rem;
  }
  
  .website-content {
    flex-direction: column;
  }
  
  .website-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
