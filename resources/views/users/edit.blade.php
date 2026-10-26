<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('users.index') }}" class="text-gray-400 hover:text-gray-600 transition">←</a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800">Tahrirlash: {{ $user->name }}</h2>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf @method('PUT')
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">

                    <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                        <h3 class="font-semibold text-gray-700">Ma'lumotlarni o'zgartirish</h3>
                    </div>

                    <div class="px-6 py-6 space-y-5">

                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ism *</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="block w-full border {{ $errors->has('name') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="block w-full border {{ $errors->has('email') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Role SELECT --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
                            <div class="relative">
                                <select name="role" required
                                        class="block w-full border {{ $errors->has('role') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none bg-white pr-10">
                                    @foreach($roles as $val => $r)
                                        @if($val === 'superadmin' && !auth()->user()->isSuperAdmin())
                                            @continue
                                        @endif
                                        <option value="{{ $val }}" {{ old('role', $user->role) === $val ? 'selected' : '' }}>
                                            {{ $r['icon'] }} {{ $r['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-400">▼</div>
                            </div>
                            @error('role')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Password (optional) --}}
                        <div class="border-t border-gray-100 pt-5">
                            <h4 class="text-sm font-medium text-gray-700 mb-1">Parolni o'zgartirish</h4>
                            <p class="text-xs text-gray-400 mb-3">Bo'sh qoldirsa parol o'zgarmaydi</p>
                            <div class="space-y-3">
                                <div>
                                    <input type="password" name="password" minlength="6"
                                           placeholder="Yangi parol (ixtiyoriy)"
                                           class="block w-full border {{ $errors->has('password') ? 'border-red-400' : 'border-gray-300' }} rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <input type="password" name="password_confirmation"
                                           placeholder="Yangi parolni tasdiqlang"
                                           class="block w-full border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                        {{-- Delete button --}}
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                              onsubmit="return confirm('{{ $user->name }} ni o\'chirasizmi?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-red-500 hover:text-red-700 text-sm font-medium hover:bg-red-50 px-3 py-2 rounded-lg transition">
                                🗑 O'chirish
                            </button>
                        </form>
                        @else
                        <div></div>
                        @endif

                        <div class="flex gap-3">
                            <a href="{{ route('users.index') }}"
                               class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition">
                                Bekor
                            </a>
                            <button type="submit"
                                    class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                                ✅ Saqlash
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
