import { ref, computed } from 'vue';
import api from '@/plugins/api';

const user = ref(null);
const token = ref(localStorage.getItem('auth_token'));
const loading = ref(false);
const error = ref(null);

export function useAuth() {
  const isAuthenticated = computed(() => !!token.value);
  
  const userRole = computed(() => user.value?.role);
  const isAdmin = computed(() => userRole.value === 'admin');
  const isPM = computed(() => userRole.value === 'pm');
  const isEngineer = computed(() => userRole.value === 'engineer');
  const isViewer = computed(() => userRole.value === 'viewer');

  const login = async (email, password) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await api.post('/auth/login', { email, password });
      token.value = response.data.token;
      user.value = response.data.user;
      
      localStorage.setItem('auth_token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      
      // Also update via fetchUser to ensure consistency
      try {
        await fetchUser();
      } catch (e) {
        // Ignore - we already have the user from login response
      }
      
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Login failed';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const register = async (userData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await api.post('/auth/register', userData);
      token.value = response.data.token;
      user.value = response.data.user;
      
      localStorage.setItem('auth_token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Registration failed';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const logout = async () => {
    loading.value = true;
    
    try {
      await api.post('/auth/logout');
    } catch (err) {
      console.error('Logout error:', err);
    } finally {
      token.value = null;
      user.value = null;
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      loading.value = false;
    }
  };

  const fetchUser = async () => {
    if (!token.value) return null;
    
    loading.value = true;
    
    try {
      const response = await api.get('/auth/me');
      user.value = response.data.user;
      localStorage.setItem('user', JSON.stringify(response.data.user));
      return response.data.user;
    } catch (err) {
      token.value = null;
      user.value = null;
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const initAuth = async () => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      user.value = JSON.parse(storedUser);
    }
    
    if (token.value) {
      try {
        await fetchUser();
      } catch (err) {
        // Token invalid, cleared in fetchUser
      }
    }
  };

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    userRole,
    isAdmin,
    isPM,
    isEngineer,
    isViewer,
    login,
    register,
    logout,
    fetchUser,
    initAuth,
  };
}

