<template>
  <div class="register-container">
    <aside class="register-aside">
      <div class="register-brand">
        <div class="logo">
          <svg viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
            <path fill="#fff" d="M14.747 9.125c.527-1.426 1.736-2.573 3.317-2.573c1.643 0 2.792 1.085 3.318 2.573l6.077 16.867c.186.496.248.931.248 1.147c0 1.209-.992 2.046-2.139 2.046c-1.303 0-1.954-.682-2.264-1.611l-.931-2.915h-8.62l-.93 2.884c-.31.961-.961 1.642-2.232 1.642c-1.24 0-2.294-.93-2.294-2.17c0-.496.155-.868.217-1.023l6.233-16.867zm.34 11.256h5.891l-2.883-8.992h-.062l-2.946 8.992z"/>
          </svg>
        </div>
        <div class="name">SocialPulse</div>
      </div>
      <div class="register-aside-body">
        <span class="register-aside-eyebrow">Social Proof Platform</span>
        <h1>The social proof dashboard your team actually wants to use.</h1>
        <p>Boost conversions with real-time social proof notifications. Easy setup, beautiful widgets, powerful analytics.</p>
      </div>
      <div class="register-aside-footer">
        <span>© 2026</span>
        <span>SOCIALPULSE</span>
      </div>
    </aside>
    <main class="register-main">
      <div class="register-main-top">
        <router-link to="/" style="font-size: 12.5px; color: var(--el-text-color-secondary); display: inline-flex; align-items: center; gap: 6px;">
          <el-icon><ArrowLeft /></el-icon>
          Back to home
        </router-link>
        <div class="switch-link">Already have an account? <router-link to="/login">Sign in</router-link></div>
      </div>

      <el-card class="register-card">
        <h2>Create your workspace</h2>
        <p class="sub">Start your free trial · no credit card required</p>

        <el-form @submit.prevent="handleRegister" :model="registerForm" label-position="top">
          <el-form-item label="Full name">
            <el-input 
              v-model="name" 
              type="text" 
              placeholder="Jane Doe" 
              :prefix-icon="User"
              required
            />
          </el-form-item>

          <el-form-item label="Email">
            <el-input 
              v-model="email" 
              type="email" 
              placeholder="you@company.com" 
              :prefix-icon="Message"
              required
            />
          </el-form-item>

          <el-form-item label="Password">
            <el-input 
              v-model="password" 
              type="password" 
              placeholder="••••••••" 
              :prefix-icon="Lock"
              show-password
              required
            />
          </el-form-item>

          <el-form-item label="Confirm Password">
            <el-input 
              v-model="passwordConfirmation" 
              type="password" 
              placeholder="••••••••" 
              :prefix-icon="Lock"
              show-password
              required
            />
          </el-form-item>

          <el-form-item>
            <el-checkbox v-model="agreeToTerms" required>
              I agree to the <a href="#" style="color: var(--el-color-primary); font-weight: 600; margin: 0 4px;">Terms</a> and <a href="#" style="color: var(--el-color-primary); font-weight: 600; margin-left: 4px;">Privacy Policy</a>
            </el-checkbox>
          </el-form-item>

          <el-button 
            type="primary" 
            class="register-submit" 
            :loading="loading" 
            @click="handleRegister"
            style="width: 100%"
          >
            Create account
            <el-icon class="el-icon--right"><ArrowRight /></el-icon>
          </el-button>
        </el-form>

        <el-alert 
          v-if="error" 
          :title="error" 
          type="error" 
          :closable="false"
          style="margin-top: 1rem"
        />
      </el-card>

      <div class="register-main-bottom">
        Already a customer? <router-link to="/login" style="color: var(--el-color-primary); font-weight: 600;">Sign in</router-link>
      </div>
    </main>
  </div>
</template>

<script>
import api from '../services/api'
import { ArrowLeft, ArrowRight, Message, Lock, User } from '@element-plus/icons-vue'

export default {
  name: 'Register',
  components: {
    ArrowLeft,
    ArrowRight,
    Message,
    Lock,
    User
  },
  data() {
    return {
      name: '',
      email: '',
      password: '',
      passwordConfirmation: '',
      loading: false,
      error: '',
      agreeToTerms: false,
      registerForm: {}
    }
  },
  methods: {
    async handleRegister() {
      if (this.password !== this.passwordConfirmation) {
        this.error = 'Passwords do not match'
        return
      }
      
      this.loading = true
      this.error = ''
      try {
        const response = await api.post('/register', {
          name: this.name,
          email: this.email,
          password: this.password,
          password_confirmation: this.passwordConfirmation
        })
        localStorage.setItem('token', response.data.token)
        this.$router.push('/')
      } catch (err) {
        this.error = err.response?.data?.message || 'Registration failed'
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.register-container {
  display: flex;
  min-height: 100vh;
  background: var(--el-bg-color);
}

.register-aside {
  width: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 3rem;
  display: flex;
  flex-direction: column;
  color: white;
}

.register-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 3rem;
}

.logo {
  width: 48px;
  height: 48px;
}

.logo svg {
  width: 100%;
  height: 100%;
}

.name {
  font-size: 1.5rem;
  font-weight: 700;
}

.register-aside-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.register-aside-eyebrow {
  font-size: 0.875rem;
  opacity: 0.8;
  margin-bottom: 1rem;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.register-aside-body h1 {
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0 0 1rem 0;
  line-height: 1.2;
}

.register-aside-body p {
  font-size: 1.125rem;
  opacity: 0.9;
  margin: 0;
  line-height: 1.6;
}

.register-aside-footer {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  opacity: 0.7;
}

.register-main {
  width: 50%;
  padding: 3rem;
  display: flex;
  flex-direction: column;
  background: var(--el-bg-color-page);
}

.register-main-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.switch-link {
  font-size: 0.875rem;
}

.switch-link a {
  color: var(--el-color-primary);
  text-decoration: none;
  font-weight: 500;
}

.switch-link a:hover {
  text-decoration: underline;
}

.register-card {
  max-width: 480px;
  margin: 0 auto;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.register-card h2 {
  margin: 0 0 0.5rem 0;
  color: var(--el-text-color-primary);
  font-size: 1.75rem;
  font-weight: 700;
}

.sub {
  color: var(--el-text-color-secondary);
  margin: 0 0 2rem 0;
  font-size: 0.875rem;
}

.register-submit {
  height: 44px;
  font-size: 1rem;
  font-weight: 500;
}

.register-main-bottom {
  text-align: center;
  margin-top: 2rem;
  font-size: 0.875rem;
  color: var(--el-text-color-secondary);
}

.register-main-bottom a {
  color: var(--el-color-primary);
  text-decoration: none;
}

.register-main-bottom a:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .register-aside {
    display: none;
  }
  
  .register-main {
    width: 100%;
  }
}
</style>
