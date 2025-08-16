# Fix Elsa's Payment Command

## Single Line Command for Production Console

```bash
php artisan tinker --execute="\$elsa = App\\Models\\User::where('name', 'Elsa')->first(); \$airbnbExpense = App\\Models\\BudgetItem::where('title', 'Airbnb uno')->first(); \$elsaSplit = \$airbnbExpense->splits()->where('user_id', \$elsa->id)->first(); \$elsaTotalPaid = App\\Models\\BudgetPayment::where('budget_item_id', \$airbnbExpense->id)->where('paid_by', \$elsa->id)->sum('amount'); \$elsaSplit->update(['paid_amount' => \$elsaTotalPaid]); echo 'Fixed! Elsa now shows \\$' . \$elsaTotalPaid . ' paid, owes \\$' . number_format(\$elsaSplit->share_amount - \$elsaTotalPaid, 2) . ' remaining.';"
```

## What this command does:
1. Finds Elsa's user record
2. Finds the "Airbnb uno" expense
3. Finds Elsa's split for this expense
4. Calculates her total actual payments from budget_payments table ($300)
5. Updates her split's paid_amount to reflect the real payment
6. Shows confirmation

## Expected Result:
- Elsa's paid amount changes from $0.00 to $300.00
- Elsa now owes only $48.40 remaining (instead of full $348.40)
- Her $300 payment is now properly reflected in the app