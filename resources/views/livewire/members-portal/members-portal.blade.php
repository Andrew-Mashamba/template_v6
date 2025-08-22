<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Members Portal & Mobile</h1>
                <p class="text-gray-600 mt-1">Manage member portal access, mobile app features, and member self-service capabilities</p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                    <span class="w-2 h-2 bg-green-400 rounded-full inline-block mr-2"></span>
                    Active
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="mb-6">
        <nav class="flex space-x-8 border-b border-gray-200">
            <button wire:click="setActiveTab('portal-settings')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                    {{ $activeTab === 'portal-settings' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Portal Settings
            </button>
            <button wire:click="setActiveTab('mobile-app')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                    {{ $activeTab === 'mobile-app' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Mobile App
            </button>
            <button wire:click="setActiveTab('user-management')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                    {{ $activeTab === 'user-management' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                User Management
            </button>
            <button wire:click="setActiveTab('analytics')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                    {{ $activeTab === 'analytics' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Analytics
            </button>
        </nav>
    </div>

    <!-- Content Area -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        @if($activeTab === 'portal-settings')
            @include('livewire.members-portal.partials.portal-settings')
        @elseif($activeTab === 'mobile-app')
            @include('livewire.members-portal.partials.mobile-app')
        @elseif($activeTab === 'user-management')
            @include('livewire.members-portal.partials.user-management')
        @elseif($activeTab === 'analytics')
            @include('livewire.members-portal.partials.analytics')
        @endif
    </div>
</div> 