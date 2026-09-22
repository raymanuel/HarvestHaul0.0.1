<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>HarvestHaul Cooperative Application Update</title>
<style>
    body { font-family: 'Segoe UI', system-ui, sans-serif; background: #F3F4F6; margin: 0; padding: 0; color: #374151; }
    .wrap { max-width: 560px; margin: 32px auto; background: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(22,40,60,0.12); }
    .head { background: linear-gradient(135deg, #16283C, #24384F); padding: 28px 32px; text-align: center; }
    .head h1 { margin: 0; color: #FFFFFF; font-size: 22px; font-weight: 800; letter-spacing: 0.5px; }
    .head p { margin: 6px 0 0; color: #B6C2D6; font-size: 13px; }
    .body { padding: 28px 32px; font-size: 15px; line-height: 1.65; }
    .body p { margin: 0 0 14px; }
    .box { border: 1px solid #E5E7EB; border-radius: 12px; padding: 18px 20px; margin: 18px 0; background: #F9FAFB; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .b-approved { background: #D1FAE5; color: #065F46; }
    .b-rejected { background: #FEE2E2; color: #991B1B; }
    .b-request { background: #FEF3C7; color: #92400E; }
    .b-suspended { background: #F3F4F6; color: #374151; }
    .b-resubmitted { background: #DBEAFE; color: #1E40AF; }
    .footer { padding: 18px 32px; text-align: center; font-size: 12px; color: #9CA3AF; border-top: 1px solid #EEF2F7; }
</style>
</head>
<body>
<div class="wrap">
    <div class="head"><h1>HarvestHaul</h1><p>Cooperative Application Update</p></div>
    <div class="body">
        <p>Hello {{ $cooperative->coopAdminUser?->name ?? 'there' }},</p>
        <p>Here is an update on your cooperative application for <strong>{{ $cooperative->name }}</strong>.</p>
        <div class="box">
            <span class="badge b-{{ $status }}">{{ $cooperative->statusLabel() }}</span>
            @if(! empty($reason))
                <p style="margin-top:14px;color:#7C2D12;"><strong>Note:</strong> {{ $reason }}</p>
            @endif
        </div>
        @switch($status)
            @case('approved')
                <p>Your cooperative has been <strong>approved</strong> and can now operate on HarvestHaul.</p>
                <p><strong>What to expect next:</strong> sign in and your cooperative workspace is open — you can add members, trucks, and staff and start operating.</p>
                @break
            @case('rejected')
                <p>We&rsquo;re sorry &mdash; your cooperative was not approved this time.</p>
                <p><strong>What to expect next:</strong> review the reason above, correct your details or documents, and resubmit from your status screen. Resubmitted applications go straight back into the review queue.</p>
                @break
            @case('request_info')
                <p>We need a little more information before we can make a decision.</p>
                <p><strong>What to expect next:</strong> check the note above, update your application, and resubmit. Your application stays in review until you respond.</p>
                @break
            @case('suspended')
                <p>Your cooperative has been <strong>suspended</strong> and its operations are paused.</p>
                <p><strong>What to expect next:</strong> your admin and staff can no longer operate until an administrator reviews and approves the cooperative again.</p>
                @break
            @case('resubmitted')
                <p>We received your resubmitted application. It is now back in the review queue.</p>
                <p><strong>What to expect next:</strong> an administrator will review it again and you will get an email as soon as there is a decision.</p>
                @break
            @default
                <p>Your application status has been updated.</p>
                <p><strong>What to expect next:</strong> check your status screen for the latest details and any requested information.</p>
        @endswitch
        <p style="margin-top:20px;color:#6B7280;font-size:13px;">If you have any questions, reply to this email or reach out to your HarvestHaul point of contact.</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} HarvestHaul. All rights reserved.</div>
</div>
</body>
</html>
