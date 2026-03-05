@props(['tags' => []])
{{--
  Usage: <x-tag-input :tags="old('tags', $prompt->tags ?? [])" />
  Outputs hidden inputs named tags[] plus an interactive pill UI.
--}}
<div x-data="{
    tags: @js(old('tags', $tags ?? [])),
    input: '',
    addTag() {
        const val = this.input.trim().toLowerCase().replace(/,+$/, '');
        if (val && !this.tags.includes(val)) this.tags.push(val);
        this.input = '';
    },
    removeTag(i) { this.tags.splice(i, 1); }
}">
    {{-- Pill display + hidden inputs --}}
    <div class="flex flex-wrap gap-1.5 mb-2 min-h-[28px]">
        <template x-for="(tag, i) in tags" :key="i">
            <span class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 border border-purple-200">
                <span x-text="tag"></span>
                <button type="button" @click="removeTag(i)"
                    class="text-purple-400 hover:text-purple-700 leading-none">&times;</button>
                <input type="hidden" name="tags[]" :value="tag">
            </span>
        </template>
        <span x-show="tags.length === 0" class="text-xs text-gray-400 self-center">No tags yet</span>
    </div>

    {{-- Text input --}}
    <input type="text" x-model="input"
        @keydown.enter.prevent="addTag()"
        @keydown.comma.prevent="addTag()"
        @keydown.backspace="if (!input && tags.length) removeTag(tags.length - 1)"
        @blur="if (input.trim()) addTag()"
        placeholder="Type a tag, press Enter or comma to add…"
        class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('tags.*') border-red-300 @enderror">
    <p class="mt-1 text-xs text-gray-400">Tags are lowercased and deduplicated automatically.</p>
</div>
