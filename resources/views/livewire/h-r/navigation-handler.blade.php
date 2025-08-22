<div class="flex h-full">
    <!-- Sidebar with Transition -->
    <div 
        x-data="{ open: true }"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="w-64 bg-white shadow-lg"
    >
        <div class="flex items-center justify-center h-16 border-b">
            <h2 class="text-xl font-semibold text-gray-800">HR Management</h2>
        </div>
        
        <nav class="mt-5 px-2">
            @foreach($menuConfig as $section => $config)
            <div class="space-y-1">
                <div 
                    x-data="{ open: false }"
                    @click="open = !open"
                    class="flex items-center justify-between px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-50 group cursor-pointer"
                >
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}" />
                        </svg>
                        {{ $config['title'] }}
                    </div>
                    <svg 
                        class="w-4 h-4 text-gray-400 group-hover:text-gray-500 transition-transform duration-200"
                        :class="{ 'rotate-180': open }"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <div 
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 transform translate-y-0"
                    x-transition:leave-end="opacity-0 transform -translate-y-2"
                    class="pl-10 space-y-1"
                >
                    @foreach($config['items'] as $item)
                    <button 
                        wire:click="navigateTo('{{ $item['component'] }}', '{{ $config['title'] }}', '{{ $item['title'] }}')"
                        class="block w-full text-left px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-50 {{ $currentComponent === $item['component'] ? 'bg-gray-100' : '' }}"
                    >
                        {{ $item['title'] }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endforeach
        </nav>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 overflow-auto">
        <!-- Breadcrumb Navigation -->
        <div class="bg-white border-b px-6 py-3">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li>
                        <button 
                            wire:click="navigateTo('hr.dashboard', 'Dashboard', 'Dashboard')"
                            class="text-gray-500 hover:text-gray-700"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </button>
                    </li>
                    @foreach($breadcrumbs as $crumb)
                    <li>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            @if($crumb['active'])
                                <span class="ml-2 text-sm font-medium text-gray-900">{{ $crumb['title'] }}</span>
                            @else
                                <button 
                                    wire:click="navigateTo('{{ $crumb['component'] }}', '{{ $crumb['section'] }}', '{{ $crumb['item'] }}')"
                                    class="ml-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                                >
                                    {{ $crumb['title'] }}
                                </button>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ol>
            </nav>
        </div>

        <!-- Loading Overlay -->
        <div 
            x-data="{ show: false }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @livewire:navigating.window="show = true"
            @livewire:navigated.window="show = false"
            class="fixed inset-0 bg-white bg-opacity-75 z-50 flex items-center justify-center"
            style="display: none;"
        >
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-10 w-10 text-blue-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-600">Loading...</span>
            </div>
        </div>

        <!-- Component Content with Transition -->
        <div 
            x-data="{ show: true }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-x-4"
            x-transition:enter-end="opacity-100 transform translate-x-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-x-0"
            x-transition:leave-end="opacity-0 transform -translate-x-4"
            @livewire:navigating.window="show = false"
            @livewire:navigated.window="show = true"
        >
            @if($currentComponent === 'hr.dashboard')
                @livewire('h-r.dashboard')
            @else
                <div class="p-6">
                    @livewire($currentComponent)
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        // Initialize any collapsed sections based on current component
        const currentSection = document.querySelector('.space-y-1 > div:first-child');
        if (currentSection) {
            currentSection.click();
        }
    });
</script>
@endpush 