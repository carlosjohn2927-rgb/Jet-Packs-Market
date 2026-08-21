<?php
/**
 * Media picker modal — shared by every editor that needs an image.
 *
 * Usage in a form:
 *   <?= vp_media_field('image', $value, 'Hero image') ?>
 * or manually: a button with data-vp-media-target="<input id>".
 */
?>
<div id="vp-media-modal" class="hidden fixed inset-0 z-[100] bg-black/50 p-4 md:p-10 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-5xl mx-auto shadow-2xl overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center gap-3">
            <h2 class="font-bold text-lg">Media library</h2>
            <span class="text-xs text-ink-800/60">Click an image to use it</span>
            <button type="button" class="ml-auto p-2 rounded hover:bg-gray-100" data-vp-media-close aria-label="Close"><i class="ri-close-line text-xl"></i></button>
        </div>
        <div class="px-5 py-3 border-b bg-gray-50 flex flex-wrap items-center gap-3">
            <input type="file" id="vp-media-upload-input" class="text-sm" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.mp4,.webm,.ogg,.mov">
            <button type="button" id="vp-media-upload-btn" class="vp-btn vp-btn-primary text-sm"><i class="ri-upload-2-line"></i> Upload</button>
            <input type="search" id="vp-media-search" class="vp-input text-sm ml-auto max-w-xs" placeholder="Search files…">
        </div>
        <div id="vp-media-grid" class="p-5 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 max-h-[60vh] overflow-y-auto">
            <p class="col-span-full text-center text-sm text-ink-800/60 py-10">Loading…</p>
        </div>
    </div>
</div>
