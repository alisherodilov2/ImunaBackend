<?php

namespace App\Services\Api\V3;

use App\Http\Resources\ReferringDoctor\ReferringDoctorResource;
use App\Http\Resources\Repot\RepotReferringDoctorResource;
use App\Models\Graph;
use App\Models\GraphArchive;
use App\Models\ReferringDoctor;
use App\Models\ReferringDoctorBalance;
use App\Models\ReferringDoctorChangeArchive;
use App\Models\ReferringDoctorPay;
use App\Models\ReferringDoctorServiceContribution;
use App\Models\Services;
use App\Models\User;
use App\Services\Api\V3\Contracts\ReferringDoctorServiceInterface;
use App\Traits\Crud;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ReferringDoctorService implements ReferringDoctorServiceInterface
{
    public $modelClass = ReferringDoctor::class;
    use Crud;
    /*************  ✨ Codeium Command ⭐  *************/
    /**
     * Filter referring doctors by start date, end date, month, and show_id
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    /******  d56c5a45-0a3c-4bf5-808f-ebaf4b73ddc0  *******/    function getMonthByIndex($index)
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
    function getYearsFromDate($inputDate)
    {
        // Create a Carbon instance from the input date
        $startDate = Carbon::createFromFormat('d.m.Y', $inputDate);
        $currentDate = Carbon::now(); // Get the current date

        // Create an array to hold the years
        $years = [];

        // Loop to add years from the start date up to the current year
        for ($year = $startDate->year; $year <= $currentDate->year; $year++) {
            $years[] = [
                'value' => $year,
                'label' => $year,
            ]; // Add the year to the array
        }

        // Add 10 years to the array
        for ($i = 1; $i <= 1; $i++) {
            $years[] =   [
                'value' =>  $currentDate->year + $i,
                'label' =>  $currentDate->year + $i,
            ]; // Add the year to the array

        }

        return $years; // Return the array of years
    }
    public function filter($request)
    {
        if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
            return [
                'data' => $this->modelClass::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->get()
            ];
        }
        $startDate = now();
        $owner = User::find(auth()->user()->owner_id);
        $endDate = now();
        $currentMonthYear = Carbon::now()->year;
        if (isset($request->year) && $request->year > 0 && is_int(+$request->year)) {
            $currentMonthYear = $request->year;
        }
        $month = now()->month;
        if (isset($request->start_date)) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
            if ($parsedDate->format('Y-m-d') ===  $request->start_date) {
                $startDate = $parsedDate;
            }
        }
        if (isset($request->month) && $request->month > 0 && $request->month < 13) {
            $month = $request->month;
        }
        if (isset($request->end_date)) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
            if ($parsedDate->format('Y-m-d') ===  $request->end_date) {
                $endDate = $parsedDate;
            }
        }
        if (isset($request->show_id) && $request->show_id > 0) {
            return [
                'data' => ReferringDoctorResource::collection($this->modelClass::where(function ($q) use ($startDate, $endDate) {})
                    ->with(['graphArchive', 'referringDoctorBalance' => function ($q) use ($request, $startDate, $endDate) {
                        if (isset($request->is_repot)) {
                        }
                    }])
                    ->where('user_id', $request->show_id)
                    ->get()),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),

            ];
        }
        if (isset($request->is_table)) {
            return [
                'data' => ReferringDoctorResource::collection($this->modelClass::where('user_id', auth()->id())->get())
            ];
        }

        return [
            'data' => ReferringDoctorResource::collection($this->modelClass::where('user_id', auth()->id())

                ->whereHas('referringDoctorBalance', function ($q) use ($request, $startDate, $endDate, $month, $currentMonthYear) {
                    if (isset($request->is_payment)) {

                        $q->whereMonth('date', $month)
                            ->whereYear('date', $currentMonthYear)
                        ;
                    } else  if (isset($request->is_repot)) {
                        if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                            $q->whereDate('date', $startDate->format('Y-m-d'));
                        } else {
                            $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);
                        }
                    }
                })
                // ->has('referringDoctorBalance')
                ->where(function ($q) use ($request) {
                    if (isset($request->full_name)) {
                        $q
                            ->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$request->full_name}%");
                    }
                })
                ->with(['graphArchive', 'referringDoctorBalance' => function ($q) use ($request, $startDate, $endDate, $month, $currentMonthYear) {
                    if (isset($request->is_payment)) {
                        $q->whereMonth('date', $month)
                            ->whereYear('date', $currentMonthYear);
                    } else {
                        if (isset($request->is_repot)) {
                            // $q->whereHas('referringDoctorBalance', function ($q) use ($startDate, $endDate) {
                            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                                $q->whereDate('date', $endDate->format('Y-m-d'));
                            } else {
                                $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);
                            }
                            // });
                        }
                    }
                }])

                ->where(function ($q) use ($request) {

                    if (isset($request->referring_doctor_id) && $request->referring_doctor_id > 0) {
                        $q->where('id', $request->referring_doctor_id);
                    }
                })
                ->get()),
            'year' => $this->getYearsFromDate($owner->created_at->format('d.m.Y')),
            'current_year' => [
                'value' => $currentMonthYear,
                'label' => $currentMonthYear,
                'data' => $currentMonthYear
            ],
            'start_date' => $startDate->format('Y-m-d'),
            'month' => $this->getMonthByIndex($month),
            'end_date' => $endDate->format('Y-m-d'),

        ];
    }
    public function show($id, $request)
    {
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
        if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
            return $this->modelClass::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                ->get();
        }
        if (auth()->user()->role == User::USER_ROLE_COUNTERPARTY) {
            $find =  ($this->modelClass
                ::where(function ($q) use ($startDate, $endDate, $request) {
                    if (isset($request->is_repot)) {
                        $q->whereHas('referringDoctorBalance', function ($q) use ($startDate, $endDate) {
                            if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                                $q->whereDate('date', $startDate->format('Y-m-d'));
                            } else {
                                $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);
                            }
                        });
                    }
                })
                ->with(['referringDoctorBalance' => function ($q) use ($startDate, $endDate, $request) {
                    // $q->whereHas('referringDoctorBalance', function ($q) use ($startDate, $endDate) {
                    if (isset($request->is_repot)) {

                        if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                            $q->whereDate('date', $startDate->format('Y-m-d'));
                        } else {
                            $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);
                        }
                    }
                    $q
                        ->with('client.ClientValue.service');
                }])
                ->where('user_id', auth()->id())
                ->find($id));
            if (isset($find)) {

                return [
                    ...($find->toArray() ?? []),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ];
            }
            return [

                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];
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

        return ($this->modelClass::where(function ($q) use ($startDate, $endDate) {
            // if (isset($request->is_repot)) {
            $q->whereHas('referringDoctorBalance', function ($q) use ($startDate, $endDate) {
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('date', $startDate->format('Y-m-d'));
                } else {
                    $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);
                }
            });
            // }
        })
            ->with(['referringDoctorBalance' => function ($q) use ($request, $startDate, $endDate) {
                // $q->whereHas('referringDoctorBalance', function ($q) use ($startDate, $endDate) {
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('date', $endDate->format('Y-m-d'))
                        ->with('client')
                    ;
                } else {
                    $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')])
                        ->with('client')
                    ;
                    // $q->with('client');
                    // });
                }
            }])
            ->where('user_id', auth()->id())
            ->find($id));
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $user = auth()->user();
        if ($user->role == User::USER_ROLE_RECEPTION) {
            $first =  User::where(['is_main' => 1, 'role' => User::USER_ROLE_COUNTERPARTY, 'owner_id' => $user->owner_id])
                // ->('owner_id', $user->owner_id)
                ->first();
            if ($first) {
                $id = User::where('is_main', 1)->first()->id;
            } else {
                $first =  User::where(['role' => User::USER_ROLE_COUNTERPARTY, 'owner_id' => $user->owner_id])->first();
                $id = $first->id ?? 0;
            }
            //    ? $mainKounterId = User::where('is_main', 1)->first()->id : $mainKounterId = 0;
        }
        $request['user_id'] = $id;

        $result = $this->store($request);
        return $result;
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);

        return $result;
    }


    public function treatment($request)
    {

        if ($request->status == 'all') {
            return [
                'data' => RepotReferringDoctorResource::collection($this->modelClass::where('user_id', $request->show_id)
                    ->whereHas('client', function ($q) use ($request) {
                        $q->whereHas('graphAchive', function ($q) use ($request) {
                            // $q->where('referring_doctor_id', $request->show_id)
                            // ->where('status', $request->status ?? '');
                        });
                    })
                    ->with(['client' => function ($q) use ($request) {
                        $q->with(['graphAchive' => function ($q) use ($request) {

                            $q
                                // ->where('referring_doctor_id', $request->show_id)
                                // ->where('status', $request->status ?? '')

                                ->with(['graphArchiveItem', 'department:id,work_end_time']);
                        }])
                            // ->has('graphAchive') // Ensure graphArchiveItem exists
                        ;
                    }])
                    ->get()),

                'doctor' => $this->modelClass::find($request->show_id) ?? []
            ];
        }
        if ($request->status == 'ambulator') {
            return [
                'data' => $this->modelClass::where(function ($q) use ($request) {
                    if (isset($request->kontragent_id) && $request->kontragent_id > 0) {
                        $q->where('user_id', $request->kontragent_id);
                    } else {
                        $q->where('user_id', auth()->id());
                    }
                })
                    ->whereHas('client', function ($q) use ($request) {
                        if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {

                            $q->whereNotIn(
                                'person_id',
                                GraphArchive::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))->pluck('person_id')->unique()->toArray()
                            );
                        } else {

                            $q->whereNotIn(
                                'person_id',
                                GraphArchive::whereIn(
                                    'user_id',
                                    User::where('owner_id', auth()->id())->pluck('id')
                                )->pluck('person_id')->unique()->toArray()
                            );
                        }
                    })
                    ->with(['client' => function ($q) use ($request) {
                        if (isset($request->full_name)) {
                            $q->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                        }
                        $q->whereNotIn('person_id', GraphArchive::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))->pluck('person_id')->unique()->toArray());
                    }])
                    ->find($request->show_id) ?? [],
                'doctor' => $this->modelClass::find($request->show_id) ?? []
            ];
        }

        return [
            'data' => $this->modelClass::where(function ($q) use ($request) {
                if (isset($request->kontragent_id) && $request->kontragent_id > 0) {
                    $q->where('user_id', $request->kontragent_id);
                } else {
                    $q->where('user_id', auth()->id());
                }
            })
                ->whereHas('client', function ($q) use ($request) {
                    $q->whereHas('graphAchive', function ($q) use ($request) {
                        $q
                            ->where('referring_doctor_id', $request->show_id)
                            ->where('status', $request->status ?? '');
                    });
                })
                ->with(['client' => function ($q) use ($request) {
                    $q->with(['graphAchive' => function ($q) use ($request) {
                        if (isset($request->full_name)) {
                            $q->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                        }
                        $q->where('referring_doctor_id', $request->show_id)
                            ->where('status', $request->status ?? '')

                            ->with(['graphArchiveItem', 'department:id,work_end_time']);
                    }])
                        ->has('graphAchive') // Ensure graphArchiveItem exists
                    ;
                }])
                ->find($request->show_id) ?? [],

            'doctor' => $this->modelClass::find($request->show_id) ?? []
        ];
    }

    public function referringDoctorBalance($request)
    {
        if (isset($request->show_id) && $request->show_id > 0) {
            $id = $request->show_id;
        } else {

            $id = auth()->id();
        }
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

        // $data  = $this->modelClass::where('user_id',$request->show_id)
        // ->whereHas('referringDoctorBalance',function ($q) use ($startDate, $endDate) {
        //     if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
        //         $q->whereDate('date', $endDate->format('Y-m-d'));
        //     } else {
        //         $q ->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);
        //     }
        // })
        // ->with(['referringDoctorBalance' => function ($q) use ($startDate, $endDate) {
        //     if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
        //         $q->whereDate('date', $endDate->format('Y-m-d'));
        //     } else {
        //         $q ->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);
        //     }
        // }])
        // ->get();
        $data  = ReferringDoctorBalance::whereIn('referring_doctor_id', ReferringDoctor::where('user_id', $id)->pluck('id'))
            ->where(function ($q) use ($startDate, $endDate) {
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('date', $endDate->format('Y-m-d'));
                } else {
                    $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);
                }
            })
            ->get();
        return [
            'data' => $data,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }
    public function referringDoctorPay($request)

    {
        // counterparty_id kontagent_id
        $balance = ReferringDoctorBalance::whereDate('date', $request->date)
            ->whereIn('referring_doctor_id', ReferringDoctor::where('user_id', $request->counterparty_id)->pluck('id'))
            // ->where(function ($query) {
            //     $query->whereRaw('total_kounteragent_contribution_price - kounteragent_contribution_price_pay != 0')
            //         ->orWhereRaw('total_kounteragent_doctor_contribution_price - kounteragent_doctor_contribution_price_pay != 0');
            // })
            ->get();
        Log::info('s', [$balance]);
        Log::info('Ss', [ReferringDoctor::where('user_id', $request->counterparty_id)->pluck('id')]);
        $kounteragent_doctor_contribution_price = 0;
        $kounteragent_contribution_price = 0;
        $payAgent = 0;
        $payDoc = 0;
        // $pay  = ReferringDoctorPay::where(['date' => $request->date, 'counterparty_id', $request->counterparty_id])->get();
        if ($balance->count() > 0) {
            $kounteragent_contribution_price = $request->kounteragent_contribution_price;;
            // if ($balance->sum('total_kounteragent_contribution_price') - $balance->sum('kounteragent_contribution_price_pay') > 0 && $request->kounteragent_contribution_price > 0) {
            // }
            // if ($balance->sum('total_kounteragent_doctor_contribution_price') - $balance->sum('kounteragent_doctor_contribution_price_pay') > 0 && $request->kounteragent_doctor_contribution_price > 0) {
            $kounteragent_doctor_contribution_price = $request->kounteragent_doctor_contribution_price;
            // }
            if ($kounteragent_contribution_price > 0 || $kounteragent_doctor_contribution_price > 0) {
                foreach ($balance as  $item) {
                    $pay_agent = $item->total_kounteragent_contribution_price - $item->kounteragent_contribution_price_pay;

                    $pay_docotr =  $item->total_kounteragent_doctor_contribution_price - $item->kounteragent_doctor_contribution_price_pay;

                    $agent_sum = 0;
                    $doc_sum = 0;
                    if ($pay_agent > 0) {
                        // 5000-5000
                        if ($kounteragent_contribution_price - $pay_agent == 0) {
                            $agent_sum = $item->total_kounteragent_contribution_price;
                            $payAgent = $payAgent +  $pay_agent;
                            $kounteragent_contribution_price = $kounteragent_contribution_price - $pay_agent;
                        } else
                            // 7000 - 3000
                            if ($kounteragent_contribution_price - $pay_agent > 0) {
                                $agent_sum = $item->total_kounteragent_contribution_price;
                                $payAgent =  $payAgent + $pay_agent;
                                $kounteragent_contribution_price = $kounteragent_contribution_price - $pay_agent;
                            } else {
                                // 3000  7000
                                $agent_sum  =  $item->kounteragent_contribution_price_pay + $agent_sum + $kounteragent_contribution_price;
                                $payAgent = $payAgent + $kounteragent_contribution_price;
                                $kounteragent_contribution_price = 0;
                            }
                    }
                    // if ($pay_agent > 0) {
                    //     // 5000-5000
                    //     if ($kounteragent_contribution_price >= $pay_agent ) {
                    //         $agent_sum = $item->total_kounteragent_contribution_price;
                    //         $payAgent = $payAgent +  $pay_agent;
                    //         $kounteragent_contribution_price = $kounteragent_contribution_price - $pay_agent;
                    //     } else{
                    //           // 3000  7000
                    //           $agent_sum  =  $agent_sum + $kounteragent_contribution_price;
                    //           $payAgent = $payAgent + $kounteragent_contribution_price;
                    //           $kounteragent_contribution_price = 0;
                    //           Log::info('sss',[ $kounteragent_contribution_price]);
                    //     }

                    // }
                    // if ($pay_docotr > 0) {
                    //     // 5000-5000
                    //     if ($kounteragent_doctor_contribution_price >= $pay_docotr ) {
                    //         $doc_sum = $item->total_kounteragent_doctor_contribution_price;
                    //         $payDoc = $payDoc +  $pay_docotr;
                    //         $kounteragent_doctor_contribution_price = $kounteragent_doctor_contribution_price - $pay_docotr;
                    //     } else{
                    //           // 3000  7000
                    //           $doc_sum  =  $doc_sum + $kounteragent_doctor_contribution_price;
                    //           $payDoc = $payDoc + $kounteragent_doctor_contribution_price;
                    //           $kounteragent_doctor_contribution_price = 0;
                    //           Log::info('sss',[ $kounteragent_contribution_price]);
                    //     }

                    // }
                    if ($pay_docotr > 0) {
                        // 5000-5000
                        if ($kounteragent_doctor_contribution_price - $pay_docotr == 0) {
                            $doc_sum = $item->total_kounteragent_doctor_contribution_price;
                            $payDoc = $payDoc +  $pay_docotr;
                            $kounteragent_doctor_contribution_price = $kounteragent_doctor_contribution_price - $pay_docotr;
                        } else
                            // 7000 - 3000
                            if ($kounteragent_doctor_contribution_price - $pay_docotr > 0) {
                                $doc_sum = $item->total_kounteragent_doctor_contribution_price;
                                $payDoc =  $payDoc + $pay_docotr;
                                $kounteragent_doctor_contribution_price = $kounteragent_doctor_contribution_price - $pay_docotr;
                            } else {
                                // 3000  7000
                                $doc_sum  = $item->kounteragent_doctor_contribution_price_pay + $doc_sum + $kounteragent_doctor_contribution_price;
                                $payDoc = $payDoc + $kounteragent_doctor_contribution_price;
                                $kounteragent_doctor_contribution_price = 0;
                            }
                    }

                    ReferringDoctorBalance::find($item->id)->update([
                        'kounteragent_contribution_price_pay' => $agent_sum > 0 ?  $agent_sum : $item->kounteragent_contribution_price_pay,
                        'kounteragent_doctor_contribution_price_pay' => $doc_sum > 0 ? $doc_sum : $item->kounteragent_doctor_contribution_price_pay,
                    ]);
                }
            }
            ReferringDoctorPay::create([
                'date' => $request->date,
                'counterparty_id' => $request->counterparty_id,
                'kounteragent_contribution_price' => $payAgent,
                'kounteragent_doctor_contribution_price' => $payDoc,
            ]);
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
        return ReferringDoctorBalance::whereIn('referring_doctor_id', ReferringDoctor::where('user_id', $request->counterparty_id)->pluck('id'))
            ->where(function ($q) use ($startDate, $endDate) {
                if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                    $q->whereDate('date', $endDate->format('Y-m-d'));
                } else {
                    $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);
                }
            })
            ->get();
        // $find = ReferringDoctorBalance::find($request->referring_doctor_balance_id);
        // $all  = ReferringDoctorPay::where('referring_doctor_balance_id', $find->id)->get();
        // $kounteragent_doctor_contribution_price = 0;
        // $kounteragent_contribution_price = 0;
        // if (($find->total_kounteragent_contribution_price - ($all->sum('kounteragent_contribution_price') + $request->kounteragent_contribution_price)) >= 0 && $request->kounteragent_contribution_price > 0) {
        //     $kounteragent_contribution_price = $request->kounteragent_contribution_price;
        // }
        // if (($find->total_kounteragent_doctor_contribution_price - ($all->sum('kounteragent_doctor_contribution_price') + $request->kounteragent_doctor_contribution_price)) >= 0 && $request->kounteragent_doctor_contribution_price > 0) {
        //     $kounteragent_doctor_contribution_price = $request->kounteragent_doctor_contribution_price;
        // }
        // if ($kounteragent_doctor_contribution_price > 0 ||  $kounteragent_contribution_price > 0) {
        //     ReferringDoctorPay::create([
        //         'referring_doctor_balance_id' => $find->id,
        //         'user_id' => auth()->id(),
        //         'kounteragent_contribution_price' => $kounteragent_contribution_price,
        //         'kounteragent_doctor_contribution_price' => $kounteragent_doctor_contribution_price,
        //     ]);
        //     $find->update([
        //         'kounteragent_contribution_price_pay' => ReferringDoctorPay::where('referring_doctor_balance_id', $find->id)->sum('kounteragent_contribution_price'),
        //         'kounteragent_doctor_contribution_price_pay' => ReferringDoctorPay::where('referring_doctor_balance_id', $find->id)->sum('kounteragent_doctor_contribution_price'),
        //     ]);
        // }
        // return ReferringDoctorBalance::find($find->id);
    }

    public function doctorPay($id, $request)
    {
        $month = $request->month;
        $currentMonthYear = $request->year;
        $find = ReferringDoctorBalance::where('referring_doctor_id', $id)
            ->whereYear('date', $request->year)
            ->whereMonth('date', $month)
            ->whereRaw('total_kounteragent_doctor_contribution_price - COALESCE(counterparty_kounteragent_contribution_price_pay, 0) > 0')
            // ->orwhereNull('counterparty_kounteragent_contribution_price_pay')
            ->get();
        $totalPrice = $request->price;
        $payPrice = 0;

        Log::info('pay', [$find]);
        if ($find->count() > 0) {
            foreach ($find as $item) {
                $debt = $item->total_kounteragent_doctor_contribution_price - $item->counterparty_kounteragent_contribution_price_pay;
                $paysum = 0;
                if ($debt > 0) {
                    // qarzi >tolov summasi
                    if ($debt >= $totalPrice) {
                        $paysum = $item->counterparty_kounteragent_contribution_price_pay + $totalPrice;
                        $payPrice = $payPrice +  $totalPrice;
                        // $totalPrice =   $totalPrice - $debt;
                        $totalPrice = 0;
                    } else {
                        // qarzi< tolov summasi
                        $paysum = $item->total_kounteragent_doctor_contribution_price;

                        $payPrice =   $payPrice  + ($debt);
                        $totalPrice = $totalPrice - $debt;
                    }
                    if ($paysum > 0) {
                        ReferringDoctorBalance::find($item->id)->update([
                            'counterparty_kounteragent_contribution_price_pay' => $paysum
                        ]);
                    } else {
                        break;
                    }
                }
            }
        }
        if ($payPrice > 0) {
            ReferringDoctorPay::create([
                'referring_doctor_id' => $id,
                'user_id' => auth()->id(),
                'date' =>  "$currentMonthYear-" . (+$month < 10 ? "0$month"  : $month) . "-01",
                'counterparty_id' => auth()->id(),
                'kounteragent_doctor_contribution_price' => $payPrice,
            ]);
        }
        return
            //  ReferringDoctorBalance::where('referring_doctor_id', $id)
            //     ->whereMonth('date', $month)
            //     ->whereYear('date', now()->year)->get()


            ReferringDoctor::with(['referringDoctorBalance' => function ($q) use ($request, $currentMonthYear, $month) {
                $q
                    // ->where('user_id', auth()->id())
                    ->whereYear('date', $request->year)
                    ->whereMonth('date', $month);
            }, 'referringDoctorPay' => function ($q) use ($request, $currentMonthYear, $month) {
                $q
                    ->where('user_id', auth()->id())
                    ->whereYear('date', $request->year)
                    ->whereMonth('date', $month);;
            }])
            ->find($request->id);

        // $all  = ReferringDoctorPay::where('referring_doctor_id', $id)->get();

    }
    public function doctorPayShow($request)
    {
        $month = $request->month;
        $currentMonthYear = $request->year;
        return ReferringDoctor::with(['referringDoctorBalance' => function ($q) use ($request, $month, $currentMonthYear) {
            $q
                // ->where('user_id', auth()->id())
                // ->whereMonth('date', $request->month)
                ->whereYear('date', $request->year)
                ->whereMonth('date', $month)
                // ->whereYear('date', $currentMonthYear)

            ;
        }, 'referringDoctorPay' => function ($q) use ($request, $month, $currentMonthYear) {
            $q
                ->where('user_id', auth()->id())
                ->whereMonth('date', $month)
                ->whereYear('date', $currentMonthYear)
            ;
        }])
            ->find($request->id);
    }


    // ReferringDoctorChangeArchive
    public function referringDoctorChangeArchive($request)
    {

        $per_page = 50;
        $per_page = $request->per_page ?? $per_page;
        $data =  ReferringDoctorChangeArchive::where(function ($q) use ($request) {
            if (auth()->user()->role == User::USER_ROLE_COUNTERPARTY) {
                $q->whereHas('from_referring_doctor', function ($q) use ($request) {
                    $q->where('user_id', auth()->id());
                })->orWhereHas('to_referring_doctor', function ($q) use ($request) {
                    $q->where('user_id', auth()->id());
                });
            } else {
                $q->whereHas('from_referring_doctor', function ($q) use ($request) {
                    $q->whereHas('user', function ($q) use ($request) {
                        $q->where('owner_id', auth()->id());
                    });
                })->orWhereHas('to_referring_doctor', function ($q) use ($request) {
                    $q->whereHas('user', function ($q) use ($request) {
                        $q->where('owner_id', auth()->id());
                    });
                });
            }
        })->with(['client:id,first_name,last_name', 'from_referring_doctor:id,first_name,last_name', 'to_referring_doctor:id,first_name,last_name'])
            ->orderBy('id', 'desc')

            ->paginate($per_page);
        return [
            'data' => $data->items(), // Faqat ma'lumotlar massivini qaytarish
            'per_page' => $per_page,
            'total' => $data->total(),
            'last_page' => $data->lastPage(),
            'current_page' => $data->currentPage(),


        ];
    }

    // xizmatlar uchun alohida narx 
    public function serviceShow($id)
    {
        $service = Services::where([
            ['user_id', auth()->user()->owner_id],
            ['kounteragent_doctor_contribution_price', 0]
        ])
            ->with('department:id,name')
            ->get();
        $referringDoctorServiceContribution = ReferringDoctorServiceContribution::where([
            ['user_id', auth()->user()->id],
            ['ref_doc_id', $id],
        ])

            ->get();
        return [
            'service' => $service,
            'contribution' => $referringDoctorServiceContribution,
            'id' => $id
        ];
    }
    public function serviceUpdate($id, $request)
    {
        if (isset($request->ref_doc_service_contribution)) {
            $reqDdata = json_decode($request->ref_doc_service_contribution);
            foreach ($reqDdata as $key => $value) {
                $referringDoctorServiceContribution = ReferringDoctorServiceContribution::where([
                    ['user_id', auth()->user()->id],
                    ['ref_doc_id', $id],
                    ['service_id', $value->service_id],
                ])->first();
                if ($referringDoctorServiceContribution) {
                    $referringDoctorServiceContribution->update([
                        'service_id' => $value->service_id,
                        'ref_doc_id' => $id,
                        'user_id' => auth()->user()->id,
                        'contribution_price' => $value->contribution_price
                    ]);
                } else {
                    ReferringDoctorServiceContribution::create([
                        'service_id' => $value->service_id,
                        'ref_doc_id' => $id,
                        'user_id' => auth()->user()->id,
                        'contribution_price' => $value->contribution_price
                    ]);
                }
            }
        }

        return true;
    }

    public function storeExcel($request)
    {
        $dataExcel = json_decode($request?->dataExcel);
        if (count($dataExcel) > 0) {
            foreach ($dataExcel as $item) {
                $dep = $this->modelClass::where(['first_name' => $item?->first_name, 'user_id' => auth()->id(), 'phone' => $item?->phone, 'workplace' => $item?->workplace])->first();
                if (!$dep) {
                    $dep = $this->modelClass::create([
                        'first_name' => $item?->first_name,
                        'phone' => $item?->phone,
                        'workplace' => $item?->workplace,
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        }
        return [
            'data' => ReferringDoctorResource::collection($this->modelClass::where('user_id', auth()->id())->get())
        ];
    }
}
