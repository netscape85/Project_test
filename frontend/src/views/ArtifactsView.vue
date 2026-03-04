<template>
  <v-app>
    <v-app-bar color="primary" dark>
      <v-btn icon @click="goBack">
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      <v-app-bar-title>Artifacts</v-app-bar-title>
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
              @update:model-value="fetchArtifacts"
            ></v-select>
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.type"
              label="Type"
              :items="artifactTypes"
              clearable
              @update:model-value="fetchArtifacts"
            ></v-select>
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.status"
              label="Status"
              :items="artifactStatuses"
              clearable
              @update:model-value="fetchArtifacts"
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.owner_user_id"
              label="Owner"
              :items="users"
              item-title="name"
              item-value="id"
              clearable
              @update:model-value="fetchArtifacts"
            ></v-select>
          </v-col>
        </v-row>

        <!-- Artifacts Table -->
        <v-card>
          <v-data-table
            :headers="headers"
            :items="artifacts"
            :loading="loading"
            :items-per-page="15"
            class="elevation-1"
          >
            <template v-slot:item.type="{ item }">
              {{ getTypeName(item.type) }}
            </template>
            
            <template v-slot:item.status="{ item }">
              <v-chip :color="getStatusColor(item.status)" size="small">
                {{ item.status }}
              </v-chip>
            </template>
            
            <template v-slot:item.project_id="{ item }">
              {{ getProjectName(item.project_id) }}
            </template>
            
            <template v-slot:item.owner="{ item }">
              {{ item.owner?.name || 'Unassigned' }}
            </template>
            
            <template v-slot:item.created_at="{ item }">
              {{ formatDate(item.created_at) }}
            </template>
            
            <template v-slot:item.actions="{ item }">
              <v-btn icon size="small" @click="viewArtifact(item)">
                <v-icon>mdi-eye</v-icon>
              </v-btn>
              <v-btn v-if="canEditArtifacts && item.status !== 'done'" icon size="small" @click="editArtifact(item)">
                <v-icon>mdi-pencil</v-icon>
              </v-btn>
              <v-btn v-if="canDeleteArtifacts" icon size="small" color="error" @click="confirmDelete(item)">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-card>
      </v-container>
    </v-main>

    <!-- View Artifact Dialog -->
    <v-dialog v-model="showViewDialog" max-width="800" scrollable>
      <v-card v-if="viewingArtifact">
        <v-card-title>{{ getTypeName(viewingArtifact.type) }}</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="6"><strong>Project:</strong> {{ getProjectName(viewingArtifact.project_id) }}</v-col>
            <v-col cols="6"><strong>Status:</strong> {{ viewingArtifact.status }}</v-col>
            <v-col cols="6"><strong>Owner:</strong> {{ viewingArtifact.owner?.name || 'Unassigned' }}</v-col>
            <v-col cols="6"><strong>Created:</strong> {{ formatDate(viewingArtifact.created_at) }}</v-col>
          </v-row>
          <v-divider class="my-4"></v-divider>
          <h3>Content</h3>
          <pre class="mt-2">{{ JSON.stringify(viewingArtifact.content_json, null, 2) }}</pre>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showViewDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Edit Artifact Dialog -->
    <v-dialog v-model="showEditDialog" max-width="800" scrollable :key="editingArtifact?.id || 'new'">
      <v-card>
        <v-card-title>Edit Artifact</v-card-title>
        <v-card-text>
          <v-text-field v-model="editingArtifact.type" label="Type" disabled></v-text-field>
          <v-select
            v-model="artifactForm.status"
            label="Status"
            :items="artifactStatuses"
          ></v-select>
          <v-select
            v-model="artifactForm.owner_user_id"
            label="Owner"
            :items="users"
            item-title="name"
            item-value="id"
            clearable
          ></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showEditDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveArtifact">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="showDeleteDialog" max-width="400">
      <v-card>
        <v-card-title>Confirm Delete</v-card-title>
        <v-card-text>
          Are you sure you want to delete artifact "{{ artifactToDelete?.type }}"?
          <br><br>
          This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="showDeleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="deleteArtifact">Delete</v-btn>
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
const artifacts = ref([]);
const projects = ref([]);
const users = ref([]);
const artifactToDelete = ref(null);
const showDeleteDialog = ref(false);
const showViewDialog = ref(false);
const showEditDialog = ref(false);
const viewingArtifact = ref(null);
const editingArtifact = ref(null);
const artifactForm = ref({ status: '', owner_user_id: null });

const filters = ref({
  project_id: null,
  type: null,
  status: null,
  owner_user_id: null
});

const headers = [
  { title: 'Type', key: 'type', sortable: true },
  { title: 'Project', key: 'project_id', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Owner', key: 'owner', sortable: false },
  { title: 'Created', key: 'created_at', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' }
];

const artifactTypes = [
  { title: 'Strategic Alignment', value: 'strategic_alignment' },
  { title: 'Big Picture', value: 'big_picture' },
  { title: 'Domain Breakdown', value: 'domain_breakdown' },
  { title: 'Module Matrix', value: 'module_matrix' },
  { title: 'Module Engineering', value: 'module_engineering' },
  { title: 'System Architecture', value: 'system_architecture' },
  { title: 'Phase Scope', value: 'phase_scope' },
];

const artifactStatuses = [
  { title: 'Not Started', value: 'not_started' },
  { title: 'In Progress', value: 'in_progress' },
  { title: 'Blocked', value: 'blocked' },
  { title: 'Done', value: 'done' },
];

// Permissions
const canManageUsers = computed(() => isAdmin.value || isPM.value);
const canEditArtifacts = computed(() => isAdmin.value || isPM.value);
const canDeleteArtifacts = computed(() => isAdmin.value || isPM.value);

const fetchArtifacts = async () => {
  loading.value = true;
  try {
    console.log('🔍 ArtifactsView: Fetching artifacts...', filters.value);
    const params = {};
    if (filters.value.project_id) params.project_id = filters.value.project_id;
    if (filters.value.type) params.type = filters.value.type;
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.owner_user_id) params.owner_user_id = filters.value.owner_user_id;
    
    const response = await api.get('/artifacts', { params });
    console.log('✅ ArtifactsView: Artifacts fetched', response.data);
    artifacts.value = response.data.data || response.data;
  } catch (err) {
    console.error('❌ ArtifactsView: Failed to fetch artifacts:', err);
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

const fetchUsers = async () => {
  try {
    const response = await api.get('/users/list');
    users.value = response.data || [];
  } catch (err) {
    console.error('Failed to fetch users:', err);
  }
};

const getTypeName = (type) => {
  const found = artifactTypes.find(t => t.value === type);
  return found ? found.title : type;
};

const getStatusColor = (status) => {
  const colors = { not_started: 'grey', in_progress: 'blue', blocked: 'red', done: 'green' };
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

const viewArtifact = (artifact) => {
  viewingArtifact.value = artifact;
  showViewDialog.value = true;
};

const editArtifact = (artifact) => {
  editingArtifact.value = { ...artifact };
  artifactForm.value = {
    type: artifact.type,
    status: artifact.status,
    owner_user_id: artifact.owner_user_id
  };
  showEditDialog.value = true;
};

const saveArtifact = async () => {
  try {
    let contentJson = editingArtifact.value.content_json;
    
    // Si content_json es un array vacío o null, usar un objeto vacío
    if (!contentJson || (Array.isArray(contentJson) && contentJson.length === 0)) {
      contentJson = {};
    } else if (typeof contentJson === 'object') {
      contentJson = JSON.parse(JSON.stringify(contentJson));
    }
    
    const data = {
      type: editingArtifact.value.type,
      status: artifactForm.value.status,
      owner_user_id: artifactForm.value.owner_user_id,
      project_id: editingArtifact.value.project_id,
      content_json: contentJson
    };
    console.log('📤 Sending artifact data:', data);
    console.log('👤 Current user:', user.value);
    console.log('🔒 Artifact owner_user_id:', editingArtifact.value.owner_user_id);
    await api.put(`/artifacts/${editingArtifact.value.id}`, data);
    showEditDialog.value = false;
    fetchArtifacts();
  } catch (err) {
    console.error('Failed to save artifact:', err);
    console.error('Error response:', err.response?.data);
    alert('No tienes permisos para editar este artifact. Solo Admin/PM o el dueño pueden editarlo.');
  }
};

const confirmDelete = (artifact) => {
  artifactToDelete.value = artifact;
  showDeleteDialog.value = true;
};

const deleteArtifact = async () => {
  try {
    await api.delete(`/artifacts/${artifactToDelete.value.id}`);
    showDeleteDialog.value = false;
    artifactToDelete.value = null;
    fetchArtifacts();
  } catch (err) {
    console.error('Failed to delete artifact:', err);
  }
};

onMounted(() => {
  fetchArtifacts();
  fetchProjects();
  fetchUsers();
});
</script>

