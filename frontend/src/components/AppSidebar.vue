<template>
  <v-navigation-drawer v-model="drawerModel" app>
    <v-list dense>
      <v-list-item 
        prepend-icon="mdi-folder" 
        title="Projects" 
        to="/projects"
      ></v-list-item>
      
      <v-list-item 
        v-if="canViewUsers"
        prepend-icon="mdi-account-group" 
        title="Users" 
        to="/users"
      ></v-list-item>
      
      <v-list-item 
        v-if="canViewArtifacts"
        prepend-icon="mdi-file-document" 
        title="Artifacts" 
        to="/artifacts"
      ></v-list-item>
      
      <v-list-item 
        v-if="canViewModules"
        prepend-icon="mdi-puzzle" 
        title="Modules" 
        to="/modules"
      ></v-list-item>
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { computed } from 'vue';
import { useAuth } from '@/composables/useAuth';

const props = defineProps({
  modelValue: Boolean
});

const emit = defineEmits(['update:modelValue']);

const { isAdmin, isPM, isEngineer } = useAuth();

const drawerModel = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const canViewUsers = computed(() => isAdmin.value);
const canViewArtifacts = computed(() => isAdmin.value || isPM.value || isEngineer.value);
const canViewModules = computed(() => isAdmin.value || isPM.value || isEngineer.value);
</script>
