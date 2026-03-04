<template>
  <v-app>
    <v-app-bar color="primary" dark>
      <v-app-bar-title>TCG Engineering Hub</v-app-bar-title>
      <v-spacer></v-spacer>
      <v-btn icon @click="logout">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>

    <v-navigation-drawer v-model="drawer" app>
      <v-list dense>
        <v-list-item prepend-icon="mdi-folder" title="Projects" to="/projects"></v-list-item>
        <v-list-item v-if="canManageArtifacts" prepend-icon="mdi-file-document" title="Artifacts" to="/artifacts"></v-list-item>
        <v-list-item v-if="canManageModules" prepend-icon="mdi-code-braces" title="Modules" to="/modules"></v-list-item>
      </v-list>
      <template v-slot:append>
        <div class="pa-2">
          <v-chip>{{ user?.name }}</v-chip>
          <v-chip color="secondary" class="ml-2">{{ user?.role }}</v-chip>
        </div>
      </template>
    </v-navigation-drawer>

    <v-main class="bg-grey-lighten-4">
      <v-container>
        <v-row class="mb-4">
          <v-col cols="12">
            <v-card elevation="0" color="transparent">
              <v-card-title class="d-flex justify-space-between align-center pa-0">
                <div>
                  <h1 class="text-h3 font-weight-bold mb-2">
                    <v-icon size="40" color="primary" class="mr-2">mdi-folder-multiple</v-icon>
                    Projects
                  </h1>
                  <p class="text-subtitle-1 text-grey">Manage your engineering projects</p>
                </div>
                <v-btn v-if="canCreateProjects" color="primary" size="large" elevation="2" @click="showCreateDialog = true">
                  <v-icon left>mdi-plus</v-icon>
                  New Project
                </v-btn>
              </v-card-title>
            </v-card>
          </v-col>
        </v-row>

        <v-row v-if="loading">
          <v-col cols="12" class="text-center py-12">
            <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
            <p class="text-h6 mt-4 text-grey">Loading projects...</p>
          </v-col>
        </v-row>

        <v-row v-else-if="projects.length === 0">
          <v-col cols="12">
            <v-card class="text-center pa-12" elevation="2">
              <v-icon size="120" color="grey-lighten-1">mdi-folder-open-outline</v-icon>
              <h2 class="text-h5 mt-6 mb-2">No projects yet</h2>
              <p class="text-body-1 text-grey mb-6">Create your first project to get started!</p>
              <v-btn v-if="canCreateProjects" color="primary" size="large" @click="showCreateDialog = true">
                <v-icon left>mdi-plus</v-icon>
                Create Project
              </v-btn>
            </v-card>
          </v-col>
        </v-row>

        <v-row v-else>
          <v-col v-for="project in projects" :key="project.id" cols="12" md="6" lg="4">
            <v-card hover class="h-100" elevation="3" @click="viewProject(project)">
              <v-card-title class="d-flex justify-space-between align-center">
                <span class="text-h6 text-truncate">{{ project.name }}</span>
                <v-chip :color="getStatusColor(project.status)" size="small" label>
                  <v-icon left size="small">mdi-circle</v-icon>
                  {{ project.status }}
                </v-chip>
              </v-card-title>
              <v-divider></v-divider>
              <v-card-text>
                <div class="d-flex align-center mb-3">
                  <v-icon size="small" class="mr-2" color="primary">mdi-account-tie</v-icon>
                  <span class="text-body-2">{{ project.client_name }}</span>
                </div>
                <div class="d-flex align-center">
                  <v-icon size="small" class="mr-2" color="primary">mdi-calendar</v-icon>
                  <span class="text-caption text-grey">Created {{ formatDate(project.created_at) }}</span>
                </div>
              </v-card-text>
              <v-card-actions v-if="canEditProjects">
                <v-btn icon size="small" @click.stop="editProject(project)">
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn v-if="canDeleteProjects" icon size="small" color="error" @click.stop="confirmDelete(project)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn size="small" variant="text" color="primary">
                  View Details
                  <v-icon right size="small">mdi-arrow-right</v-icon>
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>

        <!-- Create/Edit Dialog -->
        <v-dialog v-model="showCreateDialog" max-width="600">
          <v-card>
            <v-card-title>{{ editingProject ? 'Edit Project' : 'New Project' }}</v-card-title>
            <v-card-text>
              <v-form ref="form" v-model="formValid" @submit.prevent="saveProject">
                <v-text-field
                  v-model="projectForm.name"
                  label="Project Name"
                  required
                  :rules="[v => !!v || 'Name is required']"
                ></v-text-field>
                <v-text-field
                  v-model="projectForm.client_name"
                  label="Client Name"
                  required
                  :rules="[v => !!v || 'Client name is required']"
                ></v-text-field>
                <v-select
                  v-model="projectForm.status"
                  label="Status"
                  :items="statusOptions"
                  :disabled="!canEditProjects"
                ></v-select>
              </v-form>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn @click="closeDialog">Cancel</v-btn>
              <v-btn color="primary" @click="saveProject" :disabled="!formValid">
                {{ editingProject ? 'Update' : 'Create' }}
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="showDeleteDialog" max-width="400">
          <v-card>
            <v-card-title>Confirm Delete</v-card-title>
            <v-card-text>
              Are you sure you want to delete project "{{ projectToDelete?.name }}"?
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn @click="showDeleteDialog = false">Cancel</v-btn>
              <v-btn color="error" @click="deleteProject">Delete</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>
      </v-container>
    </v-main>
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
const projects = ref([]);
const loading = ref(false);
const showCreateDialog = ref(false);
const showDeleteDialog = ref(false);
const editingProject = ref(null);
const projectToDelete = ref(null);
const formValid = ref(false);

const projectForm = ref({
  name: '',
  client_name: '',
  status: 'draft',
});

const statusOptions = [
  { title: 'Draft', value: 'draft' },
  { title: 'Discovery', value: 'discovery' },
  { title: 'Execution', value: 'execution' },
  { title: 'Delivered', value: 'delivered' },
];

// Permissions
const canCreateProjects = computed(() => isAdmin.value || isPM.value);
const canEditProjects = computed(() => isAdmin.value || isPM.value);
const canDeleteProjects = computed(() => isAdmin.value);
const canManageArtifacts = computed(() => isAdmin.value || isPM.value);
const canManageModules = computed(() => isAdmin.value || isPM.value || true);

const fetchProjects = async () => {
  loading.value = true;
  try {
    console.log('🔍 ProjectsView: Fetching projects...');
    const response = await api.get('/projects');
    console.log('✅ ProjectsView: Projects fetched', response.data);
    // Handle paginated response (Laravel returns { data: [...], current_page: x, ... })
    projects.value = response.data.data || response.data;
  } catch (err) {
    console.error('❌ ProjectsView: Failed to fetch projects:', err);
  } finally {
    loading.value = false;
  }
};

const getStatusColor = (status) => {
  const colors = {
    draft: 'grey',
    discovery: 'blue',
    execution: 'orange',
    delivered: 'green',
  };
  return colors[status] || 'grey';
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};

const viewProject = (project) => {
  router.push(`/projects/${project.id}`);
};

const editProject = (project) => {
  editingProject.value = project;
  projectForm.value = {
    name: project.name,
    client_name: project.client_name,
    status: project.status,
  };
  showCreateDialog.value = true;
};

const confirmDelete = (project) => {
  projectToDelete.value = project;
  showDeleteDialog.value = true;
};

const closeDialog = () => {
  showCreateDialog.value = false;
  editingProject.value = null;
  projectForm.value = {
    name: '',
    client_name: '',
    status: 'draft',
  };
};

const saveProject = async () => {
  if (!formValid.value) {
    console.warn('ProjectsView: Form is not valid');
    return;
  }
  
  try {
    const data = {
      name: projectForm.value.name,
      client_name: projectForm.value.client_name,
      status: projectForm.value.status
    };
    
    if (editingProject.value) {
      await api.put(`/projects/${editingProject.value.id}`, data);
    } else {
      await api.post('/projects', data);
    }
    closeDialog();
    fetchProjects();
  } catch (err) {
    console.error('❌ ProjectsView: Failed to save project:', err);
    alert('Error: ' + (err.response?.data?.message || err.message));
  }
};

const deleteProject = async () => {
  try {
    await api.delete(`/projects/${projectToDelete.value.id}`);
    showDeleteDialog.value = false;
    projectToDelete.value = null;
    fetchProjects();
  } catch (err) {
    console.error('Failed to delete project:', err);
  }
};

const logout = async () => {
  await authLogout();
  router.push('/login');
};

onMounted(() => {
  fetchProjects();
});
</script>

