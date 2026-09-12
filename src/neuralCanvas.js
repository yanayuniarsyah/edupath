export function initNeuralCanvas() {
  const canvas = document.getElementById('bg-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  let width, height;
  let particles = [];
  let mouse = { x: null, y: null };
  const maxParticles = 160;
  const connectionDistanceSquared = 14400; // 120px * 120px
  const mouseRadiusSquared = 22500; // 150px * 150px

  // Set canvas size
  const resize = () => {
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width;
    canvas.height = height;
    initParticles();
  };

  const initParticles = () => {
    particles = [];
    const count = Math.min(maxParticles, Math.floor((width * height) / 11000));
    for (let i = 0; i < count; i++) {
      particles.push({
        x: Math.random() * width,
        y: Math.random() * height,
        vx: (Math.random() - 0.5) * 0.8, // -0.4 to 0.4
        vy: (Math.random() - 0.5) * 0.8,
        size: Math.random() * 1.5 + 0.5, // 0.5px to 2.0px
      });
    }
  };

  const draw = () => {
    ctx.clearRect(0, 0, width, height);

    // Update and draw particles
    for (let i = 0; i < particles.length; i++) {
      const p = particles[i];

      // Mouse repulsion
      if (mouse.x !== null && mouse.y !== null) {
        const dx = p.x - mouse.x;
        const dy = p.y - mouse.y;
        const distSq = dx * dx + dy * dy;

        if (distSq < mouseRadiusSquared) {
          const force = (mouseRadiusSquared - distSq) / mouseRadiusSquared;
          p.x += (dx / Math.sqrt(distSq)) * force * 5;
          p.y += (dy / Math.sqrt(distSq)) * force * 5;
        }
      }

      // Movement
      p.x += p.vx;
      p.y += p.vy;

      // Bounce
      if (p.x < 0 || p.x > width) p.vx *= -1;
      if (p.y < 0 || p.y > height) p.vy *= -1;

      // Ensure particles stay within bounds after repulsion
      if (p.x < 0) p.x = 0;
      if (p.x > width) p.x = width;
      if (p.y < 0) p.y = 0;
      if (p.y > height) p.y = height;

      // Draw particle
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(34, 211, 238, 0.9)';
      ctx.fill();

      // Draw connections
      for (let j = i + 1; j < particles.length; j++) {
        const p2 = particles[j];
        const dx = p.x - p2.x;
        const dy = p.y - p2.y;
        const distSq = dx * dx + dy * dy;

        if (distSq < connectionDistanceSquared) {
          const opacity = 1 - distSq / connectionDistanceSquared;
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(p2.x, p2.y);
          ctx.strokeStyle = `rgba(34, 211, 238, ${opacity * 0.5})`;
          ctx.lineWidth = 0.8;
          ctx.stroke();
        }
      }
    }

    requestAnimationFrame(draw);
  };

  window.addEventListener('resize', resize);
  window.addEventListener('mousemove', (e) => {
    mouse.x = e.clientX;
    mouse.y = e.clientY;
  });
  window.addEventListener('mouseout', () => {
    mouse.x = null;
    mouse.y = null;
  });

  resize();
  draw();
}
