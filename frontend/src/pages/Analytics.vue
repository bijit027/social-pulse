<template>
  <div class="analytics-page">
    <Sidebar />
    <main class="main-content">
      <div class="page-header">
        <h1>Analytics</h1>
        <p>Track performance and engagement across all your social proof notifications</p>
      </div>

      <div class="content-wrapper">
        <!-- Date Range Filter -->
        <el-card class="filter-card" shadow="hover">
          <el-form :inline="true">
            <el-form-item label="Date Range">
              <el-date-picker
                v-model="dateRange"
                type="daterange"
                range-separator="to"
                start-placeholder="Start date"
                end-placeholder="End date"
                :shortcuts="dateShortcuts"
                @change="fetchAnalytics"
              />
            </el-form-item>
            <el-form-item label="Site">
              <el-select v-model="selectedSite" placeholder="All Sites" clearable @change="fetchAnalytics" style="width: 200px">
                <el-option label="All Sites" :value="null" />
                <el-option v-for="site in sites" :key="site.id" :label="site.name" :value="site.id" />
              </el-select>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="fetchAnalytics">Refresh</el-button>
              <el-button @click="exportReport">Export Report</el-button>
            </el-form-item>
          </el-form>
        </el-card>

        <!-- Stats Cards -->
        <el-row :gutter="20">
          <el-col :span="6">
            <el-card class="stat-card views" shadow="hover">
              <div class="stat-content">
                <div class="stat-icon">
                  <el-icon><View /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ formatNumber(stats.totalViews) }}</div>
                  <div class="stat-label">Total Views</div>
                  <div class="stat-change" :class="stats.viewsChange >= 0 ? 'positive' : 'negative'">
                    {{ stats.viewsChange >= 0 ? '+' : '' }}{{ stats.viewsChange }}%
                  </div>
                </div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card clicks" shadow="hover">
              <div class="stat-content">
                <div class="stat-icon">
                  <el-icon><Pointer /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ formatNumber(stats.totalClicks) }}</div>
                  <div class="stat-label">Total Clicks</div>
                  <div class="stat-change" :class="stats.clicksChange >= 0 ? 'positive' : 'negative'">
                    {{ stats.clicksChange >= 0 ? '+' : '' }}{{ stats.clicksChange }}%
                  </div>
                </div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card ctr" shadow="hover">
              <div class="stat-content">
                <div class="stat-icon">
                  <el-icon><TrendCharts /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ stats.ctr }}%</div>
                  <div class="stat-label">Click-Through Rate</div>
                  <div class="stat-change" :class="stats.ctrChange >= 0 ? 'positive' : 'negative'">
                    {{ stats.ctrChange >= 0 ? '+' : '' }}{{ stats.ctrChange }}%
                  </div>
                </div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="6">
            <el-card class="stat-card displays" shadow="hover">
              <div class="stat-content">
                <div class="stat-icon">
                  <el-icon><Monitor /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ formatNumber(stats.totalDisplays) }}</div>
                  <div class="stat-label">Total Displays</div>
                  <div class="stat-change" :class="stats.displaysChange >= 0 ? 'positive' : 'negative'">
                    {{ stats.displaysChange >= 0 ? '+' : '' }}{{ stats.displaysChange }}%
                  </div>
                </div>
              </div>
            </el-card>
          </el-col>
        </el-row>

        <!-- Charts -->
        <el-row :gutter="20">
          <el-col :span="16">
            <el-card class="chart-card" shadow="hover">
              <template #header>
                <div class="card-header">
                  <span>Views & Clicks Over Time</span>
                  <el-radio-group v-model="chartPeriod" size="small" @change="fetchAnalytics">
                    <el-radio-button label="7d">7 Days</el-radio-button>
                    <el-radio-button label="30d">30 Days</el-radio-button>
                    <el-radio-button label="90d">90 Days</el-radio-button>
                  </el-radio-group>
                </div>
              </template>
              <div class="simple-chart">
                <div class="chart-placeholder">
                  <el-icon :size="48" color="#909399"><TrendCharts /></el-icon>
                  <p>Chart visualization coming soon</p>
                  <el-button type="primary" size="small" @click="exportReport">Export Data</el-button>
                </div>
              </div>
            </el-card>
          </el-col>
          <el-col :span="8">
            <el-card class="chart-card" shadow="hover">
              <template #header>
                <div class="card-header">
                  <span>Notifications by Source</span>
                </div>
              </template>
              <div class="source-distribution">
                <div v-for="source in sourceDistribution" :key="source.name" class="source-item">
                  <div class="source-label">{{ source.name }}</div>
                  <div class="source-bar">
                    <div class="source-fill" :style="{ width: source.percentage + '%', background: source.color }"></div>
                  </div>
                  <div class="source-value">{{ source.count }}</div>
                </div>
              </div>
            </el-card>
          </el-col>
        </el-row>

        <!-- Top Performing Notifications -->
        <el-card class="top-notifications-card" shadow="hover">
          <template #header>
            <div class="card-header">
              <span>Top Performing Notifications</span>
              <el-button type="text" @click="$router.push('/notifications')">View All</el-button>
            </div>
          </template>
          <el-table :data="topNotifications" stripe>
            <el-table-column prop="message" label="Message" min-width="200" />
            <el-table-column prop="source" label="Source" width="120">
              <template #default="{ row }">
                <el-tag :type="getSourceTag(row.source)" size="small">{{ getSourceLabel(row.source) }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="website.name" label="Site" width="150" />
            <el-table-column prop="total_views" label="Views" width="100" sortable />
            <el-table-column prop="total_clicks" label="Clicks" width="100" sortable />
            <el-table-column label="CTR" width="100">
              <template #default="{ row }">
                {{ calculateCTR(row) }}%
              </template>
            </el-table-column>
          </el-table>
        </el-card>

        <!-- Site Performance -->
        <el-card class="site-performance-card" shadow="hover">
          <template #header>
            <div class="card-header">
              <span>Site Performance</span>
            </div>
          </template>
          <el-table :data="sitePerformance" stripe>
            <el-table-column prop="name" label="Site" min-width="150" />
            <el-table-column prop="domain" label="Domain" min-width="200" />
            <el-table-column prop="total_views" label="Views" width="100" sortable />
            <el-table-column prop="total_clicks" label="Clicks" width="100" sortable />
            <el-table-column prop="total_displays" label="Displays" width="100" sortable />
            <el-table-column label="CTR" width="100">
              <template #default="{ row }">
                {{ calculateSiteCTR(row) }}%
              </template>
            </el-table-column>
            <el-table-column prop="active_visitors" label="Live Visitors" width="120">
              <template #default="{ row }">
                <div class="live-visitors">
                  <span class="live-dot"></span>
                  {{ row.active_visitors || 0 }}
                </div>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>
    </main>
  </div>
</template>

<script>
import api from '../services/api'
import Sidebar from '../components/Sidebar.vue'
import { View, Pointer, TrendCharts, Monitor } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

export default {
  name: 'Analytics',
  components: {
    Sidebar,
    View,
    Pointer,
    TrendCharts,
    Monitor
  },
  data() {
    return {
      loading: true,
      dateRange: [new Date(Date.now() - 30 * 24 * 60 * 60 * 1000), new Date()],
      dateShortcuts: [
        {
          text: 'Last 7 days',
          value: () => [new Date(Date.now() - 7 * 24 * 60 * 60 * 1000), new Date()]
        },
        {
          text: 'Last 30 days',
          value: () => [new Date(Date.now() - 30 * 24 * 60 * 60 * 1000), new Date()]
        },
        {
          text: 'Last 90 days',
          value: () => [new Date(Date.now() - 90 * 24 * 60 * 60 * 1000), new Date()]
        }
      ],
      selectedSite: null,
      chartPeriod: '30d',
      sites: [],
      stats: {
        totalViews: 0,
        totalClicks: 0,
        ctr: 0,
        totalDisplays: 0,
        viewsChange: 0,
        clicksChange: 0,
        ctrChange: 0,
        displaysChange: 0
      },
      topNotifications: [],
      sitePerformance: [],
      sourceDistribution: [],
      notifications: []
    }
  },
  async mounted() {
    await this.fetchSites()
    await this.fetchAnalytics()
  },
  methods: {
    async fetchSites() {
      try {
        const response = await api.get('/websites')
        this.sites = response.data
      } catch (err) {
        ElMessage.error('Failed to fetch sites')
      }
    },
    async fetchAnalytics() {
      this.loading = true
      try {
        const params = {
          start_date: this.dateRange[0].toISOString().split('T')[0],
          end_date: this.dateRange[1].toISOString().split('T')[0],
          period: this.chartPeriod
        }
        
        if (this.selectedSite) {
          params.site_id = this.selectedSite
        }

        const response = await api.get('/analytics', { params })
        const data = response.data
        
        this.stats = {
          totalViews: data.summary?.total_views || 0,
          totalClicks: data.summary?.total_clicks || 0,
          ctr: data.summary?.ctr || 0,
          totalDisplays: data.summary?.total_displays || 0,
          viewsChange: data.views_change || 0,
          clicksChange: data.clicks_change || 0,
          ctrChange: data.ctr_change || 0,
          displaysChange: data.displays_change || 0
        }

        this.topNotifications = data.top_notifications || []
        this.sitePerformance = data.site_performance || []
        this.notifications = data.notifications || []

        // Calculate source distribution from notifications
        const sourceCounts = {}
        this.notifications.forEach(n => {
          const source = n.source || 'manual'
          sourceCounts[source] = (sourceCounts[source] || 0) + 1
        })

        const total = Object.values(sourceCounts).reduce((a, b) => a + b, 0) || 1
        const colors = {
          woocommerce: '#67C23A',
          stripe: '#F56C6C',
          manual: '#909399',
          surecart: '#409EFF',
          edd: '#E6A23C'
        }

        this.sourceDistribution = Object.entries(sourceCounts).map(([name, count]) => ({
          name: this.getSourceLabel(name),
          count,
          percentage: (count / total) * 100,
          color: colors[name] || '#909399'
        }))
      } catch (err) {
        ElMessage.error('Failed to fetch analytics')
      } finally {
        this.loading = false
      }
    },
    exportReport() {
      ElMessage.info('Export feature coming soon')
    },
    formatNumber(num) {
      if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M'
      }
      if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K'
      }
      return num.toString()
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
    calculateCTR(notification) {
      if (!notification.total_views || notification.total_views === 0) return 0
      return ((notification.total_clicks / notification.total_views) * 100).toFixed(2)
    },
    calculateSiteCTR(site) {
      if (!site.total_views || site.total_views === 0) return 0
      return ((site.total_clicks / site.total_views) * 100).toFixed(2)
    }
  }
}
</script>

<style scoped>
.analytics-page {
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

.filter-card {
  border-radius: 12px;
}

.stat-card {
  border-radius: 12px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
}

.stat-card.views .stat-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.clicks .stat-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.ctr .stat-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.displays .stat-icon {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--el-text-color-primary);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--el-text-color-secondary);
  margin-bottom: 0.25rem;
}

.stat-change {
  font-size: 0.75rem;
  font-weight: 600;
}

.stat-change.positive {
  color: #67c23a;
}

.stat-change.negative {
  color: #f56c6c;
}

.chart-card {
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

.chart-container {
  height: 300px;
  width: 100%;
}

.simple-chart {
  height: 300px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chart-placeholder {
  text-align: center;
  color: var(--el-text-color-secondary);
}

.chart-placeholder p {
  margin: 1rem 0;
  font-size: 0.875rem;
}

.source-distribution {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1rem 0;
}

.source-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.source-label {
  width: 100px;
  font-size: 0.875rem;
  color: var(--el-text-color-regular);
}

.source-bar {
  flex: 1;
  height: 24px;
  background: var(--el-bg-color-page);
  border-radius: 4px;
  overflow: hidden;
}

.source-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.source-value {
  width: 50px;
  text-align: right;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.top-notifications-card,
.site-performance-card {
  border-radius: 12px;
}

.live-visitors {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.live-dot {
  width: 8px;
  height: 8px;
  background: #22c55e;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
  }
  70% {
    box-shadow: 0 0 0 6px rgba(34, 197, 94, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
  }
}
</style>
