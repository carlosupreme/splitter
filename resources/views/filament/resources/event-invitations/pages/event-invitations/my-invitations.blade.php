<x-filament-panels::page>
    <div class="space-y-6">
        
        <!-- Pending Invitations Section -->
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    Pending Invitations
                    @if(count($pendingInvitations) > 0)
                        <span class="ml-2 px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 rounded-full">
                            {{ count($pendingInvitations) }}
                        </span>
                    @endif
                </h2>
            </div>
            
            <div class="p-6">
                @if(count($pendingInvitations) > 0)
                    <div class="space-y-4">
                        @foreach($pendingInvitations as $invitation)
                            <div class="bg-gradient-to-r from-orange-50 to-yellow-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-4 border border-orange-200 dark:border-gray-600">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <h3 class="font-semibold text-gray-900 dark:text-white text-lg mr-3">
                                                {{ $invitation['event']['title'] }}
                                            </h3>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                {{ match($invitation['event']['category']) {
                                                    'party' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
                                                    'trip' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                    'dinner' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                                    'meeting' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                    'conference' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                                    'sports' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                                } }}">
                                                {{ ucfirst($invitation['event']['category']) }}
                                            </span>
                                        </div>
                                        
                                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">
                                            Invited by <strong>{{ $invitation['event']['organizer']['name'] }}</strong>
                                        </p>
                                        
                                        @if($invitation['event']['description'])
                                            <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">
                                                {{ $invitation['event']['description'] }}
                                            </p>
                                        @endif
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ \Carbon\Carbon::parse($invitation['event']['start_date'])->format('M j, Y g:i A') }}
                                            </div>
                                            
                                            @if($invitation['event']['location'])
                                                <div class="flex items-center text-gray-600 dark:text-gray-400">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    {{ $invitation['event']['location'] }}
                                                </div>
                                            @endif
                                            
                                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ \Carbon\Carbon::parse($invitation['invited_at'])->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-2 ml-4">
                                        <button 
                                            wire:click="acceptInvitation({{ $invitation['id'] }})"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Accept
                                        </button>
                                        
                                        <button 
                                            wire:click="declineInvitation({{ $invitation['id'] }})"
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Decline
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No pending invitations</h3>
                        <p class="text-gray-500 dark:text-gray-400">You don't have any pending event invitations at the moment.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Responded Invitations Section -->
        @if(count($respondedInvitations) > 0)
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Recent Responses
                        <span class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 rounded-full">
                            {{ count($respondedInvitations) }}
                        </span>
                    </h2>
                </div>
                
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($respondedInvitations as $invitation)
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white mr-2">
                                                {{ $invitation['event']['title'] }}
                                            </h4>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                {{ $invitation['status'] === 'accepted' 
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' 
                                                }}">
                                                {{ ucfirst($invitation['status']) }}
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                            <span>{{ $invitation['event']['organizer']['name'] }}</span>
                                            <span class="mx-2">•</span>
                                            <span>{{ \Carbon\Carbon::parse($invitation['responded_at'])->diffForHumans() }}</span>
                                        </div>
                                        
                                        @if($invitation['response_message'])
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 italic">
                                                "{{ $invitation['response_message'] }}"
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        
    </div>
</x-filament-panels::page>
