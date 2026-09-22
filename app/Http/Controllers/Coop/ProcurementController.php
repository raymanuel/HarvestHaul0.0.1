<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CropAvailability;
use App\Models\FarmerPayment;
use App\Models\Notification;
use App\Models\ReceivingRecord;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProcurementController extends Controller
{
    /**
     * Cooperative — procurement queue.
     * Receiving records the cooperative has not yet confirmed. Once priced
     * and confirmed, the farmer's payout (weight × buying price) is locked
     * in and a CropAvailability is seeded for buyer ordering.
     */
    public function index()
    {
        $cooperativeId = $this->cooperativeId();

        $awaitingPrice = ReceivingRecord::with(['haulJob', 'farmer', 'crop', 'cropGrade'])
            ->where('cooperative_id', $cooperativeId)
            ->where('status', ReceivingRecord::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        $awaitingConfirmation = ReceivingRecord::with(['haulJob', 'farmer', 'crop', 'cropGrade'])
            ->where('cooperative_id', $cooperativeId)
            ->where('status', ReceivingRecord::STATUS_PRICED)
            ->orderBy('created_at')
            ->get();

        $recent = ReceivingRecord::with(['haulJob', 'farmer', 'crop'])
            ->where('cooperative_id', $cooperativeId)
            ->where('status', ReceivingRecord::STATUS_CONFIRMED)
            ->orderByDesc('confirmed_at')
            ->limit(12)
            ->get();

        $totalConfirmed = ReceivingRecord::where('cooperative_id', $cooperativeId)
            ->where('status', ReceivingRecord::STATUS_CONFIRMED)
            ->sum('total_amount');

        return view('coop.procurement.index', compact('awaitingPrice', 'awaitingConfirmation', 'recent', 'totalConfirmed'));
    }

    public function show(ReceivingRecord $receivingRecord)
    {
        $this->authorizeCoop($receivingRecord);
        $receivingRecord->load(['haulJob.stops.haulRequest', 'farmer', 'crop', 'cropGrade', 'confirmer', 'canceller', 'availability', 'payments.recorder']);

        return view('coop.procurement.show', compact('receivingRecord'));
    }

    public function setPrice(Request $request, ReceivingRecord $receivingRecord)
    {
        $this->authorizeCoop($receivingRecord);

        if ($receivingRecord->status !== ReceivingRecord::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'receiving' => 'A price can only be set for a record that is still awaiting price.',
            ]);
        }

        $data = $request->validate([
            'buying_price_per_kg' => 'required|numeric|min:0',
            'remarks'               => 'nullable|string|max:1000',
        ]);

        $weight = (float) $receivingRecord->actual_weight_kg;
        $price = (float) $data['buying_price_per_kg'];
        $total = $weight * $price;

        $receivingRecord->update([
            'buying_price_per_kg' => $price,
            'total_amount'        => $total,
            'remarks'             => $data['remarks'] ?? $receivingRecord->remarks,
            'status'              => ReceivingRecord::STATUS_PRICED,
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'price_receiving',
            'target_type' => 'receiving_record',
            'target_id'   => $receivingRecord->id,
            'notes'       => "Price set for {$receivingRecord->farmer?->name}: {$weight} kg × ₱{$price}/kg = ₱".number_format($total, 2).'. Awaiting confirmation.',
        ]);

        return redirect()->route('coop.procurement.show', $receivingRecord)
            ->with('success', 'Price recorded. Confirm the procurement to notify the farmer and lock in the payout.');
    }

    public function confirm(Request $request, ReceivingRecord $receivingRecord)
    {
        $this->authorizeCoop($receivingRecord);

        if ($receivingRecord->status !== ReceivingRecord::STATUS_PRICED) {
            throw ValidationException::withMessages([
                'receiving' => $receivingRecord->status === ReceivingRecord::STATUS_PENDING
                    ? 'Set a buying price before confirming.'
                    : 'This record is already confirmed.',
            ]);
        }

        $data = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $weight = (float) $receivingRecord->actual_weight_kg;
        $price = (float) $receivingRecord->buying_price_per_kg;
        $total = (float) $receivingRecord->total_amount;

        $receivingRecord->update([
            'remarks'      => $data['remarks'] ?? $receivingRecord->remarks,
            'status'       => ReceivingRecord::STATUS_CONFIRMED,
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);

        // Crop becomes sellable inventory only once the purchase is confirmed.
        CropAvailability::create([
            'cooperative_id'      => $receivingRecord->cooperative_id,
            'receiving_record_id' => $receivingRecord->id,
            'crop_id'             => $receivingRecord->crop_id,
            'crop_variety_id'     => $receivingRecord->crop_variety_id,
            'crop_grade_id'       => $receivingRecord->crop_grade_id,
            'quantity_kg'         => $weight,
            'selling_price_per_kg' => null,
            'status'              => CropAvailability::STATUS_AVAILABLE,
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'confirm_receiving',
            'target_type' => 'receiving_record',
            'target_id'   => $receivingRecord->id,
            'notes'       => "Procurement confirmed for {$receivingRecord->farmer?->name}: {$weight} kg × ₱{$price}/kg = ₱".number_format($total, 2).'.',
        ]);

        // Notify the farmer: payout info + what to expect next.
        Notification::create([
            'user_id'  => $receivingRecord->farmer_id,
            'title'    => 'Procurement confirmed',
            'message'  => "Your cooperative confirmed the pickup: {$weight} kg at ₱".number_format($price, 2)."/kg (₱".number_format($total, 2)."). See the payout details in your farmer dashboard.",
            'link'     => route('farmer.dashboard'),
            'category' => 'procurement',
        ]);

        return redirect()->route('coop.procurement.index')
            ->with('success', 'Procurement confirmed. The farmer was notified.')
            ->with('next_steps', [
                'title'   => 'Procurement confirmed',
                'message' => "{$weight} kg × ₱".number_format($price, 2)." = ₱".number_format($total, 2)." is locked in for the farmer.",
                'steps'   => ['The farmer can see the payout in their dashboard.', 'The crop is available to buyers until it is reserved.'],
                'cta'     => ['label' => 'Back to procurement queue', 'url' => route('coop.procurement.index')],
            ]);
    }

    public function recordPayment(Request $request, ReceivingRecord $receivingRecord)
    {
        $this->authorizeCoop($receivingRecord);

        if (! $receivingRecord->isConfirmed()) {
            throw ValidationException::withMessages([
                'receiving' => 'Confirm the procurement before recording a farmer payment.',
            ]);
        }

        $balanceDue = $receivingRecord->balanceDue();
        if ($balanceDue <= 0) {
            throw ValidationException::withMessages([
                'receiving' => 'This purchase is already fully paid.',
            ]);
        }

        $data = $request->validate([
            'amount'    => ['required', 'numeric', 'min:0.01', 'max:'.$balanceDue],
            'method'    => ['required', 'in:'.implode(',', [FarmerPayment::METHOD_CASH, FarmerPayment::METHOD_BANK_TRANSFER, FarmerPayment::METHOD_E_WALLET])],
            'reference' => 'nullable|string|max:100',
            'remarks'   => 'nullable|string|max:500',
        ]);

        $payment = FarmerPayment::create([
            'receiving_record_id' => $receivingRecord->id,
            'cooperative_id'      => $receivingRecord->cooperative_id,
            'amount'              => $data['amount'],
            'method'              => $data['method'],
            'reference'           => $data['reference'] ?? null,
            'paid_at'             => now(),
            'recorded_by'         => Auth::id(),
            'remarks'             => $data['remarks'] ?? null,
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'record_farmer_payment',
            'target_type' => 'receiving_record',
            'target_id'   => $receivingRecord->id,
            'notes'       => "Payment of ₱".number_format($payment->amount, 2)." ({$data['method']}) recorded for {$receivingRecord->farmer?->name}.",
        ]);

        $newBalance = $receivingRecord->fresh()->balanceDue();
        Notification::create([
            'user_id'  => $receivingRecord->farmer_id,
            'title'    => 'Payment received',
            'message'  => "Your cooperative recorded a payment of ₱".number_format($payment->amount, 2).". Remaining balance: ₱".number_format($newBalance, 2).'.',
            'link'     => route('farmer.dashboard'),
            'category' => 'procurement',
        ]);

        return redirect()->route('coop.procurement.show', $receivingRecord)
            ->with('success', 'Payment recorded.');
    }

    public function cancel(Request $request, ReceivingRecord $receivingRecord)
    {
        $this->authorizeCoop($receivingRecord);

        if (! in_array($receivingRecord->status, [ReceivingRecord::STATUS_PENDING, ReceivingRecord::STATUS_PRICED], true)) {
            throw ValidationException::withMessages([
                'receiving' => 'Confirmed procurements cannot be cancelled.',
            ]);
        }

        $availability = $receivingRecord->availability;
        if ($availability && (float) $availability->sold_kg > 0) {
            throw ValidationException::withMessages([
                'receiving' => 'This crop has already been sold to a buyer and cannot be cancelled.',
            ]);
        }

        $data = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $receivingRecord->update([
            'status'              => ReceivingRecord::STATUS_CANCELLED,
            'cancelled_by'        => Auth::id(),
            'cancelled_at'        => now(),
            'cancellation_reason' => $data['cancellation_reason'],
        ]);

        if ($availability) {
            $availability->update(['status' => CropAvailability::STATUS_ARCHIVED]);
        }

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'cancel_receiving',
            'target_type' => 'receiving_record',
            'target_id'   => $receivingRecord->id,
            'notes'       => "Procurement cancelled for {$receivingRecord->farmer?->name}: {$data['cancellation_reason']}",
        ]);

        return redirect()->route('coop.procurement.index')
            ->with('success', 'Procurement cancelled.');
    }

    private function cooperativeId(): int
    {
        $id = Auth::user()?->cooperative_id;
        if (! $id) {
            abort(403, 'You are not an active cooperative admin.');
        }

        return $id;
    }

    private function authorizeCoop(ReceivingRecord $record): void
    {
        if ($record->cooperative_id !== Auth::user()?->cooperative_id) {
            abort(403, 'This receiving record does not belong to your cooperative.');
        }
    }
}
