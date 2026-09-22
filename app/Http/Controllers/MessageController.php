<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\BuyerOrder;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * In-app messaging: farmer ↔ coop, delivery ↔ coop, buyer ↔ coop
     * (spec 15.2). Delivery personnel cannot start a thread with a farmer.
     * A buyer has no fixed cooperative_id (they may order from several
     * cooperatives), so their valid partners and the thread's
     * cooperative_id are resolved per-pair via a shared BuyerOrder rather
     * than a simple cooperative_id column match.
     */
    public function index()
    {
        $user = Auth::user();

        $partners = $this->validPartners($user)->map(function (User $partner) use ($user) {
            $partner->unread_count = Message::where('sender_id', $partner->id)
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->count();
            $partner->has_thread = Message::forUser($user->id, $partner->id)->exists();

            return $partner;
        })->sortByDesc(fn ($p) => $p->unread_count)
            ->values();

        return view('messages.index', compact('partners'));
    }

    public function show(User $conversation)
    {
        $user = Auth::user();
        $this->assertCoopScoped($user, $conversation);

        $cooperativeId = $this->threadCooperativeId($user, $conversation);

        $messages = Message::forUser($user->id, $conversation->id)
            ->where('cooperative_id', $cooperativeId)
            ->orderBy('created_at')
            ->get();

        // Mark incoming messages from the conversation partner as read.
        // Scoped to this thread's cooperative_id, same as $messages above —
        // keeps this query consistent with the rest of the class rather than
        // relying solely on the (sender_id, recipient_id) pair.
        Message::where('sender_id', $conversation->id)
            ->where('recipient_id', $user->id)
            ->where('cooperative_id', $cooperativeId)
            ->where('read_at', null)
            ->update(['read_at' => now()]);

        return view('messages.show', compact('conversation', 'messages'));
    }

    public function store(StoreMessageRequest $request, User $conversation)
    {
        $user = Auth::user();
        $this->assertCoopScoped($user, $conversation);

        $cooperativeId = $this->threadCooperativeId($user, $conversation);
        $isFirstMessage = Message::forUser($user->id, $conversation->id)->doesntExist();

        $message = Message::create([
            'sender_id'      => $user->id,
            'recipient_id'   => $conversation->id,
            'cooperative_id' => $cooperativeId,
            'body'           => $request->body,
            'context_type'   => $isFirstMessage ? $request->context_type : null,
            'context_id'     => $isFirstMessage ? $request->context_id : null,
        ]);

        Notification::create([
            'user_id'  => $conversation->id,
            'title'    => 'New message from '.$user->name,
            'message'  => \Illuminate\Support\Str::limit($request->body, 120).' Open the conversation to reply.',
            'link'     => route('messages.show', $conversation),
            'category' => 'message',
        ]);

        return back()->with('success', 'Message sent.');
    }

    public function poll(Request $request, User $conversation)
    {
        $user = Auth::user();
        $this->assertCoopScoped($user, $conversation);

        $cooperativeId = $this->threadCooperativeId($user, $conversation);
        $lastId = (int) ($request->query('after', 0));

        $latest = Message::forUser($user->id, $conversation->id)
            ->where('cooperative_id', $cooperativeId)
            ->orderByDesc('id')
            ->value('id');

        $unread = Message::where('sender_id', $conversation->id)
            ->where('recipient_id', $user->id)
            ->where('cooperative_id', $cooperativeId)
            ->where('read_at', null)
            ->count();

        return response()->json([
            'has_new'   => $latest > $lastId,
            'latest_id' => $latest,
            'unread'    => $unread,
        ]);
    }

    /**
     * Every user this $user is allowed to message, regardless of whether a
     * thread already exists — this is what lets a user start a brand-new
     * conversation, not just reply to one already in progress.
     */
    private function validPartners(User $user): Collection
    {
        if ($user->role === UserRole::BUYER->value) {
            $cooperativeIds = BuyerOrder::forBuyer($user->id)->pluck('cooperative_id')->unique();

            return User::where('role', UserRole::COOP_ADMIN->value)
                ->whereIn('cooperative_id', $cooperativeIds)
                ->orderBy('name')
                ->get();
        }

        if ($user->role === UserRole::COOP_ADMIN->value) {
            $buyerIds = BuyerOrder::where('cooperative_id', $user->cooperative_id)->pluck('buyer_id')->unique();

            return User::where('id', '!=', $user->id)
                ->where(function ($q) use ($user, $buyerIds) {
                    $q->where(function ($q2) use ($user) {
                        $q2->where('cooperative_id', $user->cooperative_id)
                            ->whereIn('role', [
                                UserRole::FARMER->value,
                                UserRole::DELIVERY_PERSONNEL->value,
                                UserRole::FIELD_RECEIVING->value,
                                UserRole::COOP_ADMIN->value,
                            ]);
                    });
                    if ($buyerIds->isNotEmpty()) {
                        $q->orWhereIn('id', $buyerIds);
                    }
                })
                ->orderBy('name')
                ->get();
        }

        // Farmer / Delivery Personnel / Field Receiving — only their own
        // cooperative's admins (delivery↔farmer is never allowed, spec 15.2).
        return User::where('role', UserRole::COOP_ADMIN->value)
            ->where('cooperative_id', $user->cooperative_id)
            ->orderBy('name')
            ->get();
    }

    private function assertCoopScoped(User $user, User $other): void
    {
        if ($other->id === $user->id) {
            abort(403, 'Cannot message yourself.');
        }

        if (! $this->validPartners($user)->contains('id', $other->id)) {
            abort(403, 'You are not allowed to message this person.');
        }
    }

    /** A buyer has no cooperative_id of their own — use the other party's. */
    private function threadCooperativeId(User $user, User $other): ?int
    {
        return $user->cooperative_id ?? $other->cooperative_id;
    }
}
