<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Shartnomani tahrirlash #{{ $contract->number }}</h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('contracts.update', $contract) }}">
                @csrf @method('PUT')
                <div class="bg-white shadow rounded-lg p-6 space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700">Yetkazuvchi *</label>
                        <input type="text" name="supplier" value="{{ old('supplier', $contract->supplier) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Boshlanish sanasi *</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $contract->start_date->toDateString()) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Muddat (kun) *</label>
                        <input type="number" name="duration_days" value="{{ old('duration_days', $contract->duration_days) }}" min="1" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Xarajat moddasi *</label>
                        <input type="text" name="expense_category" value="{{ old('expense_category', $contract->expense_category) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></div>
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
