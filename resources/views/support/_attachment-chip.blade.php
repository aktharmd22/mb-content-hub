{{-- Download chip for a support attachment. Expects: $url, $att (SupportAttachment) --}}
<a href="{{ $url }}"
   style="display: inline-flex; align-items: center; gap: 10px; padding: 8px 12px; background: rgb(var(--ink-800)); border: 1px solid rgb(var(--ink-700)); border-radius: 10px;"
   class="hover:border-indigo-500/40 transition-colors">
    <span style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <svg style="width: 15px; height: 15px; color: #818cf8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </span>
    <span class="min-w-0 flex-1">
        <span class="block text-xs font-medium text-gray-200 truncate">{{ $att->original_name }}</span>
        <span class="block text-[10px] text-gray-500">{{ $att->humanSize() }}{{ $att->humanSize() ? ' · ' : '' }}Download</span>
    </span>
    <svg style="width: 14px; height: 14px; color: #64748b; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
</a>
