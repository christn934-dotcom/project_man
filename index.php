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
        body.light {
            --bg: #f8fafc; --bg-card: rgba(0,0,0,0.03); --bg-card-hover: rgba(0,0,0,0.06);
            --text: #1e293b; --text-muted: #64748b; --border: rgba(0,0,0,0.08);
        }
        body.light .navbar.scrolled { background: rgba(248,250,252,0.95); border-bottom-color: var(--border); box-shadow: 0 4px 30px rgba(0,0,0,0.08); }
        body.light .hero h1 { color: #1e293b; }
        body.light .hero p { color: #64748b; }
        body.light .feature-card { background: #ffffff; border: 1px solid #e2e8f0; }
        body.light .feature-card h3 { color: #1e293b; }
        body.light .feature-card p { color: #64748b; }
        body.light .devices-wrapper { filter: none; }
        body.light .device-laptop, body.light .device-tablet, body.light .device-phone { filter: drop-shadow(0 10px 40px rgba(0,0,0,0.08)); }
        body.light .stat-number { color: #4f46e5; }
        body.light .stat-label { color: #64748b; }
        body.light .cta-section h2 { color: #1e293b; }
        body.light .cta-section p { color: #64748b; }
        body.light .footer { border-top-color: #e2e8f0; }
        body.light .footer a { color: #64748b; }
        body.light .section-label { color: #4f46e5; }
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

        /* ============================================
           DEVICE MOCKUP PREVIEW SECTION
           ============================================ */
        .preview-section {
            padding: 100px 60px 80px;
            position: relative;
            overflow: hidden;
            overflow-x: hidden;
        }
        .devices-wrapper {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 32px;
            max-width: 1100px;
            margin: 0 auto;
            perspective: 1200px;
        }

        /* --- LAPTOP --- */
        .device-laptop {
            flex: 0 0 520px;
            position: relative;
            z-index: 2;
            animation: deviceFloat 6s ease-in-out infinite;
        }
        .laptop-screen {
            background: #1a1f2e;
            border-radius: 12px 12px 0 0;
            border: 2px solid #333;
            padding: 6px 6px 0;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .laptop-browser-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: #111520;
            border-radius: 8px 8px 0 0;
        }
        .browser-dot { width: 8px; height: 8px; border-radius: 50%; }
        .browser-dot.r { background: #ef4444; }
        .browser-dot.y { background: #f59e0b; }
        .browser-dot.g { background: #22c55e; }
        .browser-url {
            flex: 1;
            background: #1e2436;
            border-radius: 4px;
            padding: 3px 10px;
            font-size: 10px;
            color: #64748b;
            margin-left: 8px;
        }
        .laptop-content {
            background: #f8fafc;
            min-height: 280px;
            padding: 0;
            position: relative;
        }
        .laptop-content-dark {
            background: #0f172a;
            min-height: 280px;
            padding: 0;
        }
        .laptop-base {
            height: 14px;
            background: linear-gradient(to bottom, #444, #2a2a2a);
            border-radius: 0 0 4px 4px;
            position: relative;
        }
        .laptop-base::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: #555;
            border-radius: 2px;
        }
        .laptop-bottom {
            height: 6px;
            background: linear-gradient(to bottom, #333, #222);
            border-radius: 0 0 8px 8px;
            margin: 0 -2px;
        }

        /* --- TABLET --- */
        .device-tablet {
            flex: 0 0 260px;
            position: relative;
            z-index: 1;
            animation: deviceFloat 6s ease-in-out infinite 1s;
        }
        .tablet-frame {
            background: #1a1f2e;
            border-radius: 16px;
            border: 3px solid #333;
            padding: 12px 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .tablet-content {
            background: #f8fafc;
            border-radius: 8px;
            min-height: 340px;
            overflow: hidden;
        }
        .tablet-content-dark {
            background: #0f172a;
            border-radius: 8px;
            min-height: 340px;
            overflow: hidden;
        }
        .tablet-camera {
            width: 6px;
            height: 6px;
            background: #222;
            border-radius: 50%;
            margin: 0 auto 6px;
        }

        /* --- PHONE --- */
        .device-phone {
            flex: 0 0 180px;
            position: relative;
            z-index: 3;
            animation: deviceFloat 6s ease-in-out infinite 2s;
        }
        .phone-frame {
            background: #1a1f2e;
            border-radius: 24px;
            border: 3px solid #333;
            padding: 10px 6px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .phone-notch {
            width: 60px;
            height: 6px;
            background: #111;
            border-radius: 3px;
            margin: 0 auto 6px;
        }
        .phone-content {
            background: #f8fafc;
            border-radius: 16px;
            min-height: 380px;
            overflow: hidden;
        }
        .phone-content-dark {
            background: #0f172a;
            border-radius: 16px;
            min-height: 380px;
            overflow: hidden;
        }
        .phone-home {
            width: 40px;
            height: 4px;
            background: #444;
            border-radius: 2px;
            margin: 8px auto 0;
        }

        @keyframes deviceFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        /* --- MOCK UI ELEMENTS --- */
        .mock-sidebar {
            width: 50px;
            background: #0f172a;
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 12px;
            gap: 8px;
        }
        .mock-sidebar-dark {
            width: 44px;
            background: #0c1120;
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 12px;
            gap: 8px;
        }
        .mock-nav-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: rgba(255,255,255,0.06);
        }
        .mock-nav-icon.active {
            background: #4f46e5;
        }
        .mock-topbar {
            height: 36px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            padding: 0 12px;
            gap: 8px;
        }
        .mock-topbar-dark {
            height: 34px;
            background: #1e2436;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            padding: 0 10px;
            gap: 6px;
        }
        .mock-search {
            flex: 1;
            height: 20px;
            background: #f1f5f9;
            border-radius: 4px;
        }
        .mock-search-dark {
            flex: 1;
            height: 18px;
            background: #111827;
            border-radius: 4px;
        }
        .mock-avatar {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }
        .mock-avatar-sm {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }
        .mock-body {
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .mock-body-dark {
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .mock-stat-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }
        .mock-stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }
        .mock-stat-num {
            font-size: 16px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 2px;
        }
        .mock-stat-lbl {
            font-size: 7px;
            color: #9ca3af;
            font-weight: 500;
        }
        .mock-table {
            width: 100%;
            border-collapse: collapse;
        }
        .mock-table th {
            font-size: 7px;
            color: #6b7280;
            font-weight: 600;
            text-align: left;
            padding: 4px 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        .mock-table td {
            font-size: 8px;
            padding: 5px 6px;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }
        .mock-badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 6px;
            font-weight: 600;
        }
        .mock-badge.green { background: #dcfce7; color: #166534; }
        .mock-badge.blue { background: #dbeafe; color: #1e40af; }
        .mock-badge.amber { background: #fef3c7; color: #92400e; }
        .mock-badge.purple { background: #f3e8ff; color: #6b21a8; }
        .mock-progress {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }
        .mock-progress-fill {
            height: 100%;
            border-radius: 2px;
            background: linear-gradient(90deg, #4f46e5, #818cf8);
        }
        .mock-card-title {
            font-size: 9px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
        }
        .mock-notif-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .mock-notif-icon {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .mock-notif-text {
            font-size: 7px;
            color: #6b7280;
            line-height: 1.3;
        }
        .mock-notif-text strong {
            color: #1f2937;
            display: block;
            font-size: 8px;
        }
        .mock-chart {
            height: 60px;
            display: flex;
            align-items: flex-end;
            gap: 4px;
            padding: 6px 0;
        }
        .mock-bar {
            flex: 1;
            border-radius: 3px 3px 0 0;
            background: linear-gradient(to top, #4f46e5, #818cf8);
            transition: height 1s ease;
        }
        .mock-bar:nth-child(even) { background: linear-gradient(to top, #7c3aed, #a78bfa); }
        .mock-task-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .mock-checkbox {
            width: 12px;
            height: 12px;
            border: 2px solid #d1d5db;
            border-radius: 3px;
            flex-shrink: 0;
        }
        .mock-checkbox.checked {
            background: #4f46e5;
            border-color: #4f46e5;
        }
        .mock-task-text {
            flex: 1;
        }
        .mock-task-title {
            font-size: 8px;
            color: #1f2937;
            font-weight: 600;
        }
        .mock-task-meta {
            font-size: 6px;
            color: #9ca3af;
        }
        .mock-phone-header {
            padding: 10px 12px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .mock-phone-title {
            font-size: 11px;
            font-weight: 700;
            color: #1f2937;
        }
        .mock-phone-body {
            padding: 0 10px 10px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

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
    
        @media (max-width: 1024px) {
            .devices-wrapper { gap: 20px; }
            .device-laptop { flex: 0 0 420px; }
            .device-tablet { flex: 0 0 220px; }
            .device-phone { flex: 0 0 155px; }
        }
        @media (max-width: 850px) {
            .devices-wrapper { gap: 16px; transform: scale(0.8); transform-origin: center top; }
            .device-laptop { flex: 0 0 380px; }
            .device-tablet { flex: 0 0 200px; }
            .device-phone { flex: 0 0 140px; }
        }
        @media (max-width: 680px) {
            .preview-section { padding: 60px 16px 40px; }
            .devices-wrapper { gap: 10px; transform: scale(0.62); transform-origin: center top; }
            .device-laptop { flex: 0 0 360px; }
            .device-tablet { flex: 0 0 190px; }
            .device-phone { flex: 0 0 135px; }
        }
        @media (max-width: 520px) {
            .preview-section { padding: 60px 16px 40px; overflow: visible; overflow-x: hidden; }
            .devices-wrapper { flex-direction: column; align-items: center; gap: 24px; transform: none; overflow: visible; }
            .device-laptop { flex: 0 0 auto; width: 88%; max-width: 380px; order: 0; }
            .device-tablet { flex: 0 0 auto; width: 60%; max-width: 240px; order: 1; }
            .device-phone { flex: 0 0 auto; width: 40%; max-width: 170px; order: 2; }
            .device-tablet, .device-phone { opacity: 0.7; }
        }
        @media (max-width: 400px) {
            .device-laptop { width: 95%; max-width: none; }
            .device-tablet, .device-phone { display: none; }
        }

    </style>
</head>
<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark'){document.body.classList.add('dark');document.body.classList.remove('light')}else if(t==='light'){document.body.classList.add('light');document.body.classList.remove('dark')}})();
</script>
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
        <div class="feature-card reveal"><div class="feature-icon cyan"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div><h3>Activity Logging</h3><p>Stay informed with real-time activity logs tracking every action across your projects.</p></div>
    </div>
</section>

<section class="preview-section" id="preview">
    <div class="section-header reveal">
        <div class="label">Interface Preview</div>
        <h2>See It in Action</h2>
        <p>A modern, intuitive interface across all your devices.</p>
    </div>
    <div class="devices-wrapper reveal">

        <!-- TABLET -->
        <div class="device-tablet">
            <div class="tablet-frame">
                <div class="tablet-camera"></div>
                <div class="tablet-content-dark">
                    <div class="mock-topbar-dark">
                        <div class="mock-search-dark" style="flex:1;"></div>
                        <div class="mock-avatar-sm"></div>
                    </div>
                    <div class="mock-body-dark">
                        <div class="mock-card-title" style="color:#e2e8f0;">My Tasks</div>
                        <div class="mock-task-item">
                            <div class="mock-checkbox checked"></div>
                            <div class="mock-task-text">
                                <div class="mock-task-title" style="color:#e2e8f0;">Design landing page</div>
                                <div class="mock-task-meta">Completed · Design</div>
                            </div>
                            <span class="mock-badge green">Done</span>
                        </div>
                        <div class="mock-task-item">
                            <div class="mock-checkbox"></div>
                            <div class="mock-task-text">
                                <div class="mock-task-title" style="color:#e2e8f0;">API integration</div>
                                <div class="mock-task-meta">Due today · Development</div>
                            </div>
                            <span class="mock-badge blue">Active</span>
                        </div>
                        <div class="mock-task-item">
                            <div class="mock-checkbox"></div>
                            <div class="mock-task-text">
                                <div class="mock-task-title" style="color:#e2e8f0;">User testing</div>
                                <div class="mock-task-meta">Due tomorrow · QA</div>
                            </div>
                            <span class="mock-badge amber">Pending</span>
                        </div>
                        <div class="mock-task-item">
                            <div class="mock-checkbox"></div>
                            <div class="mock-task-text">
                                <div class="mock-task-title" style="color:#e2e8f0;">Write documentation</div>
                                <div class="mock-task-meta">Due Friday · Docs</div>
                            </div>
                            <span class="mock-badge purple">Review</span>
                        </div>
                        <div class="mock-task-item">
                            <div class="mock-checkbox checked"></div>
                            <div class="mock-task-text">
                                <div class="mock-task-title" style="color:#e2e8f0;">Setup CI/CD</div>
                                <div class="mock-task-meta">Completed · DevOps</div>
                            </div>
                            <span class="mock-badge green">Done</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LAPTOP (center) -->
        <div class="device-laptop">
            <div class="laptop-screen">
                <div class="laptop-browser-bar">
                    <span class="browser-dot r"></span>
                    <span class="browser-dot y"></span>
                    <span class="browser-dot g"></span>
                    <span class="browser-url">localhost:8080/admin-dashboard.php</span>
                </div>
                <div class="laptop-content-dark" style="position:relative; min-height:280px;">
                    <!-- Sidebar -->
                    <div class="mock-sidebar-dark">
                        <div class="mock-nav-icon active"></div>
                        <div class="mock-nav-icon"></div>
                        <div class="mock-nav-icon"></div>
                        <div class="mock-nav-icon"></div>
                        <div class="mock-nav-icon" style="margin-top:auto;"></div>
                    </div>
                    <!-- Content -->
                    <div style="margin-left:44px;">
                        <div class="mock-topbar-dark">
                            <div class="mock-search-dark"></div>
                            <div style="display:flex;gap:4px;align-items:center;">
                                <span style="font-size:10px;color:#f59e0b;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
                                <div class="mock-avatar-sm"></div>
                            </div>
                        </div>
                        <div style="padding:10px 14px;">
                            <div style="font-size:7px;color:#94a3b8;font-weight:600;letter-spacing:1px;margin-bottom:2px;">DASHBOARD</div>
                            <div style="font-size:14px;font-weight:800;color:#f8fafc;margin-bottom:10px;">Welcome back, Admin</div>
                            <!-- Stat cards -->
                            <div class="mock-stat-row" style="margin-bottom:10px;">
                                <div style="background:#1e2436;border:1px solid rgba(255,255,255,0.06);border-radius:6px;padding:8px 10px;">
                                    <div style="font-size:18px;font-weight:800;color:#818cf8;">12</div>
                                    <div style="font-size:7px;color:#94a3b8;">Projects</div>
                                </div>
                                <div style="background:#1e2436;border:1px solid rgba(255,255,255,0.06);border-radius:6px;padding:8px 10px;">
                                    <div style="font-size:18px;font-weight:800;color:#c084fc;">48</div>
                                    <div style="font-size:7px;color:#94a3b8;">Tasks</div>
                                </div>
                                <div style="background:#1e2436;border:1px solid rgba(255,255,255,0.06);border-radius:6px;padding:8px 10px;">
                                    <div style="font-size:18px;font-weight:800;color:#f472b6;">8</div>
                                    <div style="font-size:7px;color:#94a3b8;">Team</div>
                                </div>
                                <div style="background:#1e2436;border:1px solid rgba(255,255,255,0.06);border-radius:6px;padding:8px 10px;">
                                    <div style="font-size:18px;font-weight:800;color:#34d399;">94%</div>
                                    <div style="font-size:7px;color:#94a3b8;">Complete</div>
                                </div>
                            </div>
                            <!-- Chart -->
                            <div style="background:#1e2436;border:1px solid rgba(255,255,255,0.06);border-radius:6px;padding:8px 10px;margin-bottom:8px;">
                                <div style="font-size:8px;font-weight:700;color:#e2e8f0;margin-bottom:4px;">Project Progress</div>
                                <div class="mock-chart">
                                    <div class="mock-bar" style="height:45%;"></div>
                                    <div class="mock-bar" style="height:70%;"></div>
                                    <div class="mock-bar" style="height:55%;"></div>
                                    <div class="mock-bar" style="height:85%;"></div>
                                    <div class="mock-bar" style="height:40%;"></div>
                                    <div class="mock-bar" style="height:90%;"></div>
                                    <div class="mock-bar" style="height:65%;"></div>
                                    <div class="mock-bar" style="height:75%;"></div>
                                    <div class="mock-bar" style="height:50%;"></div>
                                    <div class="mock-bar" style="height:95%;"></div>
                                    <div class="mock-bar" style="height:60%;"></div>
                                    <div class="mock-bar" style="height:80%;"></div>
                                </div>
                            </div>
                            <!-- Recent tasks mini table -->
                            <div style="background:#1e2436;border:1px solid rgba(255,255,255,0.06);border-radius:6px;padding:8px 10px;">
                                <div style="font-size:8px;font-weight:700;color:#e2e8f0;margin-bottom:4px;">Recent Tasks</div>
                                <div style="display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                                    <span style="font-size:7px;color:#cbd5e1;">Homepage redesign</span>
                                    <span class="mock-badge green" style="font-size:5px;">Done</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                                    <span style="font-size:7px;color:#cbd5e1;">API endpoints</span>
                                    <span class="mock-badge blue" style="font-size:5px;">Active</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;padding:3px 0;">
                                    <span style="font-size:7px;color:#cbd5e1;">User auth module</span>
                                    <span class="mock-badge amber" style="font-size:5px;">Review</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="laptop-base"></div>
            <div class="laptop-bottom"></div>
        </div>

        <!-- PHONE -->
        <div class="device-phone">
            <div class="phone-frame">
                <div class="phone-notch"></div>
                <div class="phone-content-dark">
                    <div class="mock-phone-header" style="padding:10px 12px 6px;">
                        <span class="mock-phone-title" style="color:#f8fafc;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg> Notifications</span>
                        <div class="mock-avatar-sm"></div>
                    </div>
                    <div class="mock-phone-body">
                        <div class="mock-notif-item" style="border-color:rgba(255,255,255,0.06);">
                            <div class="mock-notif-icon" style="background:rgba(79,70,229,0.15);">✓</div>
                            <div class="mock-notif-text">
                                <strong style="color:#e2e8f0;">New task assigned</strong>
                                <span>Design the settings page</span>
                            </div>
                        </div>
                        <div class="mock-notif-item" style="border-color:rgba(255,255,255,0.06);">
                            <div class="mock-notif-icon" style="background:rgba(34,197,94,0.15);">▣</div>
                            <div class="mock-notif-text">
                                <strong style="color:#e2e8f0;">Project created</strong>
                                <span>Mobile App v2.0</span>
                            </div>
                        </div>
                        <div class="mock-notif-item" style="border-color:rgba(255,255,255,0.06);">
                            <div class="mock-notif-icon" style="background:rgba(244,114,182,0.15);">✎</div>
                            <div class="mock-notif-text">
                                <strong style="color:#e2e8f0;">Task updated</strong>
                                <span>Homepage redesign completed</span>
                            </div>
                        </div>
                        <div class="mock-notif-item" style="border-color:rgba(255,255,255,0.06);">
                            <div class="mock-notif-icon" style="background:rgba(245,158,11,0.15);">♙</div>
                            <div class="mock-notif-text">
                                <strong style="color:#e2e8f0;">Team member added</strong>
                                <span>John joined the project</span>
                            </div>
                        </div>
                        <div class="mock-notif-item" style="border-color:rgba(255,255,255,0.06);">
                            <div class="mock-notif-icon" style="background:rgba(79,70,229,0.15);">✓</div>
                            <div class="mock-notif-text">
                                <strong style="color:#e2e8f0;">Task completed</strong>
                                <span>API integration done</span>
                            </div>
                        </div>
                        <div class="mock-notif-item" style="border-color:rgba(255,255,255,0.06);">
                            <div class="mock-notif-icon" style="background:rgba(34,211,238,0.15);">▥</div>
                            <div class="mock-notif-text">
                                <strong style="color:#e2e8f0;">Report generated</strong>
                                <span>Monthly progress report</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="phone-home"></div>
        </div>

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

// Mobile nav
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

// Device mockup parallax on scroll
window.addEventListener("scroll", function() {
    var wrapper = document.querySelector(".devices-wrapper");
    if (!wrapper) return;
    var rect = wrapper.getBoundingClientRect();
    var viewH = window.innerHeight;
    if (rect.top < viewH && rect.bottom > 0) {
        var progress = (viewH - rect.top) / (viewH + rect.height);
        var laptops = document.querySelector(".device-laptop");
        var tablet = document.querySelector(".device-tablet");
        var phone = document.querySelector(".device-phone");
        if (laptops) laptops.style.transform = "translateY(" + (-12 + (progress * 8)) + "px)";
        if (tablet) tablet.style.transform = "translateY(" + (-12 + (progress * 12)) + "px)";
        if (phone) phone.style.transform = "translateY(" + (-12 + (progress * 16)) + "px)";
    }
});
</script>

<?php include "theme_toggle_floating.php"; ?>
<script src="dark_mode.php"></script>
</body>
</html>