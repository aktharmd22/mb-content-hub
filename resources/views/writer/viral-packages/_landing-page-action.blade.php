@php
    /** @var \App\Models\ViralPackage $package */
    /** @var \App\Models\ViralPackageDeliverable $d */
    $hasUrl   = filled($d->landing_page_url);
    $editable = in_array($d->stage, ['pending', 'in_progress', 'review'], true) && ! $package->isCompleted();
@endphp

<div x-data="{ landingOpen: false }">
    {{-- Pause live auto-refresh while the publish form is open --}}
    <template x-if="landingOpen"><span data-live-lock="1" style="display:none"></span></template>

    {{-- Current published link --}}
    @if($hasUrl)
        <div class="flex items-center gap-2 px-3 py-2.5 bg-ink-900/70 border border-ink-700 rounded-lg mb-3" style="min-width:0;">
            <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.828-2.828a4 4 0 015.656 0 4 4 0 010 5.656l-1.5 1.5"/></svg>
            <a href="{{ $d->landing_page_url }}" target="_blank" rel="noopener" class="text-xs text-indigo-300 hover:text-indigo-200 flex-1 truncate" title="{{ $d->landing_page_url }}">{{ $d->landing_page_url }}</a>
            <a href="{{ $d->landing_page_url }}" target="_blank" rel="noopener" class="text-xs text-emerald-400 hover:text-emerald-300 whitespace-nowrap font-medium">Open</a>
            <button type="button" onclick="copyToClipboard(@js($d->landing_page_url))" class="text-xs text-indigo-400 hover:text-indigo-300 whitespace-nowrap">Copy</button>
        </div>
    @endif

    @if($d->stage === 'approved')
        <div class="flex items-center justify-center gap-2 px-4 py-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm font-medium rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Approved by sales
        </div>
    @elseif($editable)
        @if($d->stage === 'review')
            <div x-show="!landingOpen" class="space-y-2">
                <div class="flex items-center justify-center gap-2 px-4 py-3 bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm font-medium rounded-lg">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Waiting for sales review
                </div>
                <button type="button" @click="landingOpen = true"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-ink-800 hover:bg-ink-700 border border-ink-600 text-gray-300 hover:text-gray-100 text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit / re-publish link
                </button>
            </div>
        @else
            <button type="button" x-show="!landingOpen" @click="landingOpen = true"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.828-2.828a4 4 0 015.656 0 4 4 0 010 5.656l-1.5 1.5"/></svg>
                {{ $hasUrl ? 'Update landing page link' : 'Publish landing page' }}
            </button>
        @endif

        <form x-show="landingOpen" x-cloak method="POST"
              action="{{ route('writer.viral-packages.deliverables.publish-landing', ['viralPackage' => $package, 'deliverable' => $d]) }}"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 -translate-y-1"
              x-transition:enter-end="opacity-100 translate-y-0"
              class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs text-gray-400 mb-1">Published landing page URL</label>
                <input type="url" name="landing_page_url" required placeholder="https://example.com/landing" value="{{ $d->landing_page_url }}"
                       class="w-full px-3 py-2.5 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
            </div>
            <textarea name="notes" rows="2" maxlength="1000" placeholder="Notes for sales (optional)"
                      class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 resize-none"></textarea>
            <div class="flex items-center gap-2">
                <button type="button" @click="landingOpen = false"
                        class="px-4 py-2.5 text-sm text-gray-400 hover:text-gray-200 hover:bg-ink-800 rounded-lg transition-colors">Cancel</button>
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Publish for review
                </button>
            </div>
        </form>
    @endif
</div>
