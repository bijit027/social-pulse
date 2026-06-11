<template>
  <div class="notifications-page">
    <Sidebar />
    <main class="main-content">
      <div class="page-header">
        <h1>Notifications</h1>
        <p>Manage all your social proof notifications across all sites</p>
      </div>

      <div class="content-wrapper">
        <el-card class="stats-card" shadow="hover">
          <el-row :gutter="20">
            <el-col :span="6">
              <div class="stat-item">
                <div class="stat-icon total">
                  <el-icon><Bell /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ stats.total }}</div>
                  <div class="stat-label">Total Notifications</div>
                </div>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-item">
                <div class="stat-icon active">
                  <el-icon><Check /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ stats.active }}</div>
                  <div class="stat-label">Active</div>
                </div>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-item">
                <div class="stat-icon auto">
                  <el-icon><Connection /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ stats.auto }}</div>
                  <div class="stat-label">Auto-generated</div>
                </div>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-item">
                <div class="stat-icon manual">
                  <el-icon><Edit /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ stats.manual }}</div>
                  <div class="stat-label">Manual</div>
                </div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <el-card class="filters-card" shadow="hover">
          <el-form :inline="true" :model="filters">
            <el-form-item label="Search">
              <el-input v-model="filters.search" placeholder="Search notifications..." clearable style="width: 250px" />
            </el-form-item>
            <el-form-item label="Source">
              <el-select v-model="filters.source" placeholder="All Sources" clearable style="width: 150px">
                <el-option label="All Sources" value="" />
                <el-option label="WooCommerce" value="woocommerce" />
                <el-option label="Stripe" value="stripe" />
                <el-option label="Manual" value="manual" />
              </el-select>
            </el-form-item>
            <el-form-item label="Status">
              <el-select v-model="filters.status" placeholder="All Status" clearable style="width: 150px">
                <el-option label="All Status" value="" />
                <el-option label="Active" value="active" />
                <el-option label="Inactive" value="inactive" />
              </el-select>
            </el-form-item>
            <el-form-item label="Site">
              <el-select v-model="filters.site" placeholder="All Sites" clearable style="width: 200px">
                <el-option label="All Sites" value="" />
                <el-option v-for="site in sites" :key="site.id" :label="site.name" :value="site.id" />
              </el-select>
            </el-form-item>
          </el-form>
        </el-card>

        <el-card class="notifications-card" shadow="hover">
          <template #header>
            <div class="card-header">
              <span>All Notifications</span>
              <el-button type="primary" :icon="Plus" @click="showAddModal = true">Add Notification</el-button>
            </div>
          </template>

          <el-table :data="filteredNotifications" v-loading="loading" stripe>
            <el-table-column prop="message" label="Message" min-width="250" />
            <el-table-column prop="source" label="Source" width="120">
              <template #default="{ row }">
                <el-tag :type="getSourceTag(row.source)" size="small">{{ getSourceLabel(row.source) }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="website.name" label="Site" width="150" />
            <el-table-column prop="city" label="Location" width="150">
              <template #default="{ row }">
                {{ row.city }}{{ row.country ? ', ' + row.country : '' }}
              </template>
            </el-table-column>
            <el-table-column prop="total_displays" label="Displays" width="100" sortable />
            <el-table-column prop="is_active" label="Status" width="100">
              <template #default="{ row }">
                <el-tag :type="row.is_active ? 'success' : 'info'" size="small">
                  {{ row.is_active ? 'Active' : 'Inactive' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="created_at" label="Created" width="150">
              <template #default="{ row }">
                {{ formatDate(row.created_at) }}
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="150" fixed="right">
              <template #default="{ row }">
                <el-button type="primary" size="small" @click="toggleNotification(row)">
                  {{ row.is_active ? 'Disable' : 'Enable' }}
                </el-button>
                <el-button type="danger" size="small" :icon="Delete" @click="deleteNotification(row.id)" />
              </template>
            </el-table-column>
          </el-table>

          <el-pagination
            v-model:current-page="pagination.page"
            v-model:page-size="pagination.pageSize"
            :total="pagination.total"
            :page-sizes="[10, 25, 50, 100]"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="fetchNotifications"
            @current-change="fetchNotifications"
            style="margin-top: 1rem; justify-content: flex-end"
          />
        </el-card>
      </div>
    </main>

    <!-- Add Notification Modal -->
    <el-dialog v-model="showAddModal" title="Add Notification" width="500px">
      <el-form :model="newNotification" label-position="top">
        <el-form-item label="Site">
          <el-select v-model="newNotification.website_id" placeholder="Select a site" style="width: 100%">
            <el-option v-for="site in sites" :key="site.id" :label="site.name" :value="site.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="Message">
          <el-input v-model="newNotification.message" type="textarea" :rows="3" placeholder="e.g., John just purchased Pro Plan" />
        </el-form-item>
        <el-form-item label="City (optional)">
          <el-input v-model="newNotification.city" type="text" placeholder="e.g., New York" />
        </el-form-item>
        <el-form-item label="Country (optional)">
          <el-input v-model="newNotification.country" type="text" placeholder="e.g., USA" />
        </el-form-item>
        <el-form-item label="Emoji">
          <el-input v-model="newNotification.emoji" type="text" placeholder="e.g., 🛒" />
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
import { Bell, Check, Connection, Edit, Plus, Delete } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'

export default {
  name: 'Notifications',
  components: {
    Sidebar,
    Bell,
    Check,
    Connection,
    Edit,
    Plus,
    Delete
  },
  data() {
    return {
      loading: true,
      notifications: [],
      sites: [],
      stats: {
        total: 0,
        active: 0,
        auto: 0,
        manual: 0
      },
      filters: {
        search: '',
        source: '',
        status: '',
        site: ''
      },
      pagination: {
        page: 1,
        pageSize: 25,
        total: 0
      },
      showAddModal: false,
      adding: false,
      addError: '',
      newNotification: {
        website_id: null,
        message: '',
        city: '',
        country: '',
        emoji: '🛒'
      }
    }
  },
  computed: {
    filteredNotifications() {
      return this.notifications.filter(n => {
        const matchSearch = !this.filters.search || 
          n.message.toLowerCase().includes(this.filters.search.toLowerCase()) ||
          (n.city && n.city.toLowerCase().includes(this.filters.search.toLowerCase())) ||
          (n.country && n.country.toLowerCase().includes(this.filters.search.toLowerCase()))
        
        const matchSource = !this.filters.source || n.source === this.filters.source
        const matchStatus = !this.filters.status || 
          (this.filters.status === 'active' && n.is_active) ||
          (this.filters.status === 'inactive' && !n.is_active)
        const matchSite = !this.filters.site || n.website_id === this.filters.site
        
        return matchSearch && matchSource && matchStatus && matchSite
      })
    }
  },
  async mounted() {
    await Promise.all([
      this.fetchNotifications(),
      this.fetchSites()
    ])
  },
  methods: {
    async fetchNotifications() {
      this.loading = true
      try {
        const response = await api.get('/notifications', {
          params: {
            page: this.pagination.page,
            per_page: this.pagination.pageSize
          }
        })
        this.notifications = response.data.data || response.data
        this.pagination.total = response.data.total || this.notifications.length
        
        // Calculate stats
        this.stats.total = this.notifications.length
        this.stats.active = this.notifications.filter(n => n.is_active).length
        this.stats.auto = this.notifications.filter(n => n.source !== 'manual').length
        this.stats.manual = this.notifications.filter(n => n.source === 'manual').length
      } catch (err) {
        ElMessage.error('Failed to fetch notifications')
      } finally {
        this.loading = false
      }
    },
    async fetchSites() {
      try {
        const response = await api.get('/websites')
        this.sites = response.data
      } catch (err) {
        ElMessage.error('Failed to fetch sites')
      }
    },
    async toggleNotification(notification) {
      try {
        await api.patch(`/notifications/${notification.id}/toggle`)
        notification.is_active = !notification.is_active
        ElMessage.success('Notification updated')
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
        this.notifications = this.notifications.filter(n => n.id !== id)
        this.stats.total--
        ElMessage.success('Notification deleted')
      } catch (err) {
        if (err !== 'cancel') {
          ElMessage.error('Failed to delete notification')
        }
      }
    },
    async handleAddNotification() {
      this.adding = true
      this.addError = ''
      try {
        const response = await api.post('/notifications', this.newNotification)
        this.showAddModal = false
        this.newNotification = { website_id: null, message: '', city: '', country: '', emoji: '🛒' }
        await this.fetchNotifications()
        ElMessage.success('Notification added!')
      } catch (err) {
        this.addError = err.response?.data?.message || 'Failed to add notification'
      } finally {
        this.adding = false
      }
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
    formatDate(date) {
      return new Date(date).toLocaleDateString()
    }
  }
}
</script>

<style scoped>
.notifications-page {
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

.page-header p {
  margin: 0;
  color: var(--el-text-color-secondary);
}

.content-wrapper {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.stats-card {
  border-radius: 12px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 8px;
  background: var(--el-bg-color-page);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.stat-icon.total {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.active {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.auto {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.manual {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--el-text-color-primary);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--el-text-color-secondary);
}

.filters-card {
  border-radius: 12px;
}

.notifications-card {
  border-radius: 12px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header span {
  font-weight: 600;
  color: var(--el-text-color-primary);
}
</style>
