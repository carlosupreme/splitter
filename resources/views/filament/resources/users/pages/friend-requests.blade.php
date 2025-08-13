<x-filament-panels::page>
    <div class="space-y-6">
        
        <!-- Pending Friend Requests Section -->
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Friend Requests
                    @if(count($pendingRequests) > 0)
                        <span class="ml-2 px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-full">
                            {{ count($pendingRequests) }}
                        </span>
                    @endif
                </h2>
            </div>
            
            <div class="p-6">
                @if(count($pendingRequests) > 0)
                    <div class="space-y-4">
                        @foreach($pendingRequests as $request)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                        <span class="text-white font-semibold text-lg">
                                            {{ strtoupper(substr($request['sender_name'], 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white">
                                            {{ $request['sender_name'] }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $request['sender_email'] }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            Sent {{ $request['created_at']->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <button 
                                        wire:click="acceptFriendRequest({{ $request['sender_id'] }})"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Accept
                                    </button>
                                    
                                    <button 
                                        wire:click="denyFriendRequest({{ $request['sender_id'] }})"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Deny
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No pending friend requests</h3>
                        <p class="text-gray-500 dark:text-gray-400">You don't have any friend requests at the moment.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Friends List Section -->
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    My Friends
                    @if(count($friends) > 0)
                        <span class="ml-2 px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full">
                            {{ count($friends) }}
                        </span>
                    @endif
                </h2>
            </div>
            
            <div class="p-6">
                @if(count($friends) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($friends as $friend)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-blue-600 rounded-full flex items-center justify-center">
                                        <span class="text-white font-semibold">
                                            {{ strtoupper(substr($friend['name'], 0, 1)) }}
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="font-medium text-gray-900 dark:text-white truncate">
                                            {{ $friend['name'] }}
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                            {{ $friend['email'] }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex-shrink-0">
                                    <button 
                                        wire:click="unfriend({{ $friend['id'] }})"
                                        wire:confirm="Are you sure you want to remove {{ $friend['name'] }} from your friends?"
                                        class="inline-flex items-center px-2 py-1 border border-red-300 dark:border-red-600 text-xs font-medium rounded text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                                        title="Remove friend"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span class="ml-1 hidden sm:inline">Remove</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No friends yet</h3>
                        <p class="text-gray-500 dark:text-gray-400">Start connecting with people to build your network!</p>
                    </div>
                @endif
            </div>
        </div>
        
    </div>
</x-filament-panels::page>