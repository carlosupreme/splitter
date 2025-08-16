# Fix Airbnb Expense Split Command

## Single Line Command for Production Console

```bash
php artisan tinker --execute="\$airbnbExpense = App\\Models\\BudgetItem::where('title', 'Airbnb uno')->first(); \$currentPayments = []; foreach (\$airbnbExpense->splits as \$split) { if (\$split->paid_amount > 0) { \$currentPayments[\$split->user_id] = \$split->paid_amount; } } \$airbnbExpense->splits()->delete(); \$airbnbExpense->createSplits(); foreach (\$currentPayments as \$userId => \$paidAmount) { \$split = \$airbnbExpense->splits()->where('user_id', \$userId)->first(); if (\$split) { \$split->update(['paid_amount' => \$paidAmount]); } } echo 'Fixed! Airbnb uno now split between ' . \$airbnbExpense->fresh()->splits->count() . ' people at \\$' . number_format(\$airbnbExpense->amount / \$airbnbExpense->fresh()->splits->count(), 2) . ' each.';"
```

## What this command does:
1. Finds the "Airbnb uno" expense
2. Backs up existing payments (preserves Carlos's $1,442 payment)
3. Deletes old splits (4 people)
4. Creates new splits (5 people = 4 attendees + organizer)
5. Restores all payments to correct users
6. Shows confirmation message

## Expected Result:
- Changes share from $435.50 to $348.40 per person
- Preserves all existing payments
- Carlos's payment remains intact but now shows as overpayment