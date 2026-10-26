<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">Foydalanuvchilar</h2>
                <p class="text-sm text-gray-500 mt-0.5">Jami: {{ $users->count() }} ta</p>
            </div>
            <a href="{{ route('users.create') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                <span class="text-lg">+</span> Yangi foydalanuvchi
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="flex items-center gap-3 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg mb-6">
                    <span>✅</span> {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg mb-6">
                    @foreach($errors->all() as $e)<p>⚠️ {{ $e }}</p>@endforeach
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ism</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Qo'shilgan</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amallar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($users as $user)
                        @php
                            $r = $roles[$user->role] ?? ['label'=>$user->role,'icon'=>'👤','color'=>'gray'];
                            $colorMap = [
                                'red'    => 'bg-red-100 text-red-800 border-red-200',
                                'purple' => 'bg-purple-100 text-purple-800 border-purple-200',
                                'green'  => 'bg-green-100 text-green-800 border-green-200',
                                'orange' => 'bg-orange-100 text-orange-800 border-orange-200',
                                'gray'   => 'bg-gray-100 text-gray-700 border-gray-200',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50 transition {{ $user->id === auth()->id() ? 'bg-blue-50' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-sm font-bold text-gray-600">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 text-sm">{{ $user->name }}</div>
                                        @if($user->id === auth()->id())
                                            <div class="text-xs text-blue-500">siz</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs font-semibold {{ $colorMap[$r['color']] }}">
                                    {{ $r['icon'] }} {{ $r['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-400">{{ $user->created_at->format('d.m.Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('users.edit', $user) }}"
                                       class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm font-medium hover:bg-indigo-50 px-3 py-1.5 rounded-lg transition">
                                        ✏️ Tahrirlash
                                    </a>
                                    @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}"
                                          onsubmit="return confirm('{{ $user->name }} ni o\'chirasizmi?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 text-red-500 hover:text-red-700 text-sm font-medium hover:bg-red-50 px-3 py-1.5 rounded-lg transition">
                                            🗑 O'chirish
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Role legend --}}
            <div class="mt-6 flex gap-3 flex-wrap">
                @foreach($roles as $key => $r)
                @php $cnt = $users->where('role',$key)->count(); @endphp
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ $r['icon'] }}</span>
                    <span>{{ $r['label'] }}: <strong>{{ $cnt }}</strong></span>
                </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
