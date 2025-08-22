<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">Today's Attendance</h3>
    </div>
    <div class="p-6">
        <!-- Attendance Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Present -->
            <div class="bg-green-50 rounded-xl p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-2xl font-bold text-gray-900">{{ $todayPresent }}</h4>
                        <p class="text-sm text-gray-600">Present</p>
                    </div>
                </div>
            </div>

            <!-- Absent -->
            <div class="bg-red-50 rounded-xl p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-lg">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-2xl font-bold text-gray-900">{{ $todayAbsent }}</h4>
                        <p class="text-sm text-gray-600">Absent</p>
                    </div>
                </div>
            </div>

            <!-- Late -->
            <div class="bg-yellow-50 rounded-xl p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-2xl font-bold text-gray-900">{{ $todayLate }}</h4>
                        <p class="text-sm text-gray-600">Late</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Attendance Rate -->
        <div class="mb-6">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Monthly Attendance Rate</h4>
            <div class="w-full bg-gray-200 rounded-full h-6 relative">
                <div class="bg-green-600 h-6 rounded-full flex items-center justify-center text-xs font-medium text-white" 
                     style="width: {{ $monthlyAttendanceRate }}%;">
                    {{ $monthlyAttendanceRate }}%
                </div>
            </div>
        </div>

        <!-- Recent Attendance Table -->
        <div>
            <h4 class="text-sm font-medium text-gray-700 mb-3">Recent Attendance</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Employee
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Check In
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Check Out
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($attendanceData as $attendance)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $attendance['employee'] }}
                            </td>
                            <td class="px-4 py-3">
                                @if($attendance['status'] == 'Present')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $attendance['status'] }}
                                    </span>
                                @elseif($attendance['status'] == 'Late')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ $attendance['status'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ $attendance['status'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $attendance['check_in'] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $attendance['check_out'] }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>