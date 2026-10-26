<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Buxgalteriya — Shartnoma #{{ $contract->number }}</h2>
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

            {{-- Approved penalty --}}
            @php $approvedPenalty = $contract->penalties->filter(fn($p) => $p->isApproved())->last(); @endphp
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-2">Tasdiqlangan jarima</h3>
                @if($approvedPenalty)
                    <p class="text-2xl font-bold text-red-600">{{ number_format($approvedPenalty->effectiveAmount(), 2) }}</p>
                    <p class="text-sm text-gray-500 mt-1">Tasdiqlangan: {{ $approvedPenalty->reviewed_at?->format('d.m.Y') }}</p>
                @else
                    <p class="text-gray-400">Tasdiqlangan jarima yo'q</p>
                @endif
            </div>

            {{-- Payment stages --}}
            @foreach($contract->paymentStages as $stage)
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-semibold text-lg">{{ $stage->name }} ({{ $stage->percentage }}%)</h3>
                        <div class="text-sm text-gray-500">
                            Summa: {{ number_format($stage->amount, 2) }} |
                            To'langan: {{ number_format($stage->paidAmount(), 2) }} |
                            Qoldiq: <span class="{{ bccomp($stage->remainingAmount(), '0', 2) > 0 ? 'text-orange-600' : 'text-green-600' }}">{{ number_format($stage->remainingAmount(), 2) }}</span>
                        </div>
                        <div class="text-sm mt-1">
                            <span class="px-2 py-0.5 rounded text-xs
                                {{ match($stage->computedStatus()) {
                                    'paid' => 'bg-green-100 text-green-800',
                                    'partial' => 'bg-blue-100 text-blue-800',
                                    'overdue' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                } }}">
                                {{ match($stage->computedStatus()) {
                                    'scheduled' => 'Rejalashtirilgan',
                                    'ready' => "To'lashga tayyor",
                                    'partial' => 'Qisman to\'langan',
                                    'paid' => "To'langan",
                                    'overdue' => 'Muddati o\'tgan',
                                    default => $stage->computedStatus()
                                } }}
                            </span>
                            @if($stage->due_date) <span class="ml-2 text-xs text-gray-400">Muddat: {{ $stage->due_date->format('d.m.Y') }}</span> @endif
                        </div>
                    </div>
                    {{-- Add payment form --}}
                    @if($stage->isConditionMet() && bccomp($stage->remainingAmount(), '0', 2) > 0)
                    <div class="border rounded p-3 text-sm">
                        <form method="POST" action="{{ route('payments.store') }}">
                            @csrf
                            <input type="hidden" name="payment_stage_id" value="{{ $stage->id }}">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500">Summa</label>
                                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $stage->remainingAmount() }}" placeholder="{{ $stage->remainingAmount() }}" class="border-gray-300 rounded text-sm w-full">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Sana</label>
                                    <input type="date" name="payment_date" value="{{ now()->toDateString() }}" class="border-gray-300 rounded text-sm w-full">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Smeta oyi</label>
                                    <select name="budget_line_id" class="border-gray-300 rounded text-sm w-full">
                                        @foreach($budgetLines as $bl)
                                        <option value="{{ $bl->id }}">{{ $bl->fiscal_year }} {{ \Carbon\Carbon::createFromDate(null,$bl->month,1)->format('F') }} — qoldiq: {{ number_format($bl->remainingAmount(), 2) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Raqam/Izoh</label>
                                    <input type="text" name="reference" class="border-gray-300 rounded text-sm w-full">
                                </div>
                            </div>
                            <button type="submit" class="mt-2 bg-purple-600 text-white px-3 py-1 rounded text-sm hover:bg-purple-700 w-full">To'lov kiritish</button>
                        </form>
                    </div>
                    @endif
                </div>

                {{-- Payments registry --}}
                @php $activePayments = $stage->payments->where('is_voided', false); $voidedPayments = $stage->payments->where('is_voided', true); @endphp
                @if($stage->payments->isNotEmpty())
                <table class="min-w-full text-xs divide-y divide-gray-200 mt-2">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-3 py-1 text-left text-gray-500">Sana</th>
                        <th class="px-3 py-1 text-right text-gray-500">Summa</th>
                        <th class="px-3 py-1 text-left text-gray-500">Smeta oyi</th>
                        <th class="px-3 py-1 text-left text-gray-500">Raqam</th>
                        <th class="px-3 py-1 text-left text-gray-500">Kim kiritgan</th>
                        <th class="px-3 py-1 text-left text-gray-500">Holat</th>
                        <th></th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($stage->payments->sortBy('payment_date') as $payment)
                        <tr class="{{ $payment->is_voided ? 'opacity-50 bg-red-50' : '' }}">
                            <td class="px-3 py-1">{{ $payment->payment_date->format('d.m.Y') }}</td>
                            <td class="px-3 py-1 text-right {{ $payment->is_voided ? 'line-through' : '' }}">{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-3 py-1">{{ $payment->budgetLine->fiscal_year }} {{ \Carbon\Carbon::createFromDate(null,$payment->budgetLine->month,1)->format('M') }}</td>
                            <td class="px-3 py-1">{{ $payment->reference ?? '—' }}</td>
                            <td class="px-3 py-1">{{ $payment->createdByUser->name }}</td>
                            <td class="px-3 py-1">
                                @if($payment->is_voided)
                                    <span class="text-red-600">Bekor: {{ $payment->void_reason }}</span>
                                @else
                                    <span class="text-green-600">Faol</span>
                                @endif
                            </td>
                            <td class="px-3 py-1">
                                @if(!$payment->is_voided)
                                @can('void', $payment)
                                <form method="POST" action="{{ route('payments.void', $payment) }}" onsubmit="return confirm('Bekor qilish sababini kiriting')">
                                    @csrf
                                    <input type="text" name="void_reason" placeholder="Sabab" required class="border-gray-300 rounded text-xs w-32 mr-1">
                                    <button type="submit" class="text-red-600 hover:underline text-xs">Bekor qilish</button>
                                </form>
                                @endcan
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
            @endforeach

            <div class="mt-4">
                <a href="{{ route('accounting.index') }}" class="text-purple-600 hover:underline">← Buxgalteriya ro'yxati</a>
            </div>
        </div>
    </div>
</x-app-layout>
