document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.createElement('canvas');
    canvas.id = 'bgCanvas';
    canvas.style.position = 'fixed';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.zIndex = '0'; // Sit between body bg and app-container
    canvas.style.pointerEvents = 'none'; // Allow clicking through
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    let width, height;
    let particles = [];

    // Configuration
    const particleCount = 70; // Slightly fewer particles for main page to reduce noise
    const connectionDistance = 150;
    const mouseDistance = 200;

    let mouse = { x: null, y: null };

    // Theme Colors
    const themes = {
        light: {
            particle: 'rgba(99, 102, 241, ', // Indigo
            line: 'rgba(99, 102, 241, '
        },
        dark: {
            particle: 'rgba(129, 140, 248, ', // Lighter Indigo
            line: 'rgba(129, 140, 248, '
        }
    };

    function getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    window.addEventListener('mousemove', (e) => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
    });

    window.addEventListener('resize', init);

    function init() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
        particles = [];

        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }
    }

    class Particle {
        constructor() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.vx = (Math.random() - 0.5) * 1.0; // Slower movement for main page
            this.vy = (Math.random() - 0.5) * 1.0;
            this.size = Math.random() * 2 + 1;
            this.baseAlpha = Math.random() * 0.5 + 0.2;
        }

        update() {
            this.x += this.vx;
            this.y += this.vy;

            // Bounce off edges
            if (this.x < 0 || this.x > width) this.vx *= -1;
            if (this.y < 0 || this.y > height) this.vy *= -1;

            // Mouse interaction
            if (mouse.x != null) {
                let dx = mouse.x - this.x;
                let dy = mouse.y - this.y;
                let distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < mouseDistance) {
                    const forceDirectionX = dx / distance;
                    const forceDirectionY = dy / distance;
                    const force = (mouseDistance - distance) / mouseDistance;
                    const directionX = forceDirectionX * force * this.size;
                    const directionY = forceDirectionY * force * this.size;

                    this.x -= directionX;
                    this.y -= directionY;
                }
            }
        }

        draw(theme) {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = themes[theme].particle + this.baseAlpha + ')';
            ctx.fill();
        }
    }

    function animate() {
        requestAnimationFrame(animate);
        ctx.clearRect(0, 0, width, height);

        const currentTheme = getCurrentTheme();

        // Draw connections first
        for (let a = 0; a < particles.length; a++) {
            for (let b = a; b < particles.length; b++) {
                let dx = particles[a].x - particles[b].x;
                let dy = particles[a].y - particles[b].y;
                let distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < connectionDistance) {
                    let opacity = 1 - (distance / connectionDistance);
                    // Reduce opacity slightly for background to be less distracting
                    ctx.strokeStyle = themes[currentTheme].line + (opacity * 0.15) + ')';
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(particles[a].x, particles[a].y);
                    ctx.lineTo(particles[b].x, particles[b].y);
                    ctx.stroke();
                }
            }
        }

        // Draw particles
        particles.forEach(particle => {
            particle.update();
            particle.draw(currentTheme);
        });
    }

    init();
    animate();
});
