<template>
  <div class="sidebar">
    <div class="sidebar-header">
      <div class="header-content">
        <div class="logo-box">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
        </div>
        <div class="logo-text-container">
          <div class="brand-name">SocialPulse</div>
          <div class="brand-desc">Social proof platform</div>
        </div>
      </div>
    </div>

    <div class="sidebar-content">
      <div class="sidebar-group">
        <div class="sidebar-group-label">Workspace</div>
        <nav class="sidebar-nav">
          <router-link to="/dashboard" class="nav-item" active-class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            <span>Dashboard</span>
          </router-link>
          <router-link to="/sites" class="nav-item" active-class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-globe"><circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            <span>Sites</span>
          </router-link>
          <router-link to="/notifications" class="nav-item" active-class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            <span>Notifications</span>
          </router-link>
          <router-link to="/activity" class="nav-item" active-class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-activity"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <span>Activity</span>
          </router-link>
          <router-link to="/sources" class="nav-item" active-class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-database"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
            <span>Sources</span>
          </router-link>
          <router-link to="/analytics" class="nav-item" active-class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bar-chart-3"><path d="M3 3v18h18"/><rect width="4" height="7" x="7" y="10" rx="1"/><rect width="4" height="12" x="15" y="5" rx="1"/></svg>
            <span>Analytics</span>
          </router-link>
          <router-link to="/settings" class="nav-item" active-class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
            <span>Settings</span>
          </router-link>
        </nav>
      </div>
    </div>

    <div class="sidebar-footer">
      <nav class="sidebar-nav">
        <div class="nav-item profile-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Profile</span>
        </div>
        <div class="nav-item logout-item" @click="handleLogout">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
          <span>Logout</span>
        </div>
      </nav>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Sidebar',
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
  width: 16rem; /* 256px */
  min-height: 100vh;
  background-color: var(--color-surface);
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

.sidebar-header {
  border-bottom: 1px solid var(--color-border);
  padding: 0.5rem;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.5rem;
}

.logo-box {
  display: grid;
  place-items: center;
  height: 2.25rem;
  width: 2.25rem;
  flex-shrink: 0;
  border-radius: var(--radius-md);
  background-color: var(--color-primary);
  color: #fff;
  box-shadow: var(--shadow-sm);
}

.logo-text-container {
  min-width: 0;
}

.brand-name {
  font-size: 0.875rem;
  font-weight: 600;
  letter-spacing: -0.025em;
  color: var(--color-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.brand-desc {
  font-size: 0.75rem;
  color: var(--color-muted-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-content {
  flex: 1;
  padding: 0.5rem;
  overflow-y: auto;
}

.sidebar-group {
  display: flex;
  flex-direction: column;
}

.sidebar-group-label {
  padding: 0.375rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: color-mix(in srgb, var(--color-text) 60%, transparent);
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  list-style: none;
  margin: 0;
  padding: 0;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.5rem;
  color: color-mix(in srgb, var(--color-text) 80%, transparent);
  text-decoration: none;
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  font-weight: 500;
  height: 2rem;
  box-sizing: border-box;
  transition: background-color 0.2s, color 0.2s;
  cursor: pointer;
}

.nav-item:hover {
  background-color: var(--color-primary-soft);
  color: oklch(0.45 0.16 45);
}

.nav-item.active {
  background-color: var(--color-primary-soft);
  color: oklch(0.45 0.16 45);
  font-weight: 500;
}

.sidebar-footer {
  padding: 0.5rem;
  border-top: 1px solid var(--color-border);
}

.logout-item:hover {
  background-color: color-mix(in srgb, var(--color-destructive) 10%, transparent);
  color: var(--color-destructive);
}

@media (max-width: 768px) {
  .sidebar {
    width: 64px;
  }
  
  .logo-text-container,
  .sidebar-group-label,
  .nav-item span {
    display: none;
  }
  
  .header-content {
    justify-content: center;
    padding: 0.5rem 0;
  }
  
  .nav-item {
    justify-content: center;
    padding: 0.375rem;
  }
}
</style>
