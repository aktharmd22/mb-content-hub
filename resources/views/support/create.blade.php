<x-app-layout>
    <x-slot name="header">New Ticket</x-slot>
    <x-slot name="title">Raise a Support Ticket</x-slot>

    <div class="p-6 w-full">
        <div class="mb-6">
            <a href="{{ route('support.index') }}" class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-300 mb-3">
                <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to tickets
            </a>
            <h1 class="text-2xl font-bold text-gray-100">Raise a support ticket</h1>
            <p class="text-sm text-gray-500 mt-1">Describe the issue clearly so it can be resolved quickly.</p>
        </div>

        <form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data"
              x-data="{ target: '{{ old('target', 'specific') }}', assignee: '{{ old('assignee_id') }}' }"
              style="background: #1e293b; border: 1px solid rgba(148,163,184,0.10); border-radius: 16px; padding: 24px;">
            @csrf

            {{-- Subject --}}
            <div class="mb-4">
                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" maxlength="200" required
                       placeholder="Short description of the issue"
                       style="width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid rgba(148,163,184,0.10); border-radius: 10px; color: #f1f5f9; font-size: 14px;"
                       class="focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/50"/>
                @error('subject') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Priority --}}
            <div class="mb-4">
                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Priority</label>
                <select name="priority" required
                        style="width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid rgba(148,163,184,0.10); border-radius: 10px; color: #f1f5f9; font-size: 14px;"
                        class="focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>

            {{-- Description --}}
            <div class="mb-4">
                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Description</label>
                <textarea name="description" rows="6" maxlength="5000" required
                          placeholder="What's the issue? Steps to reproduce, what you expected, what happened..."
                          style="width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid rgba(148,163,184,0.10); border-radius: 10px; color: #f1f5f9; font-size: 14px; resize: vertical;"
                          class="focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/50">{{ old('description') }}</textarea>
                @error('description') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Attachments (multiple, accumulating) --}}
            <div class="mb-4" x-data="fileUploader()">
                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Attachments <span class="text-gray-600 normal-case font-normal">(optional, up to 10 files · 10MB each)</span></label>

                <input x-ref="input" type="file" name="attachments[]" multiple class="hidden" @change="pick($event)"/>

                {{-- Add button --}}
                <button type="button" @click="$refs.input.click()"
                        class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-lg transition-colors hover:border-indigo-500/50"
                        style="background: #0f172a; border: 1px solid rgba(148,163,184,0.14);">
                    <svg class="w-4 h-4 flex-shrink-0" style="color: #818cf8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-200" x-text="files.length ? 'Add more files' : 'Add files'"></span>
                    <span class="text-[11px] text-gray-500 hidden sm:inline" x-show="!files.length">screenshots, docs, logs…</span>
                    <span class="text-[11px] text-indigo-300" x-show="files.length" x-text="files.length + ' attached'"></span>
                </button>

                {{-- Selected files list --}}
                <div x-show="files.length" x-cloak class="mt-2" style="display: flex; flex-direction: column; gap: 8px;">
                    <template x-for="(f, i) in files" :key="i">
                        <div style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: #0f172a; border: 1px solid rgba(99,102,241,0.3); border-radius: 10px;">
                            <span style="width: 28px; height: 28px; border-radius: 7px; background: rgba(99,102,241,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg style="width: 14px; height: 14px; color: #818cf8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <span class="text-sm text-gray-100 truncate" style="flex: 1 1 0%; min-width: 0;" x-text="f"></span>
                            <button type="button" @click="remove(i)" style="flex-shrink: 0;" class="text-gray-500 hover:text-rose-400" title="Remove">
                                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="clearAll()" class="text-[11px] text-gray-500 hover:text-rose-400 transition-colors" style="align-self: flex-start;">Clear all</button>
                </div>
                @error('attachments.*') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Assign target --}}
            <div class="mb-6">
                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Send To</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 14px; background: #0f172a; border: 2px solid rgba(148,163,184,0.12); border-radius: 10px; cursor: pointer; transition: all 0.15s;"
                           :style="target === 'admin_pool' ? 'border-color: #6366f1;' : ''">
                        <input type="radio" name="target" value="admin_pool" x-model="target"
                               {{ old('target') === 'admin_pool' ? 'checked' : '' }}
                               style="margin-top: 2px; accent-color: #6366f1;"/>
                        <div>
                            <p class="text-sm font-semibold text-gray-200">Admin team (general)</p>
                            <p class="text-xs text-gray-500 mt-0.5">Any available admin will pick it up.</p>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 14px; background: #0f172a; border: 2px solid rgba(148,163,184,0.12); border-radius: 10px; cursor: pointer; transition: all 0.15s;"
                           :style="target === 'specific' ? 'border-color: #6366f1;' : ''">
                        <input type="radio" name="target" value="specific" x-model="target"
                               {{ old('target', 'specific') === 'specific' ? 'checked' : '' }}
                               style="margin-top: 2px; accent-color: #6366f1;"/>
                        <div>
                            <p class="text-sm font-semibold text-gray-200">Specific person</p>
                            <p class="text-xs text-gray-500 mt-0.5">Send directly to a teammate.</p>
                        </div>
                    </label>
                </div>

                <div x-show="target === 'specific'" x-cloak style="display: none;" class="mt-3">
                    <select name="assignee_id" x-model="assignee"
                            style="width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid rgba(148,163,184,0.10); border-radius: 10px; color: #f1f5f9; font-size: 14px;"
                            class="focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                        <option value="">— Pick a teammate —</option>
                        @foreach($assignableUsers as $u)
                            @php
                                $rl = match($u->role) {
                                    'admin' => 'Admin', 'sales' => 'Sales',
                                    'tech_team' => 'Tech Team', 'content_team' => 'Content Team',
                                    default => strtoupper(str_replace('_', ' ', (string) $u->role)),
                                };
                            @endphp
                            <option value="{{ $u->id }}" {{ (int) old('assignee_id') === $u->id ? 'selected' : '' }}>{{ $u->name }} — {{ $rl }}</option>
                        @endforeach
                    </select>
                    @error('assignee_id') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-ink-700">
                <a href="{{ route('support.index') }}" class="px-4 py-2 text-sm text-gray-400 hover:text-gray-200 rounded-lg transition-colors">Cancel</a>
                <button type="submit"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-radius: 10px; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(99,102,241,0.3);"
                        class="hover:opacity-90 transition-opacity">
                    Raise Ticket
                    <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </div>
        </form>
    </div>

    <script>
        // Accumulating multi-file uploader: each pick ADDS to the selection (DataTransfer trick).
        function fileUploader() {
            return {
                dt: new DataTransfer(),
                files: [],
                pick(e) {
                    Array.from(e.target.files).forEach(f => this.dt.items.add(f));
                    this.commit();
                },
                remove(idx) {
                    const next = new DataTransfer();
                    Array.from(this.dt.files).forEach((f, i) => { if (i !== idx) next.items.add(f); });
                    this.dt = next;
                    this.commit();
                },
                clearAll() {
                    this.dt = new DataTransfer();
                    this.commit();
                },
                commit() {
                    this.$refs.input.files = this.dt.files;
                    this.files = Array.from(this.dt.files).map(f => f.name);
                },
            };
        }
    </script>
</x-app-layout>
