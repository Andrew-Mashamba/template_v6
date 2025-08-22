{{-- Dashboard router based on department or user dashboard_type (numeric) --}}
@php
    $user = Auth::user();
    $department = $user->department;

    $dashboardType = $department && $department->dashboard_type ? $department->dashboard_type : ($user->dashboard_type ?? null);
@endphp

<div class="h-full w-full">

    @if (session()->has('message'))
        <div class="alert alert-success bg-blue-100 font-bold text-center justify-center">
            <strong>{{ session('message') }}</strong>
        </div>
    @endif

    <div class="w-full h-full grid justify-items-center">
        <div class="w-full m-auto grid justify-items-center">
            <div class="w-full bg-gray-100 rounded-lg p-2">
  
                {{-- Dashboard Switcher --}}
                @php
                    $dashboardType = 1;
                @endphp

              
                <div>
                    <div class="w-full">
                        <div class="w-full pt-2">
                            @switch($dashboardType)
                                @case(1)
                                <livewire:dashboard.front-desk />
                                   
                                @break
                                @case(2)
                                    <livewire:dashboard.accountant-dashboard />
                                    @break
                                @case(3)
                                    <livewire:dashboard.loan-officer-dashboard />
                                    @break
                                @case(4)
                                    <livewire:dashboard.branch-manager-dashboard />
                                    @break
                                @case(5)
                                    <livewire:dashboard.teller-dashboard />
                                    @break
                                @case(6)
                                    <livewire:dashboard.member-dashboard />
                                    @break
                                @case(7)
                                    <livewire:dashboard.procurement-dashboard />
                                    @break
                                @case(8)
                                    <livewire:dashboard.hr-dashboard />
                                    @break
                                @case(9)
                                    <livewire:dashboard.auditor-dashboard />
                                    @break
                                @case(10)
                                    <livewire:dashboard.system-admin-dashboard />
                                    @break
                                @default
                                <livewire:dashboard.ceo-dashboard />
                                   
                                    
                            @endswitch
                        </div>




                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
