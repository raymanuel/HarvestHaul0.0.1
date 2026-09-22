<?php

$f = "C:\\Users\\ADMIN\\HarvestHaul0.0.1\\app\\Http\\Controllers\\Admin\\CooperativeVerificationController.php";
$s = file_get_contents($f); if ($s === false) { fwrite(STDERR, "STOP read\n"); exit(1); }

$anchors = [
    'approve' => "        \$this->log(\$request, \$cooperative, 'approved', 'Cooperative approved and can now operate on the platform.');\n\n        return back()->with('success', \"{\$cooperative->name} was approved. The cooperative admin can open their workspace and add people.\");",
    'reject' => "        \$this->log(\$request, \$cooperative, 'rejected', 'Cooperative rejected. Reason: '.\$data['rejection_reason']);\n\n        return back()->with('success', \"{\$cooperative->name} was rejected. The applicant sees the reason and can register again with corrected documents.\");",
    'requestInfo' => "        \$this->log(\$request, \$cooperative, 'request_info', 'Requested more information: '.\$data['admin_notes']);\n\n        return back()->with('success', \"More information was requested from {\$cooperative->name}. It stays in Under Review until they respond or you decide.\");",
    'suspend' => "        \$this->log(\$request, \$cooperative, 'suspended', 'Cooperative suspended from platform operations.');\n\n        return back()->with('success', \"{\$cooperative->name} was suspended. Its admin and staff can no longer operate until you approve it again.\");",
];

$injections = [
    'approve' => "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'approved', null, \$request->user()->name));",
    'reject' => "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'rejected', \$data['rejection_reason'], \$request->user()->name));",
    'requestInfo' => "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'request_info', \$data['admin_notes'], \$request->user()->name));",
    'suspend' => "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, 'suspended', null, \$request->user()->name));",
];

foreach ($anchors as $key => $anchor) {
    if (strpos($s, $anchor) === false) { fwrite(STDERR, "STOP anchor $key\n"); exit(1); }
    if (strpos($s, $injections[$key]) !== false) { fwrite(STDOUT, "$key: already wired\n"); continue; }
    /* anchor begins with the log() line; prepend mail send directly above it */
    $s = str_replace($anchor, "        Mail::to(\$cooperative->coopAdminUser->email)->send(new CooperativeStatusMail(\$cooperative, " . stringify($key) . "));\n" . $anchor, $s);
}
file_put_contents($f, $sItalian);

$final = file_get_contents($f);
echo "approve send:   " . (strpos($final, "new CooperativeStatusMail(\$cooperative, 'approved'") !== false ? "PASS" : "FAIL") . "\n";
echo "reject send:    " . (strpos($final, "new CooperativeStatusMail(\$cooperative, 'rejected'") !== false ? "PASS" : "FAIL") . "\n";
echo "request_info:   " . (strpos($final, "new CooperativeStatusMail(\$cooperative, 'request_info'") !== false ? "PASS" : "FAIL") . "\n";
echo "suspended send: " . (strpos($final, "new CooperativeStatusMail(\$cooperative, 'suspended'") !== false ? "PASS" : "FAIL") . "\n";
echo "Mail import:    " . (strpos($final, "use App\\Mail\\CooperativeStatusMail;") !== false && strpos($final, "use Illuminate\\Support\\Facades\\Mail;") !== false ? "PASS" : "FAIL") . "\n";

passthru("php -l " . $f);

function stringify(string $k): string {
    return match ($k) {
        'approve' => "'approved', null, \$request->user()->name",
        'reject' => "'rejected', \$data['rejection_reason'], \$request->user()->name",
        'requestInfo' => "'request_info', \$data['admin_notes'], \$request->user()->name",
        'suspend' => "'suspended', null, \$request->user()->name",
    };
}
