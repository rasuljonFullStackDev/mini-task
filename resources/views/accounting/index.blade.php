<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buxgalteriya</h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Budget lines summary --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-3">Smeta holati</h3>
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Moliyaviy yil</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Xarajat moddasi</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Oy</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Ajratilgan</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Sarflangan</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Qoldiq</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($budgetLines as $bl)
                        <tr>
                            <td class="px-4 py-2">{{ $bl->fiscal_year }}</td>
                            <td class="px-4 py-2">{{ $bl->expense_category }}</td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::createFromDate(null, $bl->month, 1)->format('F') }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($bl->allocated_amount, 2) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($bl->spentAmount(), 2) }}</td>
                            <td class="px-4 py-2 text-right {{ bccomp($bl->remainingAmount(), '0', 2) < 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ number_format($bl->remainingAmount(), 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Contracts list --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shartnoma</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Holat</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Umumiy summa</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">To'langan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tasdiqlangan jarima</th>
                        <th></th>
                    </tr></thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($contracts as $contract)
                        @php
                            $totalPaid = $contract->paymentStages->sum(fn($s) => $s->paidAmount());
                            $approvedPenalty = $contract->penalties->filter(fn($p) => $p->isApproved())->last();
                        @endphp
                        <tr>
                            <td class="px-6 py-4 text-sm font-mono">{{ $contract->number }}</td>
                            <td class="px-6 py-4 text-sm">{{ ['active'=>'Faol','completed'=>'Yakunlangan'][$contract->status] ?? $contract->status }}</td>
                            <td class="px-6 py-4 text-right text-sm">{{ number_format($contract->totalPaidAmount(), 2) }}</td>
                            <td class="px-6 py-4 text-right text-sm">{{ number_format($totalPaid, 2) }}</td>
                            <td class="px-6 py-4 text-right text-sm {{ $approvedPenalty ? 'text-red-600' : 'text-gray-400' }}">
                                {{ $approvedPenalty ? number_format($approvedPenalty->effectiveAmount(), 2) : '0.00' }}
                            </td>
                            <td class="px-6 py-4 text-right"><a href="{{ route('accounting.show', $contract) }}" class="text-purple-600 hover:underline text-sm">Batafsil</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-6 py-4 text-center text-gray-400">Shartnomalar yo'q</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $contracts->links() }}</div>
        </div>
    </div>
</x-app-layout>
