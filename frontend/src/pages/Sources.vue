<template>
  <div class="sources-page">
    <Sidebar />
    <main class="main-content">
      <div class="page-header">
        <h1>Sources</h1>
        <p>Connect your platforms to automatically generate social proof notifications</p>
      </div>

      <div class="content-wrapper">
        <el-card class="stats-card" shadow="hover">
          <el-row :gutter="20">
            <el-col :span="6">
              <div class="stat-item">
                <div class="stat-icon connected">
                  <el-icon><Connection /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ stats.connected }}</div>
                  <div class="stat-label">Connected</div>
                </div>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-item">
                <div class="stat-icon total">
                  <el-icon><Monitor /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ stats.totalSites }}</div>
                  <div class="stat-label">Total Sites</div>
                </div>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-item">
                <div class="stat-icon webhooks">
                  <el-icon><Bell /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ stats.webhooks }}</div>
                  <div class="stat-label">Active Webhooks</div>
                </div>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="stat-item">
                <div class="stat-icon notifications">
                  <el-icon><ChatDotRound /></el-icon>
                </div>
                <div class="stat-info">
                  <div class="stat-value">{{ stats.notifications }}</div>
                  <div class="stat-label">Notifications</div>
                </div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <el-card class="platforms-card" shadow="hover">
          <template #header>
            <div class="card-header">
              <span>Available Integrations</span>
            </div>
          </template>

          <el-row :gutter="20">
            <!-- WooCommerce -->
            <el-col :span="12">
              <el-card class="platform-card" shadow="hover">
                <div class="platform-header">
                  <div class="platform-icon woocommerce">
                    <span>🛒</span>
                  </div>
                  <div class="platform-info">
                    <h3>WooCommerce</h3>
                    <p>Automatically capture purchase events from your WooCommerce store</p>
                  </div>
                </div>
                <div class="platform-status">
                  <el-tag type="success">Connected</el-tag>
                </div>
                <div class="platform-actions">
                  <el-button type="primary" @click="viewWooCommerceSetup">View Setup</el-button>
                  <el-button @click="viewWooCommerceDocs">Documentation</el-button>
                </div>
              </el-card>
            </el-col>

            <!-- Stripe -->
            <el-col :span="12">
              <el-card class="platform-card" shadow="hover">
                <div class="platform-header">
                  <div class="platform-icon stripe">
                    <span>💳</span>
                  </div>
                  <div class="platform-info">
                    <h3>Stripe</h3>
                    <p>Capture payment events from your Stripe account</p>
                  </div>
                </div>
                <div class="platform-status">
                  <el-tag type="success">Connected</el-tag>
                </div>
                <div class="platform-actions">
                  <el-button type="primary" @click="viewStripeSetup">View Setup</el-button>
                  <el-button @click="viewStripeDocs">Documentation</el-button>
                </div>
              </el-card>
            </el-col>

            <!-- SureCart -->
            <el-col :span="12">
              <el-card class="platform-card" shadow="hover">
                <div class="platform-header">
                  <div class="platform-icon surecart">
                    <span>🛍️</span>
                  </div>
                  <div class="platform-info">
                    <h3>SureCart</h3>
                    <p>Integrate with SureCart for e-commerce notifications</p>
                  </div>
                </div>
                <div class="platform-status">
                  <el-tag type="info">Coming Soon</el-tag>
                </div>
                <div class="platform-actions">
                  <el-button disabled>Coming Soon</el-button>
                </div>
              </el-card>
            </el-col>

            <!-- Easy Digital Downloads -->
            <el-col :span="12">
              <el-card class="platform-card" shadow="hover">
                <div class="platform-header">
                  <div class="platform-icon edd">
                    <span>📦</span>
                  </div>
                  <div class="platform-info">
                    <h3>Easy Digital Downloads</h3>
                    <p>Capture digital product sales from EDD</p>
                  </div>
                </div>
                <div class="platform-status">
                  <el-tag type="info">Coming Soon</el-tag>
                </div>
                <div class="platform-actions">
                  <el-button disabled>Coming Soon</el-button>
                </div>
              </el-card>
            </el-col>

            <!-- Shopify -->
            <el-col :span="12">
              <el-card class="platform-card" shadow="hover">
                <div class="platform-header">
                  <div class="platform-icon shopify">
                    <span>🏪</span>
                  </div>
                  <div class="platform-info">
                    <h3>Shopify</h3>
                    <p>Connect your Shopify store for purchase notifications</p>
                  </div>
                </div>
                <div class="platform-status">
                  <el-tag type="info">Coming Soon</el-tag>
                </div>
                <div class="platform-actions">
                  <el-button disabled>Coming Soon</el-button>
                </div>
              </el-card>
            </el-col>

            <!-- Custom Webhook -->
            <el-col :span="12">
              <el-card class="platform-card" shadow="hover">
                <div class="platform-header">
                  <div class="platform-icon custom">
                    <span>🔗</span>
                  </div>
                  <div class="platform-info">
                    <h3>Custom Webhook</h3>
                    <p>Connect any platform via custom webhook integration</p>
                  </div>
                </div>
                <div class="platform-status">
                  <el-tag type="info">Coming Soon</el-tag>
                </div>
                <div class="platform-actions">
                  <el-button disabled>Coming Soon</el-button>
                </div>
              </el-card>
            </el-col>
          </el-row>
        </el-card>

        <!-- WooCommerce Setup Dialog -->
        <el-dialog v-model="showWooCommerceDialog" title="WooCommerce Setup" width="600px">
          <div class="setup-content">
            <h4>Step-by-Step Instructions</h4>
            <ol class="setup-steps">
              <li>Log in to your WordPress/WooCommerce admin dashboard</li>
              <li>Navigate to <strong>WooCommerce → Settings → Webhooks</strong></li>
              <li>Click the <strong>"Add webhook"</strong> button</li>
              <li>Fill in the webhook details:
                <ul>
                  <li><strong>Name:</strong> SocialPulse Notifications</li>
                  <li><strong>Status:</strong> Active</li>
                  <li><strong>Topic:</strong> Order Created</li>
                  <li><strong>Delivery URL:</strong> Copy the URL below</li>
                </ul>
              </li>
              <li>Click <strong>"Save webhook"</strong></li>
              <li>Test the webhook by creating a test order</li>
            </ol>
            <div class="webhook-url-section">
              <label>Webhook URL:</label>
              <div class="webhook-url-box">
                <el-input v-model="wooCommerceWebhookUrl" readonly />
                <el-button type="primary" :icon="copiedWooCommerce ? Check : DocumentCopy" @click="copyWooCommerceWebhook">
                  {{ copiedWooCommerce ? 'Copied!' : 'Copy' }}
                </el-button>
              </div>
            </div>
          </div>
          <template #footer>
            <el-button @click="showWooCommerceDialog = false">Close</el-button>
            <el-button type="primary" @click="openWooCommerceDocs">View Full Documentation</el-button>
          </template>
        </el-dialog>

        <!-- Stripe Setup Dialog -->
        <el-dialog v-model="showStripeDialog" title="Stripe Setup" width="600px">
          <div class="setup-content">
            <h4>Step-by-Step Instructions</h4>
            <ol class="setup-steps">
              <li>Log in to your Stripe dashboard</li>
              <li>Navigate to <strong>Developers → Webhooks</strong></li>
              <li>Click the <strong>"Add endpoint"</strong> button</li>
              <li>Fill in the endpoint details:
                <ul>
                  <li><strong>Endpoint URL:</strong> Copy the URL below</li>
                  <li><strong>Events to send:</strong> Select the following events:
                    <ul>
                      <li>checkout.session.completed</li>
                      <li>payment_intent.succeeded</li>
                      <li>charge.succeeded</li>
                    </ul>
                  </li>
                </ul>
              </li>
              <li>Click <strong>"Add endpoint"</strong></li>
              <li>Test the webhook using Stripe's test mode</li>
            </ol>
            <div class="webhook-url-section">
              <label>Webhook URL:</label>
              <div class="webhook-url-box">
                <el-input v-model="stripeWebhookUrl" readonly />
                <el-button type="primary" :icon="copiedStripe ? Check : DocumentCopy" @click="copyStripeWebhook">
                  {{ copiedStripe ? 'Copied!' : 'Copy' }}
                </el-button>
              </div>
            </div>
          </div>
          <template #footer>
            <el-button @click="showStripeDialog = false">Close</el-button>
            <el-button type="primary" @click="openStripeDocs">View Full Documentation</el-button>
          </template>
        </el-dialog>
      </div>
    </main>
  </div>
</template>

<script>
import api from '../services/api'
import Sidebar from '../components/Sidebar.vue'
import { Connection, Monitor, Bell, ChatDotRound, DocumentCopy, Check } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

export default {
  name: 'Sources',
  components: {
    Sidebar,
    Connection,
    Monitor,
    Bell,
    ChatDotRound,
    DocumentCopy,
    Check
  },
  data() {
    return {
      loading: true,
      stats: {
        connected: 0,
        totalSites: 0,
        webhooks: 0,
        notifications: 0
      },
      showWooCommerceDialog: false,
      showStripeDialog: false,
      copiedWooCommerce: false,
      copiedStripe: false,
      wooCommerceWebhookUrl: '',
      stripeWebhookUrl: '',
      selectedSite: null
    }
  },
  computed: {
    wooCommerceWebhookUrl() {
      if (!this.selectedSite) return 'Select a site first'
      return import.meta.env.VITE_API_URL.replace('/api', '') + '/api/webhook/woocommerce/' + this.selectedSite.pixel_id
    },
    stripeWebhookUrl() {
      if (!this.selectedSite) return 'Select a site first'
      return import.meta.env.VITE_API_URL.replace('/api', '') + '/api/webhook/stripe/' + this.selectedSite.pixel_id
    }
  },
  async mounted() {
    await this.fetchStats()
  },
  methods: {
    async fetchStats() {
      this.loading = true
      try {
        const [sitesResponse, notificationsResponse] = await Promise.all([
          api.get('/websites'),
          api.get('/notifications')
        ])
        
        const sites = sitesResponse.data
        const notifications = notificationsResponse.data.data || notificationsResponse.data
        
        this.stats.totalSites = sites.length
        this.stats.connected = sites.filter(s => s.is_active).length
        this.stats.notifications = notifications.length
        this.stats.webhooks = sites.filter(s => s.is_active).length * 2 // WooCommerce + Stripe per site
        
        if (sites.length > 0) {
          this.selectedSite = sites[0]
        }
      } catch (err) {
        ElMessage.error('Failed to fetch stats')
      } finally {
        this.loading = false
      }
    },
    viewWooCommerceSetup() {
      if (!this.selectedSite) {
        ElMessage.warning('Please create a site first')
        this.$router.push('/sites')
        return
      }
      this.showWooCommerceDialog = true
    },
    viewStripeSetup() {
      if (!this.selectedSite) {
        ElMessage.warning('Please create a site first')
        this.$router.push('/sites')
        return
      }
      this.showStripeDialog = true
    },
    viewWooCommerceDocs() {
      window.open('https://woocommerce.com/document/webhooks/', '_blank')
    },
    viewStripeDocs() {
      window.open('https://stripe.com/docs/webhooks', '_blank')
    },
    openWooCommerceDocs() {
      window.open('https://woocommerce.com/document/webhooks/', '_blank')
    },
    openStripeDocs() {
      window.open('https://stripe.com/docs/webhooks', '_blank')
    },
    copyWooCommerceWebhook() {
      navigator.clipboard.writeText(this.wooCommerceWebhookUrl)
      this.copiedWooCommerce = true
      setTimeout(() => this.copiedWooCommerce = false, 2000)
      ElMessage.success('Webhook URL copied!')
    },
    copyStripeWebhook() {
      navigator.clipboard.writeText(this.stripeWebhookUrl)
      this.copiedStripe = true
      setTimeout(() => this.copiedStripe = false, 2000)
      ElMessage.success('Webhook URL copied!')
    }
  }
}
</script>

<style scoped>
.sources-page {
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

.stat-icon.connected {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.total {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.webhooks {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.notifications {
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

.platforms-card {
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

.platform-card {
  margin-bottom: 1.5rem;
  border-radius: 12px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.platform-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.platform-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.platform-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  flex-shrink: 0;
}

.platform-icon.woocommerce {
  background: linear-gradient(135deg, #96588a 0%, #7f54b3 100%);
}

.platform-icon.stripe {
  background: linear-gradient(135deg, #635bff 0%, #00d4ff 100%);
}

.platform-icon.surecart {
  background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
}

.platform-icon.edd {
  background: linear-gradient(135deg, #48dbfb 0%, #0abde3 100%);
}

.platform-icon.shopify {
  background: linear-gradient(135deg, #96c93d 0%, #00b09b 100%);
}

.platform-icon.custom {
  background: linear-gradient(135deg, #a8a8a8 0%, #6b6b6b 100%);
}

.platform-info h3 {
  margin: 0 0 0.5rem 0;
  color: var(--el-text-color-primary);
  font-size: 1.125rem;
  font-weight: 600;
}

.platform-info p {
  margin: 0;
  color: var(--el-text-color-secondary);
  font-size: 0.875rem;
  line-height: 1.5;
}

.platform-status {
  margin-bottom: 1rem;
}

.platform-actions {
  display: flex;
  gap: 0.5rem;
}

.setup-content h4 {
  margin: 0 0 1rem 0;
  color: var(--el-text-color-primary);
  font-size: 1rem;
  font-weight: 600;
}

.setup-steps {
  margin: 0 0 1.5rem 0;
  padding-left: 1.25rem;
  color: var(--el-text-color-regular);
  line-height: 1.8;
}

.setup-steps li {
  margin-bottom: 0.75rem;
}

.setup-steps ul {
  margin: 0.5rem 0 0.75rem 0;
  padding-left: 1.25rem;
}

.setup-steps ul li {
  margin-bottom: 0.25rem;
}

.webhook-url-section {
  margin-top: 1.5rem;
  padding: 1rem;
  background: var(--el-bg-color-page);
  border-radius: 8px;
}

.webhook-url-section label {
  display: block;
  margin-bottom: 0.5rem;
  color: var(--el-text-color-regular);
  font-weight: 500;
}

.webhook-url-box {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}
</style>
