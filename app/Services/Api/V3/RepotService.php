<?php

namespace App\Services\Api\V3;

use App\Http\Resources\DailyRepot\DailyRepotResource;
use App\Http\Resources\DoctorBalance\DoctorBalanceResource;
use App\Http\Resources\DoctorBalance\DoctorBalanceShowResource;
use App\Http\Resources\ReferringDoctor\ReferringDoctorResource;
use App\Http\Resources\Repot\RepotCcounterpartyResoruce;
use App\Models\Advertisements;
use App\Models\Client;
use App\Models\ClientBalance;
use App\Models\ClientValue;
use App\Models\ClinetPaymet;
use App\Models\DailyRepot;
use App\Models\DoctorBalance;
use App\Models\Expense;
use App\Models\GraphArchive;
use App\Models\MaterialExpense;
use App\Models\ReferringDoctor;
use App\Models\Services;
use App\Models\Branch;
use App\Models\Departments;
use App\Models\PharmacyProduct;
use App\Models\ReferringDoctorBalance;
use App\Models\User;
use App\Services\Api\V3\Contracts\RepotServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepotService implements RepotServiceInterface
{

    function daysBetweenDates($date1, $date2)
    {
        $start = Carbon::parse($date1);
        $end = Carbon::parse($date2);
        return $start->diffInDays($end);
    }
    function getMonthByIndex($index)
    {
        $oylar = [
            ["label" => "yanvar", "value" => 1],
            ["label" => "fevral", "value" => 2],
            ["label" => "mart", "value" => 3],
            ["label" => "aprel", "value" => 4],
            ["label" => "may", "value" => 5],
            ["label" => "iyun", "value" => 6],
            ["label" => "iyul", "value" => 7],
            ["label" => "avgust", "value" => 8],
            ["label" => "sentyabr", "value" => 9],
            ["label" => "oktyabr", "value" => 10],
            ["label" => "noyabr", "value" => 11],
            ["label" => "dekabr", "value" => 12],
        ];

        // Check if the index is valid
        foreach ($oylar as $month) {
            if ($month['value'] == $index) {
                return $month; // Return the month if found
            }
        }

        return null; // Return null if not found
    }
    public function repot($request)
    {
        $currentMonthIndex = Carbon::now()->month;

        $is_all = false;
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
        $currentdate = now()->format('Y-m-d');
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
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
        $kontragent = User::where(['role' => User::USER_ROLE_COUNTERPARTY])
            ->where(function ($q) use ($branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    if ($branch_id == 'all') {
                        $q->whereIn('owner_id', $branchAllId);
                    } else
                    if ($branch_id > 0) {
                        $q->where('owner_id', $branch_id);
                    } else {
                        $q->where('owner_id', auth()->user()->id);
                    }
                } else {
                    $q->where('owner_id', auth()->user()->owner_id);
                }
            })
            ->whereHas('referringDoctor', function ($q) use ($currentMonthIndex, $currentdate) {
                $q->whereHas('referringDoctorBalance', function ($q) use ($currentMonthIndex, $currentdate) {
                    // $q->whereMonth('date', $currentMonthIndex);
                    $q->whereDate('date', $currentdate);
                });
            })->with(['referringDoctor' => function ($q) use ($currentMonthIndex, $currentdate) {
                $q->with(['referringDoctorBalance' => function ($q) use ($currentMonthIndex, $currentdate) {
                    $q->whereDate('date', $currentdate);
                }]);
            }])
            ->get();

        $docotr = User::where(['role' => User::USER_ROLE_DOCTOR])->where(function ($q) {
            if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                $q->where('owner_id', auth()->user()->owner_id);
            } else {
                $q->where('owner_id', auth()->user()->id);
            }
        })
            ->whereHas('doctorBalance', function ($q) use ($currentMonthIndex, $currentdate) {
                $q->whereDate('date', $currentdate);
            })->with(['doctorBalance' => function ($q) use ($currentMonthIndex, $currentdate) {
                $q->whereDate('date', $currentdate);
            }])
            ->get();
        $umumiy = Client::whereNotNull('parent_id')

            ->whereHas('parent', function ($query) use ($request) {
                if (isset($request->full_name)) {
                    $query->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                }
                if (isset($request->phone)) {
                    $query->where('phone', 'like', '%' . $request->phone . '%');
                }
                if (isset($request->person_id)) {
                    $query->where('person_id', 'like', '%' . $request->person_id . '%');
                }
            })
            ->with(['clientPayment.user', 'clientValue.service.department', 'user.owner', 'currentBalance:id,balance'])
            ->where(function ($q) use ($startDate, $endDate, $branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                    $q->where('user_id', auth()->id());
                } else {
                    if ($branch_id == 'all') {
                        $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
                    } else
                    if ($branch_id > 0) {
                        $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
                    } else {
                        $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                    }
                }
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('created_at', $startDate->format('Y-m-d'));
                } else {
                    $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                }
            })
            // ->where('is')
        ;
        if (isset($request->status) && $request->status == 'pay_all_client') {
            $expence =  Expense::where(function ($q) use ($startDate, $endDate, $branch_id, $branchAllId) {

                if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                    $q->where('user_id', auth()->id());
                } else {
                    if ($branch_id == 'all') {
                        $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
                    } else
                    if ($branch_id > 0) {
                        $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
                    } else {
                        $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                    }
                }




                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('created_at', $startDate->format('Y-m-d'));
                } else {
                    $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                }
            })->get();
            return [
                'data' => $umumiy

                    ->where(function ($q) use ($request) {
                        if (isset($request->is_statsionar)) {
                            $q->where('is_statsionar', 1);
                        } else {
                            $q
                                ->whereNull('is_statsionar')
                                ->orWhere('is_statsionar', 0)
                            ;
                        }
                    })
                    ->get()->map((function ($item) {
                        $balnce = Client::find($item->parent_id)->balance ?? 0;
                        return  [
                            ...$item->toArray(),
                            'balance' => $balnce
                        ];
                    })),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'expence' => [
                    'cash' => $expence
                        ->where('pay_type', 'cash')
                        ->sum('price'),
                    'card' => $expence
                        ->where('pay_type', 'card')
                        ->sum('price'),
                    'transfer' => $expence
                        ->where('pay_type', 'transfer')
                        ->sum('price'),
                    'total' => $expence->sum('price'),
                ],

            ];
        }
        $tolovlar = ClinetPaymet::where(function ($q) use ($startDate, $endDate, $branch_id, $branchAllId) {
            if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
            } else {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
                } else
                if ($branch_id > 0) {
                    $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                }
            }
        })
            ->whereHas('client', function ($q) use ($currentMonthIndex, $currentdate) {
                $q->whereDate('created_at', $currentdate);
            });
        $xizmatlar = ClientValue::where(function ($q) use ($startDate, $endDate, $branch_id, $branchAllId) {
            if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                // $q->where('user_id',  auth()->id());
                // $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
            } else {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
                } else
                if ($branch_id > 0) {
                    $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                }
            }
        })
            ->where('is_active', 1)
            ->whereHas('client', function ($q) use ($currentMonthIndex, $currentdate) {
                $q->whereDate('created_at', $currentdate);
            });

        $expence =  Expense::where(function ($q) use ($startDate, $endDate, $branch_id, $branchAllId) {
            if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                $q->where('user_id', auth()->id());
            } else {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
                } else
                if ($branch_id > 0) {
                    $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                }
            }

            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('created_at', $startDate->format('Y-m-d'));
            } else {
                $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            }
        })

            ->sum('price');
        $materialExpense =  MaterialExpense::where(function ($q) use ($startDate, $endDate, $branch_id, $branchAllId) {
            if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                $q->where('user_id', auth()->id());
            } else {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
                } else
                if ($branch_id > 0) {
                    $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                }
            }
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('created_at', $startDate->format('Y-m-d'));
            } else {
                $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            }
        })
            ->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'))
            ->with(['materialExpenseItem.productReceptionItem'])->get()
            ->sum(function ($q) {
                return $q->materialExpenseItem->sum(function ($q2) {
                    return $q2->productReceptionItem->sum('price')  * $q2->qty;
                });
            });
        $dailyRepot = DailyRepot::where(function ($q) use ($startDate, $endDate, $branch_id, $branchAllId) {
            if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                $q->where(['user_id' => auth()->id(), 'status' => 'start']);
            } else {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
                } else
                if ($branch_id > 0) {
                    $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                }
            }
        })->whereDate('created_at', now()->format('Y-m-d'))->first();
        $dailyBalance =  $dailyRepot ? ClientBalance::where('daily_repot_id', $dailyRepot->id)
            ->where('status', 'pay')
            ->sum('price') : 0;
        // statsionar room price
        // $stationarClint = Client
        // bals 
        $balance = ClientBalance::where('daily_repot_id', $dailyRepot->id)
            ->where('status', 'pay')
            ->sum('price')-ClientBalance::where('daily_repot_id', $dailyRepot->id)
            ->where('status', 'use')
            ->sum('price');
        // $balance = ClinetPaymet::where('balance', '>', 0)->where(function ($q) use ($branch_id, $branchAllId, $startDate, $endDate) {
        //     if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
        //         $q->where('user_id', auth()->id());
        //     } else {
        //         if ($branch_id == 'all') {
        //             $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
        //         } else
        //         if ($branch_id > 0) {
        //             $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
        //         } else {
        //             $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
        //         }
        //     }
        //     if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
        //         $q->whereDate('created_at', $startDate->format('Y-m-d'));
        //     } else {
        //         $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
        //     }
        // })->sum('balance');
        return [
            'kontragent' => $kontragent->sum(function ($clientValue) {
                return $clientValue->referringDoctor
                    ->sum(function ($q) {
                        return   $q->referringDoctorBalance->sum('total_kounteragent_contribution_price');
                    });
                // Agar qiymat mavjud bo'lmasa 0 qilamiz

            }),
            'stationar_total_price' =>   $umumiy
                ->get()
                ->where('is_statsionar', 1)
                ->sum(function ($q) {
                    $qty = 0;
                    if ($q->day_qty > 0) {
                        $qty = $q->day_qty;
                    } else {
                        if ($q->is_finish_statsionar) {
                            $qty = $this->daysBetweenDates($q->admission_date, $q->finish_statsionar_date) + 1;
                        } else {
                            $qty = $this->daysBetweenDates($q->admission_date, now()->format('Y-m-d')) + 1;
                        }
                    }
                    return $q->total_price + ($q->statsionar_room_price * $qty);
                }),
            'total_price' =>   $umumiy
                ->whereNull('is_statsionar')
                ->orWhere('is_statsionar', 0)
                ->sum('total_price'),
            'sss' => $umumiy
                ->Where('is_statsionar', 1)
                ->get(),

            'doctor' => $docotr->sum(function ($clientValue) {
                return $clientValue->doctorBalance
                    ->sum(function ($q) {
                        return   $q->total_doctor_contribution_price;
                    });
                // Agar qiymat mavjud bo'lmasa 0 qilamiz

            }),
            'balance' =>    $balance,
            'debt' =>   $xizmatlar->select(DB::raw("
            SUM(
                CASE 
                    WHEN discount <= 100 
                    THEN (total_price - (total_price / 100 ) * discount) - pay_price
                    ELSE (total_price - discount) - pay_price
                END
            ) as total
        "))
                ->value('total') ?? 0,
            'cash' =>   $tolovlar->sum('cash_price'),
            'card' =>   $tolovlar->sum('card_price'),
            'kassa' => ($dailyRepot->total_price ?? 0) + ($dailyBalance ?? 0),
            'transfer' =>   $tolovlar->sum('transfer_price'),
            'discount' =>   $xizmatlar->select(DB::raw("
            SUM(
                CASE 
                    WHEN discount <= 100 
                    THEN (total_price / 100) * discount
                    ELSE discount 
                END
            ) as total
        "))
                ->value('total') ?? 0,

            'expense' => $expence,
            'material_expense' => $materialExpense,
        ];
    }
    public function counterparty($request)
    {
        $currentMonthIndex = Carbon::now()->month;
        $is_all = false;
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
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
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

        // if ($id) {
        //     $find = User::where(['role' => User::USER_ROLE_COUNTERPARTY, 'owner_id' => auth()->id()])
        //         ->whereHas('referringDoctor', function ($q) use ($currentMonthIndex) {
        //             $q->whereHas('referringDoctorBalance', function ($q) use ($currentMonthIndex) {
        //                 $q->whereMonth('date', $currentMonthIndex);
        //             });
        //         })->with(['referringDoctor' => function ($q) use ($currentMonthIndex) {
        //             $q->with(['referringDoctorBalance' => function ($q) use ($currentMonthIndex) {
        //                 $q->whereMonth('date', $currentMonthIndex);
        //             }]);
        //         }])->find($id);
        //     return [
        //         'data' => $find->referringDoctor,
        //         'target' => $find
        //     ];
        // }
        $kontragent = User::where(['role' => User::USER_ROLE_COUNTERPARTY])
            ->where(function ($q) use ($branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                    $q->where(['owner_id' => auth()->user()->owner_id,]);
                } else {
                    if ($branch_id == 'all') {
                        $q->whereIn('owner_id', $branchAllId);
                    } else
                    if ($branch_id > 0) {
                        $q->where('owner_id', $branch_id);
                    } else {
                        $q->where('owner_id', auth()->id());
                    }
                }
            })
            ->whereHas('referringDoctor', function ($q) use ($currentMonthIndex, $startDate, $endDate) {
                $q->whereHas('referringDoctorBalance', function ($q) use ($currentMonthIndex, $startDate, $endDate) {
                    // $q->whereMonth('date', $currentMonthIndex);
                    if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                        $q->whereDate('date', $endDate->format('Y-m-d'));
                    } else {
                        $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                    }
                });
            })->with([
                'referringDoctor' => function ($q) use ($currentMonthIndex, $startDate, $endDate) {
                    $q->with(['referringDoctorBalance' => function ($q) use ($currentMonthIndex, $startDate, $endDate) {
                        // $q->whereMonth('date', $currentMonthIndex);
                        if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                            $q->whereDate('date', $endDate->format('Y-m-d'));
                        } else {
                            $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                        }
                    }]);
                },
                'owner:id,name'
            ])
            ->get();

        return  [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),

            'data' => RepotCcounterpartyResoruce::collection($kontragent)
        ];
    }

    public function doctor($request)
    {
        $currentMonthIndex = Carbon::now()->month;
        $is_all = false;
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
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
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
        $kontragent = User::where(['role' => User::USER_ROLE_DOCTOR])
            ->where(function ($q) use ($branch_id, $branchAllId) {

                if (auth()->user()->role == User::USER_ROLE_RECEPTION || auth()->user()->role == User::USER_ROLE_DOCTOR) {
                    $q->where('owner_id', auth()->user()->owner_id);
                } else {
                    if ($branch_id == 'all') {
                        $q->whereIn('owner_id', $branchAllId);
                    } else
                    if ($branch_id > 0) {
                        $q->where('owner_id', $branch_id);
                    } else {
                        $q->where('owner_id', auth()->user()->id);
                    }
                }
            })
            ->whereHas('doctorBalance', function ($q) use ($currentMonthIndex, $startDate, $endDate) {
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('date', $endDate->format('Y-m-d'));
                } else {
                    $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                }
            })
            ->with(['doctorBalance' => function ($q) use ($currentMonthIndex, $startDate, $endDate) {
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('date', $endDate->format('Y-m-d'));
                } else {
                    $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                }
            }, 'department', 'owner'])
            ->get();
        return  [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),

            'data' => DoctorBalanceResource::collection($kontragent)
        ];
    }
    public function doctorShow($id, $request)
    {
        $per_page = $request->per_page ?? 50;
        $batch_number_index =  0;
        $currentMonthIndex = Carbon::now()->month;
        $is_all = false;
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
        }
        if (isset($request->batch_number) && +$request->batch_number > 0) {
            $batch_number_index = $request->batch_number;
        }
        $currentMonth = now()->month;
        $batch_number = [...DailyRepot::whereMonth('created_at', $currentMonth)
            ->where('batch_number', '>', 0)
            ->distinct()
            ->pluck('batch_number')];

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
        $calc =  DoctorBalance::where(function ($q) use ($id) {
            if (auth()->user()->role == User::USER_ROLE_DOCTOR) {
                $q->where('doctor_id', auth()->id());
            } else {
                $q->where('doctor_id', $id);
            }
        })
            ->with(['dailyRepot' => function ($q) use ($batch_number_index) {
                if ($batch_number_index > 0) {
                    $q->where('batch_number', $batch_number_index);
                }
            }]);
        $total_price = $calc->sum('total_price');
        $service_count = $calc->sum('service_count');
        $total_doctor_contribution_price = $calc->sum('total_doctor_contribution_price');
        $data = $calc
            ->where(function ($q) use ($currentMonth, $startDate, $endDate, $batch_number_index) {
                if (isset($batch_number_index) && ($batch_number_index) > 0) {
                    $q->whereHas('dailyRepot', function ($q) use ($batch_number_index) {
                        $q->where('batch_number', $batch_number_index);
                    });
                } else {
                    if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                        $q->whereDate('date', $endDate->format('Y-m-d'));
                    } else {
                        $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                    }
                }
            })
            ->where(function ($q) use ($request) {
                if (isset($request->full_name)) {
                    $q->whereHas('client', function ($q) use ($request) {
                        $q->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                    });
                }
            })
            ->with('client:id,first_name,last_name,person_id,created_at')
            ->paginate($per_page);
        return  [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'data' => DoctorBalanceShowResource::collection($data->items()),
            'total' => $data->total(),
            'last_page' => $data->lastPage(),
            'current_page' => $data->currentPage(),
            'per_page' => $data->perPage(),
            'total_price' => $total_price,
            'service_count' => $service_count,
            'total_doctor_contribution_price' => $total_doctor_contribution_price,
            'batch_number' => $batch_number
        ];
    }
    public function doctorShowService($id)
    {

        $data = DoctorBalance::find($id);
        $contribution_data = collect(json_decode($data->contribution_data)); // Decode JSON and wrap it in a collection

        // Get service names for each service_id
        $enriched_data = $contribution_data->map(function ($item) {
            $service = Services::find($item->service_id); // Fetch service by ID
            $item->id = 0; // Add service name
            $item->service_name = $service ? $service->name : null; // Add service name
            return $item; // Return modified item
        });

        return  $enriched_data;
    }
    public function dailyRepot($request)
    {
        $currentMonthIndex = Carbon::now()->month;
        $is_all = false;
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
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
        $status = 'start';
        if (isset($request->status)) {
            $status = $request->status;
        }
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
        $kontragent = DailyRepot::where(function ($q) use ($status, $startDate, $endDate, $branch_id, $branchAllId) {
            if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                $q->where(['user_id' => auth()->id(), 'status' => $status]);
            } else
            if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                // $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                } else
                if ($branch_id > 0) {
                    $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                }
                $q->where(['status' => $status]);
                // $q->where(['user_id' => auth()->id()]);
            } else {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
            }
            // if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
            //     $q->whereDate('created_at', $endDate->format('Y-m-d'));
            // } else {
            //     $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);;
            // }
        })
            ->with(['dailyRepotExpense.expense', 'dailyRepotClient', 'user.owner'])
            ->orderBy('created_at', 'desc')
            ->get();

        return  [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => $status,
            'data' => DailyRepotResource::collection($kontragent)
        ];
    }
    public function dailyRepotShow($id)
    {

        $find = DailyRepot::with(['dailyRepotExpense.expense.expenseType', 'dailyRepotClient', 'user.owner'])->where('user_id', auth()->id())
            ->find($id);
        $count = DailyRepot::whereDate('created_at', $find->created_at)
            ->where('user_id', auth()->id())
            ->where('status', 'finish')
            ->count();
        return  [
            'data' => [...$find->toArray(), 'partiya' => $count]
        ];
    }
    public function counterpartyShow($id, $request)
    {
        $currentMonthIndex = Carbon::now()->month;
        $is_all = false;
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
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
        // if ($id) {
        //     $find = User::where(['role' => User::USER_ROLE_COUNTERPARTY, 'owner_id' => auth()->id()])
        //         ->whereHas('referringDoctor', function ($q) use ($currentMonthIndex) {
        //             $q->whereHas('referringDoctorBalance', function ($q) use ($currentMonthIndex) {
        //                 $q->whereMonth('date', $currentMonthIndex);
        //             });
        //         })->with(['referringDoctor' => function ($q) use ($currentMonthIndex) {
        //             $q->with(['referringDoctorBalance' => function ($q) use ($currentMonthIndex) {
        //                 $q->whereMonth('date', $currentMonthIndex);
        //             }]);
        //         }])->find($id);
        //     return [
        //         'data' => $find->referringDoctor,
        //         'target' => $find
        //     ];
        // }
        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'data' => ReferringDoctorResource::collection(
                // User::where(['role' => User::USER_ROLE_COUNTERPARTY, 'owner_id' => auth()->id()])
                User::where(['role' => User::USER_ROLE_COUNTERPARTY])
                    ->where(function ($q) {
                        if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                            $q->where(['owner_id' => auth()->user()->owner_id,]);
                        } else {
                            $q->where('owner_id', auth()->id());
                        }
                    })
                    ->whereHas('referringDoctor', function ($q) use ($currentMonthIndex, $startDate, $endDate) {
                        $q->whereHas('referringDoctorBalance', function ($q) use ($currentMonthIndex, $startDate, $endDate) {
                            // $q->whereMonth('date', $currentMonthIndex);
                            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                                $q->whereDate('date', $endDate->format('Y-m-d'));
                            } else {
                                $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                            }
                        });
                    })->with(['referringDoctor' => function ($q) use ($currentMonthIndex, $startDate, $endDate) {
                        $q->with(['referringDoctorBalance' => function ($q) use ($currentMonthIndex, $startDate, $endDate) {
                            // $q->whereMonth('date', $currentMonthIndex);
                            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                                $q->whereDate('date', $endDate->format('Y-m-d'));
                            } else {
                                $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                            }
                        }]);
                    }])
                    ->find($id)?->referringDoctor ?? []
            )
        ];
    }
    public function counterpartyClientShow($id, $request)
    {
        $currentMonthIndex = Carbon::now()->month;
        $is_all = false;
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
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
        // if ($id) {
        //     $find = User::where(['role' => User::USER_ROLE_COUNTERPARTY, 'owner_id' => auth()->id()])
        //         ->whereHas('referringDoctor', function ($q) use ($currentMonthIndex) {
        //             $q->whereHas('referringDoctorBalance', function ($q) use ($currentMonthIndex) {
        //                 $q->whereMonth('date', $currentMonthIndex);
        //             });
        //         })->with(['referringDoctor' => function ($q) use ($currentMonthIndex) {
        //             $q->with(['referringDoctorBalance' => function ($q) use ($currentMonthIndex) {
        //                 $q->whereMonth('date', $currentMonthIndex);
        //             }]);
        //         }])->find($id);
        //     return [
        //         'data' => $find->referringDoctor,
        //         'target' => $find
        //     ];
        // }
        $find =  (ReferringDoctor::where(function ($q) use ($startDate, $endDate) {
            // if (isset($request->is_repot)) {
            $q->whereHas('referringDoctorBalance', function ($q) use ($startDate, $endDate) {
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('date', $startDate->format('Y-m-d'));
                } else {
                    $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                }
            });
            // }
        })
            ->with(['referringDoctorBalance' => function ($q) use ($request, $startDate, $endDate) {
                // $q->whereHas('referringDoctorBalance', function ($q) use ($startDate, $endDate) {
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('date', $endDate->format('Y-m-d'))
                        ->with('client.clientValue.service')
                    ;
                } else {
                    $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')])
                        ->with('client.clientValue.service')
                    ;
                    // $q->with('client');
                    // });
                }
            }])
            // ->where('user_id', auth()->id())
            ->find($id));
        $dep = $find->referringDoctorBalance->flatMap(function ($item) {
            return $item->client->clientValue->map(function ($item) {
                return $item->department_id;
            });
        });
        return [
            ...$find->toArray(),
            'department' => Departments::whereIn('id', $dep)->get(['name', 'id'])
        ];
    }


    // DailyRepot
    function dailyRepotUpdate($request)
    {
        // $client = ClinetPaymet::whereDate('created_at', now()->format('Y-m-d'))
        //     ->where('user_id', auth()->id())
        //     // ->whereIn('user_id',User::where('owner_id',auth()->user()->owner_id)->pluck('id'))
        //     ->get();
        // $expnece = Expense::whereDate('created_at', now()->format('Y-m-d'))
        //     ->where('user_id', auth()->id())
        //     ->get();
        // $dailyRepot = DailyRepot::whereDate('created_at', now()->format('Y-m-d'))
        //     ->where('user_id', auth()->id())
        //     ->first();
        // if (!$dailyRepot) {
        //     // $dailyRepot = DailyRepot::create([
        // }
        $request =    $request;
        $dailyRepot = DailyRepot::find($request->id);
        $clientBalance =   ClientBalance::where('daily_repot_id', $dailyRepot->id)
            ->where('status', 'pay')
            ->get();
        $request['total_price'] = $dailyRepot->total_price + $clientBalance->sum('price');
        $request['cash_price'] = $dailyRepot->cash_price +  $clientBalance->where('pay_type', 'cash')->sum('price') ?? 0;
        $request['card_price'] = $dailyRepot->card_price +  $clientBalance->where('pay_type', 'card')->sum('price') ?? 0;
        $request['transfer_price'] = $dailyRepot->transfer_price +  $clientBalance->where('pay_type', 'transfer')->sum('price') ?? 0;
        $dailyRepot->update($request->all());
        return (DailyRepot::with(['dailyRepotExpense.expense.expenseType', 'dailyRepotClient'])->find($dailyRepot->id));
    }




    // excelRepot
    function excelRepot($request)
    {

        $result = [];
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

        // Total qiymatlarni yig'ish
        $total = Client::whereNotNull('parent_id')
            ->where(function ($q) {
                $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
            })
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as total_price_sum')
            ->groupBy('date')->get();

        // Cash qiymatlarni yig'ish
        $cash = DailyRepot::where(function ($q) use ($startDate, $endDate) {
            $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('created_at', $endDate->format('Y-m-d'));
            } else {
                $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            }
        })


            ->selectRaw('DATE(created_at) as date, SUM(cash_price) as cash_price_sum,SUM(card_price) as card_price_sum,SUM(transfer_price) as transfer_price_sum')
            ->groupBy('date')->get();

        // $result massivini yangilash funksiyasi
        $updateResult = function ($data, $columns) use (&$result) {
            foreach ($data as $item) {
                // $result massivida sanani qidiramiz
                $existingKey = collect($result)->search(fn($resultItem) => $resultItem['date'] === $item->date);

                if ($existingKey !== false) {
                    // Mavjud sanaga har bir ustunni qo'shish
                    foreach ($columns as $key => $alias) {
                        if (!isset($result[$existingKey][$alias])) {
                            $result[$existingKey][$alias] = 0;
                        }
                        $result[$existingKey][$alias] += $item->$key ?? 0; // Null bo'lsa 0 qiymatni qo'shadi
                    }
                } else {
                    // Yangi sana uchun ma'lumot qo'shish
                    $newEntry = ['date' => $item->date];
                    foreach ($columns as $key => $alias) {
                        $newEntry[$alias] = $item->$key ?? 0; // Null bo'lsa 0 qiymatni qo'shadi
                    }
                    $result[] = $newEntry;
                }
            }
        };



        // Total ma'lumotlarni qo'shish
        $updateResult($total, ['total_price_sum' => 'total_price']);


        // Cash ma'lumotlarni qo'shish
        $updateResult($cash, [
            'cash_price_sum' => 'cash_price',
            'card_price_sum' => 'card_price',
            'transfer_price_sum' => 'transfer_price',
        ]);


        $qarz = ClientValue::where(function ($q) use ($startDate, $endDate) {
            $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('created_at', $endDate->format('Y-m-d'));
            } else {
                $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            }
        })
            ->where('is_active', 1)
            ->selectRaw("
        DATE(created_at) as date, 
        SUM(
            CASE 
                WHEN discount <= 100 
                THEN (total_price - (total_price / 100) * discount) - pay_price
                ELSE (total_price - discount) - pay_price
            END
        ) as total_loan
    ")
            ->groupBy('date')->get();
        $updateResult($qarz, ['total_loan' => 'debt_price']);

        // dorixona
        $pharmacyProduct = PharmacyProduct::where(function ($q) use ($endDate, $startDate) {
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('created_at', $endDate->format('Y-m-d'));
            } else {
                $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            }
        })->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'))->selectRaw(
            'DATE(created_at) as date, SUM(qty) as total_qty'
        )

            ->groupBy(DB::raw('DATE(created_at)')) // DATE funktsiyasidan foydalanilyapti
            ->get();
        Log::info(' $pharmacyProduct ', [$pharmacyProduct]);
        $updateResult($pharmacyProduct, ['total_qty' => 'pharmacy_product_qty']);

        $kounteragent = ReferringDoctorBalance::selectRaw(
            'DATE(created_at) as date, SUM(total_kounteragent_contribution_price) as total_kounteragent_contribution_price_sum'
        )
            ->groupBy(DB::raw('DATE(created_at)')) // DATE funktsiyasidan foydalanilyapti
            ->get();
        $updateResult($kounteragent, ['total_kounteragent_contribution_price_sum' => 'kounteragent_price']);


        $cashExpence =  Expense::where(function ($q) use ($startDate, $endDate) {
            $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('created_at', $endDate->format('Y-m-d'));
            } else {
                $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            }
        })
            ->where('pay_type', 'cash')
            ->selectRaw(
                'DATE(created_at) as date, SUM(price) as expense_cash_total_price'
            )
            ->groupBy(DB::raw('DATE(created_at)')) // DATE funktsiyasidan foydalanilyapti
            ->get();;
        $updateResult($cashExpence, ['expense_cash_total_price' => 'expense_cash_price']);
        // analiz soni

        $analiz = ClientValue::where(function ($q) use ($startDate, $endDate) {

            $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
            $q->where('is_at_home', '!=', 1);
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('created_at', $endDate->format('Y-m-d'));
            } else {
                $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            }
        })
            ->selectRaw(
                'DATE(created_at) as date, SUM(qty) as analiz_qty'
            )
            ->groupBy(DB::raw('DATE(created_at)'))

            ->get();
        $updateResult($analiz, ['analiz_qty' => 'analiz_qty']);
        // return $result;
        // muolajadgilar
        $mulojagayozilgnlar = GraphArchive::
            // whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            // ->
            where(function ($q) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
            })
            ->whereHas('graphArchiveItem', function ($q) use ($startDate, $endDate) {
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('agreement_date', $endDate->format('Y-m-d'));
                } else {
                    $q->whereBetween('agreement_date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                }
                // if (isset($request->is_today) && $request->is_today == 1) {
                //     $q->whereDate('agreement_date', now()->format('Y-m-d'));
                // } else {
                //     $q->whereYear('agreement_date', $currentYear);
                // }

                // if (!$is_all) {
                //     $q->whereMonth('agreement_date', $currentMonthIndex);
                // }
            })->where('status', GraphArchive::STATUS_LIVE)
            ->selectRaw(
                'DATE(created_at) as date, COUNT(id) as count'
            )
            ->groupBy(DB::raw('DATE(created_at)'))->get();
        $updateResult($mulojagayozilgnlar, ['count' => 'muolja_qty']);
        // MED PREDSTAVITELDAN YANGI BEMORLAR 
        $yollanma_client  = Client::WhereNull('parent_id')
            ->whereHas('clientItem', function ($q) {
                $q->where('referring_doctor_id', '>', 0);
                $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
            })
            ->where(function ($q) use ($startDate, $endDate) {
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('created_at', $endDate->format('Y-m-d'));
                } else {
                    $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                }
            })

            ->selectRaw(
                'DATE(created_at) as date, COUNT(DISTINCT person_id) as count'
            )
            ->groupBy(DB::raw('DATE(created_at)'))->get();
        $updateResult($yollanma_client, ['count' => 'yollanma_client']);
        // yollanma anzliz
        $yollanma_analiz = ClientValue::where(function ($q)  use ($startDate, $endDate) {
            $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
            $q->where('is_at_home', '!=', 1);
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('created_at', $endDate->format('Y-m-d'));
            } else {
                $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            }
        })
            ->whereHas('client', function ($q) {
                $q->where('referring_doctor_id', '>', 0);
            })->selectRaw(
                'DATE(created_at) as date, COUNT(id) as count'
            )
            ->groupBy(DB::raw('DATE(created_at)'))->get();


        $updateResult($yollanma_analiz, ['count' => 'yollanma_analiz']);


        // // soical analiz

        // $advertisements_analiz  = Client::WhereNull('parent_id')
        //     // advertisements_id
        //     ->where(function ($q) {
        //         $q->whereIn('advertisements_id', '>', '0');
        //     })

        //     ->withCount('clientValue')

        //     ->selectRaw(
        //         'DATE(created_at) as date, COUNT(DISTINCT advertisements_id) as count'
        //     )
        //     ->groupBy(DB::raw('DATE(created_at)'))->get();

        // $advertisements_analiz = Advertisements::with(['clientAnaliz.clientValue' => function ($q) {
        //     // $q->where('is_at_home', '!=', 1);
        //     $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
        // }]);
        // $advertisements_analiz->map((function ($q) {
        //     return [
        //         'name' => $q->name,
        //         'qty' => $q->clientAnaliz->sum(function ($q) {
        //             return $q->clientValue->sum('qty');
        //         }),
        //     ];
        // }));
        // $advertisements_analiz = ClientValue::where(function ($q) {
        //     $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
        //     $q->where('is_at_home', '!=', 1);
        //     $q->where('is_active', 1);
        // })
        //     ->whereHas('client', function ($q) {
        //         $q->where('advertisements_id', '>', 0);
        //     })->selectRaw(
        //         'DATE(created_at) as date, COUNT(qty) as count'
        //     )
        //     ->groupBy(DB::raw('DATE(created_at)'))->get();
        $adv = [];
        $advertisements_analiz = ClientValue::where(function ($q) use ($startDate, $endDate) {
            $q->whereIn('client_values.user_id', User::where('owner_id', auth()->user()->id)->pluck('id')) // To'liq yo'l
                ->where('client_values.is_at_home', '!=', 1) // To'liq yo'l
                ->where('client_values.is_active', 1); // To'liq yo'l
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('client_values.created_at', $endDate->format('Y-m-d'));
            } else {
                $q->whereBetween('client_values.created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            }
        })
            ->whereHas('client', function ($q) {
                $q->where('advertisements_id', '>', 0); // Client modelidagi advertisements_id > 0
            })
            ->join('clients', 'clients.id', '=', 'client_values.client_id') // clients jadvalini qo'shish
            ->selectRaw(
                'clients.advertisements_id, DATE(client_values.created_at) as date, SUM(client_values.qty) as total_qty' // kerakli maydonlarni tanlash
            )
            ->groupBy('clients.advertisements_id', DB::raw('DATE(client_values.created_at)')) // guruhlash advertisements_id va created_at bo'yicha
            ->orderBy('date', 'asc') // created_at bo'yicha tartiblash
            ->get()->groupBy(function ($q) {
                return $q->advertisements_id;
            });
        foreach ($advertisements_analiz as $key => $value) {
            $adv[] = $key;
            $updateResult($advertisements_analiz[$key], ['total_qty' => 'analiz_adv_' . $key]);
        }
            // $advertisements_yangi_bemor = ClientValue::where(function ($q) {
            //     $q->whereIn('client_values.user_id', User::where('owner_id', auth()->user()->id)->pluck('id')) // To'liq yo'l
            //         ->where('client_values.is_at_home', '!=', 1) // To'liq yo'l
            //         ->where('client_values.is_active', 1); // To'liq yo'l
            // })
            //     ->whereHas('client', function ($q) {
            //         $q->where('advertisements_id', '>', 0)
            //             ->whereHas('parent', function ($q) {
            //                 $q->whereHas('grapaechveChek', function ($q) {
            //                     $q->whereHas('graphArchiveItem', function ($q) {
            //                         $q->whereRaw('graph_archive_items.created_at = clients.parent.created_at')
            //                             ->orderBy('created_at', 'asc') // Birinchi elementni olish uchun
            //                             ->limit(1); // Faqat birinchi elementni tekshirish    
            //                     });
            //                 });
            //             })

            //         ; // Client modelidagi advertisements_id > 0
            //     })
            //     ->join('clients', 'clients.id', '=', 'client_values.client_id') // clients jadvalini qo'shish
            //     ->selectRaw(
            //         'clients.advertisements_id, DATE(client_values.created_at) as date, SUM(client_values.qty) as total_qty' // kerakli maydonlarni tanlash
            //     )
            //     ->groupBy('clients.advertisements_id', DB::raw('DATE(client_values.created_at)')) // guruhlash advertisements_id va created_at bo'yicha
            //     ->orderBy('date', 'asc') // created_at bo'yicha tartiblash
            //     ->get()->groupBy(function ($q) {
            //         return $q->advertisements_id;
            //     }); 


            // $advertisements_new_client = Advertisements::whereHas('client', function ($q) {
            //     $q->whereDate('created_at', now()->format('Y-m-d'));
            // })
            //     // ->has('client.clientValue')
            //     ->get();
            // ->selectRaw(
            //     'DATE(created_at) as date, COUNT(id) as count'
            // )
            // ->groupBy(DB::raw('DATE(created_at)'))->get();
        ;
        $GraphArchive = GraphArchive::whereHas('graphArchiveItem', function ($q) use ($startDate, $endDate) {
            // $q->where('client_id','>','0')
            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                $q->whereDate('agreement_date', $endDate->format('Y-m-d'));
            } else {
                $q->whereBetween('agreement_date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            }
            $q->whereHas('client', function ($q) {
                $q
                    ->whereExists(function ($existsQuery) {
                        $existsQuery->select(DB::raw(1))
                            ->from('clients as parent')
                            ->whereColumn('clients.parent_id', '=', 'parent.id')
                            ->whereRaw('DATE(clients.created_at) = DATE(parent.created_at)'); // created_at larni faqat sana bo‘yicha solishtirish
                    });
            })
                ->limit(1);
        })
            ->whereHas('person', function ($q) {
                $q
                    ->limit(1)
                    ->whereHas('clientItem', function ($q) {
                        $q->where('advertisements_id', '>', 0);
                    });
            })
            ->with(['person' => function ($q) {
                $q->with(['clientItem']);
            }])
            ->where('use_status', 'treatment')->get();

        foreach ($GraphArchive as $key => $value) {
            $id = isset($value->person->clientItem) ? $value->person->clientItem->first()?->advertisements_id ?? false : false;
            if ($id) {
                $existingKey = collect($result)->search(fn($resultItem) => $resultItem['date'] === $value->person->created_at->format('Y-m-d'));
                if (!collect($adv)->contains($id)) {
                    $adv[] = $id;
                }
                Log::info('id: ' . $existingKey);
                Log::info('created_at: ' . $value->person->created_at->format('Y-m-d'));
                if ($existingKey !== false) {
                    $result[$existingKey]['adv_new_client_' . $id] =  ($result[$existingKey]['adv_new_client_' . $id] ?? 0) + 1;
                } else {
                    $result[] = [
                        'date' => $value->created_at->format('Y-m-d'),
                        'adv_new_client_' . $id => 1,
                    ];
                }
            }
        }
        // 'yollanma_client' => $yollanma_client->pluck('person_id')->unique()->count(),
        return [
            'data' => [
                ...collect($result)
                    ->filter(function ($q) use ($startDate, $endDate) {
                        return $q['date'] >= $startDate->format('Y-m-d') && $q['date'] <= $endDate->format('Y-m-d');
                    })
            ],
            'advertisements_analiz' => $advertisements_analiz,
            'advertisements_yangi_bemor' => $GraphArchive,
            'adv' => Advertisements::whereIn('id', $adv)->get(['id', 'name']),
            // 'cash' => $cash
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }
}
