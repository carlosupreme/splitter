Critical Bug #1: Split Recalculation Missing

Location: BudgetItem.createSplits() - Lines 56-92
Problem: When attendees leave/join events, splits are not recalculated automatically
Impact: Incorrect split amounts when attendee count changes

Current Behavior:
- Initial expense: 5 people × $200 = $1000
- 1 person leaves → Still 5 splits of $200 (total $1000) ❌
- Should be: 4 people × $250 = $1000 ✅

Critical Bug #2: Organizer Can Be Excluded from Splits

Location: BudgetItem.createSplits() - Lines 68-72
Problem: If organizer is already in acceptedInvitees, they won't be added again, but if they're not, they get added. Inconsistent logic.

// Current problematic logic:
if (!$attendees->contains('id', $organizer->id)) {
$attendees->push($organizer);
}

Issue: Organizer should ALWAYS be included regardless of invitation status.

Bug #3: Event Invitation Status Logic Error

Location: Event.getUserInvitationStatus() - Lines 118-123
Problem: Uses string comparison instead of enum comparison

// Current:
InvitationStatus::from($invitation->status)
// Should handle: What if status is already an enum instance?

Bug #4: Orphaned Splits When Payments Exist

Location: Split recalculation system
Problem: When attendee count changes, existing splits with payments could become orphaned or incorrect

Scenario:
1. 5 attendees, $200 each
2. User A pays $200 (marked as "paid")
3. 1 attendee leaves → Should be $250 each
4. User A now owes $50 more, but their split shows "paid" ❌

Bug #5: Missing Event Status Validation

Location: BudgetItem.createSplits()
Problem: No validation that event hasn't ended or been cancelled

Bug #6: Potential Memory Issues

Location: Event.getBudgetSummaryForUser() - Lines 170-196
Problem: Loads ALL expenses in memory without pagination
foreach ($this->expenses as $expense) // Could be hundreds of expenses

Bug #7: Inconsistent Decimal Precision

Location: Multiple models
Problem: Some calculations use float, others use decimal:2
- BudgetSplit.getRemainingAmount() returns float
- Database stores decimal:2
- Could cause precision issues in calculations

Design Issue #1: Event Budget vs SharedExpense Confusion

Location: Event.getTotalIncomes() - Lines 160-167
Problem: Method name suggests income items, but actually calculates total payments
public function getTotalIncomes(): float // Misleading name
{
return $this->expenses()  // Actually payments, not incomes
->with('payments')
->get()
->flatMap->payments
->sum('amount');
}

Design Issue #2: No Audit Trail

Location: Split recalculation
Problem: When splits change, no history of previous amounts or why they changed

Would you like me to propose solutions for these issues, starting with the most critical ones?
