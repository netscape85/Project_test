<template>
  <v-app>
    <v-main class="bg-gradient">
      <v-container class="fill-height" fluid>
        <v-row align="center" justify="center">
          <v-col cols="12" sm="8" md="5" lg="4">
            <v-card class="elevation-24 rounded-xl">
              <v-card-text class="pa-8">
                <div class="text-center mb-8">
                  <v-avatar size="80" color="primary" class="mb-4">
                    <v-icon size="50" color="white">mdi-rocket-launch</v-icon>
                  </v-avatar>
                  <h1 class="text-h4 font-weight-bold mb-2">TCG Engineering Hub</h1>
                  <p class="text-subtitle-1 text-grey">Sign in to continue</p>
                </div>
                
                <v-form @submit.prevent="handleLogin">
                  <v-text-field
                    v-model="email"
                    label="Email"
                    prepend-inner-icon="mdi-email"
                    type="email"
                    variant="outlined"
                    required
                    :error-messages="errors.email"
                    class="mb-2"
                  ></v-text-field>
                  
                  <v-text-field
                    v-model="password"
                    label="Password"
                    prepend-inner-icon="mdi-lock"
                    type="password"
                    variant="outlined"
                    required
                    :error-messages="errors.password"
                    class="mb-4"
                  ></v-text-field>
                  
                  <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
                    <v-icon left>mdi-alert-circle</v-icon>
                    {{ error }}
                  </v-alert>
                  
                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    block
                    :loading="loading"
                    class="mb-4"
                    elevation="2"
                  >
                    <v-icon left>mdi-login</v-icon>
                    Sign In
                  </v-btn>
                </v-form>
                
                <v-divider class="my-4"></v-divider>
                
                <v-card variant="tonal" color="info" class="pa-3">
                  <div class="text-center">
                    <v-icon size="small" class="mr-1">mdi-information</v-icon>
                    <span class="text-caption font-weight-medium">Demo Credentials</span>
                  </div>
                  <div class="text-center text-caption mt-2">
                    <strong>Email:</strong> admin@example.com<br>
                    <strong>Password:</strong> password
                  </div>
                </v-card>
              </v-card-text>
            </v-card>
            
            <div class="text-center mt-4">
              <p class="text-caption text-grey">© 2024 TCG Engineering Hub. All rights reserved.</p>
            </div>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { login, loading: authLoading, error: authError } = useAuth();

const email = ref('admin@example.com');
const password = ref('password');
const errors = ref({});

const loading = computed(() => authLoading.value);
const error = computed(() => authError.value);

const handleLogin = async () => {
  errors.value = {};
  
  if (!email.value) {
    errors.value.email = 'Email is required';
    return;
  }
  if (!password.value) {
    errors.value.password = 'Password is required';
    return;
  }

  try {
    await login(email.value, password.value);
    router.push('/');
  } catch (err) {
    // Error is handled in useAuth
  }
};
</script>

<style scoped>
.bg-gradient {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>

