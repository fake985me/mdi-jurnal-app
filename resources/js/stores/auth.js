import { defineStore } from 'pinia';
import api from '../services/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('auth_token') || null,
        lastActivity: Date.now(), // Track last user activity
        idleTimer: null, // Timer for idle timeout
        idleWarningTimer: null, // Timer for idle warning
        // 2FA state
        twoFactorUserId: null,
        twoFactorEmail: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        isSuperAdmin: (state) => state.user?.role === 'superadmin',
        isAdmin: (state) => state.user?.role === 'admin' || state.user?.role === 'superadmin',
        isSales: (state) => state.user?.role === 'sales',
    },

    actions: {
        async login(credentials) {
            try {
                const response = await api.post('/login', credentials);

                // Check if 2FA is required
                if (response.data.requires_2fa) {
                    this.twoFactorUserId = response.data.user_id;
                    this.twoFactorEmail = response.data.email;
                    return { requires_2fa: true };
                }

                // Normal login (if 2FA is not required)
                this.token = response.data.token;
                this.user = response.data.user;
                localStorage.setItem('auth_token', response.data.token);

                // Start idle timeout after successful login
                this.startIdleTimeout();

                return response.data;
            } catch (error) {
                throw error;
            }
        },

        async verify2fa(code) {
            try {
                const response = await api.post('/verify-2fa', {
                    user_id: this.twoFactorUserId,
                    code: code,
                });

                this.token = response.data.token;
                this.user = response.data.user;
                localStorage.setItem('auth_token', response.data.token);

                // Clear 2FA state
                this.twoFactorUserId = null;
                this.twoFactorEmail = null;

                // Start idle timeout after successful login
                this.startIdleTimeout();

                return response.data;
            } catch (error) {
                throw error;
            }
        },

        async resend2faCode() {
            try {
                const response = await api.post('/resend-2fa', {
                    user_id: this.twoFactorUserId,
                });
                this.twoFactorEmail = response.data.email;
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        async logout() {
            try {
                await api.post('/logout');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                // Clear idle timers
                this.clearIdleTimeout();

                this.user = null;
                this.token = null;
                localStorage.removeItem('auth_token');
            }
        },

        async fetchUser() {
            try {
                const response = await api.get('/user');
                this.user = response.data;
                return response.data;
            } catch (error) {
                // Don't call logout API - just clear local state to prevent 429 loop
                this.clearIdleTimeout();
                this.user = null;
                this.token = null;
                localStorage.removeItem('auth_token');
                throw error;
            }
        },

        // Initialize auth state on app startup
        async initializeAuth() {
            const token = localStorage.getItem('auth_token');

            // If no token exists, clear auth state
            if (!token) {
                this.user = null;
                this.token = null;
                return false;
            }

            // Validate token by fetching user
            try {
                await this.fetchUser();

                // Setup idle timeout and activity listeners
                this.setupActivityListeners();

                return true;
            } catch (error) {
                // Token is invalid or expired, clear it
                this.user = null;
                this.token = null;
                localStorage.removeItem('auth_token');
                return false;
            }
        },

        // Check if user is authenticated with valid token
        async checkAuth() {
            // If no token, not authenticated
            if (!this.token) {
                return false;
            }

            // If we have token but no user, try to fetch user
            if (!this.user) {
                try {
                    await this.fetchUser();
                    return true;
                } catch (error) {
                    return false;
                }
            }

            return true;
        },

        startIdleTimeout() {
            const IDLE_TIMEOUT = 6 * 60 * 60 * 1000; // 1 minute for testing (change to: 8 * 60 * 60 * 1000 for production)
            const WARNING_TIMEOUT = IDLE_TIMEOUT - (60 * 1000); // Show warning 10 seconds before logout

            // Clear existing timers
            this.clearIdleTimeout();

            // Set warning timer (50 seconds for 1-minute test)
            this.idleWarningTimer = setTimeout(() => {
                if (this.isAuthenticated) {
                    console.warn('Session akan berakhir dalam 60 detik karena tidak ada aktivitas');
                    alert('Sesi Anda akan berakhir dalam 60 detik karena tidak ada aktivitas. Silakan lakukan aktivitas untuk tetap login.');
                }
            }, WARNING_TIMEOUT);

            // Set logout timer (60 seconds for 1-minute test)
            this.idleTimer = setTimeout(async () => {
                if (this.isAuthenticated) {
                    console.log('Auto logout: 30 menit tidak ada aktivitas');
                    await this.logout();
                    // Redirect to login
                    window.location.href = '/login';
                }
            }, IDLE_TIMEOUT);
        },

        // Reset idle timeout on user activity
        resetIdleTimeout() {
            this.lastActivity = Date.now();
            if (this.isAuthenticated) {
                this.startIdleTimeout();
            }
        },

        // Clear idle timeout timers
        clearIdleTimeout() {
            if (this.idleTimer) {
                clearTimeout(this.idleTimer);
                this.idleTimer = null;
            }
            if (this.idleWarningTimer) {
                clearTimeout(this.idleWarningTimer);
                this.idleWarningTimer = null;
            }
        },

        // Setup activity listeners
        setupActivityListeners() {
            // Events that count as user activity
            const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

            // Throttle: only reset every 30 seconds to avoid too many resets
            let lastReset = 0;
            const throttleTime = 30000; // 30 seconds

            const handleActivity = () => {
                const now = Date.now();
                if (now - lastReset > throttleTime) {
                    lastReset = now;
                    this.resetIdleTimeout();
                }
            };

            // Add listeners
            events.forEach(event => {
                window.addEventListener(event, handleActivity, { passive: true });
            });

            // Start initial timeout
            this.resetIdleTimeout();
        },
    },
});
