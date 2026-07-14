<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions — HarvestHaul</title>
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
        h1 { font-size: 1.75rem; font-weight: 700; color: #3A7D44; margin-bottom: 0.25rem; }
        .updated { font-size: 0.8rem; color: #9ca3af; margin-bottom: 2rem; }
        h2 { font-size: 1.1rem; font-weight: 600; color: #1b1b18; margin-top: 2rem; margin-bottom: 0.5rem; }
        p { font-size: 0.9rem; color: #4b5563; margin-bottom: 1rem; }
        ul { margin: 0.5rem 0 1rem 1.5rem; }
        li { font-size: 0.9rem; color: #4b5563; margin-bottom: 0.3rem; }
        .back { display: inline-block; margin-top: 2rem; font-size: 0.85rem; color: #3A7D44; font-weight: 600; text-decoration: none; }
        .back:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Terms and Conditions</h1>
        <p class="updated">Last updated: July 7, 2026</p>

        <p>These Terms and Conditions govern your use of the HarvestHaul platform operated by HarvestHaul Logistics Solutions ("we," "us," or "our"). By registering or using the platform, you agree to these terms.</p>

        <h2>1. Accounts & Registration</h2>
        <p>You must provide accurate, complete information when creating an account. You are responsible for maintaining the confidentiality of your credentials. You must be at least 18 years old to register.</p>

        <h2>2. User Roles & Responsibilities</h2>
        <p>Each user role (Farmer, Logistics Partner, Cooperative, Buyer, Driver, Admin) carries specific responsibilities as outlined in the platform. Farmers list harvests, Logistics facilitate transport, Buyers purchase crops, and Drivers execute deliveries. You may only use features relevant to your assigned role.</p>

        <h2>3. Posts & Transactions</h2>
        <p>Crop posts must be accurate in volume, quality, and price. All financial transactions are arranged directly between parties. HarvestHaul facilitates connection and coordination but is not a party to any sale or transport contract between users.</p>

        <h2>4. Prohibited Conduct</h2>
        <ul>
            <li>Providing false or misleading information</li>
            <li>Manipulating prices or colluding to bypass platform processes</li>
            <li>Harassing, threatening, or abusing other users</li>
            <li>Using the platform for any unlawful purpose</li>
        </ul>

        <h2>5. Limitation of Liability</h2>
        <p>HarvestHaul is provided on an "as is" basis. We make no warranties regarding the accuracy of posts, the quality of crops, or the performance of logistics services. We are not liable for any indirect, incidental, or consequential damages arising from your use of the platform.</p>

        <h2>6. Termination</h2>
        <p>We reserve the right to suspend or terminate accounts that violate these terms or engage in fraudulent activity. Users may delete their accounts at any time via their profile settings.</p>

        <h2>7. Changes</h2>
        <p>We may update these terms at any time. Continued use of the platform after changes constitutes acceptance of the revised terms.</p>

        <h2>8. Contact</h2>
        <p>For questions, contact us at <strong>pineda.raymanuel@gmail.com</strong>.</p>

        <a href="{{ url()->previous() === url()->current() ? url('/') : url()->previous() }}" class="back">&larr; Back</a>
    </div>
</body>
</html>
