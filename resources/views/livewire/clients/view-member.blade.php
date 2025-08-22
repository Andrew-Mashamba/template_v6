{{-- View Member Modal --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30">
    <div class="bg-white rounded-2xl w-full max-w-8xl shadow-none p-0 overflow-hidden">
        <div class="flex flex-col md:flex-row gap-0">
            {{-- Profile and Basic Info --}}
            <div class="md:w-1/3 bg-gray-50 flex flex-col items-center justify-center p-8">
                <div class="w-32 h-32 mb-4">
                    @if($member->profile_photo)
                        <img src="{{ Storage::url($member->profile_photo) }}" alt="Profile" class="rounded-full w-full h-full object-cover border-4 border-white">
                    @else
                        <div class="rounded-full w-full h-full bg-gray-200 flex items-center justify-center text-4xl text-gray-400">
                            {{ strtoupper(substr($member->first_name,0,1).substr($member->last_name,0,1)) }}
                        </div>
                    @endif
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-gray-900">{{ $member->first_name }} {{ $member->last_name }}</div>
                    <div class="text-gray-500 text-sm mt-1">{{ $member->client_number }}</div>
                    <div class="text-gray-400 text-xs mt-1">{{ $member->membership_type }}</div>
                </div>
            </div>
            {{-- Details --}}
            <div class="md:w-2/3 p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <div class="font-semibold text-gray-700 mb-2">Personal Information</div>
                        <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-sm">
                            <div class="text-gray-500">DOB</div><div>{{ $member->date_of_birth }}</div>
                            <div class="text-gray-500">Gender</div><div>{{ $member->gender }}</div>
                            <div class="text-gray-500">Marital</div><div>{{ $member->marital_status }}</div>
                            <div class="text-gray-500">Nationality</div><div>{{ $member->nationality }}</div>
                        </div>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-700 mb-2">Contact</div>
                        <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-sm">
                            <div class="text-gray-500">Phone</div><div>{{ $member->phone_number }}</div>
                            <div class="text-gray-500">Email</div><div>{{ $member->email }}</div>
                            <div class="text-gray-500">Address</div><div>{{ $member->address }}</div>
                        </div>
                    </div>
                    <button wire:click="showAllData" class="px-2 py-1 text-xs rounded bg-gray-700 text-white hover:bg-gray-800 ml-auto">Show All Data</button>
                </div>
                <div class="mb-6">
                    <div class="font-semibold text-gray-700 mb-2">Accounts</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @forelse($member->accounts as $account)
                            <div class="bg-gray-100 rounded-lg p-4">
                            @if($account->parentAccount)
                                    <div class="text-xs text-gray-800 font-bold mt-1">{{ $account->parentAccount->account_name }}</div>
                                @endif
                                <div class="text-xs text-gray-500 mb-1">{{ $account->account_name }}</div>
                                <div class="text-sm text-gray-600 mb-1">#{{ $account->account_number }}</div>
                                <div class="text-lg font-bold text-gray-900">{{ number_format($account->balance, 2) }}</div>
                                
                                @if($account->locked_amount > 0)
                                    <div class="text-xs text-red-600 mt-1">Locked: {{ number_format($account->locked_amount, 2) }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="col-span-3 text-center text-gray-500 py-4">No accounts found</div>
                        @endforelse
                    </div>
                </div>
                <div class="mb-6">
                    <div class="font-semibold text-gray-700 mb-2">Ongoing Loans</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-500 text-xs">
                                    <th class="py-1 px-2 text-left">Loan #</th>
                                    <th class="py-1 px-2 text-left">Type</th>
                                    <th class="py-1 px-2 text-left">Amount</th>
                                    <th class="py-1 px-2 text-left">Balance</th>
                                    <th class="py-1 px-2 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($member->loans as $loan)
                                    <tr>
                                        <td class="py-1 px-2">{{ $loan->loan_number }}</td>
                                        <td class="py-1 px-2">{{ $loan->loan_type }}</td>
                                        <td class="py-1 px-2">{{ number_format($loan->amount,2) }}</td>
                                        <td class="py-1 px-2">{{ number_format($loan->balance,2) }}</td>
                                        <td class="py-1 px-2">
                                            <span class="inline-block rounded px-2 py-0.5 text-xs {{ $loan->status==='active'?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700' }}">{{ $loan->status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-gray-400 py-2">No active loans</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <div class="font-semibold text-gray-700 mb-2">Available Control Numbers</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-500 text-xs">
                                    <th class="py-1 px-2 text-left">Control #</th>
                                    <th class="py-1 px-2 text-left">Amount</th>
                                    <th class="py-1 px-2 text-left">Due Date</th>
                                    <th class="py-1 px-2 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($member->bills as $bill)
                                    <tr>
                                        <td class="py-1 px-2">{{ $bill->control_number }}</td>
                                        <td class="py-1 px-2">{{ number_format($bill->amount,2) }}</td>
                                        <td class="py-1 px-2">{{ $bill->due_date }}</td>
                                        <td class="py-1 px-2">
                                            <span class="inline-block rounded px-2 py-0.5 text-xs {{ $bill->status==='paid'?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700' }}">{{ $bill->status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-gray-400 py-2">No control numbers</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-end bg-gray-50 p-4">
            <button wire:click="$emit('closeModal')" class="px-4 py-2 rounded bg-gray-700 text-white hover:bg-gray-800">Close</button>
        </div>
    </div>

</div> 