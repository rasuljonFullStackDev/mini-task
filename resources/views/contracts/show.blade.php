<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Shartnoma #{{ $contract->number }}</h2>
            <div class="flex gap-2">
                @can('activate', $contract)
                    @if($contract->isDraft())
                    <form method="POST" action="{{ route('contracts.activate', $contract) }}">
                        @csrf
                        <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Faollashtirish</button>
                    </form>
                    @endif
                @endcan
                @can('addDelivery', $contract)
                    <a href="{{ route('deliveries.create', $contract) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Partiya kiritish</a>
                @endcan
                @if(auth()->user()->isAccountant() && !$contract->isDraft())
                    <a href="{{ route('accounting.show', $contract) }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Buxgalteriya</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
                    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                </div>
            @endif

            {{-- Contract info --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-3">Ma'lumotlar</h3>
                <dl class="grid grid-cols-3 gap-4 text-sm">
                    <div><dt class="text-gray-500">Yetkazuvchi</dt><dd class="font-medium">{{ $contract->supplier }}</dd></div>
                    <div><dt class="text-gray-500">Boshlanish</dt><dd>{{ $contract->start_date->format('d.m.Y') }}</dd></div>
                    <div><dt class="text-gray-500">Muddat sanasi</dt><dd>{{ $contract->deadlineDate()->format('d.m.Y') }}</dd></div>
                    <div><dt class="text-gray-500">Ijrochi</dt><dd>{{ $contract->executor->name }}</dd></div>
                    <div><dt class="text-gray-500">Holat</dt><dd>{{ ['draft'=>'Qoralama','active'=>'Faol','completed'=>'Yakunlangan'][$contract->status] }}</dd></div>
                    <div><dt class="text-gray-500">Umumiy summa</dt><dd class="font-medium">{{ number_format($contract->totalPaidAmount(), 2) }}</dd></div>
                </dl>
            </div>

            {{-- Items table --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-3">Tovar qatorlari</h3>
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nomi</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Pullik soni</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Bonus soni</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Narx</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Summa</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Yetkazilgan</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qoldiq</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($contract->items as $item)
                        <tr>
                            <td class="px-4 py-2">{{ $item->name }}</td>
                            <td class="px-4 py-2 text-right">{{ $item->paid_quantity }}</td>
                            <td class="px-4 py-2 text-right">{{ $item->bonus_quantity }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($item->totalPaidAmount(), 2) }}</td>
                            <td class="px-4 py-2 text-right">{{ $item->deliveredPaidQuantity() }}/{{ $item->deliveredBonusQuantity() }} bonus</td>
                            <td class="px-4 py-2 text-right {{ $item->remainingPaidQuantity() > 0 ? 'text-orange-600' : 'text-green-600' }}">
                                {{ $item->remainingPaidQuantity() }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Penalty card --}}
            @if(!$contract->isDraft())
            <div class="grid grid-cols-2 gap-6">
                {{-- Penalty calculator --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-3">Jarima</h3>
                    <form method="GET" action="{{ route('contracts.show', $contract) }}" class="flex gap-2 mb-4">
                        <input type="date" name="as_of" value="{{ $asOf->toDateString() }}" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <button type="submit" class="bg-gray-100 px-3 py-1 rounded border text-sm">Hisoblash</button>
                    </form>
                    <dl class="text-sm space-y-2">
                        <div class="flex justify-between"><dt class="text-gray-500">Muddat sanasi</dt><dd>{{ $contract->deadlineDate()->format('d.m.Y') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Kechikish kunlari</dt><dd>{{ $penaltyData['overdue_days'] }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Limit (50%)</dt><dd>{{ number_format($penaltyData['limit'], 2) }}</dd></div>
                        <div class="flex justify-between font-semibold"><dt>Jami jarima</dt><dd class="{{ bccomp($penaltyData['total'], '0', 2) > 0 ? 'text-red-600' : '' }}">{{ number_format($penaltyData['total'], 2) }}</dd></div>
                    </dl>

                    @if(count($penaltyData['daily']) > 0)
                    <div class="mt-4 max-h-40 overflow-y-auto">
                        <table class="min-w-full text-xs divide-y divide-gray-200">
                            <thead><tr>
                                <th class="py-1 text-left text-gray-500">Sana</th>
                                <th class="py-1 text-right text-gray-500">Kelmagan summa</th>
                                <th class="py-1 text-right text-gray-500">Jarima</th>
                            </tr></thead>
                            <tbody>
                                @foreach($penaltyData['daily'] as $day)
                                <tr>
                                    <td class="py-1">{{ $day['date'] }}</td>
                                    <td class="py-1 text-right">{{ number_format($day['outstanding'], 2) }}</td>
                                    <td class="py-1 text-right">{{ number_format($day['daily_penalty'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @can('view', $contract)
                    <form method="POST" action="{{ route('penalties.store', $contract) }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="as_of" value="{{ $asOf->toDateString() }}">
                        <button class="bg-orange-500 text-white px-3 py-1 rounded text-sm hover:bg-orange-600">
                            Boshliqqa yuborish
                        </button>
                    </form>
                    @endcan
                    @endif
                </div>

                {{-- Penalty approvals (chief) --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-3">Jarima holati</h3>
                    @forelse($contract->penalties as $penalty)
                    <div class="border rounded p-3 mb-3 text-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="font-medium">{{ $penalty->calculated_at->format('d.m.Y') }}</span>
                                <span class="ml-2 px-2 py-0.5 rounded text-xs
                                    {{ $penalty->status === 'approved' ? 'bg-green-100 text-green-800' : ($penalty->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ['pending'=>'Kutilmoqda','approved'=>'Tasdiqlandi','rejected'=>'Rad etildi'][$penalty->status] }}
                                </span>
                            </div>
                            <div class="text-right">
                                <div>Hisoblangan: {{ number_format($penalty->calculated_amount, 2) }}</div>
                                @if($penalty->isApproved())
                                <div class="text-green-700 font-medium">Tasdiqlangan: {{ number_format($penalty->approved_amount ?? $penalty->calculated_amount, 2) }}</div>
                                @endif
                            </div>
                        </div>
                        @can('review', $penalty)
                        <form method="POST" action="{{ route('penalties.review', $penalty) }}" class="mt-2 flex gap-2 items-end">
                            @csrf
                            <div>
                                <label class="block text-xs text-gray-500">Tasdiqlangan summa</label>
                                <input type="number" name="approved_amount" step="0.01" value="{{ $penalty->calculated_amount }}" class="border-gray-300 rounded text-xs w-28">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500">Izoh</label>
                                <input type="text" name="review_notes" class="border-gray-300 rounded text-xs w-40">
                            </div>
                            <button name="action" value="approve" class="bg-green-500 text-white px-2 py-1 rounded text-xs hover:bg-green-600">Tasdiqlash</button>
                            <button name="action" value="reject" class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">Rad etish</button>
                        </form>
                        @endcan
                    </div>
                    @empty
                    <p class="text-gray-400 text-sm">Jarima yo'q</p>
                    @endforelse
                </div>
            </div>
            @endif

            {{-- Deliveries --}}
            @if($contract->deliveries->isNotEmpty())
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-3">Yetkazilgan partiyalar</h3>
                @foreach($contract->deliveries as $delivery)
                <div class="border rounded p-3 mb-2 text-sm">
                    <div class="font-medium">{{ $delivery->delivery_date->format('d.m.Y') }} {{ $delivery->notes ? '— '.$delivery->notes : '' }}</div>
                    @foreach($delivery->items as $dItem)
                    <div class="ml-4 text-gray-600">{{ $dItem->contractItem->name }}: {{ $dItem->paid_quantity }} pullik, {{ $dItem->bonus_quantity }} bonus</div>
                    @endforeach
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
