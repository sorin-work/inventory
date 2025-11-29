@php
    $message = session('status');
    $error = session('error');
@endphp

<div
    x-data="{ message: @js($message), error: @js($error), timer: null }"
    x-init="if (message || error) { timer = setTimeout(() => { message = null; error = null }, 5000) }"
    x-on:notify.window="message = $event.detail.message ?? $event.detail[0]?.message ?? null; error = null; clearTimeout(timer); timer = setTimeout(() => message = null, 5000)"
    class="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center px-4"
>
    <div x-show="message" x-transition style="display: none;" class="pointer-events-auto rounded-md bg-gray-900 px-4 py-2 text-sm text-white shadow-lg" role="status">
        <span x-text="message"></span>
    </div>
    <div x-show="error" x-transition style="display: none;" class="pointer-events-auto rounded-md bg-rose-600 px-4 py-2 text-sm text-white shadow-lg" role="alert">
        <span x-text="error"></span>
    </div>
</div>
