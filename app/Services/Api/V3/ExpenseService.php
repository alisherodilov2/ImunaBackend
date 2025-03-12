<?php

namespace App\Services\Api\V3;

use App\Http\Resources\Expense\ExpenseResource;
use App\Models\Branch;
use App\Models\DailyRepot;
use App\Models\DailyRepotExpense;
use App\Models\Expense;
use App\Models\User;
use App\Services\Api\V3\Contracts\ExpenseServiceInterface;
use App\Traits\Crud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class ExpenseService implements ExpenseServiceInterface
{
    public $modelClass = Expense::class;
    use Crud;
    public function filter($request)
    {
        if (auth()->user()->role === User::USER_ROLE_DIRECTOR) {
            return $this->modelClass::Where('user_id', auth()->id())
                ->with('expenseType')
                ->get();
        }

        $startDate = now();
        $endDate = now();
        if (isset($request->start_date)) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
            if ($parsedDate->format('Y-m-d') ===  $request->start_date) {
                $startDate = $parsedDate;
            }
        }
        if (isset($request->end_date)) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
            if ($parsedDate->format('Y-m-d') ===  $request->end_date) {
                $endDate = $parsedDate;
            }
        }
        return [
            'data' => ExpenseResource::collection($this->modelClass::where('user_id', auth()->user()->id)
                ->with('expenseType')
                ->where(function ($q) use ($request, $startDate, $endDate) {
                    if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                        $q
                            ->whereDate('created_at', $endDate->format('Y-m-d'));
                    } else {
                        $q
                            ->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);;
                    }
                })
                ->get()),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;

        $result = $this->store($request);
        $dailyRepot = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'start'])
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->first();

        if ($dailyRepot) {
            // if (!DailyRepotExpense::where(['expense_id', $result->id, 'daily_repot_id' => $dailyRepot->id])->first()) {
            //     DailyRepotExpense::create([
            //         'expense_id' => $result->id,
            //         'daily_repot_id' => $dailyRepot->id
            //     ]);
            // }
        } else {
            $dailyRepot =    DailyRepot::create([
                'user_id' => auth()->id(),
                'status' => 'start',

            ]);
            // if (!DailyRepotExpense::where(['expense_id', $result->id, 'daily_repot_id' => $dailyRepot->id])->first()) {
            //     DailyRepotExpense::create([
            //         'expense_id' => $result->id,
            //         'daily_repot_id' => $dailyRepot->id
            //     ]);
            // }
        }
        DailyRepotExpense::create([
            'expense_id' => $result->id,
            'daily_repot_id' => $dailyRepot->id
        ]);
        $batch_number  = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'finish'])->whereDate('created_at', $dailyRepot->created_at->format('Y-m-d'))->max('batch_number');
        $dailyRepot->update([
            'expemse_total_price' => Expense::where('user_id', auth()->id())->whereDate('created_at', now()->format('Y-m-d'))
                ->whereIn('id', DailyRepotExpense::where('daily_repot_id', $dailyRepot->id)->pluck('expense_id'))
                ->sum('price'),
            'batch_number' => $batch_number + 1
        ]);


        return $this->modelClass::where('id', $result->id)->with('expenseType')->first();
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);

        return $this->modelClass::where('id', $result->id)->with('expenseType')->first();
    }


    public function repot($request)
    {
        $startDate = now();
        $endDate = now();
        $branch_id = 0;
        $branchAllId = [];
        if (isset($request->branch_id) && ($request->branch_id > 0 || $request->branch_id == 'all')) {
            $branch_id = $request->branch_id;
            if ($branch_id == 'all') {
                $branchData = Branch::where('main_branch_id', auth()->user()->id)->with('branchItems')->first();
                if ($branchData) {
                    $branchAllId[] = $branchData->main_branch_id;
                    $branchAllId = [
                        ...$branchAllId,
                        ...$branchData->branchItems->pluck('target_branch_id')->toArray()
                    ];
                }
            }
        }
        if (isset($request->start_date)) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
            if ($parsedDate->format('Y-m-d') ===  $request->start_date) {
                $startDate = $parsedDate;
            }
        }
        if (isset($request->end_date)) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
            if ($parsedDate->format('Y-m-d') ===  $request->end_date) {
                $endDate = $parsedDate;
            }
        }
        $expense = $this->modelClass::where(function ($q) use ($startDate, $endDate, $branch_id, $branchAllId) {
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('expenses.created_at', $endDate->format('Y-m-d'));
            } else {
                $q->whereBetween('expenses.created_at', [$startDate->format('Y-m-d'), $endDate->copy()->addDay()->format('Y-m-d')]);
            }
            if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                if ($branch_id == 'all') {
                    $q->whereIn('expenses.user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
                } elseif ($branch_id > 0) {
                    $q->whereIn('expenses.user_id', User::where('owner_id', $branch_id)->pluck('id'));
                } else {
                    $q->whereIn('expenses.user_id', User::where('owner_id', auth()->id())->pluck('id'));
                }
            } else {
                $q->where('expenses.user_id', auth()->id());
            }
        })
            ->join('users', 'expenses.user_id', '=', 'users.id')
            ->selectRaw('
                DATE(expenses.created_at) as date,
                COUNT(DISTINCT expenses.expense_type_id) as unique_expense_type_count,
                SUM(expenses.price) as total_price,
                GROUP_CONCAT(expenses.user_id) as user_ids,
                users.owner_id as owner_id
            ')
            ->groupByRaw('DATE(expenses.created_at), users.owner_id')
            ->get();

        return [
            'data' => ($expense->map((function ($item) {
                return [
                    'owner_name' => User::find($item->owner_id)->name ?? '-',
                    'date' => $item->date,
                    'unique_expense_type_count' => $item->unique_expense_type_count,
                    'total_price' => $item->total_price,
                    'user_ids' => $item->user_ids,
                    'owner_id' => $item->owner_id,
                ];
            }))),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }
    public function repotShow($request)
    {

        $materialExpense = $this->modelClass::whereDate('created_at', $request->date)
            // ->whereHas('user', function ($q) use ($request) {
            //     // if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
            //         $q->where('owner_id', $request->branch_id);
            //     // }
            // })
            ->where(function ($q)use($request) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    $q->whereIn('user_id', User::where('owner_id', $request->branch_id)->pluck('id'));
                    // $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                } else {
                    $q->where('user_id', auth()->id());
                }
            })
            ->with(['expenseType'])
            ->get();
        return ($materialExpense);
    }
}
