<template>
  <v-app>
    <AppToolbar @toggle-drawer="drawer = !drawer" />
    <AppSidebar v-model="drawer" />
    
    <v-main>
      <v-container>
        <v-row>
          <v-col cols="12" md="8" lg="6" class="mx-auto">
            <v-card>
              <v-card-title class="text-h5">Edit Profile</v-card-title>
              <v-card-text>
                <v-form ref="formRef" v-model="formValid">
                  <v-text-field
                    v-model="form.name"
                    label="Name"
                    prepend-icon="mdi-account"
                    :rules="[v => !!v || 'Name is required']"
                  ></v-text-field>
                  
                  <v-text-field
                    v-model="form.email"
                    label="Email"
                    prepend-icon="mdi-email"
                    type="email"
                    :rules="[v => !!v || 'Email is required', v => /.+@.+\..+/.test(v) || 'Email must be valid']"
                  ></v-text-field>
                  
                  <v-divider class="my-4"></v-divider>
                  
                  <v-text-field
                    v-model="form.current_password"
                    label="Current Password"
                    prepend-icon="mdi-lock"
                    type="password"
                    hint="Leave blank to keep current password"
                    persistent-hint
                  ></v-text-field>
                  
                  <v-text-field
                    v-model="form.password"
                    label="New Password"
                    prepend-icon="mdi-lock-reset"
                    type="password"
                    :rules="form.password ? [v => v.length >= 8 || 'Minimum 8 characters'] : []"
                  ></v-text-field>
                  
                  <v-text-field
                    v-model="form.password_confirmation"
                    label="Confirm New Password"
                    prepend-icon="mdi-lock-check"
                    type="password"
                    :rules="[v => v === form.password || 'Passwords must match']"
                  ></v-text-field>
                  
                  <v-alert v-if="error" type="error" class="mt-4">{{ error }}</v-alert>
                  <v-alert v-if="success" type="success" class="mt-4">{{ success }}</v-alert>
                </v-form>
              </v-card-text>
              <v-card-actions>
                <v-btn @click="$router.back()">Cancel</v-btn>
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="updateProfile" :loading="loading" :disabled="!formValid">
                  Save Changes
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
    
    <AppFooter />
  </v-app>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import api from '@/plugins/api';
import AppToolbar from '@/components/AppToolbar.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppFooter from '@/components/AppFooter.vue';

const $router = useRouter();
const { user, fetchUser } = useAuth();
const drawer = ref(true);
const formRef = ref(null);
const formValid = ref(false);
const loading = ref(false);
const error = ref('');
const success = ref('');

const form = ref({
  name: '',
  email: '',
  current_password: '',
  password: '',
  password_confirmation: ''
});

onMounted(() => {
  if (user.value) {
    form.value.name = user.value.name;
    form.value.email = user.value.email;
  }
});

const updateProfile = async () => {
  error.value = '';
  success.value = '';
  loading.value = true;
  
  try {
    const data = {
      name: form.value.name,
      email: form.value.email
    };
    
    await api.put('/auth/profile', data);
    
    if (form.value.password) {
      await api.put('/auth/password', {
        current_password: form.value.current_password,
        password: form.value.password,
        password_confirmation: form.value.password_confirmation
      });
    }
    
    await fetchUser();
    success.value = 'Profile updated successfully';
    
    setTimeout(() => {
      $router.push('/projects');
    }, 1000);
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update profile';
  } finally {
    loading.value = false;
  }
};
</script>
