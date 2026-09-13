<?php

namespace App\Http\Controllers;

use App\Models\OutboundOrder;
use App\Models\PoolingJobStatus;
use App\Traits\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutboundTrackController extends Controller
{
    use Notifiable;

    public function show(string $token)
    {
        $order = OutboundOrder::where('tracking_token', $token)
            ->with(['customerCard', 'orderLines', 'poolingJob.truck', 'poolingJob.driver'])
            ->first();

        abort_unless($order, 404);

        return view('outbound.track', compact('order'));
    }

    public function confirm(Request $request, string $token)
    {
        $order = OutboundOrder::where('tracking_token', $token)->first();

        if (!$order || $order->status !== 'awaiting_confirmation') {
            return back()->withErrors(['order' => 'This delivery is not awaiting confirmation.']);
        }

        $job = $order->poolingJob;
        if (!$job || $job->status !== PoolingJobStatus::AWAITING_CONFIRMATION) {
            return back()->withErrors(['order' => 'This delivery is not awaiting confirmation.']);
        }

        DB::transaction(function () use ($order, $job) {
            $order->update([
                'status'         => 'completed',
                'tracking_token' => null,
                'confirmed_at'   => now(),
                'completed_at'   => now(),
            ]);

            $job->update([
                'status'       => PoolingJobStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            if ($job->truck) {
                $job->truck->update(['status' => 'available']);
            }

            self::logAudit(null, 'customer_confirmed_outbound', 'outbound_orders', $order->id, "Customer {$order->customerCard->name} confirmed receipt for outbound order #{$order->id}.");

            if ($order->logisticsProfile && $order->logisticsProfile->user_id) {
                self::sendNotification(
                    $order->logisticsProfile->user_id,
                    'Customer confirmed delivery',
                    "{$order->customerCard->name} confirmed receipt of outbound order #{$order->id}. This order is now complete.",
                    route('coop.outbound.show', $order)
                );
            }
        });

        return redirect()->route('outbound.track.complete')
            ->with('success', "Delivery confirmed. Thank you for your business! Order #{$order->id} is now complete.");
    }

    public function complete()
    {
        return view('outbound.track-complete');
    }

    public function ping(string $token)
    {
        $order = OutboundOrder::where('tracking_token', $token)->first();
        abort_unless($order, 404);

        $tracking = $order->poolingJob?->latestTracking;

        return response()->json([
            'status' => $tracking ? 'success' : 'empty',
            'data'   => $tracking ? [
                'latitude'  => (float) $tracking->latitude,
                'longitude' => (float) $tracking->longitude,
                'speed_kmh' => (float) ($tracking->speed_kmh ?? 0),
                'posted_at' => $tracking->posted_at->toIso8601String(),
            ] : null,
            'job_status' => $order->poolingJob?->status?->value,
            'order_status' => $order->status,
        ]);
    }
}