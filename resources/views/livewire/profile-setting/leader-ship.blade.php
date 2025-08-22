<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="p-6">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-blue-900 rounded-xl shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Institution Leadership & Management</h1>
                        <p class="text-gray-600 mt-1">Manage leaders, committees, meetings, attendance, and documents in one place.</p>
                    </div>
                </div>
                <!-- Quick Stats -->
                <div class="flex items-center space-x-4">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Leaders</p>
                                <p class="text-lg font-semibold text-gray-900">{{ \App\Models\LeaderShipModel::count() }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Committees</p>
                                <p class="text-lg font-semibold text-gray-900">{{ \App\Models\Committee::count() }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Upcoming Meetings</p>
                                <p class="text-lg font-semibold text-gray-900">{{ \App\Models\Meeting::where('meeting_date', '>=', now())->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex gap-6">
            <!-- Sidebar Navigation -->
            <div class="w-80 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <!-- Search Section -->
                <div class="p-6 border-b border-gray-100">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" wire:model.debounce.300ms="sidebarSearch" placeholder="Search sections..." class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white focus:bg-white" />
                    </div>
                </div>
                <!-- Navigation Menu -->
                <div class="p-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 px-2">Navigation</h3>
                    @php
                        $sections = [
                            ['id' => 'leadership', 'label' => 'Leadership', 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z', 'description' => 'Manage leaders'],
                            ['id' => 'committees', 'label' => 'Committees', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'description' => 'Manage committees'],
                            ['id' => 'members', 'label' => 'Members', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'description' => 'Committee members'],
                            ['id' => 'meetings', 'label' => 'Meetings', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'description' => 'Schedule and manage meetings'],
                            ['id' => 'attendance', 'label' => 'Attendance', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'description' => 'Meeting attendance'],
                            ['id' => 'documents', 'label' => 'Documents', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'description' => 'Meeting documents'],
                            ['id' => 'stipend_report', 'label' => 'Stipend Report', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'description' => 'Stipend payments report'],
                        ];
                        $activeSection = $activeSection ?? 'leadership';
                    @endphp
                    <nav class="space-y-2">
                        @foreach ($sections as $section)
                            <button wire:click="$set('activeSection', '{{ $section['id'] }}')" class="relative w-full group transition-all duration-200">
                                <div class="flex items-center p-3 rounded-xl transition-all duration-200
                                    @if ($activeSection === $section['id']) 
                                        bg-blue-900 text-white shadow-lg 
                                    @else 
                                        bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900 
                                    @endif">
                                    <div class="mr-3">
                                        <svg class="w-5 h-5 @if ($activeSection === $section['id']) text-white @else text-gray-500 group-hover:text-gray-700 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <div class="font-medium text-sm">{{ $section['label'] }}</div>
                                        <div class="text-xs opacity-75">{{ $section['description'] }}</div>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </nav>
                </div>
                <!-- Quick Actions -->
                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-2">Quick Actions</h3>
                    <div class="space-y-2">
                        <button wire:click="newLeaderModal" class="w-full flex items-center p-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-white rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Leader
                        </button>
                        <button wire:click="$set('activeSection', 'committees')" class="w-full flex items-center p-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-white rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Add Committee
                        </button>
                        <button wire:click="$set('activeSection', 'meetings')" class="w-full flex items-center p-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-white rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Schedule Meeting
                        </button>
                    </div>
                </div>
            </div>
            <!-- Main Content Area -->
            <div class="flex-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">
                                    @php
                                        $sectionTitles = [
                                            'leadership' => 'Leadership',
                                            'committees' => 'Committees',
                                            'members' => 'Committee Members',
                                            'meetings' => 'Meetings',
                                            'attendance' => 'Meeting Attendance',
                                            'documents' => 'Meeting Documents',
                                        ];
                                    @endphp
                                    {{ $sectionTitles[$activeSection] ?? 'Leadership' }}
                                </h2>
                                <p class="text-gray-600 mt-1">
                                    @php
                                        $sectionDescriptions = [
                                            'leadership' => 'Manage all institution leaders',
                                            'committees' => 'Create and manage committees',
                                            'members' => 'Assign and manage committee members',
                                            'meetings' => 'Schedule and manage meetings',
                                            'attendance' => 'Mark and review meeting attendance',
                                            'documents' => 'Upload and manage meeting documents',
                                        ];
                                    @endphp
                                    {{ $sectionDescriptions[$activeSection] ?? '' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="p-8">
                        @if($activeSection === 'leadership')
                            <livewire:profile-setting.leader-ship-table />
                        @elseif($activeSection === 'committees')
                            <livewire:profile-setting.committee-manager />
                        @elseif($activeSection === 'members' && isset($selectedCommitteeId))
                            <livewire:profile-setting.committee-members-manager :committee_id="$selectedCommitteeId" />
                        @elseif($activeSection === 'meetings' && isset($selectedCommitteeId))
                            <livewire:profile-setting.meeting-manager :committee_id="$selectedCommitteeId" />
                        @elseif($activeSection === 'attendance' && isset($selectedMeetingId))
                            <livewire:profile-setting.meeting-attendance-manager :meeting_id="$selectedMeetingId" />
                        @elseif($activeSection === 'documents' && isset($selectedMeetingId))
                            <livewire:profile-setting.meeting-documents-manager :meeting_id="$selectedMeetingId" />
                        @elseif($activeSection === 'stipend_report')
                            <livewire:profile-setting.stipend-report-manager />
                        @else
                            <div class="text-center text-gray-400 py-12">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>Select a section to begin.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
