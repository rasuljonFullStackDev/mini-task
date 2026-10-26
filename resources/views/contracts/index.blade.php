<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Shartnomalar</h2>
            @can('create', App\Models\Contract::class)
                <a href="{{ route('contracts.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Yangi shartnoma</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Raqam</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Yetkazuvchi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Boshlanish</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Muddat (kun)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Holat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ijrochi</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($contracts as $contract)
                        <tr>
                            <td class="px-6 py-4 font-mono text-sm">{{ $contract->number }}</td>
                            <td class="px-6 py-4 text-sm">{{ $contract->supplier }}</td>
                            <td class="px-6 py-4 text-sm">{{ $contract->start_date->format('d.m.Y') }}</td>
                            <td class="px-6 py-4 text-sm">{{ $contract->duration_days }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = ['draft'=>'gray','active'=>'blue','completed'=>'green'];
                                    $statusLabels = ['draft'=>'Qoralama','active'=>'Faol','completed'=>'Yakunlangan'];
                                    $color = $statusColors[$contract->status];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                                    {{ $statusLabels[$contract->status] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $contract->executor->name }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('contracts.show', $contract) }}" class="text-blue-600 hover:underline text-sm">Ko'rish</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-6 py-4 text-center text-gray-400">Shartnomalar yo'q</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $contracts->links() }}</div>
        </div>
    </div>
</x-app-layout>
