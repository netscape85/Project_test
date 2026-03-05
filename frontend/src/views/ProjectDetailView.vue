<template>
  <v-app>
    <AppToolbar @toggle-drawer="drawer = !drawer" />
    <AppSidebar v-model="drawer" />

    <v-main>
      <v-container v-if="loading">
        <v-row>
          <v-col cols="12" class="text-center">
            <v-progress-circular indeterminate color="primary"></v-progress-circular>
          </v-col>
        </v-row>
      </v-container>

      <v-container v-else-if="project">
        <!-- Project Header -->
        <v-row class="mb-4">
          <v-col cols="12">
            <v-card elevation="4">
              <v-card-title class="d-flex justify-space-between align-center bg-gradient pa-4">
                <div>
                  <h1 class="text-h4 mb-2">{{ project.name }}</h1>
                  <v-chip :color="getStatusColor(project.status)" label class="text-uppercase">
                    <v-icon left size="small">mdi-circle</v-icon>
                    {{ project.status }}
                  </v-chip>
                </div>
                <div class="d-flex gap-2">
                  <v-btn v-if="canExportProject" color="success" variant="elevated" @click="exportProject">
                    <v-icon left>mdi-download</v-icon>
                    Export JSON
                  </v-btn>
                  <v-btn v-if="canEditProjects" color="primary" variant="elevated" @click="editProject">
                    <v-icon left>mdi-pencil</v-icon>
                    Edit Project
                  </v-btn>
                </div>
              </v-card-title>
              <v-card-subtitle class="pa-4">
                <v-icon size="small" class="mr-1">mdi-account-tie</v-icon>
                {{ project.client_name }}
              </v-card-subtitle>
              <v-divider></v-divider>
              <v-card-text class="pa-4">
                <v-row>
                  <v-col cols="12" md="6">
                    <div class="d-flex align-center mb-2">
                      <v-icon class="mr-2" color="primary">mdi-calendar-plus</v-icon>
                      <div>
                        <div class="text-caption text-grey">Created</div>
                        <div class="font-weight-medium">{{ formatDate(project.created_at) }}</div>
                      </div>
                    </div>
                    <div class="d-flex align-center">
                      <v-icon class="mr-2" color="primary">mdi-account</v-icon>
                      <div>
                        <div class="text-caption text-grey">Created By</div>
                        <div class="font-weight-medium">{{ project.creator?.name }}</div>
                      </div>
                    </div>
                  </v-col>
                  <v-col cols="12" md="6">
                    <div v-if="project.updated_at" class="d-flex align-center">
                      <v-icon class="mr-2" color="primary">mdi-calendar-edit</v-icon>
                      <div>
                        <div class="text-caption text-grey">Last Updated</div>
                        <div class="font-weight-medium">{{ formatDate(project.updated_at) }}</div>
                      </div>
                    </div>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Artifacts Section -->
        <v-row>
          <v-col cols="12">
            <v-card class="mb-4">
              <v-card-title class="d-flex justify-space-between align-center bg-primary">
                <span class="text-white">Artifacts</span>
                <v-btn v-if="canManageArtifacts" color="white" variant="outlined" size="small" @click="openNewArtifactDialog">
                  <v-icon left>mdi-plus</v-icon>
                  Add Artifact
                </v-btn>
              </v-card-title>
            </v-card>
          </v-col>
        </v-row>

        <v-row v-if="artifacts.length === 0">
          <v-col cols="12">
            <v-card class="text-center pa-8">
              <v-icon size="64" color="grey">mdi-file-document-outline</v-icon>
              <p class="text-h6 mt-4 text-grey">No artifacts yet</p>
              <p class="text-grey">Add your first artifact to get started!</p>
              <v-btn v-if="canManageArtifacts" color="primary" class="mt-4" @click="openNewArtifactDialog">
                <v-icon left>mdi-plus</v-icon>
                Add Artifact
              </v-btn>
            </v-card>
          </v-col>
        </v-row>

        <v-row v-else>
          <v-col v-for="artifact in artifacts" :key="artifact.id" cols="12" md="6" lg="4">
            <v-card hover class="h-100" elevation="2">
              <v-card-title class="d-flex justify-space-between align-center">
                <span class="text-truncate">{{ getArtifactTypeName(artifact.type) }}</span>
                <v-chip :color="getArtifactStatusColor(artifact.status)" size="small" label>
                  {{ artifact.status.replace('_', ' ') }}
                </v-chip>
              </v-card-title>
              <v-divider></v-divider>
              <v-card-text>
                <div class="d-flex align-center mb-2">
                  <v-icon size="small" class="mr-2">mdi-account</v-icon>
                  <span class="text-caption">{{ artifact.owner?.name || 'Unassigned' }}</span>
                </div>
                <div v-if="artifact.completed_at" class="d-flex align-center">
                  <v-icon size="small" class="mr-2">mdi-calendar-check</v-icon>
                  <span class="text-caption">{{ formatDate(artifact.completed_at) }}</span>
                </div>
                <v-alert v-if="artifact.blocking_reason" type="warning" density="compact" class="mt-2">
                  {{ artifact.blocking_reason }}
                </v-alert>
              </v-card-text>
              <v-card-actions v-if="canEditArtifacts && artifact.status !== 'done'">
                <v-btn icon size="small" @click.stop="editArtifact(artifact)">
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn v-if="canDeleteArtifacts" icon size="small" color="error" @click.stop="confirmDeleteArtifact(artifact)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn size="small" variant="text" @click="viewArtifact(artifact)">
                  View Details
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>

        <!-- Modules Section -->
        <v-row class="mt-4">
          <v-col cols="12">
            <v-card class="mb-4">
              <v-card-title class="d-flex justify-space-between align-center bg-primary">
                <span class="text-white">Modules</span>
                <v-btn v-if="canManageModules" color="white" variant="outlined" size="small" @click="showModuleDialog = true">
                  <v-icon left>mdi-plus</v-icon>
                  Add Module
                </v-btn>
              </v-card-title>
            </v-card>
          </v-col>
        </v-row>

        <v-row v-if="modules.length === 0">
          <v-col cols="12">
            <v-card class="text-center pa-8">
              <v-icon size="64" color="grey">mdi-code-braces</v-icon>
              <p class="text-h6 mt-4 text-grey">No modules yet</p>
              <p class="text-grey">Add your first module to get started!</p>
              <v-btn v-if="canManageModules" color="primary" class="mt-4" @click="showModuleDialog = true">
                <v-icon left>mdi-plus</v-icon>
                Add Module
              </v-btn>
            </v-card>
          </v-col>
        </v-row>

        <v-row v-else>
          <v-col v-for="module in modules" :key="module.id" cols="12" md="6">
            <v-card hover class="h-100" elevation="2">
              <v-card-title class="d-flex justify-space-between align-center">
                <span class="text-truncate">{{ module.name }}</span>
                <v-chip :color="getModuleStatusColor(module.status)" size="small" label>
                  {{ module.status.replace('_', ' ') }}
                </v-chip>
              </v-card-title>
              <v-card-subtitle>
                <v-icon size="small" class="mr-1">mdi-domain</v-icon>
                {{ module.domain }}
              </v-card-subtitle>
              <v-divider></v-divider>
              <v-card-text>
                <p class="text-body-2">{{ module.objective?.substring(0, 150) }}{{ module.objective?.length > 150 ? '...' : '' }}</p>
              </v-card-text>
              <v-card-actions>
                <v-btn v-if="canEditModules" icon size="small" @click.stop="editModule(module)">
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn 
                  v-if="canValidateModules && module.status === 'draft'" 
                  icon 
                  size="small"
                  color="success"
                  @click.stop="validateModule(module)"
                  :disabled="!canModuleBeValidated(module)"
                >
                  <v-icon>mdi-check</v-icon>
                </v-btn>
                <v-btn v-if="canDeleteModules" icon size="small" color="error" @click.stop="confirmDeleteModule(module)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn size="small" variant="text" @click="viewModule(module)">
                  View Details
                </v-btn>
              </v-card-actions>
              <v-card-text v-if="!canModuleBeValidated(module) && module.status === 'draft'" class="pt-0">
                <v-alert type="warning" density="compact">
                  <v-icon size="small" class="mr-1">mdi-alert</v-icon>
                  Missing required fields for validation
                </v-alert>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Audit Timeline Section -->
        <v-row class="mt-6">
          <v-col cols="12">
            <v-card elevation="2">
              <v-card-title class="bg-primary text-white d-flex align-center">
                <v-icon class="mr-2" color="white">mdi-history</v-icon>
                Activity Timeline
              </v-card-title>
              <v-divider></v-divider>
              
              <v-card-text v-if="auditEvents.length === 0" class="text-center pa-8">
                <v-icon size="64" color="grey-lighten-1">mdi-timeline-clock-outline</v-icon>
                <p class="text-h6 mt-4 text-grey">No activity yet</p>
                <p class="text-grey">Project activity will appear here</p>
              </v-card-text>
              
              <v-table v-else density="compact">
                <thead>
                  <tr>
                    <th class="text-left">Date</th>
                    <th class="text-left">Action</th>
                    <th class="text-left">User</th>
                    <th class="text-left">Changes</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="event in auditEvents" :key="event.id">
                    <td class="text-caption">
                      {{ formatDateTime(event.created_at) }}
                    </td>
                    <td>
                      <v-chip :color="getEventColor(event.action)" size="small" label>
                        <v-icon left size="small">{{ getEventIcon(event.action) }}</v-icon>
                        {{ getEventActionName(event.action) }}
                      </v-chip>
                    </td>
                    <td>
                      <div class="d-flex align-center">
                        <v-avatar size="24" color="primary" class="mr-2">
                          <span class="text-caption white--text">{{ event.actor?.name?.charAt(0) }}</span>
                        </v-avatar>
                        <span class="text-body-2">{{ event.actor?.name || 'System' }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="text-caption">
                        <span v-if="getEventChanges(event)" v-html="getEventChanges(event)"></span>
                        <span v-else class="text-grey">-</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </v-card>
          </v-col>
        </v-row>
      </v-container>

      <!-- Edit Project Dialog -->
      <v-dialog v-model="showProjectDialog" max-width="600">
        <v-card>
          <v-card-title>Edit Project</v-card-title>
          <v-card-text>
            <v-form ref="form" v-model="formValid">
              <v-text-field v-model="projectForm.name" label="Project Name" required></v-text-field>
              <v-text-field v-model="projectForm.client_name" label="Client Name" required></v-text-field>
              <v-select v-model="projectForm.status" label="Status" :items="statusOptions" :disabled="!canEditProjects"></v-select>
            </v-form>
            <v-alert v-if="projectError" type="error" class="mt-2">
              {{ projectError }}
            </v-alert>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn @click="showProjectDialog = false">Cancel</v-btn>
            <v-btn color="primary" @click="saveProject" :disabled="!formValid">Save</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Add/Edit Artifact Dialog -->
      <v-dialog v-model="showArtifactDialog" max-width="800" scrollable :key="editingArtifact?.id || 'new'">
        <v-card>
          <v-card-title>{{ editingArtifact ? 'Edit Artifact' : 'Add Artifact' }}</v-card-title>
          <v-card-text>
            <v-form ref="artifactForm" v-model="artifactFormValid">
              <v-select
                v-model="artifactForm.type"
                label="Artifact Type"
                :items="artifactTypes"
                :disabled="!!editingArtifact"
                required
              ></v-select>
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

              <v-divider class="my-4"></v-divider>
              <h3 class="text-h6 mb-4">Content</h3>

              <div v-if="artifactForm.type === 'strategic_alignment' && artifactForm.content_json">
                <v-textarea v-model="artifactForm.content_json.transformation" label="Transformation" rows="2"></v-textarea>
                <v-textarea v-model="artifactForm.content_json.supported_decisions" label="Supported Decisions (one per line)" rows="2" hint="Separate with newlines"></v-textarea>
                <v-textarea v-model="artifactForm.content_json.measurable_success" label="Measurable Success (metric:target, one per line)" rows="2" hint="Format: metric:target"></v-textarea>
                <v-textarea v-model="artifactForm.content_json.out_of_scope" label="Out of Scope (one per line)" rows="2"></v-textarea>
              </div>

              <div v-if="artifactForm.type === 'big_picture' && artifactForm.content_json">
                <v-textarea v-model="artifactForm.content_json.ecosystem_vision" label="Ecosystem Vision" rows="2"></v-textarea>
                <v-textarea v-model="artifactForm.content_json.impacted_domains" label="Impacted Domains (one per line)" rows="2"></v-textarea>
                <v-textarea v-model="artifactForm.content_json.success_definition" label="Success Definition" rows="2"></v-textarea>
              </div>

              <div v-if="artifactForm.type === 'domain_breakdown' && artifactForm.content_json">
                <v-textarea v-model="artifactForm.content_json.domains" label="Domains (name:objective:owner_email, one per line)" rows="3" hint="Format: name:objective:owner_email"></v-textarea>
              </div>

              <div v-if="artifactForm.type === 'module_matrix' && artifactForm.content_json">
                <v-textarea v-model="artifactForm.content_json.modules_overview" label="Modules Overview (name:domain:priority:phase, one per line)" rows="3" hint="Format: name:domain:priority:phase"></v-textarea>
              </div>

              <div v-if="artifactForm.type === 'module_engineering' && artifactForm.content_json">
                <v-textarea v-model="artifactForm.content_json.notes" label="Engineering Notes" rows="3"></v-textarea>
              </div>

              <div v-if="artifactForm.type === 'system_architecture' && artifactForm.content_json">
                <v-textarea v-model="artifactForm.content_json.auth_model" label="Auth Model" rows="2"></v-textarea>
                <v-textarea v-model="artifactForm.content_json.api_style" label="API Style" rows="2"></v-textarea>
                <v-textarea v-model="artifactForm.content_json.data_model_notes" label="Data Model Notes" rows="2"></v-textarea>
                <v-textarea v-model="artifactForm.content_json.scalability_notes" label="Scalability Notes" rows="2"></v-textarea>
              </div>

              <div v-if="artifactForm.type === 'phase_scope' && artifactForm.content_json">
                <v-textarea v-model="artifactForm.content_json.included_modules" label="Included Modules (module IDs, one per line)" rows="2"></v-textarea>
                <v-textarea v-model="artifactForm.content_json.excluded_items" label="Excluded Items (one per line)" rows="2"></v-textarea>
                <v-textarea v-model="artifactForm.content_json.acceptance_criteria" label="Acceptance Criteria (one per line)" rows="2"></v-textarea>
              </div>
            </v-form>
            <v-alert v-if="artifactError" type="error" class="mt-2">
              {{ artifactError }}
            </v-alert>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn @click="closeArtifactDialog">Cancel</v-btn>
            <v-btn color="primary" @click="saveArtifact" :disabled="!artifactFormValid">Save</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Add/Edit Module Dialog -->
      <v-dialog v-model="showModuleDialog" max-width="800" scrollable :key="editingModule?.id || 'new'">
        <v-card>
          <v-card-title>{{ editingModule ? 'Edit Module' : 'Add Module' }}</v-card-title>
          <v-card-text>
            <v-form ref="moduleForm" v-model="moduleFormValid">
              <v-text-field v-model="moduleForm.name" label="Module Name" required></v-text-field>
              <v-text-field v-model="moduleForm.domain" label="Domain" required></v-text-field>
              <v-select v-model="moduleForm.status" label="Status" :items="moduleStatuses" :disabled="!!editingModule"></v-select>
              
              <v-divider class="my-4"></v-divider>
              <h3 class="text-h6 mb-4">Module Details</h3>
              
              <v-textarea v-model="moduleForm.objective" label="Objective *" rows="2" hint="Required for validation"></v-textarea>
              <v-textarea v-model="moduleFormInputsText" label="Inputs (one per line) *" rows="2" hint="At least 1 required for validation"></v-textarea>
              <v-textarea v-model="moduleFormOutputsText" label="Outputs (one per line) *" rows="2" hint="At least 1 required for validation"></v-textarea>
              <v-textarea v-model="moduleForm.responsibility" label="Responsibility * (who/what owns this module operationally)" rows="2" hint="Required for validation"></v-textarea>
              <v-textarea v-model="moduleForm.data_structure" label="Data Structure" rows="2"></v-textarea>
              <v-textarea v-model="moduleForm.logic_rules" label="Logic Rules" rows="2"></v-textarea>
              <v-textarea v-model="moduleForm.failure_scenarios" label="Failure Scenarios" rows="2"></v-textarea>
              <v-textarea v-model="moduleForm.audit_trail_requirements" label="Audit Trail Requirements" rows="2"></v-textarea>
              <v-textarea v-model="moduleFormDependenciesText" label="Dependencies (module IDs, one per line)" rows="2"></v-textarea>
              <v-text-field v-model="moduleForm.version_note" label="Version Note"></v-text-field>
            </v-form>
            <v-alert v-if="moduleError" type="error" class="mt-2">
              {{ moduleError }}
            </v-alert>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn @click="closeModuleDialog">Cancel</v-btn>
            <v-btn color="primary" @click="saveModule" :disabled="!moduleFormValid">Save</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Delete Artifact Confirmation Dialog -->
      <v-dialog v-model="showDeleteArtifactDialog" max-width="400">
        <v-card>
          <v-card-title>Confirm Delete</v-card-title>
          <v-card-text>
            Are you sure you want to delete artifact "{{ artifactToDelete?.type }}"?
            <br><br>
            This action cannot be undone.
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn @click="showDeleteArtifactDialog = false">Cancel</v-btn>
            <v-btn color="error" @click="deleteArtifact">Delete</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Delete Module Confirmation Dialog -->
      <v-dialog v-model="showDeleteModuleDialog" max-width="400">
        <v-card>
          <v-card-title>Confirm Delete</v-card-title>
          <v-card-text>
            Are you sure you want to delete module "{{ moduleToDelete?.name }}"?
            <br><br>
            This action cannot be undone.
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn @click="showDeleteModuleDialog = false">Cancel</v-btn>
            <v-btn color="error" @click="deleteModule">Delete</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-main>
    
    <AppFooter />
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import api from '@/plugins/api';
import AppToolbar from '@/components/AppToolbar.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppFooter from '@/components/AppFooter.vue';

const router = useRouter();
const route = useRoute();
const { isAdmin, isPM, isEngineer } = useAuth();

const drawer = ref(true);
const loading = ref(false);
const project = ref(null);
const artifacts = ref([]);
const modules = ref([]);
const users = ref([]);
const auditEvents = ref([]);

const projectError = ref('');
const artifactError = ref('');
const moduleError = ref('');

const showProjectDialog = ref(false);
const showArtifactDialog = ref(false);
const showModuleDialog = ref(false);
const showDeleteArtifactDialog = ref(false);
const showDeleteModuleDialog = ref(false);

const artifactToDelete = ref(null);
const moduleToDelete = ref(null);

const projectForm = ref({ name: '', client_name: '', status: 'draft' });
const artifactForm = ref({ type: '', status: 'not_started', owner_user_id: null, content_json: {} });
const moduleForm = ref({ 
  name: '', domain: '', status: 'draft', objective: '', inputs: [], outputs: [], responsibility: '',
  data_structure: '', logic_rules: '', failure_scenarios: '', audit_trail_requirements: '',
  dependencies: [], version_note: ''
});

const artifactContentFields = {
  strategic_alignment: ['transformation', 'supported_decisions', 'measurable_success', 'out_of_scope'],
  big_picture: ['ecosystem_vision', 'impacted_domains', 'success_definition'],
  domain_breakdown: ['domains'],
  module_matrix: ['modules_overview'],
  module_engineering: ['notes'],
  system_architecture: ['auth_model', 'api_style', 'data_model_notes', 'scalability_notes'],
  phase_scope: ['included_modules', 'excluded_items', 'acceptance_criteria'],
};

watch(() => artifactForm.value?.type, (newType, oldType) => {
  if (!artifactForm.value) return;
  if (newType && artifactContentFields[newType]) {
    const currentContent = artifactForm.value.content_json || {};
    const isEmpty = Object.keys(currentContent).length === 0;
    if (isEmpty || (oldType && oldType !== newType)) {
      artifactForm.value.content_json = {};
      artifactContentFields[newType].forEach(field => {
        artifactForm.value.content_json[field] = '';
      });
    }
  }
});

const moduleFormInputsText = computed({
  get: () => moduleForm.value.inputs?.join('\n') || '',
  set: (val) => { moduleForm.value.inputs = val.split('\n').filter(i => i.trim()); }
});

const moduleFormOutputsText = computed({
  get: () => moduleForm.value.outputs?.join('\n') || '',
  set: (val) => { moduleForm.value.outputs = val.split('\n').filter(i => i.trim()); }
});

const moduleFormDependenciesText = computed({
  get: () => moduleForm.value.dependencies?.join('\n') || '',
  set: (val) => { moduleForm.value.dependencies = val.split('\n').filter(i => i.trim()); }
});

const editingArtifact = ref(null);
const editingModule = ref(null);

const formValid = ref(false);
const artifactFormValid = ref(false);
const moduleFormValid = ref(false);

const statusOptions = [
  { title: 'Draft', value: 'draft' },
  { title: 'Discovery', value: 'discovery' },
  { title: 'Execution', value: 'execution' },
  { title: 'Delivered', value: 'delivered' },
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

const moduleStatuses = [
  { title: 'Draft', value: 'draft' },
  { title: 'Validated', value: 'validated' },
  { title: 'Ready for Build', value: 'ready_for_build' },
];

const canEditProjects = computed(() => isAdmin.value || isPM.value);
const canExportProject = computed(() => isAdmin.value || isPM.value || isEngineer.value);
const canManageArtifacts = computed(() => isAdmin.value || isPM.value);
const canEditArtifacts = computed(() => isAdmin.value || isPM.value);
const canDeleteArtifacts = computed(() => isAdmin.value || isPM.value);
const canManageModules = computed(() => isAdmin.value || isPM.value);
const canEditModules = computed(() => isAdmin.value || isPM.value || isEngineer.value);
const canDeleteModules = computed(() => isAdmin.value || isPM.value);
const canValidateModules = computed(() => isAdmin.value || isPM.value);

const projectId = computed(() => route.params.id);

const fetchProject = async () => {
  loading.value = true;
  try {
    console.log('🔍 ProjectDetailView: Fetching project...', projectId.value);
    const response = await api.get(`/projects/${projectId.value}`);
    console.log('✅ ProjectDetailView: Project fetched', response.data);
    project.value = response.data;
    
    // Extract audit events from the same response
    if (response.data.auditEvents) {
      auditEvents.value = response.data.auditEvents;
      console.log('✅ Audit events loaded:', auditEvents.value.length);
    }
  } catch (err) {
    console.error('❌ ProjectDetailView: Failed to fetch project:', err);
  } finally {
    loading.value = false;
  }
};

const fetchArtifacts = async () => {
  try {
    console.log('🔍 ProjectDetailView: Fetching artifacts...', projectId.value);
    const response = await api.get(`/projects/${projectId.value}/artifacts`);
    console.log('✅ ProjectDetailView: Artifacts fetched', response.data);
    artifacts.value = response.data.data || response.data;
  } catch (err) {
    console.error('❌ ProjectDetailView: Failed to fetch artifacts:', err);
  }
};

const fetchModules = async () => {
  try {
    console.log('🔍 ProjectDetailView: Fetching modules...', projectId.value);
    const response = await api.get(`/projects/${projectId.value}/modules`);
    console.log('✅ ProjectDetailView: Modules fetched', response.data);
    modules.value = response.data.data || response.data;
  } catch (err) {
    console.error('❌ ProjectDetailView: Failed to fetch modules:', err);
  }
};

const fetchUsers = async () => {
  try {
    console.log('🔍 ProjectDetailView: Fetching users...');
    const response = await api.get('/users/list');
    console.log('✅ ProjectDetailView: Users fetched', response.data);
    users.value = response.data || [];
  } catch (err) {
    console.error('❌ ProjectDetailView: Failed to fetch users:', err);
  }
};

const fetchAuditEvents = async () => {
  try {
    const response = await api.get(`/projects/${projectId.value}`);
    if (response.data.auditEvents) {
      auditEvents.value = response.data.auditEvents;
      console.log('✅ Audit events loaded:', auditEvents.value.length);
    }
  } catch (err) {
    console.error('Failed to fetch audit events:', err);
  }
};

const getStatusColor = (status) => {
  const colors = { draft: 'grey', discovery: 'blue', execution: 'orange', delivered: 'green' };
  return colors[status] || 'grey';
};

const getArtifactStatusColor = (status) => {
  const colors = { not_started: 'grey', in_progress: 'blue', blocked: 'red', done: 'green' };
  return colors[status] || 'grey';
};

const getModuleStatusColor = (status) => {
  const colors = { draft: 'grey', validated: 'blue', ready_for_build: 'green' };
  return colors[status] || 'grey';
};

const getArtifactTypeName = (type) => {
  const names = {
    strategic_alignment: 'Strategic Alignment',
    big_picture: 'Big Picture',
    domain_breakdown: 'Domain Breakdown',
    module_matrix: 'Module Matrix',
    module_engineering: 'Module Engineering',
    system_architecture: 'System Architecture',
    phase_scope: 'Phase Scope',
  };
  return names[type] || type;
};

const formatDate = (date) => new Date(date).toLocaleDateString();

const formatDateTime = (date) => {
  const d = new Date(date);
  return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const getEventChanges = (event) => {
  if (!event.changes_after) return null;
  
  const changes = [];
  const before = event.changes_before || {};
  const after = event.changes_after || {};
  
  // Compare fields
  for (const key in after) {
    if (key === 'updated_at' || key === 'created_at') continue;
    
    if (before[key] !== after[key]) {
      const fieldName = key.replace('_', ' ');
      if (before[key]) {
        changes.push(`<strong>${fieldName}:</strong> "${before[key]}" → "${after[key]}"`);
      } else {
        changes.push(`<strong>${fieldName}:</strong> "${after[key]}"`);
      }
    }
  }
  
  return changes.length > 0 ? changes.join('<br>') : null;
};

const canModuleBeValidated = (module) => {
  const hasObjective = module.objective && module.objective.trim();
  const hasInputs = module.inputs && Array.isArray(module.inputs) && module.inputs.length >= 1;
  const hasOutputs = module.outputs && Array.isArray(module.outputs) && module.outputs.length >= 1;
  const hasResponsibility = module.responsibility && module.responsibility.trim();
  return hasObjective && hasInputs && hasOutputs && hasResponsibility;
};

const validateModule = async (module) => {
  if (!canModuleBeValidated(module)) {
    alert('Cannot validate: Fill objective, inputs (≥1), outputs (≥1), and responsibility');
    return;
  }
  try {
    await api.post(`/modules/${module.id}/validate`);
    fetchModules();
  } catch (err) {
    console.error('Failed to validate module:', err);
    alert(err.response?.data?.message || 'Failed to validate module');
  }
};

const exportProject = async () => {
  try {
    console.log('📥 Exporting project:', projectId.value);
    const response = await api.get(`/projects/${projectId.value}/export`);
    
    // Create blob and download
    const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `project_${project.value.name.replace(/\s+/g, '_')}_export.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    
    console.log('✅ Project exported successfully');
  } catch (err) {
    console.error('❌ Failed to export project:', err);
    alert('Failed to export project: ' + (err.response?.data?.message || err.message));
  }
};

const editProject = () => {
  projectError.value = '';
  projectForm.value = { name: project.value.name, client_name: project.value.client_name, status: project.value.status };
  showProjectDialog.value = true;
};

const saveProject = async () => {
  projectError.value = '';
  try {
    const data = {
      name: projectForm.value.name,
      client_name: projectForm.value.client_name,
      status: projectForm.value.status
    };
    console.log('📤 ProjectDetailView: Saving project:', data);
    const response = await api.put(`/projects/${projectId.value}`, data);
    console.log('✅ ProjectDetailView: Project saved:', response.data);
    showProjectDialog.value = false;
    fetchProject();
  } catch (err) {
    console.error('❌ ProjectDetailView: Failed to save project:', err);
    const errorDetails = err.response?.data?.errors ? JSON.stringify(err.response.data.errors) : err.response?.data?.message || err.message;
    projectError.value = 'Error: ' + errorDetails;
  }
};

const openNewArtifactDialog = () => {
  artifactError.value = '';
  editingArtifact.value = null;
  artifactForm.value = { type: '', status: 'not_started', owner_user_id: null, content_json: {} };
  showArtifactDialog.value = true;
};

const viewArtifact = (artifact) => {
  router.push(`/projects/${projectId.value}/artifacts/${artifact.id}`);
};

const editArtifact = (artifact) => {
  artifactError.value = '';
  editingArtifact.value = artifact;
  
  let contentJson = {};
  if (artifact.content_json) {
    const parsed = typeof artifact.content_json === 'string' ? JSON.parse(artifact.content_json) : artifact.content_json;
    contentJson = { ...parsed };
    // Convert arrays back to strings for textareas
    Object.keys(contentJson).forEach(key => {
      if (Array.isArray(contentJson[key])) {
        contentJson[key] = contentJson[key].join('\n');
      }
    });
  }
  
  artifactForm.value = { 
    type: artifact.type,
    status: artifact.status,
    owner_user_id: artifact.owner_user_id,
    content_json: contentJson
  };
  showArtifactDialog.value = true;
};

const closeArtifactDialog = () => {
  showArtifactDialog.value = false;
  editingArtifact.value = null;
  artifactForm.value = { type: '', status: 'not_started', owner_user_id: null, content_json: {} };
};



const saveArtifact = async () => {
  if (!artifactFormValid.value) {
    console.warn('Artifact form is not valid');
    return;
  }
  artifactError.value = '';
  try {
    const projectIdValue = projectId.value;
    const editingArtifactId = editingArtifact.value?.id;
    
    const data = { 
      type: editingArtifactId ? editingArtifact.value.type : artifactForm.value.type,
      status: artifactForm.value.status,
      owner_user_id: artifactForm.value.owner_user_id,
      project_id: projectIdValue,
      content_json: processArtifactContent(
        editingArtifactId ? editingArtifact.value.type : artifactForm.value.type,
        artifactForm.value.content_json || {}
      )
    };
    console.log('📤 ProjectDetailView: Artifact data to send:', data);
    let response;
    if (editingArtifactId) {
      response = await api.put(`/artifacts/${editingArtifactId}`, data);
    } else {
      response = await api.post(`/projects/${projectIdValue}/artifacts`, data);
    }
    console.log('✅ ProjectDetailView: Artifact saved:', response.data);
    closeArtifactDialog();
    fetchArtifacts();
  } catch (err) {
    console.error('❌ ProjectDetailView: Failed to save artifact:', err);
    const errorDetails = err.response?.data?.errors ? JSON.stringify(err.response.data.errors) : err.response?.data?.message || err.message;
    artifactError.value = 'Error: ' + errorDetails;
  }
};

const processArtifactContent = (type, content) => {
  const processed = { ...content };
  const parseLines = (val) => {
    if (!val) return [];
    return val.split('\n').map(l => l.trim()).filter(l => l);
  };
  switch (type) {
    case 'strategic_alignment':
      if (processed.supported_decisions) processed.supported_decisions = parseLines(processed.supported_decisions);
      if (processed.measurable_success) processed.measurable_success = parseLines(processed.measurable_success);
      if (processed.out_of_scope) processed.out_of_scope = parseLines(processed.out_of_scope);
      break;
    case 'big_picture':
      if (processed.impacted_domains) processed.impacted_domains = parseLines(processed.impacted_domains);
      break;
    case 'domain_breakdown':
      if (processed.domains) processed.domains = parseLines(processed.domains);
      break;
    case 'module_matrix':
      if (processed.modules_overview) processed.modules_overview = parseLines(processed.modules_overview);
      break;
    case 'phase_scope':
      if (processed.included_modules) processed.included_modules = parseLines(processed.included_modules);
      if (processed.excluded_items) processed.excluded_items = parseLines(processed.excluded_items);
      if (processed.acceptance_criteria) processed.acceptance_criteria = parseLines(processed.acceptance_criteria);
      break;
  }
  return processed;
};

const viewModule = (module) => {
  router.push(`/projects/${projectId.value}/modules/${module.id}`);
};

const editModule = (module) => {
  moduleError.value = '';
  editingModule.value = module;
  moduleForm.value = { 
    name: module.name, domain: module.domain, status: module.status, 
    objective: module.objective || '', inputs: module.inputs || [], outputs: module.outputs || [], responsibility: module.responsibility || '',
    data_structure: module.data_structure || '', logic_rules: module.logic_rules || '', 
    failure_scenarios: module.failure_scenarios || '', audit_trail_requirements: module.audit_trail_requirements || '',
    dependencies: module.dependencies || [], version_note: module.version_note || ''
  };
  showModuleDialog.value = true;
};

const closeModuleDialog = () => {
  showModuleDialog.value = false;
  editingModule.value = null;
  moduleForm.value = { 
    name: '', domain: '', status: 'draft', objective: '', inputs: [], outputs: [], responsibility: '',
    data_structure: '', logic_rules: '', failure_scenarios: '', audit_trail_requirements: '',
    dependencies: [], version_note: ''
  };
};

const saveModule = async () => {
  if (!moduleFormValid.value) {
    console.warn('Module form is not valid');
    return;
  }
  moduleError.value = '';
  try {
    const projectIdValue = projectId.value;
    const editingModuleId = editingModule.value?.id;
    
    const data = {
      name: moduleForm.value.name,
      domain: moduleForm.value.domain,
      status: moduleForm.value.status,
      objective: moduleForm.value.objective,
      inputs: moduleForm.value.inputs,
      outputs: moduleForm.value.outputs,
      responsibility: moduleForm.value.responsibility,
      data_structure: moduleForm.value.data_structure,
      logic_rules: moduleForm.value.logic_rules,
      failure_scenarios: moduleForm.value.failure_scenarios,
      audit_trail_requirements: moduleForm.value.audit_trail_requirements,
      dependencies: moduleForm.value.dependencies,
      version_note: moduleForm.value.version_note,
      project_id: projectIdValue
    };
    console.log('📤 ProjectDetailView: Module data to send:', data);
    let response;
    if (editingModuleId) {
      response = await api.put(`/modules/${editingModuleId}`, data);
    } else {
      response = await api.post(`/projects/${projectIdValue}/modules`, data);
    }
    console.log('✅ ProjectDetailView: Module saved:', response.data);
    closeModuleDialog();
    fetchModules();
  } catch (err) {
    console.error('❌ ProjectDetailView: Failed to save module:', err);
    const errorDetails = err.response?.data?.errors ? JSON.stringify(err.response.data.errors) : err.response?.data?.message || err.message;
    moduleError.value = 'Error: ' + errorDetails;
  }
};

const confirmDeleteArtifact = (artifact) => {
  artifactToDelete.value = artifact;
  showDeleteArtifactDialog.value = true;
};

const deleteArtifact = async () => {
  try {
    await api.delete(`/artifacts/${artifactToDelete.value.id}`);
    showDeleteArtifactDialog.value = false;
    artifactToDelete.value = null;
    fetchArtifacts();
  } catch (err) {
    console.error('Failed to delete artifact:', err);
  }
};

const confirmDeleteModule = (module) => {
  moduleToDelete.value = module;
  showDeleteModuleDialog.value = true;
};

const deleteModule = async () => {
  try {
    await api.delete(`/modules/${moduleToDelete.value.id}`);
    showDeleteModuleDialog.value = false;
    moduleToDelete.value = null;
    fetchModules();
  } catch (err) {
    console.error('Failed to delete module:', err);
  }
};

const getEventColor = (action) => {
  const colors = { 
    created: 'success', 
    updated: 'info', 
    status_changed: 'warning', 
    validated: 'purple', 
    completed: 'success', 
    deleted: 'error' 
  };
  return colors[action] || 'grey';
};

const getEventIcon = (action) => {
  const icons = { 
    created: 'mdi-plus-circle', 
    updated: 'mdi-pencil', 
    status_changed: 'mdi-swap-horizontal', 
    validated: 'mdi-check-circle', 
    completed: 'mdi-check-all', 
    deleted: 'mdi-delete' 
  };
  return icons[action] || 'mdi-circle';
};

const getEventActionName = (action) => {
  const names = { 
    created: 'Created', 
    updated: 'Updated', 
    status_changed: 'Status Changed', 
    validated: 'Validated', 
    completed: 'Completed', 
    deleted: 'Deleted' 
  };
  return names[action] || action;
};

onMounted(() => {
  fetchProject();
  fetchArtifacts();
  fetchModules();
  fetchUsers();
  // fetchAuditEvents is called inside fetchProject now
});
</script>

