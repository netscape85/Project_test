<template>
  <v-app-bar color="primary" dark>
    <v-app-bar-nav-icon @click="$emit('toggle-drawer')"></v-app-bar-nav-icon>
    <v-app-bar-title>TCG Engineering Hub</v-app-bar-title>
    <v-spacer></v-spacer>
    
    <!-- Usuario y Rol vistosos -->
    <div class="user-info-card mr-4">
      <v-container fluid class="pa-0">
        <v-row no-gutters align="center">
          <v-col cols="auto">
            <v-avatar color="white" size="32" class="mr-2">
              <v-icon color="primary" size="18">mdi-account</v-icon>
            </v-avatar>
          </v-col>
          <v-col>
            <div class="user-info-text">
              <span class="label">Username:</span>
              <span class="username">{{ user?.name || 'User' }}</span>
            </div>
            <div class="user-info-text">
              <span class="label">Rol:</span>
              <v-chip 
                :color="getRoleColor(user?.role)" 
                size="x-small" 
                class="ml-1 role-chip"
              >
                {{ getRoleName(user?.role) }}
              </v-chip>
            </div>
          </v-col>
        </v-row>
      </v-container>
    </div>
    
    <v-btn icon @click="goToProfile">
      <v-icon>mdi-account-edit</v-icon>
    </v-btn>
    
    <v-btn icon @click="handleLogout">
      <v-icon>mdi-logout</v-icon>
    </v-btn>
  </v-app-bar>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { user, logout } = useAuth();

const getRoleName = (role) => {
  const roles = {
    admin: 'Admin',
    pm: 'Project Manager',
    engineer: 'Engineer',
    viewer: 'Viewer'
  };
  return roles[role] || role || 'Unknown';
};

const getRoleColor = (role) => {
  const colors = {
    admin: 'red-darken-2',
    pm: 'purple',
    engineer: 'blue',
    viewer: 'green'
  };
  return colors[role] || 'grey';
};

const goToProfile = () => {
  router.push('/profile');
};

const handleLogout = async () => {
  await logout();
  router.push('/login');
};

defineEmits(['toggle-drawer']);
</script>

<style scoped>
.user-info-card {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  padding: 6px 12px;
  min-width: 180px;
}

.user-info-text {
  font-size: 11px;
  line-height: 1.3;
  color: rgba(255, 255, 255, 0.9);
}

.label {
  font-weight: 600;
  color: rgba(255, 255, 255, 0.7);
}

.username {
  font-weight: 700;
  color: #fff;
  margin-left: 2px;
}

.role-chip {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 9px;
  height: 18px !important;
}
</style>
