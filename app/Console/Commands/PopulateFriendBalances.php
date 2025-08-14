<?php

namespace App\Console\Commands;

use App\Models\FriendBalance;
use App\Models\User;
use Illuminate\Console\Command;

class PopulateFriendBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'friend-balances:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate friend balance records for all existing friendships';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Populating friend balance records for existing friendships...');

        $users = User::whereNotNull('email_verified_at')->get();
        $created = 0;

        foreach ($users as $user) {
            $friends = $user->getFriends();

            foreach ($friends as $friend) {
                // Create balance record if it doesn't exist
                $balance = FriendBalance::getOrCreateBalance($user->id, $friend->id);

                if ($balance->wasRecentlyCreated) {
                    $created++;
                    $this->line("Created balance record: {$user->name} ↔ {$friend->name}");
                }
            }
        }

        $this->info("Completed! Created {$created} new friend balance records.");

        // Now update all balances based on existing shared expenses
        $this->info('Recalculating balances based on existing shared expenses...');

        $expenses = \App\Models\SharedExpense::with('participants')->get();

        foreach ($expenses as $expense) {
            FriendBalance::updateBalanceForExpense($expense);
        }

        $this->info('All friend balances have been populated and calculated!');
    }
}
