<x-layout title="Cooperative Status — HarvestHaul">
    <main class="mx-auto max-w-2xl px-4 py-10">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if(! $cooperative)
                <div class="px-6 py-12 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-6 w-6 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>
                    </div>
                    <h1 class="mt-4 text-lg font-bold text-slate-900">No cooperative application found</h1>
                    <p class="mt-2 text-sm text-slate-500">Your account isn't linked to a cooperative application yet. If you just registered as a cooperative, make sure you completed the cooperative details step.</p>
                    <a href="{{ route('home') }}" class="mt-6 inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Go to home</a>
                </div>
            @elseif($cooperative->isApproved())
                <div class="px-6 py-12 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <h1 class="mt-4 text-lg font-bold text-slate-900">Your cooperative is approved</h1>
                    <p class="mt-2 text-sm text-slate-500">{{ $cooperative->name }} is fully approved. Head to your workspace to get started.</p>
                    <a href="{{ route('coop.dashboard') }}" class="mt-6 inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Open cooperative workspace</a>
                </div>
            @else
                @if($cooperative->isPending() || $cooperative->isUnderReview())
                    <header class="border-b border-slate-200 px-6 py-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Application pending</p>
                        <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $cooperative->name }}</h1>
                        <p class="mt-1 text-sm text-slate-500">Your cooperative application is in the review queue. A HarvestHaul administrator will evaluate it — you'll get an email as soon as there's a decision.</p>
                    </header>
                    <div class="flex flex-wrap gap-3 px-6 py-5">
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Status: Pending review</span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $cooperative->type }}</span>
                    </div>
                    <div class="flex flex-col gap-2 border-t border-slate-200 px-6 py-4 text-sm text-slate-600">
                        <p><span class="font-semibold text-slate-800">Official email:</span> {{ $cooperative->official_email }}</p>
                        <p><span class="font-semibold text-slate-800">Contact:</span> {{ $cooperative->contact_number }}</p>
                        <p><span class="font-semibold text-slate-800">Address:</span> {{ $cooperative->street_address }}</p>
                    </div>
                @elseif($cooperative->isRequiresRevision())
                    <header class="border-b border-slate-200 px-6 py-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Needs your input</p>
                        <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $cooperative->name }}</h1>
                        <p class="mt-1 text-sm text-slate-500">The reviewer needs more information before your cooperative can be approved. Read the note below, update your details, and resubmit.</p>
                    </header>
                    @if($cooperative->admin_notes)
                        <div class="mx-6 mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">What's needed</p>
                            <p class="mt-1 text-sm text-amber-800">{{ $cooperative->admin_notes }}</p>
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-3 px-6 py-5">
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Status: Needs revision</span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $cooperative->type }}</span>
                    </div>
                    <div class="border-t border-slate-200 px-6 py-6">
                        <button type="button" onclick="document.getElementById('resubmit-form-revision').classList.toggle('hidden')" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Edit details &amp; resubmit</button>
                        <form id="resubmit-form-revision" method="POST" action="{{ route('coop.status.resubmit') }}" enctype="multipart/form-data" class="mt-4 hidden space-y-4">
                            @csrf
                            <div>
                                <label for="rname" class="block text-sm font-medium text-slate-700">Cooperative name</label>
                                <input type="text" name="name" id="rname" value="{{ old('name', $cooperative->name) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="rtype" class="block text-sm font-medium text-slate-700">Type</label>
                                <input type="text" name="type" id="rtype" value="{{ old('type', $cooperative->type) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="rcontact_number" class="block text-sm font-medium text-slate-700">Contact number</label>
                                <input type="text" name="contact_number" id="rcontact_number" value="{{ old('contact_number', $cooperative->contact_number) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="rofficial_email" class="block text-sm font-medium text-slate-700">Official email</label>
                                <input type="email" name="official_email" id="rofficial_email" value="{{ old('official_email', $cooperative->official_email) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="rstreet_address" class="block text-sm font-medium text-slate-700">Street address</label>
                                <textarea name="street_address" id="rstreet_address" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">{{ old('street_address', $cooperative->street_address) }}</textarea>
                            </div>
                            <div>
                                <label for="rresubmission_note" class="block text-sm font-medium text-slate-700">What changed / note for reviewer (optional)</label>
                                <textarea name="resubmission_note" id="rresubmission_note" rows="3" placeholder="Tell the reviewer what you fixed or any missing details you've now provided…" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">{{ old('resubmission_note') }}</textarea>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                @foreach(['cert_document' => 'Certificate document', 'articles_document' => 'Articles of cooperation', 'bylaws_document' => 'Bylaws'] as $f => $label)
                                <div>
                                    <label for="r{{ $f }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                                    <input type="file" name="{{ $f }}" id="r{{ $f }}" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                                    @if($cooperative->{$f.'_document_path'})
                                        <p class="mt-1 text-xs text-slate-400">A {{ $label }} is already on file — upload a new one only if you're replacing it.</p>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-3 pt-1">
                                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Resubmit application</button>
                                <p class="text-xs text-slate-400">Resubmitting puts your cooperative straight back into the review queue.</p>
                            </div>
                        </form>
                    </div>
                @elseif($cooperative->isRejected())
                    <header class="border-b border-slate-200 px-6 py-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Application not approved</p>
                        <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $cooperative->name }}</h1>
                        <p class="mt-1 text-sm text-slate-500">We weren't able to approve your cooperative this time. Review the reason below, update your details, and resubmit to go back into the review queue.</p>
                    </header>
                    @if($cooperative->rejection_reason)
                        <div class="mx-6 mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Why it wasn't approved</p>
                            <p class="mt-1 text-sm text-red-700">{{ $cooperative->rejection_reason }}</p>
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-3 px-6 py-5">
                        <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Status: Rejected</span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $cooperative->type }}</span>
                    </div>
                    <div class="border-t border-slate-200 px-6 py-6">
                        <button type="button" onclick="document.getElementById('resubmit-form').classList.toggle('hidden')" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Edit details &amp; resubmit</button>
                        <form id="resubmit-form" method="POST" action="{{ route('coop.status.resubmit') }}" enctype="multipart/form-data" class="mt-4 hidden space-y-4">
                            @csrf
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700">Cooperative name</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $cooperative->name) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="type" class="block text-sm font-medium text-slate-700">Type</label>
                                <input type="text" name="type" id="type" value="{{ old('type', $cooperative->type) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="contact_number" class="block text-sm font-medium text-slate-700">Contact number</label>
                                <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number', $cooperative->contact_number) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="official_email" class="block text-sm font-medium text-slate-700">Official email</label>
                                <input type="email" name="official_email" id="official_email" value="{{ old('official_email', $cooperative->official_email) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="street_address" class="block text-sm font-medium text-slate-700">Street address</label>
                                <textarea name="street_address" id="street_address" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">{{ old('street_address', $cooperative->street_address) }}</textarea>
                            </div>
                            <div>
                                <label for="resubmission_note" class="block text-sm font-medium text-slate-700">What changed / note for reviewer (optional)</label>
                                <textarea name="resubmission_note" id="resubmission_note" rows="3" placeholder="Tell the reviewer what you fixed or any missing details you've now provided…" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">{{ old('resubmission_note') }}</textarea>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                @foreach(['cert_document' => 'Certificate document', 'articles_document' => 'Articles of cooperation', 'bylaws_document' => 'Bylaws'] as $f => $label)
                                <div>
                                    <label for="{{ $f }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                                    <input type="file" name="{{ $f }}" id="{{ $f }}" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                                    @if($cooperative->{$f.'_document_path'})
                                        <p class="mt-1 text-xs text-slate-400">A {{ $label }} is already on file — upload a new one only if you're replacing it.</p>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            <div class="flex items-center gap-3 pt-1">
                                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Resubmit application</button>
                                <p class="text-xs text-slate-400">Resubmitting puts your cooperative straight back into the review queue.</p>
                            </div>
                        </form>
                    </div>
                @elseif($cooperative->isSuspended())
                    <header class="border-b border-slate-200 px-6 py-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Suspended</p>
                        <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $cooperative->name }}</h1>
                        <p class="mt-1 text-sm text-slate-500">Your cooperative is temporarily suspended while HarvestHaul reviews the situation. Cooperative operations are paused.</p>
                    </header>
                    @if($cooperative->rejection_reason)
                        <div class="mx-6 mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reason for suspension</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $cooperative->rejection_reason }}</p>
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-3 px-6 py-5">
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Status: Suspended</span>
                    </div>
                    <div class="border-t border-slate-200 px-6 py-5 text-sm text-slate-500">
                        <p>Need help? Reach out to your HarvestHaul point of contact for more details about this suspension.</p>
                    </div>
                @endif
            @endif
        </div>
    </main>
</x-layout>
