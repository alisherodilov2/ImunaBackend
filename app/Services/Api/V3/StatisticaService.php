<?php

namespace App\Services\Api\V3;

use App\Models\Client;
use App\Models\ClientValue;
use App\Models\Advertisements;
use App\Models\PharmacyProduct;
use App\Models\CounterpartySetting;
use App\Models\DailyRepot;
use App\Models\Expense;
use App\Models\GraphArchive;
use App\Models\ReferringDoctor;
use App\Models\ReferringDoctorBalance;
use App\Models\DoctorBalance;
use App\Models\Services;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserCounterpartyPlan;
use App\Services\Api\V3\Contracts\StatisticaServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatisticaService implements StatisticaServiceInterface
{
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
    public function statistica($request)
    {
        $currentYear = Carbon::now()->year;
        $currentMonthIndex = Carbon::now()->month;
        $is_all = false;
        $branch_id = 0;
        $branchAllId = [];
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
        }
        if (isset($request->year)) {
            $currentYear = $request->year;
        }
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
        if ((isset($request->is_all) && $request->is_all == 1)) {
            $is_all = true;
            $currentMonthIndex = Carbon::now()->month;
        }

        if ($request->is_today == 1) {
            $currentMonthIndex = Carbon::now()->month;
            $is_all = false;
        }


        $mulojagayozilgnlar = GraphArchive::
            // whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            // ->
            where(function ($q) use ($branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    if ($branch_id == 'all') {
                        $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                    } else
                    if ($branch_id > 0) {
                        $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                    } else {
                        $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                    }
                } else {
                    $q->where('user_id', auth()->user()->id);
                }
            })
            ->whereHas('graphArchiveItem', function ($q) use ($request, $currentMonthIndex, $is_all, $currentYear) {
                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('agreement_date', now()->format('Y-m-d'));
                } else {
                    $q->whereYear('agreement_date', $currentYear);
                }

                if (!$is_all) {
                    $q->whereMonth('agreement_date', $currentMonthIndex);
                }
            })->with(['graphArchiveItem' => function ($q) use ($request) {
                $q->with(['client', 'graphItem.department']);
            }, 'person'])
            ->get();
        // optimallash kerak
       
       
       
        $bemorlar = Client::
            // whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            where(function ($q) use ($branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    if ($branch_id == 'all') {
                        $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                    } else
                    if ($branch_id > 0) {
                        $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                    } else {
                        $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                    }
                } else {
                    $q->where('user_id', auth()->user()->id);
                }
            })
            ->where(function ($q) use ($request, $currentMonthIndex, $is_all, $currentYear) {
                // if (isset($request->is_today) && $request->is_today == 1) {
                //     $q->whereDate('created_at', now()->format('Y-m-d'));
                // } else {
                //     $q->whereYear('created_at', $currentYear);
                // }
                // if (!$is_all) {
                //     $q->whereMonth('created_at', $currentMonthIndex);
                // }
            });
        $ambulato = Client::whereNotIn('person_id', $mulojagayozilgnlar->pluck('person_id'))
            ->where(function ($q) use ($branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    if ($branch_id == 'all') {
                        $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                    } else
                    if ($branch_id > 0) {
                        $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                    } else {
                        $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                    }
                } else {
                    $q->where('user_id', auth()->user()->id);
                }
            })
            // ->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            ->where(function ($q) use ($request, $currentMonthIndex, $is_all, $currentYear) {
                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('created_at', now()->format('Y-m-d'));
                } else {
                    $q->whereYear('created_at', $currentYear);
                }
                if (!$is_all) {
                    $q->whereMonth('created_at', $currentMonthIndex);
                }
            })->get();

        $muolaja =  $mulojagayozilgnlar->where('status', GraphArchive::STATUS_LIVE); ///savol umumiymi yoki mulajasi tugamagnlarimi
      
        $yakunlangan =  GraphArchive::where('status', GraphArchive::STATUS_FINISH)
            ->where(function ($q) use ($branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    if ($branch_id == 'all') {
                        $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                    } else
                    if ($branch_id > 0) {
                        $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                    } else {
                        $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                    }
                } else {
                    $q->where('user_id', auth()->user()->id);
                }
            })
            ->whereHas('graphArchiveItem', function ($q) use ($request, $currentMonthIndex, $is_all, $currentYear) {
                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('agreement_date', now()->format('Y-m-d'));
                } else {
                    $q->whereYear('agreement_date', $currentYear);
                }
                if (!$is_all) {
                    $q->whereMonth('agreement_date', $currentMonthIndex);
                }
            })->with(['graphArchiveItem' => function ($q) use ($request) {
                $q->with(['client', 'graphItem.department']);
            }, 'person']);



        $arxiv =  GraphArchive::where('status', GraphArchive::STATUS_ARCHIVE)
            ->where(function ($q) use ($branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    if ($branch_id == 'all') {
                        $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                    } else
                    if ($branch_id > 0) {
                        $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                    } else {
                        $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                    }
                } else {
                    $q->where('user_id', auth()->user()->id);
                }
            })
            ->whereHas('graphArchiveItem', function ($q) use ($request, $currentMonthIndex, $is_all, $currentYear) {
                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('agreement_date', now()->format('Y-m-d'));
                } else {
                    $q->whereYear('agreement_date', $currentYear);
                }
                if (!$is_all) {

                    $q->whereMonth('agreement_date', $currentMonthIndex);
                }
            })
            ->with(['graphArchiveItem' => function ($q) use ($request) {
                $q->with(['client', 'graphItem.department']);
            }, 'person']);

        $kelmaganlar = GraphArchive::where(function ($q) use ($branch_id, $branchAllId) {
            if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                } else
                if ($branch_id > 0) {
                    $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                }
            } else {
                $q->where('user_id', auth()->user()->id);
            }
        })->whereHas('graphArchiveItem', function ($q) use ($request, $currentMonthIndex, $is_all) {
            // $q->whereHas('graphItem', function ($q) use ($request, $currentMonthIndex, $is_all) {
            if (isset($request->is_today) && $request->is_today == '1') {
                $q->whereDate('agreement_date', now()->format('Y-m-d'));
            }
            if (!$is_all) {
                $q->whereMonth('agreement_date', $currentMonthIndex);
            }
            $q
                // $q
                ->whereNull('client_id')
                ->whereHas('department', function ($q) {
                    $time = Carbon::now()->format('H:i');
                    $date =  Carbon::now()->format('Y-m-d');
                    $q
                        ->whereRaw("graph_archive_items.agreement_date < '$date'")
                        ->orWhere(function ($q) use ($date, $time) {
                            $q->whereRaw("graph_archive_items.agreement_date = '$date'")
                                ->whereRaw("departments.work_end_time <= '$time'");
                        })
                    ;
                });
            // ->whereNull('client_id')
            // ->whereHas('department', function ($q) {
            //     // Using a join to reference columns directly
            //     $q
            //         // ->whereRow('CASE when  graph_archive_items.client_id IS NULL  1 else 0 end ')
            //         // ->whereRaw("CASE 
            //         // WHEN graph_archive_items.agreement_date = CURRENT_DATE  THEN (CASE WHEN  graph_archive_items.agreement_time IS NULL OR graph_archive_items.agreement_time = '-' THEN  (CASE WHEN departments.work_end_time = CURRENT_TIME then 1 else 0 end) else (departments.work_end_time > graph_archive_items.agreement_time)  end) ELSE (departments.work_end_time > graph_archive_items.agreement_time) END");
            //         ->whereRaw("
            //         CASE 
            //          WHEN graph_archive_items.agreement_date <= CURRENT_DATE
            //           THEN (CASE
            //              WHEN  graph_archive_items.agreement_time IS NULL OR graph_archive_items.agreement_time = '-'
            //                 THEN  (CASE
            //                  WHEN departments.work_end_time <= CURRENT_TIME  then 1 else 0 end)
            //                  else (departments.work_end_time >= graph_archive_items.agreement_time)  
            //             end) 
            //          END")
            //         // ->whereTime('departments.work_end_time','<=',now()->format('H:i'))
            //         ->whereNull('graph_archive_items.client_id')
            //     ;
            // });
            // });
        })
            ->with(['graphArchiveItem' => function ($q) use ($request) {
                $q

                    ->with(['client', 'graphItem.department']);
            }, 'person']);

        // bugungi sana borini olamiz
        $yangibemorlar = GraphArchive::where('status', GraphArchive::STATUS_LIVE)
            ->where(function ($q) use ($branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    if ($branch_id == 'all') {
                        $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                    } else
                    if ($branch_id > 0) {
                        $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                    } else {
                        $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                    }
                } else {
                    $q->where('user_id', auth()->user()->id);
                }
            })
            // ->whereHas('graphArchiveItem', function ($q) {
            //     // 1. Bugungi sanaga teng bo'lgan yozuvni olish
            //     $q->whereDate('agreement_date', now()->format('Y-m-d'));
            // })
            ->whereHas('person', function ($q) {
                // 1. Bugungi sanaga teng bo'lgan yozuvni olish
                $q->whereDate('created_at', now()->format('Y-m-d'));
            })
            ->with(['graphArchiveItem' => function ($q) use ($request) {
                $q->with(['client', 'graphItem.department']);
            }, 'person'])
            ->get() // Natijalarni olish
            // ->filter(function ($item) {
            //     // 2. Har bir yozuvda graphArchiveItem bo'lsa va birinchi yozuvning sanasini tekshirish
            //     $first = $item->graphArchiveItem->first();
            //     return $first && $first->agreement_date == now()->format('Y-m-d'); // Agar birinchi yozuv bo'lsa va sanasi bugunga teng bo'lsa, qaytaring
            // })
        ;
        $tugayotkanlar =  GraphArchive::where('status', GraphArchive::STATUS_LIVE)
            ->where(function ($q) use ($branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    if ($branch_id == 'all') {
                        $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                    } else
                    if ($branch_id > 0) {
                        $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                    } else {
                        $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                    }
                } else {
                    $q->where('user_id', auth()->user()->id);
                }
            })
            ->whereHas('graphArchiveItem', function ($q) {
                // 1. Bugungi sanaga teng bo'lgan yozuvni olish
                $q->whereDate('agreement_date', now()->format('Y-m-d'))
                    ->orderBy('agreement_date', 'desc'); // Kamayish tartibida
                ;
            })
            ->with([
                'graphArchiveItem' => function ($q) {
                    // 1. Bugungi sanaga teng bo'lgan yozuvni olish
                    $q
                        ->with(['client', 'graphItem.department'])
                        ->orderBy('agreement_date', 'desc'); // Kamayish tartibida
                },
                'person'
            ])
            ->get() // Natijalarni olish
            ->filter(function ($item) {
                // 2. Har bir yozuvda graphArchiveItem bo'lsa va birinchi yozuvning sanasini tekshirish
                $first = $item->graphArchiveItem->first();
                return $first && $first->agreement_date == now()->format('Y-m-d'); // Agar birinchi yozuv bo'lsa va sanasi bugunga teng bo'lsa, qaytaring
            });

        if (isset($request->status)) {
            if ($request->status == 'ambulator') {
                return [
                    'data' => [...$ambulato->whereNull('parent_id')]
                ];
            }
            if ($request->status == 'live') {
                return [
                    'data' => [...$muolaja]
                ];
            }
            if ($request->status == 'archive') {
                return [
                    'data' => [...$arxiv->get()]
                ];
            }
            if ($request->status == 'kelmaganlar') {
                return [
                    'data' => [...$kelmaganlar->get()]
                ];
            }
            if ($request->status == 'finish') {
                return [
                    'data' => [...$yakunlangan->get()]
                ];
            }
            if ($request->status == 'bemorlar') {
                return [
                    'data' => Client::whereIn('person_id', $bemorlar->pluck('person_id')->unique())
                        ->whereNull('parent_id')
                        ->get()
                ];
            }
            if ($request->status == 'yangibemorlar') {
                return [
                    'data' =>  [...$yangibemorlar]

                ];
            }
            if ($request->status == 'tugayotkanlar') {
                return [
                    'data' =>  [...$tugayotkanlar]

                ];
            }
            if ($request->status == 'erkaklar') {
                return [
                    'data' =>
                    Client::whereIn('person_id', $bemorlar->pluck('person_id')->unique())
                        ->whereNull('parent_id')
                        ->where(function ($q) use ($branch_id, $branchAllId) {
                            if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                                if ($branch_id == 'all') {
                                    $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                                } else
                                if ($branch_id > 0) {
                                    $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                                } else {
                                    $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                                }
                            } else {
                                $q->where('user_id', auth()->user()->id);
                            }
                        })
                        ->where('sex', 'male')
                        ->get()
                    // $bemorlar
                    //     ->where('sex', 'male')
                    //     ->distinct('person_id') // Takrorlanmas qilish
                    //     ->get()

                ];
            }
            if ($request->status == 'muoljadagilar') {
                return [
                    'data' => [
                        ...$muolaja
                    ]
                ];
            }
            if ($request->status == 'ayollar') {
                return [
                    'data' =>
                    Client::whereIn('person_id', $bemorlar->pluck('person_id')->unique())
                        ->whereNull('parent_id')
                        ->where('sex', 'female')
                        ->where(function ($q)  use ($branch_id, $branchAllId) {
                            if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                                if ($branch_id == 'all') {
                                    $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                                } else
                                if ($branch_id > 0) {
                                    $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                                } else {
                                    $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                                }
                            } else {
                                $q->where('user_id', auth()->user()->id);
                            }
                        })
                        ->get()
                    //     $bemorlar->where('sex', 'female')
                    //    ->distinct('person_id') // Takrorlanmas qilish
                    //         ->get()

                ];
            }
        }
        $bemorsoni =  $bemorlar->pluck('person_id')->unique()
            ->count();
        // Log::info('ayol', [$bemorlar->where('sex','!=' ,'male')->get()]);
        $erkaksoni = $bemorlar->where('sex', 'male')->pluck('person_id')->unique()->count();
        $xarjatlar = Expense
            ::
            // where(function ($q) use ($request, $currentMonthIndex, $is_all) {
            //     if($branch_id > 0){
            //         $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
            //     }else{
            //         $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
            //     }
            // })
            // whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            where(function ($q) use ($request, $currentMonthIndex, $is_all, $branch_id, $branchAllId, $currentYear) {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                } else
                if ($branch_id > 0) {
                    $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
                }


                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('created_at', now()->format('Y-m-d'));
                } else {
                    $q->whereYear('created_at', $currentYear);
                }
                if (!$is_all) {
                    $q->whereMonth('created_at', $currentMonthIndex);
                }
            })
            ->sum('price');
        $service = ClientValue::where(function ($q) use ($request, $currentMonthIndex, $is_all, $branch_id, $branchAllId, $currentYear) {
            if ($branch_id == 'all') {
                $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
            } else
            if ($branch_id > 0) {
                $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
            } else {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
            }
            if (isset($request->is_today) && $request->is_today == 1) {
                $q->whereDate('created_at', now()->format('Y-m-d'));
            } else {
                $q->whereYear('created_at', $currentYear);
            }
            if (!$is_all) {
                $q->whereMonth('created_at', $currentMonthIndex);
            }
        })
            ->where('is_active', 1)
            ->where('is_pay', 1)
            ->sum('qty');

        $daily = DailyRepot::whereIn(
            'user_id',
            User::where(function ($q) use ($branch_id, $branchAllId) {
                if ($branch_id == 'all') {
                    $q->whereIn('owner_id', $branchAllId);
                } else
                if ($branch_id > 0) {
                    $q->where('owner_id', $branch_id);
                } else {
                    $q->where('owner_id', auth()->user()->id);
                }
                // where('owner_id', auth()->user()->id)->pluck('id')
            })->pluck('id')
        )
            // ->whereDate('created_at', now()->format('Y-m-d'))
            ->get();
        $kunxarajat = Expense::whereIn('user_id',         User::where(function ($q) use ($branch_id, $branchAllId) {
            if ($branch_id == 'all') {
                $q->whereIn('owner_id', $branchAllId);
            } else
            if ($branch_id > 0) {
                $q->where('owner_id', $branch_id);
            } else {
                $q->where('owner_id', auth()->user()->id);
            }
            // where('owner_id', auth()->user()->id)->pluck('id')
        })->pluck('id'))
            // ->whereDate('created_at', now()->format('Y-m-d'))
            ->get();
        // kountur docitr + agent+docotr
        // kuntur doctor va agent
        $referdoctorbalance = ReferringDoctorBalance::whereHas('client', function ($q) use ($branch_id, $branchAllId) {
            $q->whereIn('user_id',         User::where(function ($q) use ($branch_id, $branchAllId) {
                if ($branch_id == 'all') {
                    $q->whereIn('owner_id', $branchAllId);
                } else
                if ($branch_id > 0) {
                    $q->where('owner_id', $branch_id);
                } else {
                    $q->where('owner_id', auth()->user()->id);
                }
                // where('owner_id', auth()->user()->id)->pluck('id')
            })->pluck('id'));
            // ->whereDate('created_at', now()->format('Y-m-d'))
            // $q->where('user_id', auth()->user()->id);
        });
        // docotrbalnce
        $docotrbalnce = DoctorBalance::whereHas('client', function ($q) use ($branch_id, $branchAllId) {
            // ->whereDate('created_at', now()->format('Y-m-d'))
            $q->whereIn('user_id',         User::where(function ($q) use ($branch_id, $branchAllId) {
                if ($branch_id == 'all') {
                    $q->whereIn('owner_id', $branchAllId);
                } else
                if ($branch_id > 0) {
                    $q->where('owner_id', $branch_id);
                } else {
                    $q->where('owner_id', auth()->user()->id);
                }
                // where('owner_id', auth()->user()->id)->pluck('id')
            })->pluck('id'));
        });

        $soffoyda =  DailyRepot::whereIn('user_id',         User::where(function ($q) use ($branch_id, $branchAllId) {
            if ($branch_id == 'all') {
                $q->whereIn('owner_id', $branchAllId);
            } else
            if ($branch_id > 0) {
                $q->where('owner_id', $branch_id);
            } else {
                $q->where('owner_id', auth()->user()->id);
            }
            // where('owner_id', auth()->user()->id)->pluck('id')
        })->pluck('id'))
            // ->whereDate('created_at', now()->format('Y-m-d'))
            ->where(function ($q) use ($request, $currentMonthIndex, $is_all) {
                if (isset($request->is_repot) && $request->is_repot == 1) {
                    if (isset($request->is_all) && $request->is_all == 1) {
                        $q->whereDate('created_at', now()->format('Y-m-d'));
                    }
                    if (isset($request->is_month) && $request->is_month == 1) {
                        $q->whereMonth('created_at', $currentMonthIndex);
                    }
                }
            })
            ->sum('total_price');
        - (
            $referdoctorbalance->sum('total_kounteragent_contribution_price') + $referdoctorbalance->sum('total_kounteragent_doctor_contribution_price') +  $docotrbalnce->sum('total_doctor_contribution_price')
        );
        $yollanma_analiz = ClientValue::where(function ($q) use ($branch_id, $branchAllId) {
            if ($branch_id == 'all') {
                $q->whereIn('user_id', User::where('owner_id', $branchAllId)->pluck('id'));
            } else if ($branch_id > 0) {
                $q->whereIn('user_id', User::where('owner_id', [$branch_id])->pluck('id'));
            } else {
                $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
            }
            $q->where('is_at_home', '!=', 1);
        })
            ->whereHas('client', function ($q) {
                $q->where('referring_doctor_id', '>', 0);
            })
            ->with('client.person')
            ->get();
        $analiz = ClientValue::where(function ($q) use ($branch_id, $branchAllId) {
            if ($branch_id == 'all') {
                $q->whereIn('user_id', User::where('owner_id', $branchAllId)->pluck('id'));
            } else if ($branch_id > 0) {
                $q->whereIn('user_id', User::where('owner_id', [$branch_id])->pluck('id'));
            } else {
                $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
            }
            $q->where('is_at_home', '!=', 1);
        })
            ->with('client.person')

            ->get();
        // reklama analiz
        $advertisements_analiz = Advertisements::with(['clientAnaliz.clientValue' => function ($q) use ($branch_id, $branchAllId) {
            $q->where('is_at_home', '!=', 1);
            if ($branch_id == 'all') {
                $q->whereIn('user_id', User::where('owner_id', $branchAllId)->pluck('id'));
            } else if ($branch_id > 0) {
                $q->whereIn('user_id', User::where('owner_id', [$branch_id])->pluck('id'));
            } else {
                $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
            }
        }])
            // ->has('client.clientValue')
            ->get();
        $advertisements_new_client = Advertisements::whereHas('client', function ($q) {
            $q->whereDate('created_at', now()->format('Y-m-d'));
        })
            // ->has('client.clientValue')
            ->get();
        // doirxona

        // MP HISSASI
        // MP - Kontragent ishlagan puli
        // kounter doktor olib kelgan mijozlkar
        $kounter_agent_client = Client::where(function ($q) use ($branch_id, $branchAllId) {
            if ($branch_id == 'all') {
                $q->whereIn('user_id', User::where('owner_id', $branchAllId)->pluck('id'));
            } else if ($branch_id > 0) {
                $q->whereIn('user_id', User::where('owner_id', [$branch_id])->pluck('id'));
            } else {
                $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
            }
            $q->where('referring_doctor_id', '>', 0);
        })->with('person')
            // ->whereHas('client', function ($q) {
            //     $q->where('referring_doctor_id', '>', 0);
            // })
            ->get();
        // 1-5
        // 6-14
        // 15+
        $fiveYearsAgo = Carbon::now()->subYears(5);
        $today = Carbon::today();
        // $currentDate = Carbon::now();
        // $fiveYearsAgo = $currentDate->subYears(5)->toDateString();
        // $sixYearsAgo = $currentDate->subYears(1)->toDateString();
        // $fifteenYearsAgo = $currentDate->subYears(15)->toDateString();

        $age1_5 = Client::WhereNull('parent_id')
            // ->whereBetween('data_birth', [
            //     $fiveYearsAgo->format('Y-m-d'),
            //     now()->addYears(1)->format('Y-m-d')
            // ])
            ->whereDate('data_birth', '>=', $today->subYears(5))
            // ->whereDate('data_birth', '<', $fiveYearsAgo->format('Y-m-d'))
            // ->whereDate('data_birth', '>',   now()->addYears(1)->format('Y-m-d'))
            // ->where('data_birth', '>', $sixYearsAgo)
            // ->where('data_birth', '>', $fiveYearsAgo->format('Y-m-d'))
            // ->where('data_birth', now()->addYears(1)->format('Y-m-d'))
            // ->whereBetween('data_birth', [$fiveYearsAgo->format('Y-m-d'), Carbon::now()->addYears(1)->format('Y-m-d')])
            ->whereHas('clientItem', function ($q)  use ($branch_id, $branchAllId) {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::where('owner_id', $branchAllId)->pluck('id'));
                } else if ($branch_id > 0) {
                    $q->whereIn('user_id', User::where('owner_id', [$branch_id])->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
                }
            })->count();
        $fourteenYearsAgo = Carbon::now()->subYears(16);
        $sixYearsAgo = Carbon::now()->subYears(6);
        $today = Carbon::today();
        $age6_14 = Client::WhereNull('parent_id')
        
        ->whereDate('data_birth', '<', now()->subYears(5))
        ->whereDate('data_birth', '>', now()->subYears(15))
            // ->whereDate('data_birth', '<', $fourteenYearsAgo->format('Y-m-d'))
            // ->whereDate('data_birth', '>', $sixYearsAgo->addYears(1)->format('Y-m-d'))
            // ->whereBetween('data_birth', [
            //     $fourteenYearsAgo->format('Y-m-d'),
            //     $sixYearsAgo->addYears(1)->format('Y-m-d')
            // ])
            // ->whereBetween('data_birth', [ $fourteenYearsAgo->format('Y-m-d'),$sixYearsAgo->addYears(1)->format('Y-m-d')])

            ->whereHas('clientItem', function ($q)  use ($branch_id, $branchAllId) {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::where('owner_id', $branchAllId)->pluck('id'));
                } else if ($branch_id > 0) {
                    $q->whereIn('user_id', User::where('owner_id', [$branch_id])->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
                }
            })->count();
        $fifteenYearsAgo = Carbon::now()->subYears(14);
        $today = Carbon::today();
        $age16 = Client::WhereNull('parent_id')
            // ->whereDate('data_birth', '<', $fifteenYearsAgo->format('Y-m-d'))
            ->whereDate('data_birth', '<=', $today->subYears(15))
            ->whereHas('clientItem', function ($q)  use ($branch_id, $branchAllId) {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::where('owner_id', $branchAllId)->pluck('id'));
                } else if ($branch_id > 0) {
                    $q->whereIn('user_id', User::where('owner_id', [$branch_id])->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
                }
            })->count();
        // where(function ($q) use ($branch_id, $branchAllId) {
        //     if ($branch_id == 'all') {
        //         $q->whereIn('user_id', User::where('owner_id', $branchAllId)->pluck('id'));
        //     } else if ($branch_id > 0) {
        //         $q->whereIn('user_id', User::where('owner_id', [$branch_id])->pluck('id'));
        //     } else {
        //         $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
        //     }
        //     $q->where('referring_doctor_id', '>', 0);
        // })
        $new_clinet =  Client::WhereNull('parent_id')
            ->whereHas('clientItem', function ($q)  use ($branch_id, $branchAllId) {
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::where('owner_id', $branchAllId)->pluck('id'));
                } else if ($branch_id > 0) {
                    $q->whereIn('user_id', User::where('owner_id', [$branch_id])->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
                }
            })
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->count();
        $yollanma_client  = Client::WhereNull('parent_id')
            ->whereHas('clientItem', function ($q)  use ($branch_id, $branchAllId) {
                $q->where('referring_doctor_id', '>', 0);
                if ($branch_id == 'all') {
                    $q->whereIn('user_id', User::where('owner_id', $branchAllId)->pluck('id'));
                } else if ($branch_id > 0) {
                    $q->whereIn('user_id', User::where('owner_id', [$branch_id])->pluck('id'));
                } else {
                    $q->whereIn('user_id', User::where('owner_id', [auth()->user()->id])->pluck('id'));
                }
            })->get();
        // ->whereDate('created_at', now()->format('Y-m-d'))
        // ->count()
        return [
            'age' => [
                'age_1_5' => $age1_5,
                'age_1_5sss' => [
                    $fiveYearsAgo->format('Y-m-d'),
                    now()->addYears(1)->format('Y-m-d')
                ],

                'age_6_14' => $age6_14,
                'age_s6_14ss' => [
                    $fourteenYearsAgo->format('Y-m-d'),
                    $sixYearsAgo->addYears(1)->format('Y-m-d')
                ],
                'age_15' => $age16,
                'age_15ss' => [
                    $fifteenYearsAgo->format('Y-m-d')
                ],
            ],
            'new_clinet' => $new_clinet,
            'new_kounter_agent_client' => $kounter_agent_client->filter(function ($q) {
                return $q->person->created_at->format('Y-m-d') == now()->format('Y-m-d');
                // uyga yozilganda keyingi sanani yozganda uni boshqa kungi analizda yangi analzi deb qabul qialdi nima qilamiz
            })->pluck('person_id')->unique()->count(),
            'kounter_agent_client' => $kounter_agent_client->pluck('person_id')->unique()->count(),

            'analiz' => $analiz->sum('qty'),
            'new_analiz' => $analiz->filter(function ($q) {
                return $q->client->person->created_at->format('Y-m-d') == now()->format('Y-m-d');
                // uyga yozilganda keyingi sanani yozganda uni boshqa kungi analizda yangi analzi deb qabul qialdi nima qilamiz
            })->sum('qty'),
            'yollanma_analiz' => $yollanma_analiz->sum('qty'),
            'new_yollanma_client' => $yollanma_client->filter(function ($q) {
                return $q->person->created_at->format('Y-m-d') == now()->format('Y-m-d');
                // uyga yozilganda keyingi sanani yozganda uni boshqa kungi analizda yangi analzi deb qabul qialdi nima qilamiz
            })->pluck('person_id')->unique()->count(),
            'yollanma_client' => $yollanma_client->pluck('person_id')->unique()->count(),

            'advertisements_analiz' => $advertisements_analiz->map((function ($q) {
                return [
                    'name' => $q->name,
                    'qty' => $q->clientAnaliz->sum(function ($q) {
                        return $q->clientValue->sum('qty');
                    }),
                ];
            })),
            'advertisements_new_client' => $advertisements_new_client->map((function ($q) {
                return [
                    'name' => $q->name,
                    'qty' => $q->client->count(),
                ];
            })),
            'soffoyda' => $soffoyda,
            'ambulator' => $ambulato->pluck('person_id')->unique()
                ->count(),
            'mulojagayozilgnlar' => $bemorsoni > 0 ? ($mulojagayozilgnlar->pluck('person_id')->unique()->count() / $bemorsoni) * 100 : 0,
            'muoljadagilar' => $muolaja->count(),
            'service' =>  $service,
            'daily' => [
                'income' => [
                    'total_price' => $daily->sum('total_price'),
                    'cash_price' => $daily->sum('cash_price'),
                    'card_price' => $daily->sum('card_price'),
                    'transfer_price' => $daily->sum('transfer_price'),
                ],
                'residue' => [
                    'total_price' => $daily->sum('total_price') -  $kunxarajat->sum('price'),
                    'cash_price' => $daily->sum('cash_price') - $kunxarajat->where('pay_type', 'cash')->sum('price'),
                    'card_price' => $daily->sum('card_price') - $kunxarajat->where('pay_type', 'card')->sum('price'),
                    'transfer_price' => $daily->sum('transfer_price') - $kunxarajat->where('pay_type', 'transfer')->sum('price'),
                ]
            ],
            'year' => [
                'label' =>      $currentYear,
                'value' =>      $currentYear,
            ],
            'xarjatlar' => $xarjatlar,
            'bemorlar' => $bemorsoni,
            'kelmaganlar' => $kelmaganlar->count(),
            'yakunlanganlar' => $yakunlangan->count(),
            'arxivlanganlar' => $arxiv->count(),
            'yangibemorlar' => count($yangibemorlar),
            'erkaklar' => $erkaksoni,
            'ayollar' =>  $bemorsoni - $erkaksoni,
            'tugayotkanlar' => $tugayotkanlar->count(),
            'month' => $this->getMonthByIndex($currentMonthIndex),
        ];
        // $yakunlangan =  Client::where('is_check_doctor' ,Client::STATUS_FINISH)
        // ->count();
    }
    public function statisticaHome($request)
    {
        $currentMonthIndex = 0;
        $is_all = false;
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
        }
        if ((isset($request->is_all) && $request->is_all == 1)) {
            $is_all = true;
            $currentMonthIndex = 0;
        }
        if ($request->is_today == 1) {
            $currentMonthIndex = 0;
            $is_all = false;
        }




        $bemorsoni = Client::whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            ->where(function ($q) use ($request, $currentMonthIndex, $is_all) {
                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('created_at', now()->format('Y-m-d'));
                }

                if ($currentMonthIndex > 0) {
                    $q->whereMonth('created_at', $currentMonthIndex);
                }
            })->pluck('person_id')->unique()
            ->count();
        // Log::info('ayol', [$bemorlar->where('sex','!=' ,'male')->get()]);
        $xarjatlar = Expense::whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            ->where(function ($q) use ($request, $currentMonthIndex, $is_all) {
                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('created_at', now()->format('Y-m-d'));
                }
                if ($currentMonthIndex > 0) {
                    $q->whereMonth('created_at', $currentMonthIndex);
                }
            })
            ->sum('price');
        $service = ClientValue::whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            ->where(function ($q) use ($request, $currentMonthIndex, $is_all) {
                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('created_at', now()->format('Y-m-d'));
                }
                if ($currentMonthIndex > 0) {
                    $q->whereMonth('created_at', $currentMonthIndex);
                }
            })
            ->where('is_active', 1)
            ->where('is_pay', 1)
            ->sum('qty');

        $daily = DailyRepot::whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->get();
        $kunxarajat = Expense::whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->get();
        // kountur docitr + agent+docotr
        // kuntur doctor va agent
        $referdoctorbalance = ReferringDoctorBalance::whereHas('client', function ($q) use ($request, $currentMonthIndex) {
            $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
            if (isset($request->is_today) && $request->is_today == 1) {
                $q->whereDate('created_at', now()->format('Y-m-d'));
            }
            if ($currentMonthIndex > 0) {
                $q->whereMonth('created_at', $currentMonthIndex);
            }
        });

        // docotrbalnce
        $docotrbalnce = DoctorBalance::whereHas('client', function ($q) use ($request, $currentMonthIndex) {
            $q->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'));
            if (isset($request->is_today) && $request->is_today == 1) {
                $q->whereDate('created_at', now()->format('Y-m-d'));
            }
            if ($currentMonthIndex > 0) {
                $q->whereMonth('created_at', $currentMonthIndex);
            }
        });
        $soffoyda =
            DailyRepot::whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
            // ->whereDate('created_at', now()->format('Y-m-d'))
            ->where(function ($q) use ($request, $currentMonthIndex, $is_all) {
                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('created_at', now()->format('Y-m-d'));
                }
                if ($currentMonthIndex > 0) {
                    $q->whereMonth('created_at', $currentMonthIndex);
                }
            })
            ->sum('total_price') -
            (
                $referdoctorbalance->sum('total_kounteragent_contribution_price') + $referdoctorbalance->sum('total_kounteragent_doctor_contribution_price') +  $docotrbalnce->sum('total_doctor_contribution_price')
            );
        $umumiysumma = Client::whereNotNull('parent_id')
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
            ->with(['clientPayment.user', 'clientValue.service.department', 'user.owner'])
            ->where(function ($q) {
                if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
                    $q->where('user_id', auth()->id());
                } else {
                    // if ($branch_id == 'all') {
                    //     $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
                    // } else
                    // if ($branch_id > 0) {
                    //     $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
                    // } else {
                    //     $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                    // }
                    $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
                }
                // if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                //     $q->whereDate('created_at', $startDate->format('Y-m-d'));
                // } else {
                //     $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                // }
            })
            // ->where('is')
        ;
        $kontragent = User::where(['role' => User::USER_ROLE_COUNTERPARTY])
            ->where(function ($q) {
                // if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                //     if ($branch_id == 'all') {
                //         $q->whereIn('owner_id', $branchAllId);
                //     } else
                //     if ($branch_id > 0) {
                //         $q->where('owner_id', $branch_id);
                //     } else {
                //         $q->where('owner_id', auth()->user()->id);
                //     }
                // } else {
                //     $q->where('owner_id', auth()->user()->owner_id);
                // }
                $q->where('owner_id', auth()->user()->id);
            })
            ->whereHas('referringDoctor', function ($q) use ($currentMonthIndex) {
                $q->whereHas('referringDoctorBalance', function ($q) use ($currentMonthIndex,) {
                    // $q->whereMonth('date', $currentMonthIndex);
                    // $q->whereDate('date', $currentdate);
                });
            })->with(['referringDoctor' => function ($q) use ($currentMonthIndex,) {
                $q->with(['referringDoctorBalance' => function ($q) use ($currentMonthIndex,) {
                    // $q->whereDate('date', $currentdate);
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
            ->whereHas('doctorBalance', function ($q) use ($currentMonthIndex) {
                // $q->whereDate('date', $currentdate);
            })->with(['doctorBalance' => function ($q) use ($currentMonthIndex) {
                // $q->whereDate('date', $currentdate);
            }])
            ->get();
        $qarz = ClientValue::where(function ($q) {
            // if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
            //     // $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
            // } else {
            //     if ($branch_id == 'all') {
            //         $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
            //     } else
            //     if ($branch_id > 0) {
            //         $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
            //     } else {
            //         $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
            //     }
            // }
            $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
        })
            ->where('is_active', 1)
            ->whereHas('client', function ($q) use ($currentMonthIndex) {
                // $q->whereDate('created_at', $currentdate);
            });
        $cashExpence =  Expense::where(function ($q) {
            // if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
            //     $q->where('user_id', auth()->id());
            // } else {
            //     if ($branch_id == 'all') {
            //         $q->whereIn('user_id', User::whereIn('owner_id', $branchAllId)->pluck('id'));
            //     } else
            //     if ($branch_id > 0) {
            //         $q->whereIn('user_id', User::whereIn('owner_id', [$branch_id])->pluck('id'));
            //     } else {
            //         $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
            //     }
            // }

            // if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
            //     $q->whereDate('created_at', $startDate->format('Y-m-d'));
            // } else {
            //     $q->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
            // }
            $q->whereIn('user_id', User::where('owner_id', auth()->id())->pluck('id'));
        })

            ->sum('price');
        // $product =  Product::where(function ($q) use ($request) {

        //     $q->where('user_id', auth()->user()->id)
        //         ->orWhereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
        //     ;
        // })
        //     ->with(['productReceptionItem' => function ($q) use ($request) {
        //         $q->with(['productReception' => function ($q) use ($request) {
        //             $q->whereIn(
        //                 'user_id',
        //                 User::where('owner_id', auth()->user()->id)->orWhere('id', auth()->user()->id)->pluck('id')
        //             );
        //         }]);
        //     }]);
        $owner = User::where([
            'role' => User::USER_ROLE_PHARMACY,
            'owner_id' => auth()->user()->id
        ])->first();
        $pharmacyProduct = PharmacyProduct::where('user_id', $owner->id ?? 0)->get();
        return [
            'soffoyda' => $soffoyda,
            'service' =>  $service,
            'kontragent' => $kontragent->sum(function ($clientValue) {
                return $clientValue->referringDoctor
                    ->sum(function ($q) {
                        return   $q->referringDoctorBalance->sum('total_kounteragent_contribution_price');
                    });
                // Agar qiymat mavjud bo'lmasa 0 qilamiz

            }),
            'dorixona' => $pharmacyProduct->sum('qty'),
            'cash_expence' =>   $cashExpence,
            'total_price' =>   $umumiysumma->sum('total_price'),
            'debt' =>   $qarz->select(DB::raw("
            SUM(
                CASE 
                    WHEN discount <= 100 
                    THEN (total_price - (total_price / 100 ) * discount) - pay_price
                    ELSE (total_price - discount) - pay_price
                END
            ) as total
        "))
                ->value('total') ?? 0,
            'daily' => [
                'income' => [
                    'total_price' => $daily->sum('total_price'),
                    'cash_price' => $daily->sum('cash_price'),
                    'card_price' => $daily->sum('card_price'),
                    'transfer_price' => $daily->sum('transfer_price'),
                ],
                'residue' => [
                    'total_price' => $daily->sum('total_price') -  $kunxarajat->sum('price'),
                    'cash_price' => $daily->sum('cash_price') - $kunxarajat->where('pay_type', 'cash')->sum('price'),
                    'card_price' => $daily->sum('card_price') - $kunxarajat->where('pay_type', 'card')->sum('price'),
                    'transfer_price' => $daily->sum('transfer_price') - $kunxarajat->where('pay_type', 'transfer')->sum('price'),
                ]
            ],
            '$referdoctorbalance' => $referdoctorbalance->sum('total_kounteragent_contribution_price'),
            '$docotrbalnce' => $docotrbalnce->sum('total_doctor_contribution_price'),
            'xarjatlar' => $xarjatlar,
            'bemorlar' => $bemorsoni,
            'month' => $this->getMonthByIndex($currentMonthIndex),
        ];
    }
    public function statisticaCounterparty($request)
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
        if ((isset($request->is_all) && $request->is_all == 1)) {
            $is_all = true;
            $currentMonthIndex = Carbon::now()->month;
        }

        if ($request->is_today == 1) {
            $currentMonthIndex = Carbon::now()->month;
            $is_all = false;
        }

        $referringDoctor = ReferringDoctor::where(function ($q) use ($currentMonthIndex, $request, $is_all) {
            $q->whereHas('referringDoctorBalance', function ($q) use ($currentMonthIndex, $request, $is_all) {
                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('date', now()->format('Y-m-d'));
                }
                if (!$is_all) {
                    $q->whereMonth('date', $currentMonthIndex);
                }
            });
        })
            ->with(['referringDoctorBalance' => function ($q) use ($currentMonthIndex) {
                $q
                    ->with('client')
                    ->whereMonth('date', $currentMonthIndex);
            }])
            ->where(function ($q) use ($request) {
                if (isset($request->show_id) && $request->show_id > 0) {
                    $q->whereIn('user_id', ReferringDoctor::where('user_id',  $request->show_id)->pluck('id'));
                } else {
                    $q->where('user_id', auth()->id());
                }
            });
        if (isset($request->show_id) && $request->show_id > 0) {
            $user =  User::find($request->show_id);
        } else {
            $user =  auth()->user();
        }

        $plan = CounterpartySetting::where(['ambulatory_service_id' => $user->ambulatory_service_id, 'treatment_service_id' => $user->treatment_service_id])
            // ->whereYear('created_at', date('Y'))
            ->where(function ($q) use ($request) {
                if (isset($request->show_id) && $request->show_id > 0) {
                    $q->where('user_id', $request->show_id);
                } else {
                    $q->where('user_id', auth()->id());
                }
            })
            ->where(function ($q) use ($currentMonthIndex) {
                $q->whereMonth('created_at',  $currentMonthIndex);
            })
            ->first();

        $ambulatory_service_id = $user->ambulatory_service_id;
        $treatment_service_id  = $user->treatment_service_id;
        if ($plan) {
            $ambulatory_service_id = $plan->ambulatory_service_id;
            $treatment_service_id = $plan->treatment_service_id;
            $treatment_id_data = json_decode($plan->treatment_id_data);
            $ambulatory_id_data = json_decode($plan->ambulatory_id_data);
        } else {
            $userCounterpartyPlan = UserCounterpartyPlan::where(function ($q) use ($request) {
                if (isset($request->show_id) && $request->show_id > 0) {
                    $q->where('user_id', $request->show_id);
                } else {
                    $q->where('user_id', auth()->id());
                }
            })->get();
            $treatment_id_data = $userCounterpartyPlan->where('status', 'treatment')->pluck('service_id');
            $ambulatory_id_data = $userCounterpartyPlan->where('status', 'ambulatory')->pluck('service_id');
        }



        $ambulatoryClietValue = ClientValue::where([
            // 'client_id' => auth()->id(),
            'is_active' => 1,
            // 'service_id' => $ambulatory_service_id,
        ])
            ->whereIn('service_id', $ambulatory_id_data)
            ->where('is_pay', 1)

            // ->where(DB::raw('total_price - (CASE WHEN discount <= 100 THEN (total_price * discount / 100) ELSE discount END)'), '=', DB::raw('pay_price'))

            ->whereHas('client', function ($q) use ($currentMonthIndex, $request, $is_all) {
                if (isset($request->show_id) && $request->show_id > 0) {
                    // $q
                    //     ->where('referring_doctor_id', $request->show_id);
                    $q->whereIn('referring_doctor_id', ReferringDoctor::where('user_id',  $request->show_id)->pluck('id'));
                } else {
                    $q->whereIn('referring_doctor_id', ReferringDoctor::where('user_id', auth()->id())->pluck('id'));
                }
                $q
                    ->whereMonth('created_at', $currentMonthIndex);
            });
        $ambulatoryClietValueQty = $ambulatoryClietValue->get()->sum('qty');
        $treatmentClietValue = ClientValue::where([
            // 'client_id' => auth()->id(),
            'is_active' => 1,
            // 'service_id' => $treatment_service_id,
        ])
            ->whereIn('service_id', $treatment_id_data)

            // ->where(DB::raw('total_price - (CASE WHEN discount <= 100 THEN (total_price * discount / 100) ELSE discount END)'), '=', DB::raw('pay_price'))
            ->where('is_pay', 1)
            ->whereHas('client', function ($q) use ($currentMonthIndex, $request) {
                if (isset($request->show_id) && $request->show_id > 0) {
                    // $q
                    //     ->where('referring_doctor_id', $request->show_id);
                    $q->whereIn('referring_doctor_id', ReferringDoctor::where('user_id',  $request->show_id)->pluck('id'));
                } else {
                    $q->whereIn('referring_doctor_id', ReferringDoctor::where('user_id', auth()->id())->pluck('id'));
                }
                $q
                    ->whereMonth('created_at', $currentMonthIndex);
            });
        $treatmentClietValueQty = $treatmentClietValue->get()->sum('qty');

        $ambulatory_service_kounteragent_price  = 0;
        $ambulatory_plan_qty = 0;
        $ambulatory_service_kounteragent_price = 0;

        $treatment_service_kounteragent_price  = 0;
        $treatment_plan_qty = 0;
        $treatment_service_kounteragent_price = 0;
        if ($plan) {
            $ambulatory_service_price = $plan->ambulatory_service_price;
            $ambulatory_plan_qty = $plan->ambulatory_plan_qty;
            $ambulatory_service_kounteragent_price = $plan->ambulatory_service_kounteragent_price;

            $treatment_service_price = $plan->treatment_service_price;
            $treatment_plan_qty = $plan->treatment_plan_qty;
            $treatment_service_kounteragent_price = $plan->treatment_service_kounteragent_price;
        } else {
            if (isset($request->show_id) && $request->show_id > 0) {
                $user =  User::find($request->show_id);
            }
            $ambulatory_service = Services::find($user->ambulatory_service_id);
            $ambulatory_service_price = $ambulatory_service->price ?? 0;
            $ambulatory_plan_qty = $user->ambulatory_plan_qty;
            $ambulatory_service_kounteragent_price = $ambulatory_service->kounteragent_contribution_price ?? 0;
            $treatment_service = Services::find($user->treatment_service_id);
            $treatment_service_price = $treatment_service->price ?? 0;
            $treatment_plan_qty = $user->treatment_plan_qty ?? 0;
            $treatment_service_kounteragent_price = $treatment_service->kounteragent_contribution_price ?? 0;
        }


        $clietValueTotal = ClientValue::where([
            // 'client_id' => auth()->id(),
            'is_active' => 1,
            // 'service_id' => $ambulatory_service_id,
        ])
            // ->whereIn('service_id', [...$treatment_id_data, ...$ambulatory_id_data])

            // ->where(DB::raw('total_price - (CASE WHEN discount <= 100 THEN (total_price * discount / 100) ELSE discount END)'), '=', DB::raw('pay_price'))
            ->where('is_pay', 1)
            ->whereHas('client', function ($q) use ($currentMonthIndex, $request, $is_all) {


                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('created_at', now()->format('Y-m-d'));
                }
                if (!$is_all) {
                    $q->whereMonth('created_at', $currentMonthIndex);
                }
                if (isset($request->show_id) && $request->show_id > 0) {
                    $q->whereIn('referring_doctor_id', ReferringDoctor::where('user_id',  $request->show_id)->pluck('id'));
                } else {
                    $q->whereIn('referring_doctor_id', ReferringDoctor::where('user_id', auth()->id())->pluck('id'));
                }
            })
            ->with('client');
        $clietValueTotal1 = ClientValue::where([
            // 'client_id' => auth()->id(),
            'is_active' => 1,
            // 'service_id' => $ambulatory_service_id,
        ])
            // ->whereIn('service_id', [...$treatment_id_data, ...$ambulatory_id_data])

            // ->where(DB::raw('total_price - (CASE WHEN discount <= 100 THEN (total_price * discount / 100) ELSE discount END)'), '=', DB::raw('pay_price'))
            ->where('is_pay', 1)
            ->whereHas('client', function ($q) use ($currentMonthIndex, $request, $is_all,) {


                if (isset($request->is_today) && $request->is_today == 1) {
                    $q->whereDate('created_at', now()->format('Y-m-d'));
                }
                if (!$is_all) {
                    $q->whereMonth('created_at', $currentMonthIndex);
                }
                if (isset($request->show_id) && $request->show_id > 0) {
                    $q->whereIn('referring_doctor_id', ReferringDoctor::where('user_id',  $request->show_id)->pluck('id'));
                } else {
                    $q->whereIn('referring_doctor_id', ReferringDoctor::where('user_id', auth()->id())->pluck('id'));
                }
            })
            ->with('client');
        Log::info('treatment_id_data', [$treatment_id_data]);
        $service_count = $clietValueTotal->sum('qty');
        return [
            'balance' => $referringDoctor->get()->sum(function ($q) {
                return $q->referringDoctorBalance->sum('total_kounteragent_contribution_price');
            }),
            'clients' =>  $clietValueTotal->get()
                ->map(function ($doctor) {
                    return $doctor->client->person_id;
                })
                ->unique() // faqat noyob person_id larni olish
                ->count(),
            // 'clients' =>  $referringDoctor->get()
            // ->flatMap(function ($doctor) {
            //     return $doctor->referringDoctorBalance->map(function ($balance) {
            //         return $balance->client->person_id;
            //     });
            // })
            // ->unique() // faqat noyob person_id larni olish
            // ->count(),
            'service_count' => $service_count,
            // 'service_count' => $referringDoctor->get()->sum(function ($q) {
            //     return $q->referringDoctorBalance->sum('service_count');
            // }),
            'ambulatory_service' => [
                'ambulatory_plan_qty' => $ambulatory_plan_qty,
                'do' => $ambulatoryClietValueQty,
                'ambulatory_price' => $ambulatory_service_price *  $ambulatory_plan_qty,
                'do_ambulatory_price' => $ambulatory_service_price * $ambulatoryClietValueQty,
            ],
            'treatment_service' => [
                'treatment_plan_qty' => $treatment_plan_qty,
                'do' => $treatmentClietValueQty,

                'treatment_price' => $treatment_service_price *  $treatment_plan_qty,
                'do_treatment_price' => $treatment_service_price * $treatmentClietValueQty,
            ],
            'total_service' => [
                'ambulatory' => $clietValueTotal->whereIn('service_id', $ambulatory_id_data)->sum('qty'),
                'treatment' => $clietValueTotal1->whereIn('service_id', $treatment_id_data)->sum('qty'),
            ],
            'plan' => $plan
        ];
    }
}
