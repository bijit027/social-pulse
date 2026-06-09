<template>
  <div class="sidebar">
    <div class="sidebar-header">
      <div class="logo">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="32" height="32" rx="8" fill="url(#gradient)"/>
          <path d="M16 8L20 12L16 16L12 12L16 8Z" fill="white"/>
          <path d="M16 16L20 20L16 24L12 20L16 16Z" fill="white"/>
          <defs>
            <linearGradient id="gradient" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
              <stop stop-color="#FF6B35"/>
              <stop offset="1" stop-color="#F7931E"/>
            </linearGradient>
          </defs>
        </svg>
        <span class="logo-text">SocialPulse</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <router-link to="/dashboard" class="nav-item" active-class="active">
        <el-icon><Odometer /></el-icon>
        <span>Dashboard</span>
      </router-link>
      <router-link to="/sites" class="nav-item" active-class="active">
        <el-icon><Monitor /></el-icon>
        <span>Sites</span>
      </router-link>
      <router-link to="/notifications" class="nav-item" active-class="active">
        <el-icon><Bell /></el-icon>
        <span>Notifications</span>
      </router-link>
      <router-link to="/sources" class="nav-item" active-class="active">
        <el-icon><Connection /></el-icon>
        <span>Sources</span>
      </router-link>
      <router-link to="/analytics" class="nav-item" active-class="active">
        <el-icon><DataAnalysis /></el-icon>
        <span>Analytics</span>
      </router-link>
      <router-link to="/settings" class="nav-item" active-class="active">
        <el-icon><Setting /></el-icon>
        <span>Settings</span>
      </router-link>
    </nav>

    <div class="sidebar-footer">
      <div class="nav-item profile-item">
        <el-icon><User /></el-icon>
        <span>Profile</span>
      </div>
      <div class="nav-item logout-item" @click="handleLogout">
        <el-icon><SwitchButton /></el-icon>
        <span>Logout</span>
      </div>
    </div>
  </div>
</template>

<script>
import { Odometer, Monitor, Bell, Connection, DataAnalysis, Setting, User, SwitchButton } from '@element-plus/icons-vue'

export default {
  name: 'Sidebar',
  components: {
    Odometer,
    Monitor,
    Bell,
    Connection,
    DataAnalysis,
    Setting,
    User,
    SwitchButton
  },
  methods: {
    handleLogout() {
      localStorage.removeItem('token')
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
.sidebar {
  width: 240px;
  min-height: 100vh;
  background: white;
  border-right: 1px solid var(--el-border-color-light);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

.sidebar-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--el-border-color-light);
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.logo-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--el-text-color-primary);
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  color: var(--el-text-color-regular);
  text-decoration: none;
  transition: all 0.2s;
  cursor: pointer;
}

.nav-item:hover {
  background: var(--el-bg-color-page);
  color: var(--el-color-primary);
}

.nav-item.active {
  background: var(--el-color-primary-light-9);
  color: var(--el-color-primary);
  border-right: 3px solid var(--el-color-primary);
}

.sidebar-footer {
  padding: 1rem;
  border-top: 1px solid var(--el-border-color-light);
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.profile-item,
.logout-item {
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
}

.logout-item:hover {
  background: #fef2f2;
  color: #f56c6c;
}

@media (max-width: 768px) {
  .sidebar {
    width: 64px;
  }
  
  .logo-text,
  .nav-item span,
  .sidebar-footer span {
    display: none;
  }
  
  .nav-item {
    justify-content: center;
    padding: 0.75rem;
  }
  
  .profile-item,
  .logout-item {
    justify-content: center;
    padding: 0.75rem;
  }
}
</style>
