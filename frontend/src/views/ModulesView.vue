<template>
  <v-app>
    <v-app-bar color="primary" dark>
      <v-btn icon @click="goBack">
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      <v-app-bar-title>Modules</v-app-bar-title>
      <v-spacer></v-spacer>
      <v-btn icon @click="logout">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>

    <v-navigation-drawer v-model="drawer" app>
      <v-list dense>
        <v-list-item prepend-icon="mdi-folder" title="Projects" to="/projects"></v-list-item>
        <v-list-item prepend-icon="mdi-file-document" title="Artifacts" to="/artifacts"></v-list-item>
        <v-list-item prepend-icon="mdi-code-braces" title="Modules" to="/modules"></v-list-item>
        <v-list-item v-if="canManageUsers" prepend-icon="mdi-account-group" title="Users" to="/users"></v-list-item>
      </v-list>
      <template v-slot:append>
        <div class="pa-2">
          <v-chip>{{ user?.name }}</v-chip>
          <v-chip color="secondary" class="ml-2">{{ user?.role }}</v-chip>
        </div>
      </template>
    </v-navigation-drawer>

    <v-main>
      <v-container>
        <!-- Filters -->
        <v-row class="mb-4">
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.project_id"
              label="Project"
              :items="projects"
              item-title="name"
              item-value="id"
              clearable
              @update:model-value="fetchModules"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.domain"
              label="Domain"
              clearable
              @input="debouncedFetch"
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.status"
              label="Status"
              :items="moduleStatuses"
              clearable
              @update:model-value="fetchModules"
            ></v-select>
          </v-col>
        </v-row>

        <!-- Modules Table -->
        <v-card>
          <v-data-table
            :headers="headers"
            :items="modules"
            :loading="loading"
            :items-per-page="15"
            class="elevation-1"
          >
            <template v-slot:item.name="{ item }">
              <div>
                <div class="font-weight-medium">{{ item.name }}</div>
                <div class="text-caption text-grey">{{ item.domain }}</div>
              </div>
            </template>
            
            <template v-slot:item.project_id="{ item }">
              {{ getProjectName(item.project_id) }}
            </template>
            
            <template v-slot:item.status="{ item }">
              <v-chip :color="getStatusColor(item.status)" size="small">
                {{ item.status }}
              </v-chip>
            </template>
            
            <template v-slot:item.objective="{ item }">
              <div class="text-truncate" style="max-width: 200px;">
                {{ item.objective || '-' }}
              </div>
            </template>
            
            <template v-slot:item.inputs_count="{ item }">
              {{ item.inputs?.length || 0 }}
            </template>
            
            <template v-slot:item.outputs_count="{ item }">
              {{ item.outputs?.length || 0 }}
            </template>
            
            <template v-slot:item.created_at="{ item }">
              {{ formatDate(item.created_at) }}
            </template>
            
            <template v-slot:item.actions="{ item }">
              <v-btn icon size="small" @click="viewModule(item)">
                <v-icon>mdi-eye</v-icon>
              </v-btn>
              <v-btn v-if="canEditModules" icon size="small" @click="editModule(item)">
                <v-icon>mdi-pencil</v-icon>
              </v-btn>
              <v-btn v-if="canDeleteModules" icon size="small" color="error" @click="confirmDelete(item)">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-card>
      </v-container>
    </v-main>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="showDeleteDialog" max-width="400">
      <v-card>
        <v-card-title>Confirm Delete</v-card-title>
        <v-card-text>
          Are you sure you want to delete module "{{ moduleToDelete?.name }}"?
          <br><br>
          This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showDeleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="deleteModule">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- View Module Dialog -->
    <v-dialog v-model="showViewDialog" max-width="800" scrollable>
      <v-card v-if="viewingModule">
        <v-card-title>{{ viewingModule.name }}</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="6"><strong>Domain:</strong> {{ viewingModule.domain }}</v-col>
            <v-col cols="6"><strong>Status:</strong> {{ viewingModule.status }}</v-col>
            <v-col cols="12"><strong>Objective:</strong> {{ viewingModule.objective }}</v-col>
            <v-col cols="6"><strong>Inputs:</strong> {{ viewingModule.inputs?.join(', ') }}</v-col>
            <v-col cols="6"><strong>Outputs:</strong> {{ viewingModule.outputs?.join(', ') }}</v-col>
            <v-col cols="12"><strong>Responsibility:</strong> {{ viewingModule.responsibility }}</v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showViewDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Edit Module Dialog -->
    <v-dialog v-model="showEditDialog" max-width="600" scrollable :key="editingModule?.id || 'new'">
      <v-card>
        <v-card-title>Edit Module</v-card-title>
        <v-card-text>
          <v-text-field v-model="moduleForm.name" label="Name"></v-text-field>
          <v-text-field v-model="moduleForm.domain" label="Domain"></v-text-field>
          <v-select v-model="moduleForm.status" label="Status" :items="moduleStatuses"></v-select>
          <v-textarea v-model="moduleForm.objective" label="Objective" rows="2"></v-textarea>
          <v-textarea v-model="moduleForm.responsibility" label="Responsibility" rows="2"></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showEditDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveModule">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import api from '@/plugins/api';

const router = useRouter();
const { user, isAdmin, isPM, logout: authLogout } = useAuth();

const drawer = ref(true);
const loading = ref(false);
const modules = ref([]);
const projects = ref([]);
const moduleToDelete = ref(null);
const showDeleteDialog = ref(false);
const showViewDialog = ref(false);
const showEditDialog = ref(false);
const viewingModule = ref(null);
const editingModule = ref(null);
const moduleForm = ref({ name: '', domain: '', objective: '', responsibility: '' });

const filters = ref({
  project_id: null,
  domain: '',
  status: null
});

const headers = [
  { title: 'Module', key: 'name', sortable: true },
  { title: 'Project', key: 'project_id', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Objective', key: 'objective', sortable: false },
  { title: 'Inputs', key: 'inputs_count', sortable: true },
  { title: 'Outputs', key: 'outputs_count', sortable: true },
  { title: 'Created', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
];

const moduleStatuses = [
  { title: 'Draft', value: 'draft' },
  { title: 'Validated', value: 'validated' },
  { title: 'Ready for Build', value: 'ready_for_build' },
];

// Permissions
const canManageUsers = computed(() => isAdmin.value || isPM.value);
const canEditModules = computed(() => isAdmin.value || isPM.value);
const canDeleteModules = computed(() => isAdmin.value || isPM.value);

// Debounced search
let searchTimeout = null;
const debouncedFetch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchModules();
  }, 300);
};

const fetchModules = async () => {
  loading.value = true;
  try {
    console.log('🔍 ModulesView: Fetching modules...', filters.value);
    const params = {};
    if (filters.value.project_id) params.project_id = filters.value.project_id;
    if (filters.value.domain) params.domain = filters.value.domain;
    if (filters.value.status) params.status = filters.value.status;
    
    const response = await api.get('/modules', { params });
    console.log('✅ ModulesView: Modules fetched', response.data);
    modules.value = response.data.data || response.data;
  } catch (err) {
    console.error('❌ ModulesView: Failed to fetch modules:', err);
  } finally {
    loading.value = false;
  }
};

const fetchProjects = async () => {
  try {
    const response = await api.get('/projects');
    projects.value = response.data.data || response.data;
  } catch (err) {
    console.error('Failed to fetch projects:', err);
  }
};

const getStatusColor = (status) => {
  const colors = { draft: 'grey', validated: 'blue', ready_for_build: 'green' };
  return colors[status] || 'grey';
};

const getProjectName = (projectId) => {
  const project = projects.value.find(p => p.id === projectId);
  return project ? project.name : `Project #${projectId}`;
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};

const goBack = () => router.push('/projects');
const logout = () => { authLogout(); router.push('/login'); };

const viewModule = (module) => {
  viewingModule.value = module;
  showViewDialog.value = true;
};

const editModule = (module) => {
  editingModule.value = { ...module };
  moduleForm.value = {
    name: module.name,
    domain: module.domain,
    status: module.status,
    objective: module.objective || '',
    responsibility: module.responsibility || ''
  };
  showEditDialog.value = true;
};

const saveModule = async () => {
  try {
    const data = {
      name: moduleForm.value.name,
      domain: moduleForm.value.domain,
      status: moduleForm.value.status,
      objective: moduleForm.value.objective,
      responsibility: moduleForm.value.responsibility,
      project_id: editingModule.value.project_id
    };
    await api.put(`/modules/${editingModule.value.id}`, data);
    showEditDialog.value = false;
    fetchModules();
  } catch (err) {
    console.error('Failed to save module:', err);
  }
};

const confirmDelete = (module) => {
  moduleToDelete.value = module;
  showDeleteDialog.value = true;
};

const deleteModule = async () => {
  try {
    await api.delete(`/modules/${moduleToDelete.value.id}`);
    showDeleteDialog.value = false;
    moduleToDelete.value = null;
    fetchModules();
  } catch (err) {
    console.error('Failed to delete module:', err);
  }
};

onMounted(() => {
  fetchModules();
  fetchProjects();
});
</script>

