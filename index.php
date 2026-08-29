<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROMASY | Project Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #4f46e5; --primary-light: #6366f1; --primary-dark: #3730a3;
            --bg: #0f172a; --bg-card: rgba(255,255,255,0.05); --bg-card-hover: rgba(255,255,255,0.08);
            --text: #f8fafc; --text-muted: #94a3b8; --border: rgba(255,255,255,0.08);
        }
        html { scroll-behavior: smooth; }
        body { font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: var(--text); overflow-x: hidden; line-height: 1.6; }
        .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; padding: 20px 60px; display: flex; justify-content: space-between; align-items: center; transition: all 0.4s ease; background: transparent; }
        .navbar.scrolled { background: rgba(15,23,42,0.95); backdrop-filter: blur(20px); border-bottom: 1px solid var(--border); padding: 14px 60px; box-shadow: 0 4px 30px rgba(0,0,0,0.3); }
        .nav-logo { font-size: 24px; font-weight: 800; letter-spacing: 3px; color: var(--text); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-logo .logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; color: white; }
        .nav-links { display: flex; align-items: center; gap: 36px; list-style: none; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 15px; font-weight: 500; transition: color 0.3s; position: relative; }
        .nav-links a:hover { color: var(--text); }
        .nav-links a::after { content: ""; position: absolute; bottom: -4px; left: 0; width: 0; height: 2px; background: var(--primary-light); transition: width 0.3s; }
        .nav-links a:hover::after { width: 100%; }
        .nav-btns { display: flex; gap: 12px; align-items: center; }
        .btn-outline { padding: 10px 24px; border: 1px solid var(--border); border-radius: 10px; color: var(--text); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.3s; background: transparent; }
        .btn-outline:hover { border-color: var(--primary-light); background: rgba(99,102,241,0.1); }
        .btn-primary { padding: 10px 24px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border: none; border-radius: 10px; color: white; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 15px rgba(79,70,229,0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(79,70,229,0.5); }
        .hero { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 120px 60px 80px; position: relative; overflow: hidden; }
        .hero-content { max-width: 720px; text-align: center; position: relative; z-index: 2; }
        .hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 50px; font-size: 13px; font-weight: 500; color: var(--primary-light); margin-bottom: 32px; animation: fadeInUp 0.8s ease forwards; opacity: 0; }
        .hero-badge .dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; animation: pulse 2s infinite; }
        .hero h1 { font-size: 64px; font-weight: 800; line-height: 1.1; margin-bottom: 24px; letter-spacing: -2px; animation: fadeInUp 0.8s ease 0.15s forwards; opacity: 0; }
        .hero h1 .gradient-text { background: linear-gradient(135deg, #818cf8, #c084fc, #f472b6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { font-size: 18px; color: var(--text-muted); line-height: 1.8; margin-bottom: 48px; max-width: 560px; margin-left: auto; margin-right: auto; animation: fadeInUp 0.8s ease 0.3s forwards; opacity: 0; }
        .hero-cta { display: flex; gap: 16px; justify-content: center; animation: fadeInUp 0.8s ease 0.45s forwards; opacity: 0; }
        .hero-cta .btn-lg { padding: 16px 36px; border-radius: 12px; font-size: 16px; font-weight: 600; text-decoration: none; transition: all 0.3s; }
        .hero-cta .btn-lg.primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; box-shadow: 0 4px 20px rgba(79,70,229,0.4); }
        .hero-cta .btn-lg.primary:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(79,70,229,0.5); }
        .hero-cta .btn-lg.secondary { background: var(--bg-card); color: var(--text); border: 1px solid var(--border); }
        .hero-cta .btn-lg.secondary:hover { background: var(--bg-card-hover); border-color: rgba(255,255,255,0.15); transform: translateY(-3px); }
        .hero-shapes { position: absolute; inset: 0; pointer-events: none; }
        .shape { position: absolute; border-radius: 50%; filter: blur(1px); }
        .shape-1 { width: 500px; height: 500px; background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%); top: -100px; right: -100px; animation: floatShape 12s ease-in-out infinite; }
        .shape-2 { width: 350px; height: 350px; background: radial-gradient(circle, rgba(192,132,252,0.12) 0%, transparent 70%); bottom: -50px; left: -50px; animation: floatShape 10s ease-in-out infinite reverse; }
        .shape-3 { width: 200px; height: 200px; background: radial-gradient(circle, rgba(244,114,182,0.1) 0%, transparent 70%); top: 40%; left: 15%; animation: floatShape 14s ease-in-out infinite 2s; }
        .shape-ring { position: absolute; border: 1px solid rgba(99,102,241,0.1); border-radius: 50%; animation: spin 30s linear infinite; }
        .shape-ring-1 { width: 600px; height: 600px; top: 50%; left: 50%; transform: translate(-50%,-50%); }
        .shape-ring-2 { width: 800px; height: 800px; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-duration: 40s; animation-direction: reverse; }
        .particles { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
        .particle { position: absolute; width: 3px; height: 3px; background: rgba(255,255,255,0.15); border-radius: 50%; animation: particleFloat linear infinite; }
        .particle:nth-child(1) { left: 10%; animation-duration: 18s; }
        .particle:nth-child(2) { left: 25%; animation-duration: 22s; animation-delay: 2s; width: 2px; height: 2px; }
        .particle:nth-child(3) { left: 40%; animation-duration: 16s; animation-delay: 4s; }
        .particle:nth-child(4) { left: 55%; animation-duration: 20s; animation-delay: 1s; width: 4px; height: 4px; opacity: 0.1; }
        .particle:nth-child(5) { left: 70%; animation-duration: 24s; animation-delay: 3s; }
        .particle:nth-child(6) { left: 85%; animation-duration: 19s; animation-delay: 5s; width: 2px; height: 2px; }
        .particle:nth-child(7) { left: 15%; animation-duration: 21s; animation-delay: 6s; }
        .particle:nth-child(8) { left: 60%; animation-duration: 17s; animation-delay: 7s; width: 2px; height: 2px; }
        .features { padding: 100px 60px; position: relative; }
        .section-header { text-align: center; margin-bottom: 72px; }
        .section-header .label { font-size: 13px; font-weight: 600; color: var(--primary-light); text-transform: uppercase; letter-spacing: 3px; margin-bottom: 16px; }
        .section-header h2 { font-size: 42px; font-weight: 800; letter-spacing: -1px; margin-bottom: 16px; }
        .section-header p { font-size: 17px; color: var(--text-muted); max-width: 500px; margin: 0 auto; }
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 1200px; margin: 0 auto; }
        .feature-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 36px; transition: all 0.4s ease; position: relative; overflow: hidden; }
        .feature-card::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--primary-light), transparent); opacity: 0; transition: opacity 0.4s; }
        .feature-card:hover { background: var(--bg-card-hover); border-color: rgba(99,102,241,0.2); transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .feature-card:hover::before { opacity: 1; }
        .feature-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 20px; }
        .feature-icon.blue { background: rgba(99,102,241,0.1); }
        .feature-icon.purple { background: rgba(192,132,252,0.1); }
        .feature-icon.pink { background: rgba(244,114,182,0.1); }
        .feature-icon.green { background: rgba(34,197,94,0.1); }
        .feature-icon.amber { background: rgba(245,158,11,0.1); }
        .feature-icon.cyan { background: rgba(34,211,238,0.1); }
        .feature-card h3 { font-size: 18px; font-weight: 700; margin-bottom: 10px; }
        .feature-card p { font-size: 14px; color: var(--text-muted); line-height: 1.7; }
        .stats { padding: 80px 60px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; max-width: 1000px; margin: 0 auto; }
        .stat-card { text-align: center; padding: 36px 20px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; transition: all 0.3s; }
        .stat-card:hover { border-color: rgba(99,102,241,0.2); transform: translateY(-2px); }
        .stat-number { font-size: 36px; font-weight: 800; background: linear-gradient(135deg, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 8px; }
        .stat-label { font-size: 14px; color: var(--text-muted); font-weight: 500; }
        .cta { padding: 100px 60px; text-align: center; }
        .cta-box { max-width: 800px; margin: 0 auto; padding: 72px 60px; background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(192,132,252,0.05)); border: 1px solid rgba(99,102,241,0.15); border-radius: 24px; position: relative; overflow: hidden; }
        .cta-box::before { content: ""; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle at center, rgba(99,102,241,0.05) 0%, transparent 50%); animation: spin 20s linear infinite; }
        .cta-box h2 { font-size: 38px; font-weight: 800; margin-bottom: 16px; position: relative; }
        .cta-box p { font-size: 17px; color: var(--text-muted); margin-bottom: 40px; max-width: 450px; margin-left: auto; margin-right: auto; position: relative; }
        .cta-box .btn-lg { display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; text-decoration: none; border-radius: 12px; font-size: 16px; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 20px rgba(79,70,229,0.4); position: relative; }
        .cta-box .btn-lg:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(79,70,229,0.5); }
        .footer { padding: 40px 60px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .footer p { font-size: 14px; color: var(--text-muted); }
        .footer-links { display: flex; gap: 24px; list-style: none; }
        .footer-links a { font-size: 14px; color: var(--text-muted); text-decoration: none; transition: color 0.3s; }
        .footer-links a:hover { color: var(--text); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes floatShape { 0%, 100% { transform: translateY(0) scale(1); } 50% { transform: translateY(-30px) scale(1.05); } }
        @keyframes spin { from { transform: translate(-50%,-50%) rotate(0deg); } to { transform: translate(-50%,-50%) rotate(360deg); } }
        @keyframes particleFloat { 0% { transform: translateY(100vh); opacity: 0; } 10% { opacity: 1; } 90% { opacity: 1; } 100% { transform: translateY(-100px); opacity: 0; } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .reveal { opacity: 0; transform: translateY(40px); transition: all 0.8s cubic-bezier(0.16,1,0.3,1); }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        @media (max-width: 1024px) { .features-grid { grid-template-columns: repeat(2, 1fr); } .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        .hamburger { display: none; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; padding: 8px; z-index: 200; }
        .hamburger span { display: block; width: 24px; height: 2px; background: var(--text); border-radius: 2px; transition: all 0.3s ease; }
        .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }
        .mobile-nav { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15,23,42,0.98); backdrop-filter: blur(20px); z-index: 150; flex-direction: column; align-items: center; justify-content: center; gap: 32px; }
        .mobile-nav.active { display: flex; }
        .mobile-nav a { color: var(--text); text-decoration: none; font-size: 22px; font-weight: 600; transition: color 0.3s; }
        .mobile-nav a:hover { color: var(--primary-light); }
        @media (max-width: 768px) { .navbar { padding: 14px 20px; } .hamburger { display: flex; } .nav-links, .nav-btns { display: none; } .hero { padding: 100px 20px 60px; min-height: auto; } .hero h1 { font-size: 32px; letter-spacing: -1px; } .hero p { font-size: 15px; margin-bottom: 32px; } .hero-cta { flex-direction: column; align-items: center; gap: 12px; } .hero-cta .btn-lg { width: 100%; max-width: 280px; text-align: center; } .features { padding: 40px 20px; } .section-header h2 { font-size: 28px; } .section-header p { font-size: 15px; } .features-grid { grid-template-columns: 1fr; gap: 16px; } .feature-card { padding: 24px; } .stats { padding: 40px 20px; } .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; } .stat-card { padding: 24px 12px; } .stat-number { font-size: 28px; } .cta { padding: 40px 20px; } .cta-box { padding: 40px 20px; } .cta-box h2 { font-size: 26px; } .cta-box p { font-size: 15px; } .footer { flex-direction: column; gap: 12px; padding: 24px 20px; text-align: center; } }
        @media (max-width: 400px) { .hero h1 { font-size: 26px; } .stats-grid { grid-template-columns: 1fr; } .cta-box { padding: 32px 16px; } }
    </style>
</head>
<body>
<nav class="navbar" id="navbar">
    <a href="index.php" class="nav-logo"><div class="logo-icon">P</div> PROMASY</a>
    <div class="mobile-nav" id="mobileNav">
        <a href="#features" onclick="closeMobileNav()">Features</a>
        <a href="#stats" onclick="closeMobileNav()">About</a>
        <a href="#cta" onclick="closeMobileNav()">Get Started</a>
        <a href="login.php">Log In</a>
        <a href="register.php">Sign Up Free</a>
    </div>
    <ul class="nav-links">
        <li><a href="#features">Features</a></li>
        <li><a href="#stats">About</a></li>
        <li><a href="#cta">Get Started</a></li>
    </ul>
    <div class="nav-btns">
        <a href="login.php" class="btn-outline">Log In</a>
        <a href="register.php" class="btn-primary">Sign Up Free</a>
    </div>
    <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
</nav>
<section class="hero">
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape-ring shape-ring-1"></div>
        <div class="shape-ring shape-ring-2"></div>
    </div>
    <div class="particles">
        <div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div>
        <div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div>
    </div>
    <div class="hero-content">
        <div class="hero-badge"><span class="dot"></span> Now in Beta</div>
        <h1>Manage Projects<br>with <span class="gradient-text">Clarity</span></h1>
        <p>Organize projects, assign tasks, track progress, and collaborate with your team — all from one powerful, centralized platform.</p>
        <div class="hero-cta">
            <a href="register.php" class="btn-lg primary">Get Started Free →</a>
            <a href="#features" class="btn-lg secondary">See Features</a>
        </div>
    </div>
</section>
<section class="features" id="features">
    <div class="section-header reveal">
        <div class="label">Features</div>
        <h2>Everything You Need</h2>
        <p>A complete toolkit for managing projects, teams, and deadlines in one place.</p>
    </div>
    <div class="features-grid">
        <div class="feature-card reveal"><div class="feature-icon blue">📋</div><h3>Project Management</h3><p>Create and organize projects with detailed milestones, budgets, and deadline tracking for complete visibility.</p></div>
        <div class="feature-card reveal"><div class="feature-icon purple">✅</div><h3>Task Tracking</h3><p>Assign tasks, set priorities, and monitor progress in real-time with status updates and due dates.</p></div>
        <div class="feature-card reveal"><div class="feature-icon pink">👥</div><h3>Team Collaboration</h3><p>Keep your team aligned with shared projects, task assignments, and centralized communication.</p></div>
        <div class="feature-card reveal"><div class="feature-icon green">📊</div><h3>Reports &amp; Analytics</h3><p>Generate insights on project performance, team productivity, and task completion rates.</p></div>
        <div class="feature-card reveal"><div class="feature-icon amber">🔐</div><h3>Role-Based Access</h3><p>Secure your data with admin, manager, and member roles — each with tailored permissions.</p></div>
        <div class="feature-card reveal"><div class="feature-icon cyan">🔔</div><h3>Activity Logging</h3><p>Stay informed with real-time activity logs tracking every action across your projects.</p></div>
    </div>
</section>
<section class="stats" id="stats">
    <div class="stats-grid">
        <div class="stat-card reveal"><div class="stat-number" data-target="3">0</div><div class="stat-label">Role Types</div></div>
        <div class="stat-card reveal"><div class="stat-number" data-target="6">0</div><div class="stat-label">Core Modules</div></div>
        <div class="stat-card reveal"><div class="stat-number" data-target="100">0</div><div class="stat-label">Secure by Design</div></div>
        <div class="stat-card reveal"><div class="stat-number" data-target="24">0</div><div class="stat-label">Hours Availability</div></div>
    </div>
</section>
<section class="cta" id="cta">
    <div class="cta-box reveal">
        <h2>Ready to Get Started?</h2>
        <p>Join PROMASY and take control of your projects today. It's free to get started.</p>
        <a href="register.php" class="btn-lg">Create Free Account →</a>
    </div>
</section>
<footer class="footer">
    <p>&copy; 2026 PROMASY. Built with purpose.</p>
    <ul class="footer-links">
        <li><a href="login.php">Login</a></li>
        <li><a href="register.php">Register</a></li>
    </ul>
</footer>
<script>
const navbar = document.getElementById("navbar");
window.addEventListener("scroll", () => { navbar.classList.toggle("scrolled", window.scrollY > 50); });
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) { entry.target.classList.add("visible"); revealObserver.unobserve(entry.target); }
    });
}, { threshold: 0.15, rootMargin: "0px 0px -50px 0px" });
document.querySelectorAll(".reveal").forEach(el => revealObserver.observe(el));
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const el = entry.target;
            const target = parseInt(el.getAttribute("data-target"));
            const suffix = target === 100 ? "%" : "+";
            let current = 0;
            const increment = target / 60;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) { current = target; clearInterval(timer); }
                el.textContent = Math.floor(current) + suffix;
            }, 20);
            counterObserver.unobserve(el);
        }
    });
}, { threshold: 0.5 });
document.querySelectorAll(".stat-number").forEach(c => counterObserver.observe(c));
</script>
<script>
const hamburgerBtn = document.getElementById("hamburgerBtn");
const mobileNav = document.getElementById("mobileNav");
if (hamburgerBtn && mobileNav) {
    hamburgerBtn.addEventListener("click", function() {
        this.classList.toggle("active");
        mobileNav.classList.toggle("active");
        document.body.style.overflow = mobileNav.classList.contains("active") ? "hidden" : "";
    });
    function closeMobileNav() {
        hamburgerBtn.classList.remove("active");
        mobileNav.classList.remove("active");
        document.body.style.overflow = "";
    }
}
</script>
</body>
</html>