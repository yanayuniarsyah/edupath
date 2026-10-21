import { createApp } from 'vue'
import RootApp from './RootApp.vue'
import router from './router/index.js'
import './index.css'
import 'katex/dist/katex.min.css'

// ----------------------------------------------------------------
// Analytics Tracker & Safe Wrapper (GA4 / Meta Pixel)
// ----------------------------------------------------------------
window.dataLayer = window.dataLayer || [];
window.gtag = window.gtag || function() { window.dataLayer.push(arguments); };
window.trackEvent = window.trackEvent || function(eventName, eventParams = {}) {
  window.gtag('event', eventName, eventParams);
  if (window.fbq) window.fbq('trackCustom', eventName, eventParams);
  console.log('[Analytics Tracker]', eventName, eventParams);
};

// ----------------------------------------------------------------
// Midtrans Snap — injeksi dinamis dari environment variable
// Ini mencegah client key hardcoded di source code atau index.html
// ----------------------------------------------------------------
const snapUrl    = import.meta.env.VITE_MIDTRANS_SNAP_URL;
const clientKey  = import.meta.env.VITE_MIDTRANS_CLIENT_KEY;

if (snapUrl && clientKey) {
  const snapScript = document.createElement('script');
  snapScript.src = snapUrl;
  snapScript.setAttribute('data-client-key', clientKey);
  snapScript.type = 'text/javascript';
  document.head.appendChild(snapScript);
} else {
  console.warn(
    '[EduPath] VITE_MIDTRANS_SNAP_URL atau VITE_MIDTRANS_CLIENT_KEY tidak dikonfigurasi.\n' +
    'Payment tidak akan berfungsi. Lihat .env.example untuk petunjuk konfigurasi.'
  );
}

createApp(RootApp).use(router).mount('#app')
