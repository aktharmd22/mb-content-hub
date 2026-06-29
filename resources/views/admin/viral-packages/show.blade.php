<x-app-layout>
    <x-slot name="header">Viral package — {{ $package->client?->name }}</x-slot>
    <x-slot name="title">{{ $package->client?->name }} package</x-slot>

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
                    <h1 class="text-xl font-medium text-gray-100">{{ $package->client?->name }}</h1>
                    @if($package->client?->company)
                        <p class="text-sm text-gray-500">{{ $package->client->company }}</p>
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
                <p class="text-xs text-gray-500 mb-4">Change who handles this package. Updates take effect immediately.</p>
                <form method="POST" action="{{ route('admin.viral-packages.reassign', $package) }}"
                      class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Sales rep</label>
                        <select name="sales_rep_id"
                                class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                            @foreach($salesReps as $u)
                                <option value="{{ $u->id }}" @selected($package->sales_rep_id == $u->id)>{{ $u->name }}{{ $u->role === 'admin' ? ' (admin)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">Tech team</label>
                        <select name="tech_team_id"
                                class="w-full px-3 py-2 text-sm bg-ink-800 border border-ink-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                            <option value="">— Unassigned —</option>
                            @foreach($techTeam as $u)
                                <option value="{{ $u->id }}" @selected($package->tech_team_id == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                        Save assignments
                    </button>
                </form>
            </div>
        @endunless

        {{-- Deliverable summary --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-5 mb-6">
            <h3 class="text-sm font-medium text-gray-100 mb-4">Deliverables <span class="text-xs font-normal text-gray-500">— you can approve, request changes, or replace content</span></h3>

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
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $c['bg'] }} {{ $c['text'] }} border {{ $c['border'] }}">
                                {{ $d->stageLabel() }}
                            </span>
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
                            @php $isImage = str_starts_with((string) $d->mime_type, 'image/'); @endphp
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
                                <a href="{{ route('admin.viral-packages.deliverables.download', ['viralPackage' => $package, 'deliverable' => $d]) }}"
                                   class="text-xs text-indigo-400 hover:text-indigo-300 whitespace-nowrap font-medium">Download</a>
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

                        {{-- Caption, hashtags & target audience (read-only, copyable) --}}
                        @if($d->stage === 'approved' && ($d->caption || $d->hashtags || $d->target_audience))
                            <div class="mt-3 pt-3 border-t border-ink-700 space-y-2.5" style="min-width: 0;">
                                @if($d->caption)
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Caption</p>
                                            <button type="button" onclick="copyToClipboard(@js($d->caption))" class="text-[11px] text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>Copy</button>
                                        </div>
                                        <div class="text-xs text-gray-200 bg-ink-900/60 border border-ink-700 rounded-lg px-3 py-2 max-h-28 overflow-y-auto" style="white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ $d->caption }}</div>
                                    </div>
                                @endif
                                @if($d->hashtags)
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Hashtags</p>
                                            <button type="button" onclick="copyToClipboard(@js($d->hashtags))" class="text-[11px] text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>Copy</button>
                                        </div>
                                        <div class="text-xs text-indigo-300 bg-ink-900/60 border border-ink-700 rounded-lg px-3 py-2 max-h-24 overflow-y-auto" style="white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;">{{ $d->hashtags }}</div>
                                    </div>
                                @endif
                                @if($d->target_audience)
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Target audience</p>
                                            <button type="button" onclick="copyToClipboard(@js($d->target_audience))" class="text-[11px] text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>Copy</button>
                                        </div>
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
