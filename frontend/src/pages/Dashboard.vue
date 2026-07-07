<template>
  <div class="dashboard-layout">
    <Sidebar />
    <div class="main-content">
      <header class="app-header">
        <el-button text class="sidebar-trigger-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="9" x2="9" y1="3" y2="21"/></svg>
        </el-button>
        <div class="header-separator"></div>
        <span class="header-title">Dashboard</span>
      </header>
      <div class="dashboard-container">
        
        <!-- Page header -->
        <div class="page-header">
          <div class="header-text">
            <h1 class="title">Welcome back</h1>
            <p class="subtitle">Manage social proof notifications across all your websites.</p>
          </div>
          <el-button type="primary" @click="showAddModal = true" class="add-btn">
            <el-icon class="btn-icon"><Plus /></el-icon> Add website
          </el-button>
        </div>

        <div v-if="loading" class="loading-container">
          <el-skeleton :rows="5" animated />
        </div>

        <div v-else class="content">
          <!-- Stats -->
          <div class="stats-grid">
            <el-card shadow="never" class="stat-card custom-card" body-style="padding: 20px;">
              <div class="stat-top">
                <div class="stat-icon-wrapper tone-primary">
                  <el-icon><Monitor /></el-icon>
                </div>
                <span class="stat-delta">+1 this month</span>
              </div>
              <div class="stat-content">
                <div class="stat-label">Total Sites</div>
                <div class="stat-value">{{ websites.length }}</div>
                <div class="stat-hint">Connected websites</div>
              </div>
            </el-card>

            <el-card shadow="never" class="stat-card custom-card" body-style="padding: 20px;">
              <div class="stat-top">
                <div class="stat-icon-wrapper tone-success">
                  <el-icon><Bell /></el-icon>
                </div>
                <span class="stat-delta">Live now</span>
              </div>
              <div class="stat-content">
                <div class="stat-label">Active Notifications</div>
                <div class="stat-value">{{ totalNotifications }}</div>
                <div class="stat-hint">Currently running</div>
              </div>
            </el-card>

            <el-card shadow="never" class="stat-card custom-card" body-style="padding: 20px;">
              <div class="stat-top">
                <div class="stat-icon-wrapper tone-info">
                  <el-icon><View /></el-icon>
                </div>
                <span class="stat-delta">+12.4%</span>
              </div>
              <div class="stat-content">
                <div class="stat-label">Total Displays</div>
                <div class="stat-value">{{ totalDisplays.toLocaleString() }}</div>
                <div class="stat-hint">All-time widget views</div>
              </div>
            </el-card>

            <el-card shadow="never" class="stat-card custom-card" body-style="padding: 20px;">
              <div class="stat-top">
                <div class="stat-icon-wrapper tone-warn">
                  <el-icon><Calendar /></el-icon>
                </div>
                <span class="stat-delta">+8% vs yesterday</span>
              </div>
              <div class="stat-content">
                <div class="stat-label">Today's Displays</div>
                <div class="stat-value">{{ todayDisplays }}</div>
                <div class="stat-hint">Last 24 hours</div>
              </div>
            </el-card>
          </div>

          <!-- Two-column grid -->
          <div class="middle-grid">
            <!-- Recent activity -->
            <el-card class="recent-activity-card custom-card" shadow="never" body-style="padding: 0;">
              <div class="card-header-custom">
                <div>
                  <h3 class="card-title">Recent activity</h3>
                  <p class="card-subtitle">Latest events from your connected sites.</p>
                </div>
                <el-button link class="view-all-btn">
                  View all
                </el-button>
              </div>
              <ul class="activity-list">
                <li v-for="(activity, index) in recentActivities" :key="activity.id" class="activity-item">
                  <div class="activity-icon-wrapper" :class="index === 0 ? 'tone-success' : index === 1 ? 'tone-primary' : 'tone-muted'">
                    <el-icon><Check /></el-icon>
                  </div>
                  <div class="activity-text">
                    <p class="activity-title">{{ activity.message }}</p>
                    <p class="activity-meta">{{ activity.website_name }} · {{ activity.source }}</p>
                  </div>
                  <time class="activity-time">{{ activity.time_ago }}</time>
                </li>
              </ul>
            </el-card>

            <!-- Quick tips / engagement -->
            <el-card class="tips-card custom-card" shadow="never">
              <div class="tips-header">
                <div class="tips-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                </div>
                <h3 class="tips-title">Boost conversion by 11%</h3>
                <p class="tips-desc">Add a recent-sales notification to your highest-traffic page to build trust at checkout.</p>
              </div>
              <div class="tips-body">
                <el-button class="w-full">Create notification</el-button>
              </div>
            </el-card>
          </div>

          <!-- Websites -->
          <el-card class="websites-card custom-card" shadow="never" body-style="padding: 0;">
            <div class="card-header-custom websites-header">
              <div>
                <h3 class="card-title">Websites</h3>
                <p class="card-subtitle">{{ websites.length }} {{ websites.length === 1 ? 'site' : 'sites' }} connected</p>
              </div>
              <div class="websites-actions">
                <el-button type="primary" plain :icon="Plus" @click="showAddModal = true" class="hidden-sm">Add Website</el-button>
              </div>
            </div>

            <div v-if="websites.length === 0" class="empty-state">
              <div class="empty-icon"><el-icon><Monitor /></el-icon></div>
              <h3 class="empty-title">Add your first website</h3>
              <p class="empty-desc">Connect a website to start showing social proof notifications to your visitors.</p>
              <el-button type="primary" @click="showAddModal = true" class="mt-4">
                <el-icon class="btn-icon"><Plus /></el-icon> Add website
              </el-button>
            </div>

            <ul v-else class="websites-list">
              <li v-for="website in websites" :key="website.id" class="website-row">
                <div class="website-info-main">
                  <div class="site-avatar tone-primary">
                    {{ website.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="site-details">
                    <div class="site-title-row">
                      <h3 class="site-name">{{ website.name }}</h3>
                      <span class="status-badge" :class="website.is_active ? 'active' : 'paused'">
                        <span class="status-dot"></span>
                        {{ website.is_active ? 'Active' : 'Paused' }}
                      </span>
                    </div>
                    <div class="site-meta-row">
                      <span class="site-domain">{{ website.domain }}</span>
                      <span class="meta-dot">·</span>
                      <span>{{ website.notifications_count || 0 }} notifications</span>
                      <span class="meta-dot">·</span>
                      <span>{{ (website.displays || 0).toLocaleString() }} displays</span>
                    </div>
                  </div>
                </div>
                <div class="website-actions-btn">
                  <el-button plain size="small" @click="goToSite(website.id)">Open</el-button>
                  <el-dropdown trigger="click">
                    <el-button text size="small" class="more-btn">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                    </el-button>
                    <template #dropdown>
                      <el-dropdown-menu>
                        <el-dropdown-item @click="goToSite(website.id)">Open</el-dropdown-item>
                        <el-dropdown-item @click="goToSiteSettings(website.id)">Settings</el-dropdown-item>
                        <el-dropdown-item divided type="danger" class="danger-text" @click="deleteWebsite(website.id)">Delete</el-dropdown-item>
                      </el-dropdown-menu>
                    </template>
                  </el-dropdown>
                </div>
              </li>
            </ul>
          </el-card>
        </div>
      </div>
    </div>

    <!-- Add Website Dialog -->
    <el-dialog v-model="showAddModal" title="Add a website" width="460px" class="custom-dialog">
      <p class="dialog-desc">Connect a new site to start delivering social proof notifications.</p>
      <el-form @submit.prevent="handleAddWebsite" :model="newWebsite" label-position="top">
        <el-form-item label="Display name" class="custom-form-item">
          <el-input v-model="newWebsite.name" placeholder="My store" required />
        </el-form-item>
        <el-form-item label="Domain" class="custom-form-item">
          <el-input v-model="newWebsite.domain" placeholder="example.com" required />
          <p class="input-hint">Don't include https:// or trailing slashes.</p>
        </el-form-item>
        <el-alert v-if="addError" :title="addError" type="error" :closable="false" style="margin-bottom: 1rem" />
      </el-form>
      <template #footer>
        <div class="dialog-footer-actions">
          <el-button @click="showAddModal = false" plain>Cancel</el-button>
          <el-button type="primary" :loading="adding" @click="handleAddWebsite">
            {{ adding ? 'Adding...' : 'Add website' }}
          </el-button>
        </div>
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
      recentActivities: [],
      totalDisplays: 0,
      todayDisplays: 0
    }
  },
  computed: {
    totalNotifications() {
      return this.websites.reduce((sum, site) => sum + (site.notifications_count || 0), 0)
    }
  },
  async mounted() {
    await Promise.all([
      this.fetchWebsites(),
      this.fetchDashboardStats()
    ])
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
    async fetchDashboardStats() {
      try {
        const response = await api.get('/dashboard-stats')
        this.totalDisplays = response.data.total_displays || 0
        this.todayDisplays = response.data.today_displays || 0
        this.recentActivities = response.data.recent_activities || []
      } catch (err) {
        console.error('Failed to fetch dashboard stats:', err)
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
.dashboard-layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background-color: var(--color-bg);
}

.main-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow-y: auto;
}

/* App Header (Sticky) */
.app-header {
  position: sticky;
  top: 0;
  z-index: 20;
  display: flex;
  height: 3.5rem; /* h-14 */
  min-height: 3.5rem;
  align-items: center;
  gap: 0.5rem; /* gap-2 */
  border-bottom: 1px solid var(--color-border);
  background-color: color-mix(in srgb, var(--color-bg) 80%, transparent);
  backdrop-filter: blur(12px);
  padding: 0 1rem;
  box-sizing: border-box;
}

.sidebar-trigger-btn {
  margin-left: -0.25rem; /* -ml-1 */
  padding: 0.5rem;
  height: auto;
  color: var(--color-muted-text);
}

.sidebar-trigger-btn:hover {
  color: var(--color-text);
  background-color: transparent;
}

.header-separator {
  margin: 0 0.25rem; /* mx-1 */
  height: 1.25rem; /* h-5 */
  width: 1px;
  background-color: var(--color-border);
}

.header-title {
  font-size: 0.875rem; /* text-sm */
  font-weight: 500;
  color: var(--color-muted-text);
}

.dashboard-container {
  margin: 0 auto;
  width: 100%;
  max-width: 80rem; /* max-w-7xl */
  padding: 2rem 1rem;
}

@media (min-width: 640px) {
  .dashboard-container {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .dashboard-container {
    padding-left: 2rem;
    padding-right: 2rem;
  }
}

/* Page Header */
.page-header {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: start;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

@media (min-width: 640px) {
  .page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
  }
}

.header-text {
  min-width: 0;
}

.title {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 2rem;
  letter-spacing: -0.025em;
  color: var(--color-text);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (min-width: 640px) {
  .title {
    font-size: 1.875rem;
    line-height: 2.25rem;
  }
}

.subtitle {
  margin-top: 0.25rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: var(--color-muted-text);
}

.add-btn {
  flex-shrink: 0;
  border-radius: var(--radius-md);
  font-weight: 500;
}

.btn-icon {
  margin-right: 0.375rem;
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 1rem;
  margin-top: 1.5rem;
}

@media (min-width: 640px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

/* Card Base Overrides for EXACT Shadow */
.custom-card {
  border: 1px solid var(--color-border) !important;
  box-shadow: var(--shadow-sm) !important;
  background-color: var(--color-surface) !important;
}

.custom-card:hover {
  box-shadow: var(--shadow-md) !important;
}

.stat-card {
  border-radius: var(--radius-lg);
  transition: box-shadow 0.2s;
}

.stat-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.stat-icon-wrapper {
  display: grid;
  place-items: center;
  height: 2.5rem;
  width: 2.5rem;
  border-radius: var(--radius-md);
}

.tone-primary {
  background-color: var(--color-primary-soft);
  color: var(--color-primary);
}

.tone-success {
  background-color: color-mix(in srgb, var(--color-success) 10%, transparent);
  color: var(--color-success);
}

.tone-info {
  background-color: #dbeafe; /* blue-100 */
  color: #1d4ed8; /* blue-700 */
}

.tone-warn {
  background-color: #fef3c7; /* amber-100 */
  color: #b45309; /* amber-700 */
}

.tone-muted {
  background-color: #f1f5f9; /* slate-100 */
  color: var(--color-muted-text);
}

.stat-delta {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-muted-text);
}

.stat-content {
  margin-top: 1rem;
}

.stat-label {
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  color: var(--color-muted-text);
}

.stat-value {
  margin-top: 0.25rem;
  font-size: 1.875rem;
  font-weight: 700;
  letter-spacing: -0.025em;
  color: var(--color-text);
}

.stat-hint {
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: var(--color-muted-text);
}

/* Middle Grid */
.middle-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 1rem;
  margin-top: 1.5rem;
}

@media (min-width: 1024px) {
  .middle-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.recent-activity-card {
  border-radius: var(--radius-lg);
  border-color: var(--color-border);
}

@media (min-width: 1024px) {
  .recent-activity-card {
    grid-column: span 2 / span 2;
  }
}

.card-header-custom {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid var(--color-border);
}

.card-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  color: var(--color-text);
}

.card-subtitle {
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: var(--color-muted-text);
  margin-bottom: 0;
}

.view-all-btn {
  font-size: 0.875rem;
  color: var(--color-muted-text);
}

.activity-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon-wrapper {
  display: grid;
  place-items: center;
  height: 2.25rem;
  width: 2.25rem;
  border-radius: var(--radius-sm);
  flex-shrink: 0;
}

.activity-text {
  min-width: 0;
  flex: 1;
}

.activity-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.activity-meta {
  font-size: 0.75rem;
  color: var(--color-muted-text);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.activity-time {
  flex-shrink: 0;
  font-size: 0.75rem;
  color: var(--color-muted-text);
}

.tips-card {
  border-radius: var(--radius-lg);
  border-color: transparent;
  background: linear-gradient(to bottom right, var(--color-primary-soft), var(--color-surface));
}

.tips-header {
  padding: 1.5rem 1.5rem 0;
}

.tips-icon {
  display: grid;
  place-items: center;
  height: 2.5rem;
  width: 2.5rem;
  border-radius: var(--radius-md);
  background-color: var(--color-primary);
  color: #fff;
  box-shadow: var(--shadow-sm);
}

.tips-title {
  margin-top: 0.75rem;
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text);
}

.tips-desc {
  font-size: 0.875rem;
  color: var(--color-muted-text);
  margin-top: 0.5rem;
  line-height: 1.25rem;
}

.tips-body {
  padding: 1.5rem;
}

.w-full {
  width: 100%;
}

/* Websites */
.websites-card {
  margin-top: 1.5rem;
  border-radius: var(--radius-lg);
  border-color: var(--color-border);
}

.websites-header {
  gap: 1rem;
}

.websites-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.website-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
  transition: background-color 0.2s;
}

.website-row:hover {
  background-color: color-mix(in srgb, var(--color-muted-text) 5%, transparent);
}

.website-row:last-child {
  border-bottom: none;
}

.website-info-main {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.site-avatar {
  display: grid;
  place-items: center;
  height: 2.5rem;
  width: 2.5rem;
  border-radius: var(--radius-md);
  font-weight: 600;
  flex-shrink: 0;
}

.site-details {
  min-width: 0;
}

.site-title-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
}

.site-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.125rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 9999px;
  border: 1px solid transparent;
}

.status-badge.active {
  background-color: color-mix(in srgb, var(--color-success) 10%, transparent);
  color: var(--color-success);
  border-color: color-mix(in srgb, var(--color-success) 30%, transparent);
}

.status-badge.paused {
  color: var(--color-muted-text);
  border-color: var(--color-border);
}

.status-dot {
  height: 0.375rem;
  width: 0.375rem;
  border-radius: 9999px;
}

.status-badge.active .status-dot {
  background-color: var(--color-success);
}

.status-badge.paused .status-dot {
  background-color: var(--color-muted-text);
}

.site-meta-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  column-gap: 0.75rem;
  row-gap: 0.125rem;
  margin-top: 0.125rem;
  font-size: 0.75rem;
  color: var(--color-muted-text);
}

.site-domain {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.meta-dot {
  opacity: 0.5;
}

.website-actions-btn {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.more-btn {
  padding: 0.5rem;
  height: auto;
}

.danger-text {
  color: var(--color-destructive) !important;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  text-align: center;
}

.empty-icon {
  display: grid;
  place-items: center;
  height: 3rem;
  width: 3rem;
  border-radius: 9999px;
  background-color: #f1f5f9;
  color: var(--color-muted-text);
  font-size: 1.5rem;
}

.empty-title {
  margin-top: 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text);
}

.empty-desc {
  margin-top: 0.25rem;
  max-width: 24rem;
  font-size: 0.75rem;
  color: var(--color-muted-text);
}

/* Dialog Overrides */
.dialog-desc {
  font-size: 0.875rem;
  color: var(--color-muted-text);
  margin-top: -1rem;
  margin-bottom: 1.5rem;
}

.custom-form-item {
  margin-bottom: 1rem;
}

.input-hint {
  font-size: 0.75rem;
  color: var(--color-muted-text);
  margin-top: 0.25rem;
}

.dialog-footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

@media (max-width: 639px) {
  .hidden-sm {
    display: none;
  }
}
</style>
