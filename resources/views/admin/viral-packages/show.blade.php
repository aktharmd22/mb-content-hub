<x-app-layout>
    <x-slot name="header">Viral package — {{ $package->client?->displayName() }}</x-slot>
    <x-slot name="title">{{ $package->client?->displayName() }} package</x-slot>

    <div class="p-6 max-w-6xl">

        <a href="{{ route('admin.viral-packages.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 mb-3 transition-colors">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to packages
        </a>

        {{-- Header --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        @if($package->isCompleted())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">Completed</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">Active</span>
                        @endif
                    </div>
                    <h1 class="text-xl font-medium text-gray-100">{{ $package->client?->displayName() }}</h1>
                    @if($package->client?->secondaryName())
                        <p class="text-sm text-gray-500">{{ $package->client->secondaryName() }}</p>
                    @endif
                </div>
                @include('partials.viral-package-progress', ['package' => $package])
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 pt-4 mt-4 border-t border-gray-100 dark:border-gray-800">
                <div>
                    <p class="text-xs text-gray-500">Sales rep</p>
                    <div class="flex items-center gap-1.5 mt-1">
                        <span class="w-5 h-5 rounded-full bg-indigo-500/15 text-indigo-300 flex items-center justify-center text-[9px] font-semibold flex-shrink-0">
                            {{ $package->salesRep ? strtoupper(substr($package->salesRep->name, 0, 1)) : '—' }}
                        </span>
                        <p class="text-sm text-gray-100 truncate">{{ $package->salesRep?->name ?? '—' }}</p>
                    </div>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Tech team</p>
                    <div class="flex items-center gap-1.5 mt-1">
                        @if($package->techTeam)
                            <span class="w-5 h-5 rounded-full bg-violet-500/15 text-violet-300 flex items-center justify-center text-[9px] font-semibold flex-shrink-0">
                                {{ strtoupper(substr($package->techTeam->name, 0, 1)) }}
                            </span>
                            <p class="text-sm text-gray-100 truncate">{{ $package->techTeam->name }}</p>
                        @else
                            <p class="text-sm text-gray-500 italic">Unassigned</p>
                        @endif
                    </div>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Created</p>
                    <p class="text-sm text-gray-100 mt-1">{{ $package->created_at->format('M j, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Delivered</p>
                    <p class="text-sm text-gray-100 mt-1">{{ $package->completed_at?->format('M j, Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Assets</p>
                    <p class="text-sm text-gray-100 mt-1">{{ $package->assets->count() }} items</p>
                </div>
            </div>
        </div>

        {{-- Admin reassignment (superior control) --}}
        @unless($package->isCompleted())
            <div class="bg-white dark:bg-gray-900 border border-indigo-500/30 rounded-lg p-5 mb-6">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h3 class="text-sm font-medium text-gray-100">Reassign (admin)</h3>
                </div>
                <p class="text-xs text-gray-500 mb-4">Change the sales rep and the tech owner for each content type. Updates take effect immediately (already-approved items keep their owner).</p>
                @php
                    $ownerOf = fn ($kind) => optional($package->deliverables->firstWhere('kind', $kind))->assigned_to;
                    $reassignTypes = [
                        ['key' => 'article',     'label' => 'Article owner'],
                        ['key' => 'social_post', 'label' => 'Posts owner'],
                        ['key' => 'reel',        'label' => 'Reels owner'],
                    ];
                    if ($package->deliverables->firstWhere('kind', 'landing_page')) {
                        $reassignTypes[] = ['key' => 'landing_page', 'label' => 'Landing page owner'];
                    }
                @endphp
                <form method="POST" action="{{ route('admin.viral-packages.reassign', $package) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1.5">Sales rep</label>
                            <select name="sales_rep_id"
                                    class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                                @foreach($salesReps as $u)
                                    <option value="{{ $u->id }}" @selected($package->sales_rep_id == $u->id)>{{ $u->name }}{{ $u->role === 'admin' ? ' (admin)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @foreach($reassignTypes as $row)
                            <div>
                                <label class="block text-xs font-medium text-gray-300 mb-1.5">{{ $row['label'] }}</label>
                                <select name="assignees[{{ $row['key'] }}]"
                                        class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                                    <option value="">— Select —</option>
                                    @foreach($techTeam as $u)
                                        <option value="{{ $u->id }}" @selected($ownerOf($row['key']) == $u->id)>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                            Save assignments
                        </button>
                    </div>
                </form>
            </div>
        @endunless

        {{-- Delivery status (admin) — record/adjust the delivered date --}}
        <div class="bg-white dark:bg-gray-900 border border-emerald-500/30 rounded-lg p-5 mb-6">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <h3 class="text-sm font-medium text-gray-100">Delivery status (admin)</h3>
            </div>
            @if($package->isCompleted())
                <p class="text-xs text-gray-500 mb-4">Marked delivered on <span class="text-emerald-300 font-medium">{{ $package->completed_at?->format('M j, Y') }}</span>. You can change the date or re-open the package.</p>
            @else
                <p class="text-xs text-gray-500 mb-4">Record this package as delivered to the client. You can set the actual delivery date.</p>
            @endif

            <div class="flex flex-wrap items-end gap-3">
                <form method="POST" action="{{ route('admin.viral-packages.set-delivered', $package) }}" class="flex items-end gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Delivery date</label>
                        <input type="date" name="delivered_at" value="{{ ($package->completed_at ?? now())->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                               class="px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/50"/>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                        {{ $package->isCompleted() ? 'Update delivery date' : 'Mark as delivered' }}
                    </button>
                </form>
                @if($package->isCompleted())
                    <form method="POST" action="{{ route('admin.viral-packages.reopen', $package) }}"
                          onsubmit="return confirm('Re-open this package and set it back to Active?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-ink-800 hover:bg-ink-700 border border-ink-600 text-gray-300 hover:text-gray-100 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                            Re-open package
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Deliverable summary --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6">
            <div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
                <h3 class="text-sm font-medium text-gray-100">Deliverables <span class="text-xs font-normal text-gray-500">— you can approve, request changes, or replace content</span></h3>
                @unless($package->isCompleted())
                    <div class="flex items-center gap-2 flex-wrap">
                        @foreach(['article' => 'Add article', 'social_post' => 'Add post', 'reel' => 'Add reel'] as $addKind => $addLabel)
                            <form method="POST" action="{{ route('admin.viral-packages.posts.add', $package) }}">
                                @csrf
                                <input type="hidden" name="kind" value="{{ $addKind }}"/>
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg transition-colors whitespace-nowrap">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    {{ $addLabel }}
                                </button>
                            </form>
                        @endforeach
                        @unless($package->deliverables->firstWhere('kind', 'landing_page'))
                            <form method="POST" action="{{ route('admin.viral-packages.landing.add', $package) }}"
                                  onsubmit="return confirm('Add a landing page deliverable to this package? The content team will be able to publish its link.');">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-lg transition-colors whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.828-2.828a4 4 0 015.656 0 4 4 0 010 5.656l-1.5 1.5"/></svg>
                                    Add landing page
                                </button>
                            </form>
                        @endunless
                    </div>
                @endunless
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @php
                    $stageColors = [
                        'pending'     => ['bg' => 'bg-gray-500/10',    'text' => 'text-gray-400',    'border' => 'border-gray-500/30'],
                        'in_progress' => ['bg' => 'bg-indigo-500/10',  'text' => 'text-indigo-300',  'border' => 'border-indigo-500/30'],
                        'review'      => ['bg' => 'bg-amber-500/10',   'text' => 'text-amber-300',   'border' => 'border-amber-500/30'],
                        'approved'    => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-300', 'border' => 'border-emerald-500/30'],
                    ];
                @endphp
                @foreach($package->deliverables as $d)
                    @php $c = $stageColors[$d->stage] ?? $stageColors['pending']; @endphp
                    <div class="border border-ink-700 rounded-lg p-4 bg-ink-800/30" style="min-width: 0; overflow: hidden;">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-100 truncate">{{ $d->title }}</p>
                                <p class="text-xs text-gray-500">{{ $d->kindLabel() }}</p>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $c['bg'] }} {{ $c['text'] }} border {{ $c['border'] }}">
                                    {{ $d->stageLabel() }}
                                </span>
                                @if(in_array($d->kind, ['article', 'social_post', 'reel'], true) && ! $package->isCompleted())
                                    <form method="POST" action="{{ route('admin.viral-packages.deliverables.remove', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                                          onsubmit="return confirm('Remove {{ $d->title }}? This cannot be undone.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Remove {{ $d->title }}"
                                                class="inline-flex items-center justify-center w-6 h-6 text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 rounded transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @if($d->assignee)
                            <p class="text-xs text-gray-500">Assigned: <span class="text-gray-300">{{ $d->assignee->name }}</span></p>
                        @endif
                        @if($d->approved_at)
                            <p class="text-xs text-emerald-400 mt-1">Approved {{ $d->approved_at->diffForHumans() }}</p>
                        @endif

                        {{-- Admin override: undo a mistaken approval and send it back to sales review --}}
                        @if($d->stage === 'approved')
                            <form method="POST" action="{{ route('admin.viral-packages.deliverables.revert', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                                  onsubmit="return confirm('Re-open {{ $d->title }}? It will go back to “Ready for review” so sales can review it again.');"
                                  class="mt-2">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[11px] font-medium text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 rounded-md transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v6h6M3 13a9 9 0 1 0 3-7.7L3 8"/></svg>
                                    Re-open (approved by mistake)
                                </button>
                            </form>
                        @endif

                        {{-- Uploaded file preview + download --}}
                        @if($d->drive_file_id)
                            @php
                                $isImage = str_starts_with((string) $d->mime_type, 'image/');
                                $isPdf   = str_contains((string) $d->mime_type, 'pdf') || str_ends_with(strtolower((string) $d->drive_filename), '.pdf');
                            @endphp
                            @if($isImage)
                                <a href="{{ route('admin.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}?inline=1"
                                   target="_blank" rel="noopener" class="block mt-3 rounded-md overflow-hidden border border-ink-700 bg-ink-900/60">
                                    <img loading="lazy"
                                         src="{{ route('admin.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}?inline=1"
                                         alt="{{ $d->drive_filename }}"
                                         class="w-full h-auto max-h-72 object-contain hover:opacity-90 transition-opacity"/>
                                </a>
                            @endif
                            <div class="flex items-center gap-2 mt-2 px-3 py-2 bg-ink-900/60 border border-ink-700 rounded-md">
                                <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="text-xs text-gray-300 flex-1 truncate" title="{{ $d->drive_filename }}">{{ $d->drive_filename }}</span>
                                @if($isPdf)
                                    <a href="{{ route('admin.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}?inline=1"
                                       target="_blank" rel="noopener"
                                       class="text-xs text-emerald-400 hover:text-emerald-300 whitespace-nowrap font-medium">View PDF</a>
                                @endif
                                <a href="{{ route('admin.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                                   class="text-xs text-indigo-400 hover:text-indigo-300 whitespace-nowrap font-medium">Download</a>
                            </div>
                        @endif

                        {{-- Landing page published link --}}
                        @if($d->kind === 'landing_page' && filled($d->landing_page_url))
                            <div class="flex items-center gap-2 mt-2 px-3 py-2 bg-ink-900/60 border border-ink-700 rounded-md" style="min-width:0;">
                                <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.828-2.828a4 4 0 015.656 0 4 4 0 010 5.656l-1.5 1.5"/></svg>
                                <a href="{{ $d->landing_page_url }}" target="_blank" rel="noopener" class="text-xs text-indigo-300 hover:text-indigo-200 flex-1 truncate" title="{{ $d->landing_page_url }}">{{ $d->landing_page_url }}</a>
                                <a href="{{ $d->landing_page_url }}" target="_blank" rel="noopener" class="text-xs text-emerald-400 hover:text-emerald-300 whitespace-nowrap font-medium">Open</a>
                                <button type="button" onclick="copyToClipboard(@js($d->landing_page_url))" class="text-xs text-indigo-400 hover:text-indigo-300 whitespace-nowrap font-medium">Copy</button>
                            </div>
                        @endif

                        {{-- Admin review actions — approve or request changes, same as the sales team --}}
                        @if($d->stage === 'review' && ! $package->isCompleted())
                            <div x-data="{ correctionOpen: false }" class="mt-3">
                                <div x-show="!correctionOpen" class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.viral-packages.deliverables.approve', ['viralPackage' => $package, 'deliverable' => $d]) }}" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium rounded-md transition-colors">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Approve
                                        </button>
                                    </form>
                                    <button type="button" @click="correctionOpen = true"
                                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/30 text-xs font-medium rounded-md transition-colors">
                                        Request changes
                                    </button>
                                </div>

                                <form x-show="correctionOpen" x-cloak method="POST" enctype="multipart/form-data"
                                      action="{{ route('admin.viral-packages.deliverables.correction', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                                      x-data="{ corrFile: '' }" class="space-y-2">
                                    @csrf
                                    <textarea name="reason" required rows="3" maxlength="1000" placeholder="What needs to change?"
                                              class="w-full px-3 py-2 text-xs bg-ink-800 border border-ink-600 rounded text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50"></textarea>
                                    <div>
                                        <label :for="`corr-file-{{ $d->id }}`"
                                               :class="corrFile ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-ink-600 hover:border-amber-500/60 hover:bg-amber-500/5'"
                                               class="flex items-center gap-2 px-3 py-2 border-2 border-dashed rounded-md cursor-pointer transition-colors">
                                            <svg class="w-3.5 h-3.5 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span class="flex-1 min-w-0 text-[11px] truncate" :class="corrFile ? 'text-gray-100' : 'text-gray-400'" x-text="corrFile || 'Attach reference file (optional)'"></span>
                                            <span x-show="corrFile" x-cloak class="text-[10px] text-rose-400 hover:text-rose-300 cursor-pointer"
                                                  @click.prevent.stop="corrFile = ''; document.getElementById('corr-file-{{ $d->id }}').value = '';">×</span>
                                        </label>
                                        <input type="file" id="corr-file-{{ $d->id }}" name="correction_assets[0][file]"
                                               @change="corrFile = $event.target.files.length ? $event.target.files[0].name : ''" class="hidden"/>
                                        <input type="hidden" name="correction_assets[0][type]" value="file"/>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="correctionOpen = false" class="text-xs text-gray-500 hover:text-gray-300">Cancel</button>
                                        <button type="submit" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-medium rounded-md">Send for correction</button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        {{-- Admin override: edit the landing page link directly --}}
                        @if($d->kind === 'landing_page')
                            <div x-data="{ lpOpen: false }" class="mt-2 flex flex-wrap items-center gap-2">
                                <button type="button" @click="lpOpen = !lpOpen"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[11px] font-medium text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 rounded-md transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span x-text="lpOpen ? 'Cancel' : '{{ filled($d->landing_page_url) ? 'Edit landing link' : 'Add landing link' }}'"></span>
                                </button>
                                @unless($package->isCompleted())
                                    <form method="POST" action="{{ route('admin.viral-packages.deliverables.remove', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                                          onsubmit="return confirm('Remove the landing page from this package? This cannot be undone.');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[11px] font-medium text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 rounded-md transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/></svg>
                                            Remove landing page
                                        </button>
                                    </form>
                                @endunless
                                <form x-show="lpOpen" x-cloak method="POST"
                                      action="{{ route('admin.viral-packages.deliverables.publish-landing', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                                      class="w-full mt-1 space-y-2">
                                    @csrf
                                    <input type="url" name="landing_page_url" required placeholder="https://example.com/landing" value="{{ $d->landing_page_url }}"
                                           class="w-full px-3 py-2 text-xs bg-ink-800 border border-ink-600 rounded text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"/>
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-md transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Save & send for review
                                    </button>
                                </form>
                            </div>
                        @else
                        {{-- Admin override: directly change/replace the uploaded content (any stage) --}}
                        <div x-data="{ open: false, fileName: '' }" class="mt-2">
                            <button type="button" @click="open = !open"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-[11px] font-medium text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 rounded-md transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span x-text="open ? 'Cancel' : '{{ $d->drive_file_id ? 'Change content' : 'Upload content' }}'"></span>
                            </button>

                            <form x-show="open" x-cloak method="POST" enctype="multipart/form-data"
                                  action="{{ route('admin.viral-packages.deliverables.replace', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                                  class="mt-2 space-y-2">
                                @csrf
                                <label :class="fileName ? 'border-emerald-500/50 bg-emerald-500/5' : 'border-indigo-500/40 hover:bg-indigo-500/5'"
                                       class="flex items-center gap-2 px-3 py-2.5 border-2 border-dashed rounded-md cursor-pointer transition-colors">
                                    <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span class="text-xs text-gray-300 truncate flex-1" x-text="fileName || 'Choose image/video to replace'"></span>
                                    <input type="file" name="file" required class="hidden"
                                           @change="fileName = $event.target.files.length ? $event.target.files[0].name : ''"/>
                                </label>
                                <button type="submit" :disabled="!fileName"
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-md transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Save new content
                                </button>
                                <p class="text-[10px] text-gray-500">Replaces the file directly — the approval status stays unchanged.</p>
                            </form>
                        </div>
                        @endif

                        {{-- Caption, hashtags & target audience (read-only, one-tap copy of all) --}}
                        @if($d->stage === 'approved' && ($d->caption || $d->hashtags || $d->target_audience))
                            @php
                                $copyAll = collect([
                                    $d->caption,
                                    $d->hashtags,
                                    $d->target_audience ? "Target audience:\n" . $d->target_audience : null,
                                ])->filter()->implode("\n\n");
                            @endphp
                            <div class="mt-3 pt-3 border-t border-ink-700 space-y-2.5" style="min-width: 0;">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Caption &amp; details</p>
                                    <button type="button" onclick="copyToClipboard(@js($copyAll))" class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-md transition-colors"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>Copy all</button>
                                </div>
                                @if($d->caption)
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Caption</p>
                                        <div class="text-xs text-gray-200 bg-ink-900/60 border border-ink-700 rounded-lg px-3 py-2 max-h-28 overflow-y-auto" style="white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ $d->caption }}</div>
                                    </div>
                                @endif
                                @if($d->hashtags)
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Hashtags</p>
                                        <div class="text-xs text-indigo-300 bg-ink-900/60 border border-ink-700 rounded-lg px-3 py-2 max-h-24 overflow-y-auto" style="white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ $d->hashtags }}</div>
                                    </div>
                                @endif
                                @if($d->target_audience)
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Target audience</p>
                                        <div class="text-xs text-gray-200 bg-ink-900/60 border border-ink-700 rounded-lg px-3 py-2 max-h-24 overflow-y-auto" style="white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ $d->target_audience }}</div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Full activity timeline --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5">
            <h3 class="text-sm font-medium text-gray-100 mb-4">Activity timeline</h3>
            @php
                $events = $package->deliverables->flatMap(fn ($d) =>
                    $d->history->map(fn ($h) => ['deliverable' => $d, 'history' => $h])
                )->sortByDesc(fn ($e) => $e['history']->changed_at);
            @endphp
            @if($events->isEmpty())
                <p class="text-xs text-gray-500">No activity yet.</p>
            @else
                <ol class="space-y-3">
                    @foreach($events as $e)
                        <li class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-100">
                                    <span class="font-medium">{{ $e['deliverable']->title }}</span>
                                    <span class="text-gray-500">→</span>
                                    <span class="text-gray-300">{{ ucfirst(str_replace('_', ' ', $e['history']->to_stage)) }}</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $e['history']->changedBy?->name ?? 'system' }} · {{ $e['history']->changed_at->diffForHumans() }}
                                </p>
                                @if($e['history']->notes)
                                    <p class="text-xs text-gray-400 mt-1 italic">"{{ $e['history']->notes }}"</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</x-app-layout>
