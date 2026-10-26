<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yangi shartnoma</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('contracts.store') }}">
                @csrf
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Shartnoma raqami *</label>
                            <input type="text" name="number" value="{{ old('number') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @error('number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Yetkazuvchi *</label>
                            <input type="text" name="supplier" value="{{ old('supplier') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @error('supplier')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Boshlanish sanasi *</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Muddat (kun) *</label>
                            <input type="number" name="duration_days" value="{{ old('duration_days', 10) }}" min="1" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Xarajat moddasi *</label>
                            <input type="text" name="expense_category" value="{{ old('expense_category', 'Dori vositalari') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>

                    <hr>
                    <h3 class="text-lg font-medium">Tovar qatorlari</h3>
                    <div id="items-container" class="space-y-3">
                        <div class="item-row grid grid-cols-4 gap-3 items-end border p-3 rounded">
                            <div>
                                <label class="block text-xs text-gray-500">Nomi *</label>
                                <input type="text" name="items[0][name]" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Pullik soni *</label>
                                <input type="number" name="items[0][paid_quantity]" min="1" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Bonus soni</label>
                                <input type="number" name="items[0][bonus_quantity]" min="0" value="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Narxi (dona) *</label>
                                <input type="number" name="items[0][unit_price]" step="0.01" min="0.01" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addItem()" class="text-blue-600 text-sm hover:underline">+ Qator qo'shish</button>

                    @if($errors->any())
                        <div class="bg-red-50 text-red-700 p-3 rounded text-sm">
                            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                        </div>
                    @endif

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('contracts.index') }}" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50">Bekor</a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Saqlash</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    let itemCount = 1;
    function addItem() {
        const container = document.getElementById('items-container');
        const i = itemCount++;
        container.insertAdjacentHTML('beforeend', `
            <div class="item-row grid grid-cols-4 gap-3 items-end border p-3 rounded">
                <div><label class="block text-xs text-gray-500">Nomi *</label>
                    <input type="text" name="items[${i}][name]" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"></div>
                <div><label class="block text-xs text-gray-500">Pullik soni *</label>
                    <input type="number" name="items[${i}][paid_quantity]" min="1" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"></div>
                <div><label class="block text-xs text-gray-500">Bonus soni</label>
                    <input type="number" name="items[${i}][bonus_quantity]" min="0" value="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"></div>
                <div><label class="block text-xs text-gray-500">Narxi (dona) *</label>
                    <input type="number" name="items[${i}][unit_price]" step="0.01" min="0.01" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"></div>
            </div>`);
    }
    </script>
</x-app-layout>
