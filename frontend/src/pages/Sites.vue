<template>
  <div class="sites-page">
    <Sidebar />
    <div class="main-content">
      <div class="page-header">
        <h1>Sites</h1>
        <el-button type="primary" :icon="Plus" @click="showAddModal = true">Add Site</el-button>
      </div>

      <div v-if="loading" class="loading-container">
        <el-skeleton :rows="5" animated />
      </div>

      <div v-else class="sites-content">
        <el-empty v-if="sites.length === 0" description="No websites yet. Create your first website to start displaying notifications.">
          <el-button type="primary" :icon="Plus" @click="showAddModal = true">Add Website</el-button>
        </el-empty>

        <div v-else class="sites-grid">
          <el-card v-for="site in sites" :key="site.id" class="site-card" shadow="hover">
            <div class="site-card-content">
              <div class="site-info">
                <h3>{{ site.name }}</h3>
                <p class="domain">{{ site.domain }}</p>
                <el-tag :type="site.is_active ? 'success' : 'info'" size="small">
                  {{ site.is_active ? 'Active' : 'Disabled' }}
                </el-tag>
              </div>
              <div class="site-stats">
                <p>{{ site.notifications_count || 0 }} Notifications</p>
                <p class="created">Created: {{ formatDate(site.created_at) }}</p>
              </div>
            </div>
            <div class="site-actions">
              <el-button type="primary" size="small" @click="goToSite(site.id)">Open</el-button>
              <el-button size="small" @click="editSite(site)">Settings</el-button>
              <el-button type="danger" size="small" :icon="Delete" @click="deleteSite(site.id)">Delete</el-button>
            </div>
          </el-card>
        </div>
      </div>
    </div>

    <!-- Add Site Modal -->
    <el-dialog v-model="showAddModal" title="Add Site" width="500px">
      <el-form @submit.prevent="handleAddSite" :model="newSite" label-position="top">
        <el-form-item label="Site Name">
          <el-input v-model="newSite.name" type="text" required placeholder="My Website" />
        </el-form-item>
        <el-form-item label="Website URL">
          <el-input v-model="newSite.domain" type="text" required placeholder="https://example.com" />
        </el-form-item>
        <el-alert v-if="addError" :title="addError" type="error" :closable="false" style="margin-bottom: 1rem" />
      </el-form>
      <template #footer>
        <el-button @click="showAddModal = false">Cancel</el-button>
        <el-button type="primary" :loading="adding" @click="handleAddSite">
          {{ adding ? 'Adding...' : 'Create Site' }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import api from '../services/api'
import Sidebar from '../components/Sidebar.vue'
import { Plus, Delete } from '@element-plus/icons-vue'

export default {
  name: 'Sites',
  components: {
    Sidebar,
    Plus,
    Delete
  },
  data() {
    return {
      sites: [],
      loading: true,
      showAddModal: false,
      adding: false,
      addError: '',
      newSite: {
        name: '',
        domain: ''
      }
    }
  },
  async mounted() {
    await this.fetchSites()
  },
  methods: {
    async fetchSites() {
      try {
        const response = await api.get('/websites')
        this.sites = response.data
      } catch (err) {
        console.error('Failed to fetch sites:', err)
      } finally {
        this.loading = false
      }
    },
    async handleAddSite() {
      this.adding = true
      this.addError = ''
      try {
        const response = await api.post('/websites', this.newSite)
        this.showAddModal = false
        this.newSite = { name: '', domain: '' }
        await this.fetchSites()
      } catch (err) {
        this.addError = err.response?.data?.message || 'Failed to add site'
      } finally {
        this.adding = false
      }
    },
    goToSite(id) {
      this.$router.push(`/sites/${id}`)
    },
    editSite(site) {
      this.$router.push(`/sites/${site.id}/settings`)
    },
    async deleteSite(id) {
      if (!confirm('Are you sure you want to delete this site?')) return
      
      try {
        await api.delete(`/websites/${id}`)
        await this.fetchSites()
      } catch (err) {
        alert('Failed to delete site')
      }
    },
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString()
    }
  }
}
</script>

<style scoped>
.sites-page {
  display: flex;
  min-height: 100vh;
  background: var(--el-bg-color-page);
}

.main-content {
  flex: 1;
  padding: 2rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.page-header h1 {
  margin: 0;
  color: var(--el-text-color-primary);
  font-size: 1.75rem;
  font-weight: 700;
}

.loading-container {
  padding: 2rem;
}

.sites-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.sites-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.site-card {
  border-radius: 12px;
}

.site-card-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1rem;
}

.site-info h3 {
  margin: 0 0 0.5rem 0;
  color: var(--el-text-color-primary);
  font-weight: 600;
}

.domain {
  color: var(--el-text-color-secondary);
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
}

.site-stats {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.site-stats p {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 0.875rem;
}

.created {
  font-size: 0.75rem;
}

.site-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

@media (max-width: 768px) {
  .main-content {
    padding: 1rem;
  }
  
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .sites-grid {
    grid-template-columns: 1fr;
  }
}
</style>
