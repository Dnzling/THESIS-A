import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import router from '../router'

// Types
interface User {
    id: number;
    name: string;
    email: string;
    role?: string;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
    [key: string]: any; // Allow for additional user properties
}

interface LoginCredentials {
    email: string;
    password: string;
    remember?: boolean;
}

interface RegisterCredentials {
    name: string;
    email: string;
    password: string;
    password_confirmation?: string;
}

export const useAuthStore = defineStore('auth', () => {
    // State
    const user = ref<User | null>(null);
    const token = ref<string | null>(null);
    const loading = ref<boolean>(false);
    const error = ref<string | null>(null);
    const authenticated = ref<boolean>(false);
    const isAuthResolved = ref<boolean>(false);

    // Getters / Computed
    const isAuthenticated = computed(() => authenticated.value);
    const currentUser = computed(() => user.value);
    
    // Role-based getters
    const isAdmin = computed(() => {
        return user.value?.role === 'admin' || user.value?.role === 'administrator';
    });

    const isEmailVerified = computed(() => {
        return !!user.value?.email_verified_at;
    });

    const userName = computed(() => {
        return user.value?.name || '';
    });

    const userEmail = computed(() => {
        return user.value?.email || '';
    });

    const userInitials = computed(() => {
        if (!user.value?.name) return '';
        
        const names = user.value.name.split(' ');
        if (names.length >= 2) {
            return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase();
        }
        return user.value.name.substring(0, 2).toUpperCase();
    });

    // Actions / Methods
    const setAuthenticated = (value: boolean) => {
        authenticated.value = value;
    };

    const setUser = (userData: User | null) => {
        user.value = userData;
    };

    const setToken = (newToken: string) => {
        token.value = newToken;
        localStorage.setItem('auth_token', newToken);
        axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
    };

    const clearToken = () => {
        token.value = null;
        localStorage.removeItem('auth_token');
        delete axios.defaults.headers.common['Authorization'];
    };

    const clearError = () => {
        error.value = null;
    };

    const login = async (credentials: LoginCredentials) => {
        loading.value = true;
        error.value = null;
        
        try {
            // Get CSRF cookie first (required for Laravel Sanctum)
            await axios.get('/sanctum/csrf-cookie');
            
            // Attempt login
            const response = await axios.post('/auth/login', credentials);
            
            // Handle token if returned from API
            if (response.data.token) {
                setToken(response.data.token);
            } else if (response.data.access_token) {
                setToken(response.data.access_token);
            }
            
            // Get user data
            await attempt();
            
            // Redirect to dashboard or intended page
            const redirectPath = router.currentRoute.value.query.redirect?.toString() || '/dashboard';
            router.push(redirectPath);
            
            return { success: true, data: response.data };
        } catch (err: any) {
            console.error('Login Failed', err);
            
            // Handle different error formats
            if (err.response?.data?.errors) {
                // Validation errors
                error.value = Object.values(err.response.data.errors).flat().join(', ');
            } else if (err.response?.data?.message) {
                error.value = err.response.data.message;
            } else {
                error.value = 'Login failed. Please check your credentials and try again.';
            }
            
            return { 
                success: false, 
                error: error.value,
                status: err.response?.status 
            };
        } finally {
            loading.value = false;
        }
    };

    const register = async (credentials: RegisterCredentials) => {
        loading.value = true;
        error.value = null;
        
        try {
            // Get CSRF cookie first
            await axios.get('/sanctum/csrf-cookie');
            
            // Attempt registration
            const response = await axios.post('/auth/register', credentials);
            
            // Handle token if returned from API
            if (response.data.token) {
                setToken(response.data.token);
            } else if (response.data.access_token) {
                setToken(response.data.access_token);
            }
            
            // Get user data
            await attempt();
            
            // Redirect to dashboard or email verification page
            router.push('/dashboard');
            
            return { success: true, data: response.data };
        } catch (err: any) {
            console.error('Registration Failed', err);
            
            // Handle different error formats
            if (err.response?.data?.errors) {
                // Validation errors
                error.value = Object.values(err.response.data.errors).flat().join(', ');
            } else if (err.response?.data?.message) {
                error.value = err.response.data.message;
            } else {
                error.value = 'Registration failed. Please try again.';
            }
            
            return { 
                success: false, 
                error: error.value,
                status: err.response?.status 
            };
        } finally {
            loading.value = false;
        }
    };

    const logout = async () => {
        loading.value = true;
        
        try {
            // Call logout endpoint if token exists
            if (token.value) {
                await axios.post('/auth/logout');
            }
            
            // Clear all auth state
            user.value = null;
            authenticated.value = false;
            clearToken();
            
            // Redirect to login page
            router.push('/login');
            
            return { success: true };
        } catch (err: any) {
            console.error('Logout failed:', err);
            
            // Still clear local state even if API fails
            user.value = null;
            authenticated.value = false;
            clearToken();
            
            // Still redirect to login
            router.push('/login');
            
            return { 
                success: false, 
                error: err.response?.data?.message || 'Logout failed' 
            };
        } finally {
            loading.value = false;
        }
    };

    const attempt = async () => {
        try {
            loading.value = true;
            
            // If we have a token, ensure it's in the headers
            if (token.value) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
            }
            
            const response = await axios.get('/api/auth/user');
            
            setUser(response.data);
            setAuthenticated(true);
            
            return response.data;
        } catch (error: any) {
            console.log("Auth attempt error: ", error);
            
            setUser(null);
            setAuthenticated(false);
            clearToken(); // Clear token on failure
            
            // Don't throw error for 401 as it's expected for unauthenticated users
            if (error.response?.status !== 401) {
                throw error;
            }
            
            return null;
        } finally {
            loading.value = false;
            isAuthResolved.value = true;
        }
    };

    const initialize = async () => {
        const savedToken = localStorage.getItem('auth_token');
        
        if (savedToken) {
            setToken(savedToken);
            try {
                await attempt();
            } catch (error) {
                console.error('Failed to restore session:', error);
                clearToken();
                setUser(null);
                setAuthenticated(false);
            }
        } else {
            isAuthResolved.value = true;
        }
    };

    const refreshUser = async () => {
        if (!authenticated.value) return null;
        
        try {
            const response = await axios.get('/api/auth/user');
            setUser(response.data);
            return response.data;
        } catch (error) {
            console.error('Failed to refresh user data:', error);
            return null;
        }
    };

    const updateUser = async (userData: Partial<User>) => {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await axios.put('/api/auth/user', userData);
            setUser(response.data);
            return { success: true, data: response.data };
        } catch (err: any) {
            console.error('Failed to update user:', err);
            
            if (err.response?.data?.errors) {
                error.value = Object.values(err.response.data.errors).flat().join(', ');
            } else if (err.response?.data?.message) {
                error.value = err.response.data.message;
            } else {
                error.value = 'Failed to update user information.';
            }
            
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    };

    const verifyEmail = async (id: string, hash: string) => {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await axios.get(`/email/verify/${id}/${hash}`);
            
            // Refresh user data after verification
            await refreshUser();
            
            return { success: true, data: response.data };
        } catch (err: any) {
            console.error('Email verification failed:', err);
            error.value = err.response?.data?.message || 'Email verification failed.';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    };

    const resendVerificationEmail = async () => {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await axios.post('/email/verification-notification');
            return { success: true, data: response.data };
        } catch (err: any) {
            console.error('Failed to resend verification email:', err);
            error.value = err.response?.data?.message || 'Failed to resend verification email.';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    };

    const forgotPassword = async (email: string) => {
        loading.value = true;
        error.value = null;
        
        try {
            await axios.get('/sanctum/csrf-cookie');
            const response = await axios.post('/forgot-password', { email });
            return { success: true, data: response.data };
        } catch (err: any) {
            console.error('Forgot password request failed:', err);
            error.value = err.response?.data?.message || 'Failed to send password reset email.';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    };

    const resetPassword = async (data: {
        email: string;
        password: string;
        password_confirmation: string;
        token: string;
    }) => {
        loading.value = true;
        error.value = null;
        
        try {
            await axios.get('/sanctum/csrf-cookie');
            const response = await axios.post('/reset-password', data);
            
            // Auto login after password reset if API supports it
            if (response.data.token) {
                setToken(response.data.token);
                await attempt();
                router.push('/dashboard');
            } else {
                router.push('/login');
            }
            
            return { success: true, data: response.data };
        } catch (err: any) {
            console.error('Password reset failed:', err);
            
            if (err.response?.data?.errors) {
                error.value = Object.values(err.response.data.errors).flat().join(', ');
            } else if (err.response?.data?.message) {
                error.value = err.response.data.message;
            } else {
                error.value = 'Failed to reset password.';
            }
            
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    };

    // Return all state, getters, and actions
    return {
        // State
        user,
        token,
        loading,
        error,
        authenticated,
        isAuthResolved,
        
        // Getters
        currentUser,
        isAuthenticated,
        isAdmin,
        isEmailVerified,
        userName,
        userEmail,
        userInitials,
        
        // Actions
        login,
        register,
        logout,
        attempt,
        initialize,
        refreshUser,
        updateUser,
        verifyEmail,
        resendVerificationEmail,
        forgotPassword,
        resetPassword,
        clearError,
        
        // Utility
        setAuthenticated,
        setUser
    };
});

// Type export for use in other files
export type AuthStore = ReturnType<typeof useAuthStore>;