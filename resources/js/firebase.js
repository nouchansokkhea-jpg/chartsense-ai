import { initializeApp } from "firebase/app";
import { getAuth, GoogleAuthProvider, signInWithPopup } from "firebase/auth";

// Official Firebase configuration provided for ChartSense AI
const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY || "AIzaSyBh7NeBrf1FTrTHIpMDvJpWwCafmjt0zzo",
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN || "attendance-app-fd219.firebaseapp.com",
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID || "attendance-app-fd219",
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET || "attendance-app-fd219.firebasestorage.app",
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID || "488465672551",
    appId: import.meta.env.VITE_FIREBASE_APP_ID || "1:488465672551:web:62f1435cef3ab32ae167c2",
    measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID || "G-XKFQYQTHZX"
};

// Initialize Firebase App & Auth
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const provider = new GoogleAuthProvider();
provider.setCustomParameters({ prompt: 'select_account' });

/**
 * Trigger Firebase Google Sign In with interactive popup
 */
export async function loginWithFirebaseGoogle(buttonEl = null) {
    let originalText = '';
    if (buttonEl) {
        originalText = buttonEl.innerHTML;
        buttonEl.innerHTML = `
            <svg class="animate-spin w-5 h-5 text-slate-800 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span style="color: #0f172a !important;">Connecting to Google...</span>
        `;
        buttonEl.style.pointerEvents = 'none';
    }

    try {
        const result = await signInWithPopup(auth, provider);
        const user = result.user;
        const idToken = await user.getIdToken();

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch('/auth/firebase/session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({
                uid: user.uid,
                email: user.email,
                name: user.displayName,
                avatar: user.photoURL,
                id_token: idToken
            })
        });

        const data = await response.json();

        if (data.success && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else {
            throw new Error(data.error || 'Failed to authenticate session.');
        }
    } catch (error) {
        console.error('Firebase Auth Error:', error);

        if (buttonEl) {
            buttonEl.innerHTML = originalText;
            buttonEl.style.pointerEvents = 'auto';
        }

        if (error.code === 'auth/popup-closed-by-user') {
            // User simply closed the popup window
            return;
        }

        if (error.code === 'auth/unauthorized-domain' || error.code === 'auth/popup-blocked') {
            console.warn('Firebase popup blocked or domain unauthorized. Falling back to account chooser...');
            window.location.href = '/auth/google/select-account';
            return;
        }

        // Fallback to internal Google account chooser if network or policy issues arise
        window.location.href = '/auth/google/select-account';
    }
}

// Attach to window for global access from Blade views
window.loginWithFirebaseGoogle = loginWithFirebaseGoogle;
window.firebaseAuth = auth;

export { app, auth, provider };
