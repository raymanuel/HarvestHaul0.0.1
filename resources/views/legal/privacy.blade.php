<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — HarvestHaul</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Figtree', sans-serif;
            background: #f8f6f1;
            color: #1b1b18;
            padding: 2rem 1rem;
            line-height: 1.7;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border-radius: 1.5rem;
            padding: 3rem 2.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border: 1px solid rgba(45,106,47,0.08);
        }
        h1 { font-size: 1.75rem; font-weight: 700; color: #2D6A2F; margin-bottom: 0.25rem; }
        .updated { font-size: 0.8rem; color: #9ca3af; margin-bottom: 2rem; }
        h2 { font-size: 1.1rem; font-weight: 600; color: #1b1b18; margin-top: 2rem; margin-bottom: 0.5rem; }
        p { font-size: 0.9rem; color: #4b5563; margin-bottom: 1rem; }
        ul { margin: 0.5rem 0 1rem 1.5rem; }
        li { font-size: 0.9rem; color: #4b5563; margin-bottom: 0.3rem; }
        .back { display: inline-block; margin-top: 2rem; font-size: 0.85rem; color: #2D6A2F; font-weight: 600; text-decoration: none; }
        .back:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Privacy Policy</h1>
        <p class="updated">Last updated: July 7, 2026</p>

        <p>HarvestHaul Logistics Solutions ("we," "us," or "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your personal information when you use our platform.</p>

        <h2>1. Information We Collect</h2>
        <ul>
            <li><strong>Account Information:</strong> Name, email address, phone number, role, and password.</li>
            <li><strong>Profile Information:</strong> Farm location coordinates, company details, business permits, and compliance documents.</li>
            <li><strong>Usage Data:</strong> Listing activity, transaction history, chat messages, and GPS location during active trips.</li>
            <li><strong>Device Information:</strong> Browser type, device type, and IP address for analytics and security.</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <ul>
            <li>To provide and maintain the platform's core functionality</li>
            <li>To facilitate crop listings, negotiations, logistics pooling, and deliveries</li>
            <li>To broadcast GPS location to relevant parties during active transport trips</li>
            <li>To verify user identities and compliance documents</li>
            <li>To generate analytics and improve platform performance</li>
            <li>To communicate important account and service updates</li>
        </ul>

        <h2>3. GPS & Location Data</h2>
        <p>GPS data is collected from drivers during active trips and shared in real-time with the dispatching logistics partner, the farmer awaiting pickup, and the buyer awaiting delivery. Location data is retained for trip records and audit purposes. You may disable GPS at any time, but this will affect tracking functionality.</p>

        <h2>4. Data Sharing</h2>
        <p>We share information only with relevant parties within the platform:</p>
        <ul>
            <li>Farmers' listings are visible to logistics partners and buyers</li>
            <li>GPS tracking is shared with logistics dispatchers, farmers, and buyers during active trips</li>
            <li>Documents are shared with platform admins for verification</li>
        </ul>
        <p>We do not sell your personal data to third parties.</p>

        <h2>5. Data Security</h2>
        <p>We implement industry-standard security measures including encryption, access controls, and regular audits. However, no system is completely secure, and we cannot guarantee absolute protection of your data.</p>

        <h2>6. Data Retention</h2>
        <p>We retain your account data for as long as your account is active. Trip records, transaction logs, and audit trails are retained for compliance purposes. You may request deletion of your account, subject to applicable legal retention requirements.</p>

        <h2>7. Your Rights</h2>
        <p>You may access, update, or delete your personal information through your account settings. For additional requests, contact us at <strong>pineda.raymanuel@gmail.com</strong>.</p>

        <h2>8. Changes</h2>
        <p>We may update this policy at any time. Material changes will be communicated via email or platform notification.</p>

        <h2>9. Contact</h2>
        <p>For questions about this policy, contact us at <strong>pineda.raymanuel@gmail.com</strong>.</p>

        <a href="{{ url()->previous() === url()->current() ? url('/') : url()->previous() }}" class="back">&larr; Back</a>
    </div>
</body>
</html>
