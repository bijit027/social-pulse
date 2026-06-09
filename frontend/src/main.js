import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'

const app = createApp(App)
app.use(router)
app.use(ElementPlus)

// Customize Element Plus theme colors
document.documentElement.style.setProperty('--el-color-primary', '#FF6B35')
document.documentElement.style.setProperty('--el-color-primary-light-3', '#FF8A5C')
document.documentElement.style.setProperty('--el-color-primary-light-5', '#FFA983')
document.documentElement.style.setProperty('--el-color-primary-light-7', '#FFC8AA')
document.documentElement.style.setProperty('--el-color-primary-light-8', '#FFD7C2')
document.documentElement.style.setProperty('--el-color-primary-light-9', '#FFE6D9')
document.documentElement.style.setProperty('--el-color-primary-dark-2', '#E55A2A')

for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

app.mount('#app')
