<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg border-l-4 border-indigo-200">
                <div class="max-w-2xl text-sm text-gray-600 space-y-3">
                    <h3 class="font-medium text-gray-900">{{ __('About offline sync') }}</h3>
                    <p>
                        {{ __('On this page, name and email are fully covered: you can save while offline and changes are queued for the server when you reconnect. Password change and account deletion are not queued—the server must verify your current password in an active session, which is unsafe or awkward to mirror offline.') }}
                    </p>
                    <p>
                        {{ __('Read-only screens (dashboard, reports, inventory logs, search and index pages) do not perform server writes, so there is nothing to sync there. Browsing those pages offline is a separate browser or PWA caching concern, not the mutation queue.') }}
                    </p>
                    <p>
                        {{ __('Sign in, register, and similar auth flows are not offline writes. Receiving a purchase order while offline only works when this device has already loaded that PO so line items are embedded in the page; otherwise open the PO online once to cache the lines.') }}
                    </p>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
