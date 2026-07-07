<template>
  <div class="activity-page">
    <Sidebar />
    <main class="main-content">
      <div class="page-header">
        <div class="header-content">
          <h1>Activity Log</h1>
          <p>A complete history of all events and notifications across your connected websites.</p>
        </div>
      </div>

      <el-card class="table-card custom-card" shadow="never">
        <div class="table-actions">
          <el-input
            v-model="searchQuery"
            placeholder="Search activity..."
            class="search-input"
            clearable
            :prefix-icon="Search"
            @input="handleSearch"
          />
          <el-button @click="fetchActivities" :icon="Refresh">Refresh</el-button>
        </div>

        <el-table
          v-loading="loading"
          :data="filteredActivities"
          style="width: 100%"
          class="custom-table"
          :row-class-name="tableRowClassName"
        >
          <el-table-column label="Event" min-width="250">
            <template #default="{ row }">
              <div class="activity-event-col">
                <div class="activity-icon-wrapper" :class="getSourceClass(row.source)">
                  <el-icon v-if="row.source === 'woocommerce' || row.source === 'stripe' || row.source === 'surecart' || row.source === 'edd'"><Check /></el-icon>
                  <el-icon v-else><Bell /></el-icon>
                </div>
                <div class="activity-text">
                  <div class="activity-message">{{ row.message }}</div>
                  <div class="activity-meta">Source: {{ getSourceLabel(row.source) }}</div>
                </div>
              </div>
            </template>
          </el-table-column>
          
          <el-table-column prop="website_name" label="Website" width="200" />
          
          <el-table-column label="Time" width="180">
            <template #default="{ row }">
              <div class="time-col">
                <span class="time-ago">{{ row.time_ago }}</span>
                <span class="exact-time">{{ formatDate(row.created_at) }}</span>
              </div>
            </template>
          </el-table-column>
          
          <template #empty>
            <div class="empty-state">
              <el-icon class="empty-icon"><Document /></el-icon>
              <h3>No activity found</h3>
              <p>There are no recent events to display.</p>
            </div>
          </template>
        </el-table>

        <div class="pagination-container" v-if="total > 0">
          <el-pagination
            v-model:current-page="currentPage"
            :page-size="15"
            layout="prev, pager, next"
            :total="total"
            @current-change="handlePageChange"
            background
          />
        </div>
      </el-card>
    </main>
  </div>
</template>

<script>
import api from '../services/api'
import Sidebar from '../components/Sidebar.vue'
import { Search, Refresh, Check, Bell, Document } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

export default {
  name: 'Activity',
  components: {
    Sidebar,
    Search,
    Refresh,
    Check,
    Bell,
    Document
  },
  data() {
    return {
      activities: [],
      loading: true,
      searchQuery: '',
      currentPage: 1,
      total: 0,
      Search,
      Refresh
    }
  },
  computed: {
    filteredActivities() {
      if (!this.searchQuery) return this.activities
      
      const query = this.searchQuery.toLowerCase()
      return this.activities.filter(activity => 
        activity.message.toLowerCase().includes(query) ||
        activity.website_name.toLowerCase().includes(query) ||
        (activity.source && activity.source.toLowerCase().includes(query))
      )
    }
  },
  mounted() {
    this.fetchActivities()
  },
  methods: {
    async fetchActivities() {
      this.loading = true
      try {
        const response = await api.get(`/activities?page=${this.currentPage}`)
        this.activities = response.data.data
        this.total = response.data.total
        this.currentPage = response.data.current_page
      } catch (err) {
        console.error('Failed to fetch activities:', err)
        ElMessage.error('Failed to load activity log')
      } finally {
        this.loading = false
      }
    },
    handleSearch() {
      // Local search is applied via computed property for now. 
      // If we want server-side search, we'd debounce and call fetchActivities with a search param.
    },
    handlePageChange(page) {
      this.currentPage = page
      this.fetchActivities()
    },
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString(undefined, { 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    getSourceLabel(source) {
      const labels = {
        woocommerce: 'WooCommerce',
        stripe: 'Stripe',
        surecart: 'SureCart',
        edd: 'Easy Digital Downloads',
        manual: 'Manual'
      }
      return labels[source] || source || 'System'
    },
    getSourceClass(source) {
      if (source === 'woocommerce' || source === 'stripe') return 'tone-success'
      if (source === 'surecart' || source === 'edd') return 'tone-primary'
      return 'tone-muted'
    },
    tableRowClassName({ rowIndex }) {
      return 'custom-table-row'
    }
  }
}
</script>

<style scoped>
.activity-page {
  display: flex;
  min-height: 100vh;
  background-color: var(--color-bg);
}

.main-content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
}

.page-header {
  margin-bottom: 2rem;
}

.page-header h1 {
  margin: 0 0 0.5rem 0;
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: -0.025em;
}

.page-header p {
  margin: 0;
  color: var(--color-muted-text);
  font-size: 0.875rem;
}

.custom-card {
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
  background-color: var(--color-surface);
}

.table-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid var(--color-border);
}

.search-input {
  width: 300px;
}

.custom-table {
  --el-table-border-color: var(--color-border);
  --el-table-header-bg-color: #f8fafc;
  --el-table-header-text-color: var(--color-muted-text);
  --el-table-text-color: var(--color-text);
  --el-table-row-hover-bg-color: #f1f5f9;
}

.custom-table :deep(th.el-table__cell) {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  padding: 1rem 1.5rem;
}

.custom-table :deep(td.el-table__cell) {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
}

.activity-event-col {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.activity-icon-wrapper {
  display: grid;
  place-items: center;
  height: 2.5rem;
  width: 2.5rem;
  border-radius: var(--radius-md);
  flex-shrink: 0;
}

.tone-success {
  background-color: color-mix(in srgb, var(--color-success) 10%, transparent);
  color: var(--color-success);
}

.tone-primary {
  background-color: var(--color-primary-soft);
  color: var(--color-primary);
}

.tone-muted {
  background-color: #f1f5f9;
  color: var(--color-muted-text);
}

.activity-message {
  font-weight: 500;
  color: var(--color-text);
  margin-bottom: 0.25rem;
}

.activity-meta {
  font-size: 0.75rem;
  color: var(--color-muted-text);
}

.time-col {
  display: flex;
  flex-direction: column;
}

.time-ago {
  font-weight: 500;
  color: var(--color-text);
}

.exact-time {
  font-size: 0.75rem;
  color: var(--color-muted-text);
  margin-top: 0.25rem;
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  padding: 1.5rem;
  border-top: 1px solid var(--color-border);
}

.empty-state {
  padding: 4rem 2rem;
  text-align: center;
}

.empty-icon {
  font-size: 3rem;
  color: var(--color-muted-text);
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  color: var(--color-text);
  font-weight: 600;
}

.empty-state p {
  margin: 0;
  color: var(--color-muted-text);
  font-size: 0.875rem;
}
</style>
