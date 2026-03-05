<template>
  <v-app>
    <v-main class="bg-gradient">
      <v-container class="fill-height" fluid>
        <v-row align="center" justify="center">
          <v-col cols="12" sm="8" md="5" lg="4">
            <v-card class="elevation-24 rounded-xl">
              <v-card-text class="pa-8">
                <div class="text-center mb-6">
                  <v-avatar size="80" color="primary" class="mb-4">
                    <v-icon size="50" color="white">mdi-rocket-launch</v-icon>
                  </v-avatar>
                  <h1 class="text-h4 font-weight-bold mb-2">Create Account</h1>
                  <p class="text-subtitle-1 text-grey">Join TCG Engineering Hub</p>
                </div>
                
                <v-form @submit.prevent="handleRegister" ref="formRef" v-model="formValid">
                  <v-text-field
                    v-model="form.name"
                    label="Full Name"
                    prepend-inner-icon="mdi-account"
                    variant="outlined"
                    required
                    :rules="[v => !!v || 'Name is required']"
                    class="mb-2"
                  ></v-text-field>
                  
                  <v-text-field
                    v-model="form.email"
                    label="Email"
                    prepend-inner-icon="mdi-email"
                    type="email"
                    variant="outlined"
                    required
                    :rules="[v => !!v || 'Email is required', v => /.+@.+\..+/.test(v) || 'Email must be valid']"
                    class="mb-2"
                  ></v-text-field>
                  
                  <v-text-field
                    v-model="form.password"
                    label="Password"
                    prepend-inner-icon="mdi-lock"
                    type="password"
                    variant="outlined"
                    required
                    :rules="[v => !!v || 'Password is required', v => v.length >= 8 || 'Minimum 8 characters']"
                    class="mb-2"
                  ></v-text-field>
                  
                  <v-text-field
                    v-model="form.password_confirmation"
                    label="Confirm Password"
                    prepend-inner-icon="mdi-lock-check"
                    type="password"
                    variant="outlined"
                    required
                    :rules="[v => !!v || 'Confirmation is required', v => v === form.password || 'Passwords must match']"
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
                    :disabled="!formValid"
                    class="mb-4"
                    elevation="2"
                  >
                    <v-icon left>mdi-account-plus</v-icon>
                    Register
                  </v-btn>
                  
                  <div class="text-center">
                    <span class="text-body-2">Already have an account? </span>
                    <v-btn variant="text" color="primary" to="/login" size="small">
                      Sign in here
                    </v-btn>
                  </div>
                </v-form>
              </v-card-text>
            </v-card>
            
            <div class="text-center mt-4">
              <p class="text-caption text-grey">© 2026 TCG Engineering Hub. All rights reserved.</p>
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
const { register, loading: authLoading, error: authError } = useAuth();

const formRef = ref(null);
const formValid = ref(false);

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const loading = computed(() => authLoading.value);
const error = computed(() => authError.value);

const handleRegister = async () => {
  if (!formValid.value) return;
  
  try {
    await register(form.value);
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
