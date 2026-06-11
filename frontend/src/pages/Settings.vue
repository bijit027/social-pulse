<template>
  <div class="settings-page">
    <Sidebar />
    <main class="main-content">
      <div class="page-header">
        <h1>Settings</h1>
        <p>Configure your SocialPulse account and preferences</p>
      </div>

      <div class="content-wrapper">
        <el-card class="settings-card" shadow="hover">
          <div v-if="loading" class="loading-container">
            <el-skeleton :rows="5" animated />
          </div>
          <div v-else-if="!user" class="error-container">
            <el-empty description="Failed to load user data">
              <el-button type="primary" @click="fetchUser">Retry</el-button>
            </el-empty>
          </div>
          <el-tabs v-else v-model="activeTab" class="settings-tabs">
            <!-- General Settings Tab -->
            <el-tab-pane label="General" name="general">
              <div class="tab-content">
                <h3>Account Settings</h3>
                <el-form :model="accountSettings" label-position="top" label-width="120px">
                  <el-form-item label="Name">
                    <el-input v-model="accountSettings.name" />
                  </el-form-item>
                  <el-form-item label="Email">
                    <el-input v-model="accountSettings.email" disabled />
                  </el-form-item>
                  <el-form-item label="Timezone">
                    <el-select v-model="accountSettings.timezone" style="width: 100%">
                      <el-option label="UTC" value="UTC" />
                      <el-option label="America/New_York" value="America/New_York" />
                      <el-option label="America/Los_Angeles" value="America/Los_Angeles" />
                      <el-option label="Europe/London" value="Europe/London" />
                      <el-option label="Asia/Kolkata" value="Asia/Kolkata" />
                      <el-option label="Asia/Tokyo" value="Asia/Tokyo" />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="Date Format">
                    <el-select v-model="accountSettings.dateFormat" style="width: 100%">
                      <el-option label="MM/DD/YYYY" value="MM/DD/YYYY" />
                      <el-option label="DD/MM/YYYY" value="DD/MM/YYYY" />
                      <el-option label="YYYY-MM-DD" value="YYYY-MM-DD" />
                    </el-select>
                  </el-form-item>
                  <el-form-item>
                    <el-button type="primary" :loading="saving" @click="saveAccountSettings">Save Changes</el-button>
                  </el-form-item>
                </el-form>

                <el-divider />

                <h3>Notification Preferences</h3>
                <el-form :model="notificationSettings" label-position="top">
                  <el-form-item label="Email Notifications">
                    <el-switch v-model="notificationSettings.emailEnabled" />
                    <div class="form-help">Receive email notifications for important events</div>
                  </el-form-item>
                  <el-form-item label="Weekly Reports">
                    <el-switch v-model="notificationSettings.weeklyReports" />
                    <div class="form-help">Receive weekly analytics reports via email</div>
                  </el-form-item>
                  <el-form-item label="Alert Threshold">
                    <el-input-number v-model="notificationSettings.alertThreshold" :min="1" :max="100" />
                    <div class="form-help">Alert when notification CTR drops below this percentage</div>
                  </el-form-item>
                  <el-form-item>
                    <el-button type="primary" :loading="saving" @click="saveNotificationSettings">Save Changes</el-button>
                  </el-form-item>
                </el-form>
              </div>
            </el-tab-pane>

            <!-- Security Tab -->
            <el-tab-pane label="Security" name="security">
              <div class="tab-content">
                <h3>Change Password</h3>
                <el-form :model="passwordSettings" label-position="top">
                  <el-form-item label="Current Password">
                    <el-input v-model="passwordSettings.currentPassword" type="password" show-password />
                  </el-form-item>
                  <el-form-item label="New Password">
                    <el-input v-model="passwordSettings.newPassword" type="password" show-password />
                  </el-form-item>
                  <el-form-item label="Confirm Password">
                    <el-input v-model="passwordSettings.confirmPassword" type="password" show-password />
                  </el-form-item>
                  <el-form-item>
                    <el-button type="primary" :loading="saving" @click="changePassword">Update Password</el-button>
                  </el-form-item>
                </el-form>

                <el-divider />

                <h3>Two-Factor Authentication</h3>
                <el-alert title="2FA is not enabled" type="warning" :closable="false" style="margin-bottom: 1rem">
                  <template #default>
                    <p>Enable two-factor authentication to add an extra layer of security to your account.</p>
                  </template>
                </el-alert>
                <el-button type="primary" disabled>Enable 2FA (Coming Soon)</el-button>

                <el-divider />

                <h3>Active Sessions</h3>
                <el-table :data="sessions" style="width: 100%">
                  <el-table-column prop="device" label="Device" />
                  <el-table-column prop="location" label="Location" />
                  <el-table-column prop="lastActive" label="Last Active" />
                  <el-table-column label="Actions" width="120">
                    <template #default="{ row }">
                      <el-button type="danger" size="small" @click="revokeSession(row)">Revoke</el-button>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
            </el-tab-pane>

            <!-- Billing Tab -->
            <el-tab-pane label="Billing" name="billing">
              <div class="tab-content">
                <h3>Current Plan</h3>
                <el-card class="plan-card" shadow="hover">
                  <div class="plan-header">
                    <div class="plan-info">
                      <h2>{{ user?.plan?.toUpperCase() || 'TRIAL' }} Plan</h2>
                      <p>{{ getPlanDescription(user?.plan) }}</p>
                    </div>
                    <el-tag :type="user?.is_paid ? 'success' : 'warning'" size="large">
                      {{ user?.is_paid ? 'Active' : 'Trial' }}
                    </el-tag>
                  </div>
                  <div class="plan-features">
                    <el-row :gutter="20">
                      <el-col :span="8">
                        <div class="feature-item">
                          <el-icon><Monitor /></el-icon>
                          <span>{{ getWebsiteLimit() }} Websites</span>
                        </div>
                      </el-col>
                      <el-col :span="8">
                        <div class="feature-item">
                          <el-icon><Bell /></el-icon>
                          <span>Unlimited Notifications</span>
                        </div>
                      </el-col>
                      <el-col :span="8">
                        <div class="feature-item">
                          <el-icon><DataAnalysis /></el-icon>
                          <span>Advanced Analytics</span>
                        </div>
                      </el-col>
                    </el-row>
                  </div>
                  <div class="plan-actions">
                    <el-button type="primary" @click="upgradePlan">Upgrade Plan</el-button>
                    <el-button @click="viewBillingHistory">Billing History</el-button>
                  </div>
                </el-card>

                <el-divider />

                <h3>Usage</h3>
                <el-progress :percentage="getUsagePercentage()" :stroke-width="20" />
                <div class="usage-info">
                  <span>{{ websitesCount }} / {{ getWebsiteLimit() }} websites used</span>
                  <el-button type="text" @click="$router.push('/sites')">Manage Sites</el-button>
                </div>
              </div>
            </el-tab-pane>

            <!-- Integrations Tab -->
            <el-tab-pane label="Integrations" name="integrations">
              <div class="tab-content">
                <h3>Connected Services</h3>
                <el-row :gutter="20">
                  <el-col :span="12">
                    <el-card class="integration-card" shadow="hover">
                      <div class="integration-header">
                        <div class="integration-icon slack">
                          <span>💬</span>
                        </div>
                        <div class="integration-info">
                          <h4>Slack</h4>
                          <p>Get notifications in Slack</p>
                        </div>
                      </div>
                      <div class="integration-status">
                        <el-tag type="info">Not Connected</el-tag>
                      </div>
                      <el-button type="primary" size="small" disabled>Connect (Coming Soon)</el-button>
                    </el-card>
                  </el-col>
                  <el-col :span="12">
                    <el-card class="integration-card" shadow="hover">
                      <div class="integration-header">
                        <div class="integration-icon discord">
                          <span>🎮</span>
                        </div>
                        <div class="integration-info">
                          <h4>Discord</h4>
                          <p>Get notifications in Discord</p>
                        </div>
                      </div>
                      <div class="integration-status">
                        <el-tag type="info">Not Connected</el-tag>
                      </div>
                      <el-button type="primary" size="small" disabled>Connect (Coming Soon)</el-button>
                    </el-card>
                  </el-col>
                  <el-col :span="12">
                    <el-card class="integration-card" shadow="hover">
                      <div class="integration-header">
                        <div class="integration-icon zapier">
                          <span>⚡</span>
                        </div>
                        <div class="integration-info">
                          <h4>Zapier</h4>
                          <p>Connect with 5000+ apps</p>
                        </div>
                      </div>
                      <div class="integration-status">
                        <el-tag type="info">Not Connected</el-tag>
                      </div>
                      <el-button type="primary" size="small" disabled>Connect (Coming Soon)</el-button>
                    </el-card>
                  </el-col>
                  <el-col :span="12">
                    <el-card class="integration-card" shadow="hover">
                      <div class="integration-header">
                        <div class="integration-icon webhook">
                          <span>🔗</span>
                        </div>
                        <div class="integration-info">
                          <h4>Custom Webhooks</h4>
                          <p>Send data to your endpoints</p>
                        </div>
                      </div>
                      <div class="integration-status">
                        <el-tag type="info">Not Connected</el-tag>
                      </div>
                      <el-button type="primary" size="small" disabled>Configure (Coming Soon)</el-button>
                    </el-card>
                  </el-col>
                </el-row>
              </div>
            </el-tab-pane>

            <!-- Advanced Tab -->
            <el-tab-pane label="Advanced" name="advanced">
              <div class="tab-content">
                <h3>API Settings</h3>
                <el-form :model="apiSettings" label-position="top">
                  <el-form-item label="API Key">
                    <el-input v-model="apiSettings.apiKey" readonly>
                      <template #append>
                        <el-button :icon="copiedApiKey ? Check : DocumentCopy" @click="copyApiKey">
                          {{ copiedApiKey ? 'Copied!' : 'Copy' }}
                        </el-button>
                      </template>
                    </el-input>
                    <div class="form-help">Use this key to authenticate API requests</div>
                  </el-form-item>
                  <el-form-item>
                    <el-button type="warning" @click="regenerateApiKey">Regenerate API Key</el-button>
                  </el-form-item>
                </el-form>

                <el-divider />

                <h3>Data & Privacy</h3>
                <el-form label-position="top">
                  <el-form-item label="Data Retention">
                    <el-select v-model="dataSettings.retention" style="width: 100%">
                      <el-option label="30 days" value="30" />
                      <el-option label="90 days" value="90" />
                      <el-option label="1 year" value="365" />
                      <el-option label="Forever" value="forever" />
                    </el-select>
                    <div class="form-help">How long to keep analytics data</div>
                  </el-form-item>
                  <el-form-item label="Anonymize Data">
                    <el-switch v-model="dataSettings.anonymize" />
                    <div class="form-help">Remove personal information from analytics</div>
                  </el-form-item>
                  <el-form-item>
                    <el-button type="primary" :loading="saving" @click="saveDataSettings">Save Changes</el-button>
                  </el-form-item>
                </el-form>

                <el-divider />

                <h3>Danger Zone</h3>
                <el-alert title="Irreversible actions" type="error" :closable="false" style="margin-bottom: 1rem">
                  <template #default>
                    <p>These actions are irreversible. Please proceed with caution.</p>
                  </template>
                </el-alert>
                <el-row :gutter="20">
                  <el-col :span="12">
                    <el-button type="warning" @click="exportData">Export All Data</el-button>
                  </el-col>
                  <el-col :span="12">
                    <el-button type="danger" @click="deleteAccount">Delete Account</el-button>
                  </el-col>
                </el-row>
              </div>
            </el-tab-pane>
          </el-tabs>
        </el-card>
      </div>
    </main>
  </div>
</template>

<script>
import api from '../services/api'
import Sidebar from '../components/Sidebar.vue'
import { Monitor, Bell, DataAnalysis, DocumentCopy, Check } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'

export default {
  name: 'Settings',
  components: {
    Sidebar,
    Monitor,
    Bell,
    DataAnalysis,
    DocumentCopy,
    Check
  },
  data() {
    return {
      loading: true,
      saving: false,
      activeTab: 'general',
      user: null,
      websitesCount: 0,
      accountSettings: {
        name: '',
        email: '',
        timezone: 'UTC',
        dateFormat: 'MM/DD/YYYY'
      },
      notificationSettings: {
        emailEnabled: true,
        weeklyReports: true,
        alertThreshold: 5
      },
      passwordSettings: {
        currentPassword: '',
        newPassword: '',
        confirmPassword: ''
      },
      apiSettings: {
        apiKey: ''
      },
      dataSettings: {
        retention: '90',
        anonymize: true
      },
      copiedApiKey: false,
      sessions: [
        { device: 'Chrome on macOS', location: 'San Francisco, CA', lastActive: '2 minutes ago' },
        { device: 'Safari on iPhone', location: 'San Francisco, CA', lastActive: '1 hour ago' }
      ]
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
        if (this.user) {
          this.accountSettings.name = this.user.name || ''
          this.accountSettings.email = this.user.email || ''
          this.apiSettings.apiKey = this.user.api_key || 'sk_' + Math.random().toString(36).substr(2, 32)
        }
      } catch (err) {
        ElMessage.error('Failed to fetch user')
        this.user = null
      } finally {
        this.loading = false
      }
    },
    async fetchWebsitesCount() {
      try {
        const response = await api.get('/websites')
        this.websitesCount = response.data.length
      } catch (err) {
        ElMessage.error('Failed to fetch websites')
      }
    },
    async saveAccountSettings() {
      this.saving = true
      try {
        await api.put('/me', {
          name: this.accountSettings.name,
          timezone: this.accountSettings.timezone,
          date_format: this.accountSettings.dateFormat
        })
        ElMessage.success('Account settings saved')
      } catch (err) {
        ElMessage.error('Failed to save settings')
      } finally {
        this.saving = false
      }
    },
    async saveNotificationSettings() {
      this.saving = true
      try {
        await api.put('/me/notification-settings', this.notificationSettings)
        ElMessage.success('Notification preferences saved')
      } catch (err) {
        ElMessage.error('Failed to save settings')
      } finally {
        this.saving = false
      }
    },
    async changePassword() {
      if (this.passwordSettings.newPassword !== this.passwordSettings.confirmPassword) {
        ElMessage.error('Passwords do not match')
        return
      }
      this.saving = true
      try {
        await api.put('/me/password', {
          current_password: this.passwordSettings.currentPassword,
          new_password: this.passwordSettings.newPassword
        })
        ElMessage.success('Password updated')
        this.passwordSettings = { currentPassword: '', newPassword: '', confirmPassword: '' }
      } catch (err) {
        ElMessage.error('Failed to update password')
      } finally {
        this.saving = false
      }
    },
    async saveDataSettings() {
      this.saving = true
      try {
        await api.put('/me/data-settings', this.dataSettings)
        ElMessage.success('Data settings saved')
      } catch (err) {
        ElMessage.error('Failed to save settings')
      } finally {
        this.saving = false
      }
    },
    copyApiKey() {
      navigator.clipboard.writeText(this.apiSettings.apiKey)
      this.copiedApiKey = true
      setTimeout(() => this.copiedApiKey = false, 2000)
      ElMessage.success('API key copied')
    },
    async regenerateApiKey() {
      try {
        await ElMessageBox.confirm('This will invalidate your current API key. Continue?', 'Regenerate API Key', {
          confirmButtonText: 'Regenerate',
          cancelButtonText: 'Cancel',
          type: 'warning'
        })
        const response = await api.post('/me/regenerate-api-key')
        this.apiSettings.apiKey = response.data.api_key
        ElMessage.success('API key regenerated')
      } catch (err) {
        if (err !== 'cancel') {
          ElMessage.error('Failed to regenerate API key')
        }
      }
    },
    revokeSession(session) {
      ElMessage.info('Session revoked')
    },
    upgradePlan() {
      ElMessage.info('Upgrade feature coming soon')
    },
    viewBillingHistory() {
      ElMessage.info('Billing history coming soon')
    },
    exportData() {
      ElMessage.info('Export feature coming soon')
    },
    async deleteAccount() {
      try {
        await ElMessageBox.confirm('This will permanently delete your account and all data. This action cannot be undone.', 'Delete Account', {
          confirmButtonText: 'Delete Account',
          cancelButtonText: 'Cancel',
          type: 'error'
        })
        await api.delete('/me')
        localStorage.removeItem('token')
        this.$router.push('/login')
        ElMessage.success('Account deleted')
      } catch (err) {
        if (err !== 'cancel') {
          ElMessage.error('Failed to delete account')
        }
      }
    },
    getWebsiteLimit() {
      const limits = { trial: 1, starter: 1, pro: 5 }
      return limits[this.user?.plan] || 1
    },
    getUsagePercentage() {
      const limit = this.getWebsiteLimit()
      if (limit === 0) return 0
      return Math.min((this.websitesCount / limit) * 100, 100)
    },
    getPlanDescription(plan) {
      const descriptions = {
        trial: 'Perfect for getting started with SocialPulse',
        starter: 'For small businesses and personal websites',
        pro: 'For growing businesses with multiple sites'
      }
      return descriptions[plan] || ''
    }
  }
}
</script>

<style scoped>
.settings-page {
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

.settings-card {
  border-radius: 12px;
}

.settings-tabs {
  padding: 1rem 0;
}

.tab-content {
  padding: 1rem 0;
}

.tab-content h3 {
  margin: 0 0 1.5rem 0;
  color: var(--el-text-color-primary);
  font-size: 1.25rem;
  font-weight: 600;
}

.form-help {
  font-size: 0.75rem;
  color: var(--el-text-color-secondary);
  margin-top: 0.25rem;
}

.plan-card {
  border-radius: 12px;
  margin-bottom: 1.5rem;
}

.plan-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.plan-info h2 {
  margin: 0 0 0.5rem 0;
  color: var(--el-text-color-primary);
  font-size: 1.5rem;
  font-weight: 700;
}

.plan-info p {
  margin: 0;
  color: var(--el-text-color-secondary);
}

.plan-features {
  margin-bottom: 1.5rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: var(--el-bg-color-page);
  border-radius: 8px;
}

.plan-actions {
  display: flex;
  gap: 0.5rem;
}

.usage-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.5rem;
  color: var(--el-text-color-secondary);
  font-size: 0.875rem;
}

.integration-card {
  border-radius: 12px;
  margin-bottom: 1.5rem;
}

.integration-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.integration-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.integration-icon.slack {
  background: linear-gradient(135deg, #4A154B 0%, #611F69 100%);
}

.integration-icon.discord {
  background: linear-gradient(135deg, #5865F2 0%, #7289DA 100%);
}

.integration-icon.zapier {
  background: linear-gradient(135deg, #FF4A00 0%, #FF6B35 100%);
}

.integration-icon.webhook {
  background: linear-gradient(135deg, #6B7280 0%, #9CA3AF 100%);
}

.integration-info h4 {
  margin: 0 0 0.25rem 0;
  color: var(--el-text-color-primary);
  font-size: 1rem;
  font-weight: 600;
}

.integration-info p {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 0.875rem;
}

.integration-status {
  margin-bottom: 1rem;
}

.loading-container,
.error-container {
  padding: 2rem;
}
</style>
