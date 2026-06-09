<template>
  <div class="settings">
    <el-container class="settings-container">
      <el-header class="settings-header">
        <div class="header-left">
          <router-link to="/">
            <el-button :icon="ArrowLeft">Back to Dashboard</el-button>
          </router-link>
          <h1>Settings</h1>
        </div>
      </el-header>

      <el-main class="settings-main">
        <div v-if="loading" class="loading-container">
          <el-skeleton :rows="5" animated />
        </div>

        <div v-else-if="user" class="settings-content">
          <el-row :gutter="20">
            <el-col :span="24">
              <el-card class="profile-card" shadow="hover">
                <template #header>
                  <div class="card-header">
                    <el-icon :size="20" color="#409eff"><User /></el-icon>
                    <span>Profile</span>
                  </div>
                </template>
                <el-descriptions :column="1" border>
                  <el-descriptions-item label="Name">{{ user.name }}</el-descriptions-item>
                  <el-descriptions-item label="Email">{{ user.email }}</el-descriptions-item>
                </el-descriptions>
              </el-card>
            </el-col>

            <el-col :span="24">
              <el-card class="plan-card" shadow="hover">
                <template #header>
                  <div class="card-header">
                    <el-icon :size="20" color="#67c23a"><CreditCard /></el-icon>
                    <span>Plan</span>
                  </div>
                </template>
                <el-descriptions :column="1" border>
                  <el-descriptions-item label="Current Plan">
                    <el-tag type="primary">{{ user.plan.toUpperCase() }}</el-tag>
                  </el-descriptions-item>
                  <el-descriptions-item v-if="user.plan === 'trial'" label="Trial Ends">
                    {{ formatDate(user.trial_ends_at) }}
                  </el-descriptions-item>
                  <el-descriptions-item label="Status">
                    <el-tag :type="user.is_on_trial ? 'warning' : (user.is_paid ? 'success' : 'info')">
                      {{ user.is_on_trial ? 'On Trial' : (user.is_paid ? 'Paid' : 'Free') }}
                    </el-tag>
                  </el-descriptions-item>
                </el-descriptions>
              </el-card>
            </el-col>

            <el-col :span="24">
              <el-card class="limits-card" shadow="hover">
                <template #header>
                  <div class="card-header">
                    <el-icon :size="20" color="#e6a23c"><DataAnalysis /></el-icon>
                    <span>Usage Limits</span>
                  </div>
                </template>
                <el-descriptions :column="1" border>
                  <el-descriptions-item label="Websites">
                    <el-progress :percentage="getUsagePercentage()" :stroke-width="20" />
                    <div style="margin-top: 0.5rem; color: var(--el-text-color-secondary); font-size: 0.875rem;">
                      {{ websitesCount }} / {{ getWebsiteLimit() }} websites
                    </div>
                  </el-descriptions-item>
                </el-descriptions>
              </el-card>
            </el-col>

            <el-col :span="24">
              <el-card class="actions-card" shadow="hover">
                <el-button type="danger" :icon="SwitchButton" @click="handleLogout" style="width: 100%">
                  Logout
                </el-button>
              </el-card>
            </el-col>
          </el-row>
        </div>
      </el-main>
    </el-container>
  </div>
</template>

<script>
import api from '../services/api'
import { ArrowLeft, User, CreditCard, DataAnalysis, SwitchButton } from '@element-plus/icons-vue'

export default {
  name: 'Settings',
  components: {
    ArrowLeft,
    User,
    CreditCard,
    DataAnalysis,
    SwitchButton
  },
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
    getUsagePercentage() {
      const limit = this.getWebsiteLimit()
      if (limit === 0) return 0
      return Math.min((this.websitesCount / limit) * 100, 100)
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
  background: var(--el-bg-color-page);
}

.settings-container {
  min-height: 100vh;
}

.settings-header {
  background: white;
  padding: 1rem 2rem;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-bottom: 1px solid var(--el-border-color-light);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.settings-header h1 {
  color: var(--el-text-color-primary);
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.settings-main {
  padding: 2rem;
  max-width: 1000px;
  margin: 0 auto;
}

.loading-container {
  padding: 2rem;
}

.settings-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.profile-card,
.plan-card,
.limits-card,
.actions-card {
  margin-bottom: 0;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

@media (max-width: 768px) {
  .settings-header {
    padding: 1rem;
  }
  
  .settings-main {
    padding: 1rem;
  }
  
  .header-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>
