<x-app-layout>
    <x-slot name="header">
        <div class="page-title">Profil</div>
    </x-slot>

    <div class="space-y-6">
        <div class="card">
            <div class="card-body max-w-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card">
            <div class="card-body max-w-2xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card border-red-200">
            <div class="card-body max-w-2xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
