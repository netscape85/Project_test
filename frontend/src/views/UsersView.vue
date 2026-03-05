<template>
  <v-app>
    <AppToolbar @toggle-drawer="drawer = !drawer" />
    <AppSidebar v-model="drawer" />

    <v-main>
      <v-container>
        <!-- Header -->
        <v-row class="mb-4">
          <v-col cols="12" class="d-flex justify-space-between align-center">
            <h1 class="text-h4">Users</h1>
            <v-btn v-if="canCreateUsers" color="primary" @click="openCreateDialog">
              <v-icon left>mdi-plus</v-icon>
              Add User
            </v-btn>
          </v-col>
        </v-row>

        <!-- Search and Filters -->
        <v-row class="mb-4">
          <v-col cols="12" md="4">
            <v-text-field
              v-model="search"
              label="Search users"
              prepend-inner-icon="mdi-magnify"
              clearable
              @input="debouncedSearch"
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="roleFilter"
              label="Filter by role"
              :items="roleOptions"
              clearable
              @update:model-value="fetchUsers"
            ></v-select>
          </v-col>
        </v-row>

        <!-- Users Table -->
        <v-card>
          <v-data-table
            :headers="headers"
            :items="users"
            :loading="loading"
            :items-per-page="15"
            class="elevation-1"
          >
            <template v-slot:item.name="{ item }">
              <div class="d-flex align-center">
                <v-avatar color="primary" size="32" class="mr-2">
                  <span class="white--text">{{ getInitials(item.name) }}</span>
                </v-avatar>
                {{ item.name }}
              </div>
            </template>
            
            <template v-slot:item.email="{ item }">
              <a :href="'mailto:' + item.email">{{ item.email }}</a>
            </template>
            
            <template v-slot:item.role="{ item }">
              <v-chip :color="getRoleColor(item.role)" size="small">
                {{ getRoleName(item.role) }}
              </v-chip>
            </template>
            
            <template v-slot:item.created_at="{ item }">
              {{ formatDate(item.created_at) }}
            </template>
            
            <template v-slot:item.actions="{ item }">
              <v-btn 
                v-if="canEditUser(item)"
                icon 
                size="small" 
                @click="openEditDialog(item)"
              >
                <v-icon>mdi-pencil</v-icon>
              </v-btn>
              <v-btn 
                v-if="canDeleteUser(item)"
                icon 
                size="small" 
                color="error"
                @click="confirmDelete(item)"
              >
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-card>
      </v-container>
    </v-main>
    
    <AppFooter />

    <!-- Create/Edit User Dialog -->
    <v-dialog v-model="showUserDialog" max-width="500" :key="editingUser?.id || 'new'">
      <v-card>
        <v-card-title>{{ editingUser ? 'Edit User' : 'Create User' }}</v-card-title>
        <v-card-text>
          <v-form ref="userFormRef" v-model="formValid">
            <v-text-field
              v-model="userForm.name"
              label="Name"
              :rules="[v => !!v || 'Name is required']"
              required
            ></v-text-field>
            
            <v-text-field
              v-model="userForm.email"
              label="Email"
              type="email"
              :rules="[v => !!v || 'Email is required', v => /.+@.+\..+/.test(v) || 'E-mail must be valid']"
              :disabled="!!editingUser"
              required
            ></v-text-field>
            
            <v-select
              v-model="userForm.role"
              label="Role"
              :items="roleOptions.filter(r => r.value)"
              :rules="[v => !!v || 'Role is required']"
              :disabled="!canChangeRole"
              required
            ></v-select>
            
            <v-text-field
              v-model="userForm.password"
              label="Password"
              type="password"
              :hint="editingUser ? 'Leave blank to keep current password' : 'Minimum 8 characters'"
              :persistent-hint="!!editingUser"
              :rules="editingUser ? [] : [v => !!v || 'Password is required', v => (v && v.length >= 8) || 'Minimum 8 characters']"
              :required="!editingUser"
            ></v-text-field>
            
            <v-text-field
              v-model="userForm.password_confirmation"
              label="Confirm Password"
              type="password"
              :rules="[v => v === userForm.password || 'Passwords must match']"
            ></v-text-field>
          </v-form>
          
          <v-alert v-if="errorMessage" type="error" class="mt-2">{{ errorMessage }}</v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" @click="saveUser" :disabled="!formValid">
            {{ editingUser ? 'Update' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="showDeleteDialog" max-width="400">
      <v-card>
        <v-card-title>Confirm Delete</v-card-title>
        <v-card-text>
          Are you sure you want to delete user <strong>{{ userToDelete?.name }}</strong>?
          <br><br>
          This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showDeleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="deleteUser">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import api from '@/plugins/api';
import AppToolbar from '@/components/AppToolbar.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppFooter from '@/components/AppFooter.vue';

const { user, isAdmin, isPM } = useAuth();

const drawer = ref(true);
const loading = ref(false);
const users = ref([]);
const search = ref('');
const roleFilter = ref(null);

// Dialog states
const showUserDialog = ref(false);
const showDeleteDialog = ref(false);
const editingUser = ref(null);
const userToDelete = ref(null);
const errorMessage = ref('');
const formValid = ref(false);

// Form data
const userForm = ref({
  name: '',
  email: '',
  role: 'engineer',
  password: '',
  password_confirmation: ''
});

// Table headers
const headers = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Role', key: 'role', sortable: true },
  { title: 'Created', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
];

// Role options
const roleOptions = [
  { title: 'Admin', value: 'admin' },
  { title: 'Project Manager', value: 'pm' },
  { title: 'Engineer', value: 'engineer' },
  { title: 'Viewer', value: 'viewer' }
];

// Permissions
const canCreateUsers = computed(() => isAdmin.value);
const canChangeRole = computed(() => isAdmin.value);

// Check if current user can edit a specific user
const canEditUser = (targetUser) => {
  return isAdmin.value;
};

// Check if current user can delete a specific user
const canDeleteUser = (targetUser) => {
  if (targetUser.id === user.value?.id) return false;
  return isAdmin.value;
};

// Debounced search
let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchUsers();
  }, 300);
};

// Fetch users
const fetchUsers = async () => {
  loading.value = true;
  try {
    console.log('🔍 UsersView: Fetching users...');
    const params = {};
    if (search.value) params.search = search.value;
    if (roleFilter.value) params.role = roleFilter.value;
    
    const response = await api.get('/users', { params });
    console.log('✅ UsersView: Users fetched', response.data);
    users.value = response.data.data || response.data;
  } catch (err) {
    console.error('❌ UsersView: Failed to fetch users:', err);
    errorMessage.value = 'Failed to load users';
  } finally {
    loading.value = false;
  }
};

// Helper functions
const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
};

const getRoleColor = (role) => {
  const colors = {
    admin: 'red',
    pm: 'purple',
    engineer: 'blue',
    viewer: 'grey'
  };
  return colors[role] || 'grey';
};

const getRoleName = (role) => {
  const names = {
    admin: 'Admin',
    pm: 'Project Manager',
    engineer: 'Engineer',
    viewer: 'Viewer'
  };
  return names[role] || role;
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

// Dialog functions
const openCreateDialog = () => {
  editingUser.value = null;
  userForm.value = {
    name: '',
    email: '',
    role: 'engineer',
    password: '',
    password_confirmation: ''
  };
  errorMessage.value = '';
  showUserDialog.value = true;
};

const openEditDialog = (item) => {
  editingUser.value = { ...item };
  errorMessage.value = '';
  
  userForm.value = {
    name: item.name,
    email: item.email,
    role: item.role,
    password: '',
    password_confirmation: ''
  };
  
  showUserDialog.value = true;
};

const closeDialog = () => {
  showUserDialog.value = false;
  editingUser.value = null;
  errorMessage.value = '';
};

const saveUser = async () => {
  errorMessage.value = '';
  
  try {
    const editingUserId = editingUser.value?.id;
    
    const data = {
      name: userForm.value.name,
      email: userForm.value.email,
      role: userForm.value.role
    };
    
    // Add password only if provided
    if (userForm.value.password) {
      data.password = userForm.value.password;
      data.password_confirmation = userForm.value.password_confirmation;
    }
    
    if (editingUserId) {
      await api.put(`/users/${editingUserId}`, data);
    } else {
      await api.post('/users', data);
    }
    
    closeDialog();
    fetchUsers();
  } catch (err) {
    console.error('Failed to save user:', err);
    errorMessage.value = err.response?.data?.message || err.response?.data?.error || 'Failed to save user';
  }
};

const confirmDelete = (item) => {
  userToDelete.value = item;
  showDeleteDialog.value = true;
};

const deleteUser = async () => {
  try {
    const userId = userToDelete.value?.id;
    await api.delete(`/users/${userId}`);
    showDeleteDialog.value = false;
    userToDelete.value = null;
    fetchUsers();
  } catch (err) {
    console.error('Failed to delete user:', err);
    errorMessage.value = err.response?.data?.message || 'Failed to delete user';
  }
};

onMounted(() => {
  fetchUsers();
});
</script>

