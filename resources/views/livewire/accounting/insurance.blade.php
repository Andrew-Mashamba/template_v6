<div>
    <div class="max-w-7xl mx-auto py-10">
        <h2 class="text-2xl font-bold mb-6">Manage Insurances</h2>

        <!-- Success Message -->
        @if (session()->has('message'))
            <div class="bg-green-500 text-white px-4 py-2 mb-4 rounded">
                {{ session('message') }}
            </div>
        @endif

        <!-- Insurance Form -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <form wire:submit.prevent="{{ $insuranceId ? 'updateInsurance' : 'createInsurance' }}">
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Insurance Name</label>
                    <input type="text" id="name" wire:model="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter Insurance Name">
                    @error('name') <span class="text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Category</label>
                    <select id="category" wire:model="category" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        @foreach($categories as $categoryOption)
                            <option value="{{ $categoryOption }}">{{ ucfirst($categoryOption) }}</option>
                        @endforeach
                    </select>
                    @error('category') <span class="text-red-500">{{ $message }}</span> @enderror
                </div>

                <!-- Show for Employees or Members -->
                @if($category != 'loans')
                    <div class="mb-4">
                        <label for="coverage_amount" class="block text-gray-700 text-sm font-bold mb-2">Coverage Amount</label>
                        <input type="number" id="coverage_amount" wire:model="coverage_amount" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter Coverage Amount">
                        @error('coverage_amount') <span class="text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="premium" class="block text-gray-700 text-sm font-bold mb-2">Premium</label>
                        <input type="number" id="premium" wire:model="premium" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter Premium">
                        @error('premium') <span class="text-red-500">{{ $message }}</span> @enderror
                    </div>
                @endif

                <!-- Show for Loans -->
                @if($category == 'loans')
                    <div class="mb-4">
                        <label for="monthly_rate" class="block text-gray-700 text-sm font-bold mb-2">Monthly Insurance Rate (%)</label>
                        <input type="number" id="monthly_rate" wire:model="monthly_rate" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter Monthly Rate">
                        @error('monthly_rate') <span class="text-red-500">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        {{ $insuranceId ? 'Update Insurance' : 'Create Insurance' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Insurance List -->
        <div class="bg-white shadow rounded-lg p-6">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Category</th>
                    <th class="px-4 py-2">Coverage Amount</th>
                    <th class="px-4 py-2">Premium</th>
                    <th class="px-4 py-2">Monthly Rate</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($insurances as $insurance)
                    <tr>
                        <td class="border px-4 py-2">{{ $insurance->name }}</td>
                        <td class="border px-4 py-2">{{ ucfirst($insurance->category) }}</td>
                        <td class="border px-4 py-2">{{ $insurance->coverage_amount ? number_format($insurance->coverage_amount, 2) : '-' }}</td>
                        <td class="border px-4 py-2">{{ $insurance->premium ? number_format($insurance->premium, 2) : '-' }}</td>
                        <td class="border px-4 py-2">{{ $insurance->monthly_rate ? number_format($insurance->monthly_rate, 2) . '%' : '-' }}</td>
                        <td class="border px-4 py-2">
                            <button wire:click="editInsurance({{ $insurance->id }})" class="bg-yellow-500 text-white px-4 py-1 rounded">Edit</button>
                            <button wire:click="deleteInsurance({{ $insurance->id }})" class="bg-red-500 text-white px-4 py-1 rounded">Delete</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
