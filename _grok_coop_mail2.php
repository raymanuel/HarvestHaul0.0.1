<?php

$root = "C:/Users/ADMIN/HarvestHaul0.0.1";
chdir($root Conduct);

/* ============ (A) CooperativeVerificationController: wire the 4 status mails ============ */
$f = "app/Http/Controllers/Admin/CooperativeVerificationController.php";
$s = file_get_contents($f);
if ($s === false) { fwrite(STDERR, "STOP read verification controller\n"); exit(1); }

/* (A1) imports: mailable + Mail facade after the Request import (byte-exact anchor) */
$impAnchor = "use Illuminate\\Http\\Request;";
if (strpos($s, $impAnchor) === false) { fwrite(STDERR, "STOP import anchor\n"); exit(1); }
foreach ([
    "use App\\Mail\\CooperativeStatusMail;",
    "use Illuminate\\Support\\Facades\\Mail;",
] as $one) {
    if (strpos($s, $one) === false) {
        $s = str_replace($impAnchor, $one . "\n" . $impAnchor, $s);
    }
}

/* (A2) approve() -> send 'approved' after the update block */
$appAnchor = "        return back()->with('success', \"{$cooperative->name} was approved and can now operate on the platform.\");";
if (strpos($s, $appAnchor) === false) { fwrite(STDERR, "STOP approve anchor\n"); exit(1); }
if (strpos($s, "'approved', \$cooperative->coopAdminUser->email') === false missing") {}
if (strpos($s, "send(new CooperativeStatusMail(\$cooperative, 'approved'") === false) {
    $s = str_replace(
        $appAnchor,
        "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'approved', null, \$request->user()->name));\n\n        return back()->with('success', \"{$cooperative->name} was approved and can now operate on the platform.\");",
        $s
    );
}

/* (A3) reject() -> send 'rejected' with the rejection reason */
$rejAnchor = "        return back()->with('success', \"{$cooperative->name} was rejected. The applicant will see the reason and can resubmit with corrected documents.\");";
if (strpos($s, $rejAnchor) === false) { fwrite(STDERR, "STOP reject anchor\n"); exit(1); }
if (strpos($s, "send(new CooperativeStatusMail(\$cooperative, 'rejected'") === false) {
    $s = str_replace(
        $rejAnchor,
        "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'rejected', \$data['rejection_reason'], \$request->user()->name));\n\n        return back()->with('success', \"{$cooperative->name} was rejected. The applicant will see the reason and can resubmit with corrected documents.\");",
        $s
    );
}

/* (A4) requestInfo() -> send 'request_info' with the requested-info reason */
$reqAnchor = "        return back()->with('success', \"{$cooperative->name} stays in Under Review. More information was requested from the cooperative admin.\");";
if (strpos($s, $reqAnchor) === false) { fwrite(STDERR, "STOP requestInfo anchor\n"); exit(1); }
if (strpos($s, "send(new CooperativeStatusMail(\$cooperative, 'request_info'") === false) {
    $s = str_replace(
        $reqAnchor,
        "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'request_info', \$data['admin_notes'], \$request->user()->name));\n\n        return back()->with('success', \"{$cooperative->name} stays in Under Review. More information was requested from the cooperative admin.\");",
        $s
    );
}

/* (A5) suspend() -> send 'suspended' */
$susAnchor = "        return back()->with('success', \"{$cooperative->name} was suspended. Its admin and staff can no longer operate until it is approved again.\");";
if (strpos($s, $susAnchor) === false) { fwrite(STDERR, "STOP suspend anchor\n"); exit(1); }
if (strpos($s, "send(new CooperativeStatusMail(\$cooperative, 'suspended'") === false) {
    $s = str_replace(
        $susAnchor,
        "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'suspended', null, \$request->user()->name));\n\n        return back()->with('success', \"{$cooperative->name} was suspended. Its admin and staff can no longer operate until it is approved again.\");",
        $s
    );
}

file_put_contents($f, $s);

/* build cooperative_status blade if missing */
if (!is_file("resources/views/emails/cooperative-status.blade.php")) {
    file_put_contents("resources/views/emails/cooperative-status.blade.php", '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Cooperative Status</title></head><body style="font-family:Segoe UI, system-ui, sans-serif;background:#f4f4f4;margin:0;padding:0"><div style="max-width:480px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden"><div style="background:#16283C;padding:24px;text-align:center"><h1 style="margin:0;color:#fff;font-size:20px">HarvestHaul</h1></div><div style="padding:24px"><p>Hello {{ $cooperative->coopAdminUser->name }},</p><p>Your cooperative <strong>{{ $cooperative->name }}</strong> has an update.</p>@if($reason)<p style="background:#FDF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px">{{ $reason }}</p>@endif<p>Log in to your cooperative status screen to view details and resubmit if needed.</p></div></div></body></html>');
}

echo "verification controller sends:";
echo "\n  approved   " . (strpos($s, "new CooperativeStatusMail(\$cooperative, 'approved'") !== false ? "ok" : "MISS");
echo "\n  rejected   " . (strpos($s, "new CooperativeStatusMail(\$cooperative, 'rejected'") !== false ? "ok" : "MISS");
echo "\n  request_info " . (strpos($s, "new CooperativeStatusMail(\$cooperative, 'request_info'") !== false ? "ok" : "MISS");
echo "\n  suspended  " . (strpos($s, "new CooperativeStatusMail(\$cooperative, 'suspended'") !== false ? "ok" : "MISS");
echo "\n  import ok  " . (strpos($s, "use App\\Mail\\CooperativeStatusMail;") !== false ? "ok" : "MISS");
echo "\n  Mail use   " . (strpos($s, "use Illuminate\\Support\\Facades\\Mail;") !== false ? "ok" : "MISS");
echo "\n";
php $f;
echo "\n";
