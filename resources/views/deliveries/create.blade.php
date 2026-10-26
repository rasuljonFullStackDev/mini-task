<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Partiya kiritish — #{{ $contract->number }}</h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('deliveries.store', $contract) }}">
                @csrf
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Yetkazilgan sana *</label>
                            <input type="date" name="delivery_date" value="{{ old('delivery_date', now()->toDateString()) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Izoh</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Tovar</th>
                                <th class="px-3 py-2 text-right text-xs text-gray-500 uppercase">Qoldiq pullik</th>
                                <th class="px-3 py-2 text-right text-xs text-gray-500 uppercase">Qoldiq bonus</th>
                                <th class="px-3 py-2 text-center text-xs text-gray-500 uppercase">Kiritilgan pullik</th>
                                <th class="px-3 py-2 text-center text-xs text-gray-500 uppercase">Kiritilgan bonus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($contract->items as $i => $item)
                            <tr>
                                <td class="px-3 py-2">{{ $item->name }}
                                    <input type="hidden" name="items[{{ $i }}][contract_item_id]" value="{{ $item->id }}">
                                </td>
                                <td class="px-3 py-2 text-right text-orange-600">{{ $item->remainingPaidQuantity() }}</td>
                                <td class="px-3 py-2 text-right text-blue-600">{{ $item->remainingBonusQuantity() }}</td>
                                <td class="px-3 py-2">
                                    <input type="number" name="items[{{ $i }}][paid_quantity]" value="0" min="0" max="{{ $item->remainingPaidQuantity() }}" class="w-full border-gray-300 rounded text-sm text-center">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="items[{{ $i }}][bonus_quantity]" value="0" min="0" max="{{ $item->remainingBonusQuantity() }}" class="w-full border-gray-300 rounded text-sm text-center">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($errors->any())<div class="bg-red-50 text-red-700 p-3 rounded text-sm">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('contracts.show', $contract) }}" class="px-4 py-2 border rounded hover:bg-gray-50">Bekor</a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Saqlash</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
