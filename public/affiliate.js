// public/affiliate.js
// Premium UI interactions for the Affiliate Landing Page

// Helper: animate numeric counter
function animateValue(id, start, end, duration) {
  const element = document.getElementById(id);
  if (!element) return;
  const range = end - start;
  const minTimer = 50;
  const stepTime = Math.max(Math.floor(duration / Math.abs(range)), minTimer);
  let startTime = null;
  function step(timestamp) {
    if (!startTime) startTime = timestamp;
    const progress = timestamp - startTime;
    const value = Math.min(start + Math.floor((progress / duration) * range), end);
    element.textContent = typeof end === 'number' && id.includes('commission')
      ? `Rp ${value.toLocaleString('id-ID')}`
      : value.toLocaleString('id-ID');
    if (progress < duration) {
      window.requestAnimationFrame(step);
    } else {
      // ensure final value
      element.textContent = typeof end === 'number' && id.includes('commission')
        ? `Rp ${end.toLocaleString('id-ID')}`
        : end.toLocaleString('id-ID');
    }
  }
  window.requestAnimationFrame(step);
}

// Mock data – in a real app this would be fetched from /api/affiliate-stats.json
const mockStats = {
  clicks: 1245,
  active: 24,
  commission: 12500000, // 12.5M IDR
};

function initStats() {
  animateValue('stats-clicks', 0, mockStats.clicks, 1500);
  animateValue('stats-active', 0, mockStats.active, 1500);
  animateValue('stats-commission', 0, mockStats.commission, 2000);
}

// Smooth scroll for FAQ details expansion (optional enhancement)
function initFAQ() {
  const details = document.querySelectorAll('#faq details');
  details.forEach((d) => {
    d.addEventListener('toggle', () => {
      if (d.open) {
        d.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
}

// Modal open/close functions for registration form
function openRegisterModal() {
  const modal = document.getElementById('register-modal');
  if (modal) modal.classList.remove('hidden');
}
function closeRegisterModal() {
  const modal = document.getElementById('register-modal');
  if (modal) modal.classList.add('hidden');
}

// Registration form submission handler
function handleRegisterForm(event) {
  event.preventDefault();
  const form = event.target;
  const data = {
    name: form.name.value,
    email: form.email.value,
    whatsapp: form.whatsapp.value,
  };
  console.log('Affiliate registration submission:', data);
  // Placeholder for real API call
  alert('Terima kasih! Pendaftaran Anda telah diterima.');
  closeRegisterModal();
}

// Attach submit listener (if element exists when script loads)
const regForm = document.getElementById('register-form');
if (regForm) {
  regForm.addEventListener('submit', handleRegisterForm);
}


// Initialize all interactive components once DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  initStats();
  initFAQ();
  // Add fade‑in animation to key sections for premium feel
  const fadeEls = document.querySelectorAll('.animate-fade-in');
  fadeEls.forEach((el) => {
    el.classList.add('opacity-0'); // start hidden (Tailwind utility)
    setTimeout(() => el.classList.remove('opacity-0'), 100); // trigger transition
  });
});
