<?php

namespace App\Services\Api\V3;

use App\Http\Resources\Client\BloodTestClint;
use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\Client\LaboratoryClientItemResource;
use App\Http\Resources\Client\LaboratoryClientResource;
use App\Http\Resources\Client\ReceptionClientResource;
use App\Http\Resources\doctor\RoomResource;
use App\Models\Client;
use App\Models\LaboratoryTemplateResultFiles;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\Department\MonitorResource;
use App\Models\LaboratoryTemplate;
use App\Models\Room;
use App\Models\ClientResult;
use App\Models\ClientValue;
use App\Models\Servicetypes;
use App\Models\DirectorSetting;
use App\Models\Branch;
use App\Models\ClientUseProduct;
use App\Models\ClinetPaymet;
use App\Models\Departments;
use App\Models\DoctorBalance;
use App\Models\ReferringDoctorServiceContribution;
use App\Models\Graph;
use App\Models\ClientBalance;
use App\Models\ClientCertificate;
use App\Models\ClientTime;
use App\Models\ClientTimeArchive;
use App\Models\DailyRepot;
use App\Models\DailyRepotClient;
use App\Models\GraphArchive;
use App\Models\GraphArchiveItem;
use App\Models\GraphItem;
use App\Models\LaboratoryTemplateResult;
use App\Models\ProductReceptionItem;
use App\Models\ReferringDoctor;
use App\Models\ReferringDoctorBalance;
use App\Models\ReferringDoctorChangeArchive;
use App\Models\ReferringDoctorPay;
use App\Models\ResultTemplate;
use App\Models\Services;
use App\Models\User;
use App\Services\Api\V3\Contracts\ClientServiceInterface;
use App\Traits\Crud;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ClientService implements ClientServiceInterface
{
    public $modelClass = Client::class;
    use Crud;


    public function totalPriceSplit($resultId, $totalPrice)
    {
        $result = ClientValue::where([
            'client_id' => $resultId,
            'is_active' => 1
        ])->get();
        $total = $totalPrice;
        foreach ($result as $value) {
            $total = $total - ($value->price * $value->qty);
            if ($total >=  0) {
                ClientValue::find($value->id)->update([
                    'pay_price' => ($value->price * $value->qty)
                ]);
            } else if ($total > 0) {
                ClientValue::find($value->id)->update([
                    'pay_price' =>  $total
                ]);
            }
        }
    }

    public function grapachiveData($personId, $status)
    {
        $data =  GraphArchive::where('use_status', $status)
            ->where('person_id',  $personId)
            ->with([
                'person',
                'graphArchiveItem' => function ($q) {
                    $q->with(['client.clientResult', 'graphItem.department']);
                },
                'treatment'
            ])
            ->get();

        return $data;
    }
    public function filter($request)
    {

        // if (auth()->user()->role == User::USER_ROLE_RECEPTION) {
        //     return $this->receptionFilter($request);
        // }
        $startDate = now();
        $endDate = now();
        $user =  auth()->user();
        if ($user->role == User::USER_ROLE_CASH_REGISTER) {
            return $this->cashFilter($request);
        }
        $serviceId = [];
        $per_page = $request->per_page ?? 50;
        // al show 
        $use_status = '';
        if (isset($request->status)) {
            $use_status = $request->status;
        }
        if ($user->role == User::USER_ROLE_DOCTOR) {
            $serviceId = Services::where('department_id', $user->department_id)->pluck('id');
        }
        if (isset($request->show_person_id) && $request->show_person_id) {



            return [
                'at_home' => $this->grapachiveData($request->show_person_id, 'at_home'),
                'treatment' => $this->grapachiveData($request->show_person_id, 'treatment'),
                'data' => new ClientResource($this->modelClass::where([
                    'person_id' => $request->show_person_id,
                ])->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->with(['clientItem' => function ($q) {
                        $q
                            ->with(['clientTime', 'clientValue' => function ($q) {
                                $q->with(['service' =>  function ($q) {
                                    $q->with('servicetype', 'department')
                                        ->with('serviceProduct.product');
                                }, 'owner']);
                            }, 'clientPayment' => function ($q) {
                                $q->with(['user', 'clientTimeArchive']);
                            }, 'clientResult'])
                            // ->orderBy('created_at', 'desc')
                        ;
                    }])
                    ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                        $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                    }])
                    ->orderBy('last_client_item_created_at', 'desc')
                    ->first())
            ];
        }
        if (isset($request->doctor_show_person_id) && $request->doctor_show_person_id) {
            $time = 0;
            if (isset($request->department_id)) {
                if ($request->department_id == 0) {
                    $serviceId = Services::where('user_id', auth()->user()->owner_id)->pluck('id');
                } else if ($request->department_id > 0) {
                    //    oji borni tasdiqlangalri
                    $check = ClientResult::where(['is_check_doctor' => Client::STATUS_FINISH, 'department_id' => $request->department_id])
                        ->whereIn('client_id', $this->modelClass::where('person_id', $request->doctor_show_person_id)
                            ->pluck('id'))
                        ->first();
                    if ($check) {
                        $serviceId = Services::where('department_id', $request->department_id)->pluck('id');
                    } else {
                        return  [
                            'message' => 'Bu bolimda malut mavjud emas',
                        ];
                    }
                }
            }

            //     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
            //         $q->with(['clientResult' => function ($q) use ($serviceId, $request) {
            //             if (isset($request->department_id) && $request->department_id > 0) {
            //                 $q
            //                     ->with('doctor')
            //                     ->where(['is_check_doctor' => Client::STATUS_FINISH, 'department_id' => $request->department_id]);
            //             } else {

            //                 $q
            //                     ->with('doctor')
            //                     ->where([
            //                         'is_check_doctor' => Client::STATUS_FINISH,
            //                         'department_id' => auth()->user()->department_id,
            //                         'doctor_id' => auth()->id()
            //                     ]);
            //             }
            //         }]);
            //     } else 
            // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
            //         $q->with('clientResult', function ($q) use ($serviceId, $request) {
            //             $q
            //                 ->with('doctor')
            //                 ->where(['doctor_id' => auth()->id()])
            //                 ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
            //             ;
            //         });

            //         // $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START]);
            //     } else
            // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
            //         $q
            //             ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
            //             ->with('clientResult', function ($q) {
            //                 $q->where('doctor_id', '!=', auth()->id());
            //             });
            //     } else {
            //         $q
            //             ->where(['user_id' => auth()->id(), 'is_pay' => 1]);
            //         // ozi royhatga olgan va pul tolamangan
            //     }
            $data = $this->modelClass::where([
                // 'user_id' => auth()->id(),
                'person_id' => $request->doctor_show_person_id,
            ])

                ->with([
                    'clientItem' => function ($q) use ($serviceId, $request) {
                        $q
                            ->whereHas('clientResult', function ($q) use ($serviceId, $request) {
                                if (isset($request->department_id) && $request->department_id > 0) {
                                    $q
                                        ->with('doctor')
                                        ->where('department_id', $request->department_id);
                                } else {

                                    $q
                                        ->with('doctor')
                                        ->where('department_id', auth()->user()->department_id);
                                }
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH]);
                                } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                                    $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START]);
                                } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                    $q->whereNull('is_check_doctor');
                                }
                            })
                            ->with([
                                'clientCertificate' => function ($q) use ($request) {
                                    if (isset($request->department_id) && $request->department_id > 0) {
                                        $q->where('department_id', $request->department_id);
                                    } else {
                                        $q->where('department_id', auth()->user()->department_id);
                                    }
                                    // $q->where('department_id', auth()->user()->department_id);
                                },
                                'clientValue' => function ($q) use ($serviceId) {
                                    $q
                                        // ->where('is_pay', 1)
                                        ->whereIn('service_id',   $serviceId)
                                        ->with('service',  function ($q) {
                                            $q
                                                ->with('serviceProduct.product')
                                                ->with('servicetype', 'department.departmentTemplateItem.template');
                                        });
                                },
                                'clientPayment.user',
                                'templateResult' => function ($q) use ($request) {

                                    $q->whereHas('clientResult', function ($q) use ($request) {
                                        if (isset($request->department_id) && $request->department_id > 0) {
                                            $q
                                                ->where('department_id', $request->department_id);
                                        } else {

                                            $q
                                                ->where('department_id', auth()->user()->department_id);
                                        }
                                    });

                                    $q

                                        ->with('doctorTemplate');
                                },
                                'doctor',
                                'clientResult' => function ($q) use ($request) {
                                    if (isset($request->department_id) && $request->department_id > 0) {
                                        $q
                                            ->with('doctor')
                                            ->where('department_id', $request->department_id);
                                    } else {

                                        $q
                                            ->with('doctor')
                                            ->where('department_id', auth()->user()->department_id);
                                    }
                                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                        $q->where(['is_check_doctor' => Client::STATUS_FINISH]);
                                    } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                                        $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START]);
                                    } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                        $q->whereNull('is_check_doctor');
                                    }
                                }
                            ])

                        ;
                    }
                ])
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->first();
            if (isset($request->target_id) && $request->target_id > 0 && is_int($request->target_id)) {
                $timeFind = $this->modelClass::with(['clientResult' => function ($q) {
                    $q->where(['department_id' => auth()->user()->department_id, 'doctor_id' => auth()->id()]);
                }])->find($request->target_id);
                $clientResultCheck = $timeFind?->clientResult
                    ?->first();
                Log::info('test', [$clientResultCheck]);
                Log::info('clientResultCheck', [$clientResultCheck]);
                if ($clientResultCheck?->is_check_doctor == 'start') {
                    $time = $this->getWorkedTimeInSeconds($clientResultCheck->start_time);
                }
            } else {
                $timeFind = $data->clientItem->last();
                $clientResultCheck = $timeFind?->clientResult
                    ?->first();
                Log::info('timeFind', [$timeFind]);

                if ($clientResultCheck?->is_check_doctor == 'start') {
                    $time = $this->getWorkedTimeInSeconds($clientResultCheck->start_time);
                }
            }
            // $allCount = ClientValue::whereIn('service_id', Services::where('user_id', auth()->user()->owner_id)
            // ->whereHas('client', function ($q) use ($request) {
            //     $q->where('person_id', $request->doctor_show_person_id);
            // })
            // ->pluck('id'))->pluck('client_id')->unique()->count();
            return [
                'time' => $time,
                // 'all_count' => $allCount,
                // 'department' => $department,
                'data' => new ClientResource($data)
            ];
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
        if (isset($request->client_id)) {
            return $this->modelClass::where('user_id', auth()->id())
                ->with([
                    'clientValue.service' => function ($q) {
                        $q
                            ->with('serviceProduct.product')
                            ->with([
                                'department',
                                'servicetype'
                            ]);
                    },
                ])
                ->find($request->client_id)
            ;
        }
        $startDateFormat = $startDate;
        $endDateFormat = $endDate;

        if (isset($request->full_name)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')

                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    // if ($user->role == User::USER_ROLE_DOCTOR) {
                    //     $q
                    //         ->whereHas('clientResult', function ($q) use ($user) {
                    //             $q
                    //                 ->where('department_id', $user->department_id)
                    //                 ->whereNull('is_check_doctor')
                    //             ;
                    //         })
                    //         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                    //             $q
                    //                 ->with('owner')
                    //                 ->whereIn('service_id',   $serviceId);
                    //         })


                    //     ;
                    // }
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
                            $q
                                // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
                                // ->orWhereHas('clientResult', function ($q) use ($user) {
                                //     $q
                                //         ->where('doctor_id', '!=', $user->id)
                                //         ->where('department_id', '=', $user->department_id);;
                                // })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                });
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                            $q
                                // ->whereDoesntHave('clientResultCheck')
                                ->whereHas('clientResult', function ($q) use ($user) {
                                    $q
                                        ->where('department_id', $user->department_id)
                                        ->whereNull('is_check_doctor')
                                        //   ->orwhere('department_id', '=', $user->department_id)
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        }
                    }
                    $q
                        ->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    // if ($user->role == User::USER_ROLE_DOCTOR) {
                    //     $q
                    //         ->whereHas('clientResult', function ($q) use ($user) {
                    //             $q
                    //                 ->where('department_id', $user->department_id)
                    //                 ->whereNull('is_check_doctor')
                    //             ;
                    //         })
                    //         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                    //             $q
                    //                 ->with('owner')
                    //                 ->whereIn('service_id',   $serviceId);
                    //         })


                    //     ;
                    // }
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else {
                            if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                $q
                                    ->whereHas('clientResult', function ($q) use ($user) {
                                        $q
                                            ->where('department_id', $user->department_id)
                                            ->whereNull('is_check_doctor')
                                        ;
                                    })
                                    ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                        $q
                                            ->with('owner')
                                            ->whereIn('service_id',   $serviceId);
                                    })


                                ;
                            }
                        }
                    }
                    $q

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q->with('serviceProduct.product')
                                            ->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                }
                            },
                            'clientPayment.user'
                        ])
                        ->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%")
                        ->orderBy('id', 'asc');
                }])
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }
        if (isset($request->phone)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where(DB::raw("phone"), 'LIKE', "%{$request->phone}%")
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
                            $q
                                // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
                                // ->orWhereHas('clientResult', function ($q) use ($user) {
                                //     $q
                                //         ->where('doctor_id', '!=', $user->id)
                                //         ->where('department_id', '=', $user->department_id);;
                                // })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                });
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                            $q
                                // ->whereDoesntHave('clientResultCheck')
                                ->whereHas('clientResult', function ($q) use ($user) {
                                    $q
                                        ->where('department_id', $user->department_id)
                                        ->whereNull('is_check_doctor')
                                        //   ->orwhere('department_id', '=', $user->department_id)
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        }
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else {
                            if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                $q
                                    ->whereHas('clientResult', function ($q) use ($user) {
                                        $q
                                            ->where('department_id', $user->department_id)
                                            ->whereNull('is_check_doctor')
                                        ;
                                    })
                                    ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                        $q
                                            ->with('owner')
                                            ->whereIn('service_id',   $serviceId);
                                    })


                                ;
                            }
                        }
                    }
                    $q

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                    // ->where('doctor_id', '!=', auth()->id())
                                    // ->where('department_id', '=', auth()->user()->department_id);
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q
                                            ->with('serviceProduct.product')
                                            ->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q
                                                ->with('serviceProduct.product')
                                                ->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)'));
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }

        if (isset($request->person_id)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where(DB::raw("person_id"), 'LIKE', "%{$request->person_id}%")
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
                            $q
                                // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
                                // ->orWhereHas('clientResult', function ($q) use ($user) {
                                //     $q
                                //         ->where('doctor_id', '!=', $user->id)
                                //         ->where('department_id', '=', $user->department_id);;
                                // })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                });
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                            $q
                                // ->whereDoesntHave('clientResultCheck')
                                ->whereHas('clientResult', function ($q) use ($user) {
                                    $q
                                        ->where('department_id', $user->department_id)
                                        ->whereNull('is_check_doctor')
                                        //   ->orwhere('department_id', '=', $user->department_id)
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        }
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else {
                            if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                $q
                                    ->whereHas('clientResult', function ($q) use ($user) {
                                        $q
                                            ->where('department_id', $user->department_id)
                                            ->whereNull('is_check_doctor')
                                        ;
                                    })
                                    ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                        $q
                                            ->with('owner')
                                            ->whereIn('service_id',   $serviceId);
                                    })


                                ;
                            }
                        }
                    }
                    $q

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q
                                            ->with('serviceProduct.product')
                                            ->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                    // ->where('is_pay', 1)
                                    // ->with('service',  function ($q) {
                                    //     $q->with('servicetype', 'department');
                                    // });
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }
        if (isset($request->data_birth)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where('data_birth', $request->data_birth)
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                    $q

                        // ->orderBy('created_at', 'asc')

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                //     $q
                                //         ->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => auth()->id()]);
                                // } else    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                //     $q
                                //         ->where('doctor_id', '!=', auth()->id())
                                //         ->where('department_id', '=', auth()->user()->department_id);;
                                // }else{

                                // }
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                    // ->where('doctor_id', '!=', auth()->id())
                                    // ->where('department_id', '=', auth()->user()->department_id);
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q
                                        ->with(['service' =>  function ($q) {
                                            $q
                                                ->with('serviceProduct.product')
                                                ->with('servicetype', 'department');
                                        }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('serviceProduct.product')
                                                ->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                    // ->where('is_pay', 1)
                                    // ->with('service',  function ($q) {
                                    //     $q->with('servicetype', 'department');
                                    // });
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }


        $data =  $this->modelClass::where(function ($q) use ($user, $use_status) {
            if ($user->role == User::USER_ROLE_RECEPTION) {
                if (isset($use_status) && $use_status != '' && strlen($use_status) > 0) {
                    if (($use_status == 'ambulatory')) {
                        $q->whereNotIn('use_status',  ['treatment', 'at_home'])
                            ->orWhereNull('use_status')
                            ->orWhere('use_status', '-');
                    } else {
                        $q->where('use_status', $use_status);
                    }
                }
                $q
                    ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
            }
        })
            ->whereNull('parent_id')
            ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                if (isset($request->use_status)) {
                    $q->where('use_status', $request->use_status);
                }
                if ($user->role == User::USER_ROLE_DOCTOR) {
                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                        $q
                            ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })

                        ;
                    } else
                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                        $q
                            ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                $q->where(['doctor_id' => $user->id])
                                    ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    } else
                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
                        $q
                            // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
                            // ->orWhereHas('clientResult', function ($q) use ($user) {
                            //     $q
                            //         ->where('doctor_id', '!=', $user->id)
                            //         ->where('department_id', '=', $user->department_id);;
                            // })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            });
                    } else
                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                }
                if (isset($request->department_id)  && $request->department_id > 0) {
                    $q
                        ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                            $q
                                ->where('department_id', $request->department_id);
                        });
                }
                if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                    $q
                        ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                } else {
                    $q
                        ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                }
                // $q->limit(1);
            })
            ->with([

                'clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                    if (isset($request->use_status)) {
                        $q->where('use_status', $request->use_status);
                    }

                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else {
                            if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                $q
                                    ->whereHas('clientResult', function ($q) use ($user) {
                                        $q
                                            ->where('department_id', $user->department_id)
                                            ->whereNull('is_check_doctor')
                                        ;
                                    })
                                    ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                        $q
                                            ->with('owner')
                                            ->whereIn('service_id',   $serviceId);
                                    })


                                ;
                            }
                        }
                    }
                    if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                        $q
                            ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                    } else {
                        $q
                            ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    }
                    // if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                    //     $q
                    //         ->whereDate('created_at', $endDateFormat->format('Y-m-d'))
                    //         ->with([
                    //             'referringDoctor',
                    //             'clientTime.department',
                    //             'clientResult' => function ($q) use ($request) {

                    //                 if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                    //                     $q
                    //                         ->where('department_id', auth()->user()->department_id)
                    //                         ->whereNull('is_check_doctor');
                    //                 } else {
                    //                     $q
                    //                         ->where('doctor_id', '=', auth()->id())
                    //                         ->where('department_id', '=', auth()->user()->department_id);
                    //                 }
                    //             },
                    //             'clientValue' => function ($q) use ($user, $serviceId) {
                    //                 if ($user->role == User::USER_ROLE_RECEPTION) {
                    //                     $q->with(['service' =>  function ($q) {
                    //                         $q->with('servicetype', 'department');
                    //                     }, 'owner']);
                    //                 } else {

                    //                     $q->whereIn('service_id',   $serviceId)
                    //                         ->with(['service' =>  function ($q) {
                    //                             $q->with('servicetype', 'department');
                    //                         }, 'owner'])

                    //                     ;
                    //                 }
                    //             },
                    //             'clientPayment.user'
                    //         ])

                    //     ;
                    // } else {
                    //     $q
                    //         ->whereBetween('created_at', [$startDateFormat, $endDateFormat])
                    //         ->with([
                    //             'referringDoctor',
                    //             'clientTime.department',
                    //             'clientResult' => function ($q) use ($request) {
                    //                 $q
                    //                     ->where('doctor_id', '=', auth()->id())
                    //                     ->where('department_id', '=', auth()->user()->department_id);
                    //             },
                    //             'clientValue' => function ($q) use ($user, $serviceId) {
                    //                 if ($user->role == User::USER_ROLE_RECEPTION) {
                    //                     $q->with(['service' => function ($q) {
                    //                         $q->with('servicetype', 'department');
                    //                     }, 'owner']);
                    //                 } else {
                    //                     $q->whereIn('service_id',   $serviceId)

                    //                         ->with('service',  function ($q) {
                    //                             $q->with('servicetype', 'department');
                    //                         });
                    //                 }
                    //             },
                    //             'clientPayment.user',


                    //         ])

                    //     ;
                    // }
                    $q
                        // ->whereDate('created_at', $endDateFormat->format('Y-m-d'))
                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                if (auth()->user()->role == User::USER_ROLE_DOCTOR) {
                                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                        $q
                                            ->where('department_id', auth()->user()->department_id)
                                            ->whereNull('is_check_doctor');
                                    } else {
                                        $q
                                            ->where('doctor_id', '=', auth()->id())
                                            ->where('department_id', '=', auth()->user()->department_id);
                                    }
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    if (isset($request->department_id)  && $request->department_id > 0) {
                                        $q->where('department_id', $request->department_id);
                                    }
                                    $q
                                        ->with('clientUseProduct.productReceptionItem')
                                        ->with('department')
                                        ->with(['service' =>  function ($q) {
                                            $q
                                                ->with('serviceProduct.product')
                                                ->with('servicetype', 'department');
                                        }, 'owner']);
                                } else {

                                    $q
                                        ->with('department')
                                        ->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                }
                            },
                            'clientPayment.user'
                        ])
                        ->orderBy('id', 'desc')
                        // ->limit(1)
                    ;
                    // $q
                    //     ->orderBy('id', 'asc');

                    // if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                    // $q->limit(1);
                    // }
                }
            ])
            // ->addSelect([
            //     'first_client_item_letter' => 1
            // ])
            // ->has('clientItem')
            //     ->orderByRaw('
            //     (SELECT department.letter 
            //     FROM client_values 
            //     JOIN departments AS department 
            //     ON department.id = client_values.department_id 
            //     WHERE client_values.client_id = clients.id
            //     LIMIT 1) ASC
            // ')
            // ->where(function ($q) use ($request) {
            //     if (isset($request->department_id)  && $request->department_id > 0) {
            //     } else {
            //         $q->withCount(['clientItem as last_client_item_queue_letter' => function ($q) {
            //             $q->select('queue_letter')
            //                 ->orderBy('created_at', 'desc') // Eng so'nggi yozuvni olish uchun tartiblash
            //                 ->limit(1);
            //         }])
            //             ->orderBy('last_client_item_queue_letter', 'asc');
            //     }
            // })

            ->withCount(['clientItem as last_client_item_queue_letter' => function ($q) {
                $q->select('queue_letter')
                    ->orderBy('created_at', 'desc') // Eng so'nggi yozuvni olish uchun tartiblash
                    ->limit(1);
            }])
            // ->addSelect([
            //     'last_client_item_queue_letter' => DB::table('client_items')
            //         ->select('queue_letter')
            //         ->whereColumn('client_items.model_id', 'model_names.id') // Aloqa o'rnatish
            //         ->orderBy('created_at', 'desc')
            //         ->limit(1)
            // ])
            // ->orderBy(DB::raw("SUBSTRING_INDEX(last_client_item_queue_letter, '-', 1)"), 'desc') // Alifbo bo‘yicha tartiblash
            // ->orderBy(DB::raw("CAST(SUBSTRING_INDEX(last_client_item_queue_letter, '-', -1) AS UNSIGNED)"), 'asc') // Raqam bo‘yicha tartiblash
            //          ->orderByRaw("SUBSTRING_INDEX(last_client_item_queue_letter, ' - ', 1) ASC") // Alifbo bo'yicha
            // ->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(last_client_item_queue_letter, ' - ', -1), ':', 1) AS UNSIGNED) ASC") // Raqam bo'yicha
            // ->orderBy('last_client_item_queue_letter', 'asc')

            // ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
            //     $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
            // }])

            // ->orderBy('last_client_item_created_at', 'desc')
            // ->get()
            // ->sortBy(function ($client) {
            //     // clientItems ichida tartiblangan ma'lumotni olish
            //     // $filteredItems = $client->clientItems
            //     //     ->filter(function ($item) {
            //     //         return $item->clientValue?->is_active === 1 
            //     //             && $item->clientValue->department?->is_queue_number === 1;
            //     //     })
            //     //     ->sortBy('created_at') // Yaratilgan vaqti bo'yicha tartiblash
            //     //     ->first(); // Birinchi mos yozuvni olish

            //     return $client?->clientItem
            //     ->filter(function ($item) {
            //         return $item->clientValue?->is_active === 1 
            //             && $item->clientValue->department?->is_queue_number === 1;
            //     })
            //     ->department?->letter;
            // })
            ->paginate($per_page);
        return [
            'data' => ClientResource::collection($data),
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'use_status' => $use_status
        ];
    }
    // kassa
    public function cashFilter($request)
    {


        $startDate = now();
        $endDate = now();
        $user =  auth()->user();
        $serviceId = [];
        $per_page = $request->per_page ?? 50;
        // al show 
        $use_status = '';
        if (isset($request->status)) {
            $use_status = $request->status;
        }
        if ($user->role == User::USER_ROLE_DOCTOR) {
            $serviceId = Services::where('department_id', $user->department_id)->pluck('id');
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
        if (isset($request->client_id)) {
            return $this->modelClass::where('user_id', auth()->id())
                ->with([
                    'clientValue.service' => function ($q) {
                        $q
                            ->with('serviceProduct.product')
                            ->with([
                                'department',
                                'servicetype'
                            ]);
                    },
                ])
                ->find($request->client_id)
            ;
        }
        $startDateFormat = $startDate;
        $endDateFormat = $endDate;

        if (isset($request->full_name)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    $q
                        ->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {

                    $q

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q->with('serviceProduct.product')
                                            ->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                }
                            },
                            'clientPayment.user'
                        ])
                        ->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%")
                        ->orderBy('id', 'asc');
                }])
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }
        if (isset($request->phone)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where(DB::raw("phone"), 'LIKE', "%{$request->phone}%")
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
                            $q
                                // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
                                // ->orWhereHas('clientResult', function ($q) use ($user) {
                                //     $q
                                //         ->where('doctor_id', '!=', $user->id)
                                //         ->where('department_id', '=', $user->department_id);;
                                // })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                });
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                            $q
                                // ->whereDoesntHave('clientResultCheck')
                                ->whereHas('clientResult', function ($q) use ($user) {
                                    $q
                                        ->where('department_id', $user->department_id)
                                        ->whereNull('is_check_doctor')
                                        //   ->orwhere('department_id', '=', $user->department_id)
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        }
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else {
                            if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                $q
                                    ->whereHas('clientResult', function ($q) use ($user) {
                                        $q
                                            ->where('department_id', $user->department_id)
                                            ->whereNull('is_check_doctor')
                                        ;
                                    })
                                    ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                        $q
                                            ->with('owner')
                                            ->whereIn('service_id',   $serviceId);
                                    })


                                ;
                            }
                        }
                    }
                    $q

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                    // ->where('doctor_id', '!=', auth()->id())
                                    // ->where('department_id', '=', auth()->user()->department_id);
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q
                                            ->with('serviceProduct.product')
                                            ->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q
                                                ->with('serviceProduct.product')
                                                ->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)'));
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }

        if (isset($request->person_id)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where(DB::raw("person_id"), 'LIKE', "%{$request->person_id}%")
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
                            $q
                                // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
                                // ->orWhereHas('clientResult', function ($q) use ($user) {
                                //     $q
                                //         ->where('doctor_id', '!=', $user->id)
                                //         ->where('department_id', '=', $user->department_id);;
                                // })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                });
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                            $q
                                // ->whereDoesntHave('clientResultCheck')
                                ->whereHas('clientResult', function ($q) use ($user) {
                                    $q
                                        ->where('department_id', $user->department_id)
                                        ->whereNull('is_check_doctor')
                                        //   ->orwhere('department_id', '=', $user->department_id)
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        }
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else {
                            if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                $q
                                    ->whereHas('clientResult', function ($q) use ($user) {
                                        $q
                                            ->where('department_id', $user->department_id)
                                            ->whereNull('is_check_doctor')
                                        ;
                                    })
                                    ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                        $q
                                            ->with('owner')
                                            ->whereIn('service_id',   $serviceId);
                                    })


                                ;
                            }
                        }
                    }
                    $q

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q
                                            ->with('serviceProduct.product')
                                            ->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                    // ->where('is_pay', 1)
                                    // ->with('service',  function ($q) {
                                    //     $q->with('servicetype', 'department');
                                    // });
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }
        if (isset($request->data_birth)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where('data_birth', $request->data_birth)
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                    $q

                        // ->orderBy('created_at', 'asc')

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                //     $q
                                //         ->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => auth()->id()]);
                                // } else    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                //     $q
                                //         ->where('doctor_id', '!=', auth()->id())
                                //         ->where('department_id', '=', auth()->user()->department_id);;
                                // }else{

                                // }
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                    // ->where('doctor_id', '!=', auth()->id())
                                    // ->where('department_id', '=', auth()->user()->department_id);
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q
                                        ->with(['service' =>  function ($q) {
                                            $q
                                                ->with('serviceProduct.product')
                                                ->with('servicetype', 'department');
                                        }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('serviceProduct.product')
                                                ->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                    // ->where('is_pay', 1)
                                    // ->with('service',  function ($q) {
                                    //     $q->with('servicetype', 'department');
                                    // });
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }
        $data =  $this->modelClass::where(function ($q) use ($user, $use_status) {
            if (isset($use_status) && $use_status != '' && strlen($use_status) > 0) {
                if (($use_status == 'ambulatory')) {
                    $q->whereNotIn('use_status',  ['treatment', 'at_home'])
                        ->orWhereNull('use_status')
                        ->orWhere('use_status', '-');
                } else {
                    $q->where('use_status', $use_status);
                }
                $q
                    ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
            }
        })
            ->whereNull('parent_id')
            ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                if (isset($request->use_status)) {
                    $q->where('use_status', $request->use_status);
                }
                if (isset($request->department_id)  && $request->department_id > 0) {
                    $q
                        ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                            $q
                                ->where('department_id', $request->department_id);
                        });
                }
                if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                    $q
                        ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                } else {
                    $q
                        ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                }
            })
            ->with([

                'clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                    if (isset($request->use_status)) {
                        $q->where('use_status', $request->use_status);
                    }
                    if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                        $q
                            ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                    } else {
                        $q
                            ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    }
                    $q
                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {},
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if (isset($request->department_id)  && $request->department_id > 0) {
                                    $q->where('department_id', $request->department_id);
                                }
                                $q
                                    ->with('clientUseProduct.productReceptionItem')
                                    ->with('department')
                                    ->with(['service' =>  function ($q) {
                                        $q
                                            ->with('serviceProduct.product')
                                            ->with('servicetype', 'department');
                                    }, 'owner']);
                            },
                            'clientPayment.user'
                        ])
                        ->orderBy('id', 'desc')
                    ;
                }
            ])
            ->withCount(['clientItem as last_client_item_queue_letter' => function ($q) {
                $q->select('queue_letter')
                    ->orderBy('created_at', 'desc') // Eng so'nggi yozuvni olish uchun tartiblash
                    ->limit(1);
            }])

            ->paginate($per_page);
        return [
            'data' => ClientResource::collection($data),
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'use_status' => $use_status
        ];
    }

    public function receptionFilter($request)
    {
        $startDate = now();
        $endDate = now();
        $user =  auth()->user();

        $serviceId = [];
        $per_page = $request->per_page ?? 50;
        // al show 
        $use_status = '';
        if (isset($request->status)) {
            $use_status = $request->status;
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

        $startDateFormat = $startDate;
        $endDateFormat = $endDate;

        if (isset($request->full_name)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
                            $q
                                // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
                                // ->orWhereHas('clientResult', function ($q) use ($user) {
                                //     $q
                                //         ->where('doctor_id', '!=', $user->id)
                                //         ->where('department_id', '=', $user->department_id);;
                                // })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                });
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                            $q
                                // ->whereDoesntHave('clientResultCheck')
                                ->whereHas('clientResult', function ($q) use ($user) {
                                    $q
                                        ->where('department_id', $user->department_id)
                                        ->whereNull('is_check_doctor')
                                        //   ->orwhere('department_id', '=', $user->department_id)
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        }
                    }
                    $q
                        ->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    // if ($user->role == User::USER_ROLE_DOCTOR) {
                    //     $q
                    //         ->whereHas('clientResult', function ($q) use ($user) {
                    //             $q
                    //                 ->where('department_id', $user->department_id)
                    //                 ->whereNull('is_check_doctor')
                    //             ;
                    //         })
                    //         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                    //             $q
                    //                 ->with('owner')
                    //                 ->whereIn('service_id',   $serviceId);
                    //         })


                    //     ;
                    // }
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else {
                            if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                $q
                                    ->whereHas('clientResult', function ($q) use ($user) {
                                        $q
                                            ->where('department_id', $user->department_id)
                                            ->whereNull('is_check_doctor')
                                        ;
                                    })
                                    ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                        $q
                                            ->with('owner')
                                            ->whereIn('service_id',   $serviceId);
                                    })


                                ;
                            }
                        }
                    }
                    $q

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q->with('serviceProduct.product')
                                            ->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                }
                            },
                            'clientPayment.user'
                        ])
                        ->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%")
                        ->orderBy('id', 'asc');
                }])
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }
        if (isset($request->phone)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where(DB::raw("phone"), 'LIKE', "%{$request->phone}%")
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
                            $q
                                // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
                                // ->orWhereHas('clientResult', function ($q) use ($user) {
                                //     $q
                                //         ->where('doctor_id', '!=', $user->id)
                                //         ->where('department_id', '=', $user->department_id);;
                                // })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                });
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                            $q
                                // ->whereDoesntHave('clientResultCheck')
                                ->whereHas('clientResult', function ($q) use ($user) {
                                    $q
                                        ->where('department_id', $user->department_id)
                                        ->whereNull('is_check_doctor')
                                        //   ->orwhere('department_id', '=', $user->department_id)
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        }
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else {
                            if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                $q
                                    ->whereHas('clientResult', function ($q) use ($user) {
                                        $q
                                            ->where('department_id', $user->department_id)
                                            ->whereNull('is_check_doctor')
                                        ;
                                    })
                                    ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                        $q
                                            ->with('owner')
                                            ->whereIn('service_id',   $serviceId);
                                    })


                                ;
                            }
                        }
                    }
                    $q

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                    // ->where('doctor_id', '!=', auth()->id())
                                    // ->where('department_id', '=', auth()->user()->department_id);
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q
                                            ->with('serviceProduct.product')
                                            ->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q
                                                ->with('serviceProduct.product')
                                                ->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)'));
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }

        if (isset($request->person_id)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where(DB::raw("person_id"), 'LIKE', "%{$request->person_id}%")
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
                            $q
                                // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
                                // ->orWhereHas('clientResult', function ($q) use ($user) {
                                //     $q
                                //         ->where('doctor_id', '!=', $user->id)
                                //         ->where('department_id', '=', $user->department_id);;
                                // })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                });
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                            $q
                                // ->whereDoesntHave('clientResultCheck')
                                ->whereHas('clientResult', function ($q) use ($user) {
                                    $q
                                        ->where('department_id', $user->department_id)
                                        ->whereNull('is_check_doctor')
                                        //   ->orwhere('department_id', '=', $user->department_id)
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        }
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else {
                            if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                $q
                                    ->whereHas('clientResult', function ($q) use ($user) {
                                        $q
                                            ->where('department_id', $user->department_id)
                                            ->whereNull('is_check_doctor')
                                        ;
                                    })
                                    ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                        $q
                                            ->with('owner')
                                            ->whereIn('service_id',   $serviceId);
                                    })


                                ;
                            }
                        }
                    }
                    $q

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q
                                            ->with('serviceProduct.product')
                                            ->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                    // ->where('is_pay', 1)
                                    // ->with('service',  function ($q) {
                                    //     $q->with('servicetype', 'department');
                                    // });
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }
        if (isset($request->data_birth)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where('data_birth', $request->data_birth)
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                    $q

                        // ->orderBy('created_at', 'asc')

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                //     $q
                                //         ->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => auth()->id()]);
                                // } else    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                //     $q
                                //         ->where('doctor_id', '!=', auth()->id())
                                //         ->where('department_id', '=', auth()->user()->department_id);;
                                // }else{

                                // }
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                    // ->where('doctor_id', '!=', auth()->id())
                                    // ->where('department_id', '=', auth()->user()->department_id);
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q
                                        ->with(['service' =>  function ($q) {
                                            $q
                                                ->with('serviceProduct.product')
                                                ->with('servicetype', 'department');
                                        }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('serviceProduct.product')
                                                ->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                    // ->where('is_pay', 1)
                                    // ->with('service',  function ($q) {
                                    //     $q->with('servicetype', 'department');
                                    // });
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->paginate($per_page);
            return [
                'data' => ClientResource::collection($data),
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }


        $data =  $this->modelClass::where(function ($q) use ($user, $use_status) {
            if (isset($use_status) && $use_status != '' && strlen($use_status) > 0) {
                if (($use_status == 'ambulatory')) {
                    $q->whereNotIn('use_status',  ['treatment', 'at_home'])
                        ->orWhereNull('use_status')
                        ->orWhere('use_status', '-');
                } else {
                    $q->where('use_status', $use_status);
                }
            }
            $q
                ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
        })
            ->whereNull('parent_id')
            ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                if (isset($request->use_status)) {
                    $q->where('use_status', $request->use_status);
                }
                if (isset($request->department_id)  && $request->department_id > 0) {
                    $q
                        ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                            $q
                                ->where('department_id', $request->department_id);
                        });
                }
                if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                    $q
                        ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                } else {
                    $q
                        ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                }
            })
            ->with([

                'clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                    if (isset($request->use_status)) {
                        $q->where('use_status', $request->use_status);
                    }
                    if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                        $q
                            ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                    } else {
                        $q
                            ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    }
                    $q
                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            // 'clientResult',
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if (isset($request->department_id)  && $request->department_id > 0) {
                                    $q->where('department_id', $request->department_id);
                                }
                                // $q
                                //     ->with('clientUseProduct.productReceptionItem')
                                //     ->with('department')
                                //     ->with(['service' =>  function ($q) {
                                //         $q
                                //             ->with('serviceProduct.product')
                                //             ->with('servicetype', 'department');
                                //     }, 'owner']);
                            },
                            'clientPayment.user'
                        ])
                        ->orderBy('id', 'desc')
                    ;
                }
            ])


            // ->withCount(['clientItem as last_client_item_queue_letter' => function ($q) {
            //     $q->select('queue_letter')
            //         ->orderBy('created_at', 'desc') // Eng so'nggi yozuvni olish uchun tartiblash
            //         ->limit(1);
            // }])

            // ->orderBy(DB::raw("SUBSTRING_INDEX(last_client_item_queue_letter, '-', 1)"), 'desc') // Alifbo bo‘yicha tartiblash
            // ->orderBy(DB::raw("CAST(SUBSTRING_INDEX(last_client_item_queue_letter, '-', -1) AS UNSIGNED)"), 'asc') // Raqam bo‘yicha tartiblash
            ->paginate($per_page);
        return [
            'data' => ReceptionClientResource::collection($data),
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'use_status' => $use_status
        ];
    }
    // xizmatlarini print qilish
    public function servicePrintChek($request)
    {
        $client = $this->modelClass::with(['clientValue' => function ($q) {
            $q
                ->where('is_active', 1)

                ->with(['service' => function ($q) {
                    $q
                        ->select('id', 'name', 'department_id')
                        ->with(['department' => function ($q) {
                            $q
                                ->select('id', 'name', 'is_chek_print', 'letter', 'floor', 'main_room');
                        }]);
                }])
                ->select('id', 'client_id', 'queue_number', 'department_id', 'service_id', 'discount', 'pay_price', 'price', 'qty', 'total_price')
            ;
        }, 'clientTime'])->where('id', $request->id)->first([
            'id',
            'pay_total_price',
            'person_id',
            'first_name',
            'data_birth',
            'total_price',
            'discount',
            'created_at',
        ]);
        return  $client;
    }
    // public function filter($request)
    // {
    //     $startDate = now();
    //     $endDate = now();
    //     $user =  auth()->user();
    //     $serviceId = [];
    //     $per_page = $request->per_page ?? 50;
    //     // al show 
    //     $use_status = '';
    //     if (isset($request->status)) {
    //         $use_status = $request->status;
    //     }
    //     if ($user->role == User::USER_ROLE_DOCTOR) {
    //         $serviceId = Services::where('department_id', $user->department_id)->pluck('id');
    //     }
    //     if (isset($request->show_person_id) && $request->show_person_id) {



    //         return [
    //             'at_home' => $this->grapachiveData($request->show_person_id, 'at_home'),
    //             'treatment' => $this->grapachiveData($request->show_person_id, 'treatment'),
    //             'data' => new ClientResource($this->modelClass::where([
    //                 'person_id' => $request->show_person_id,
    //             ])->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
    //                 ->with(['clientItem' => function ($q) {
    //                     $q
    //                         ->with(['clientTime', 'clientValue' => function ($q) {
    //                             $q->with(['service' =>  function ($q) {
    //                                 $q->with('servicetype', 'department');
    //                             }, 'owner']);
    //                         }, 'clientPayment' => function ($q) {
    //                             $q->with(['user', 'clientTimeArchive']);
    //                         }, 'clientResult'])
    //                         // ->orderBy('created_at', 'desc')
    //                     ;
    //                 }])
    //                 ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
    //                     $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
    //                 }])
    //                 ->orderBy('last_client_item_created_at', 'desc')
    //                 ->first())
    //         ];
    //     }
    //     if (isset($request->doctor_show_person_id) && $request->doctor_show_person_id) {
    //         $time = 0;
    //         if (isset($request->department_id)) {
    //             if ($request->department_id == 0) {
    //                 $serviceId = Services::where('user_id', auth()->user()->owner_id)->pluck('id');
    //             } else if ($request->department_id > 0) {
    //                 //    oji borni tasdiqlangalri
    //                 $check = ClientResult::where(['is_check_doctor' => Client::STATUS_FINISH, 'department_id' => $request->department_id])
    //                     ->whereIn('client_id', $this->modelClass::where('person_id', $request->doctor_show_person_id)
    //                         ->pluck('id'))
    //                     ->first();
    //                 if ($check) {
    //                     $serviceId = Services::where('department_id', $request->department_id)->pluck('id');
    //                 } else {
    //                     return  [
    //                         'message' => 'Bu bolimda malut mavjud emas',
    //                     ];
    //                 }
    //             }
    //         }

    //         //     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //         //         $q->with(['clientResult' => function ($q) use ($serviceId, $request) {
    //         //             if (isset($request->department_id) && $request->department_id > 0) {
    //         //                 $q
    //         //                     ->with('doctor')
    //         //                     ->where(['is_check_doctor' => Client::STATUS_FINISH, 'department_id' => $request->department_id]);
    //         //             } else {

    //         //                 $q
    //         //                     ->with('doctor')
    //         //                     ->where([
    //         //                         'is_check_doctor' => Client::STATUS_FINISH,
    //         //                         'department_id' => auth()->user()->department_id,
    //         //                         'doctor_id' => auth()->id()
    //         //                     ]);
    //         //             }
    //         //         }]);
    //         //     } else 
    //         // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //         //         $q->with('clientResult', function ($q) use ($serviceId, $request) {
    //         //             $q
    //         //                 ->with('doctor')
    //         //                 ->where(['doctor_id' => auth()->id()])
    //         //                 ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
    //         //             ;
    //         //         });

    //         //         // $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START]);
    //         //     } else
    //         // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //         //         $q
    //         //             ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
    //         //             ->with('clientResult', function ($q) {
    //         //                 $q->where('doctor_id', '!=', auth()->id());
    //         //             });
    //         //     } else {
    //         //         $q
    //         //             ->where(['user_id' => auth()->id(), 'is_pay' => 1]);
    //         //         // ozi royhatga olgan va pul tolamangan
    //         //     }
    //         $data = $this->modelClass::where([
    //             // 'user_id' => auth()->id(),
    //             'person_id' => $request->doctor_show_person_id,
    //         ])

    //             ->with([
    //                 'clientItem' => function ($q) use ($serviceId, $request) {
    //                     $q
    //                         ->whereHas('clientResult', function ($q) use ($serviceId, $request) {
    //                             if (isset($request->department_id) && $request->department_id > 0) {
    //                                 $q
    //                                     ->with('doctor')
    //                                     ->where('department_id', $request->department_id);
    //                             } else {

    //                                 $q
    //                                     ->with('doctor')
    //                                     ->where('department_id', auth()->user()->department_id);
    //                             }
    //                             if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                                 $q->where(['is_check_doctor' => Client::STATUS_FINISH]);
    //                             } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //                                 $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START]);
    //                             } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                                 $q->whereNull('is_check_doctor');
    //                             }
    //                         })
    //                         ->with(['clientValue' => function ($q) use ($serviceId) {
    //                             $q
    //                                 // ->where('is_pay', 1)
    //                                 ->whereIn('service_id',   $serviceId)
    //                                 ->with('service',  function ($q) {
    //                                     $q->with('servicetype', 'department.departmentTemplateItem.template');
    //                                 });
    //                         }, 'clientPayment.user', 'templateResult' => function ($q) {
    //                             $q->with('doctorTemplate');
    //                         }, 'doctor', 'clientResult' => function ($q) use ($request) {
    //                             if (isset($request->department_id) && $request->department_id > 0) {
    //                                 $q
    //                                     ->with('doctor')
    //                                     ->where('department_id', $request->department_id);
    //                             } else {

    //                                 $q
    //                                     ->with('doctor')
    //                                     ->where('department_id', auth()->user()->department_id);
    //                             }
    //                             if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                                 $q->where(['is_check_doctor' => Client::STATUS_FINISH]);
    //                             } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //                                 $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START]);
    //                             } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                                 $q->whereNull('is_check_doctor');
    //                             }
    //                         }])

    //                     ;
    //                 }
    //             ])
    //             ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
    //                 $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
    //             }])
    //             ->orderBy('last_client_item_created_at', 'desc')
    //             ->first();
    //         if (isset($request->target_id) && $request->target_id > 0 && is_int($request->target_id)) {
    //             $timeFind = $this->modelClass::with(['clientResult' => function ($q) {
    //                 $q->where(['department_id' => auth()->user()->department_id, 'doctor_id' => auth()->id()]);
    //             }])->find($request->target_id);
    //             $clientResultCheck = $timeFind?->clientResult
    //                 ?->first();
    //             Log::info('test', [$clientResultCheck]);
    //             Log::info('clientResultCheck', [$clientResultCheck]);
    //             if ($clientResultCheck?->is_check_doctor == 'start') {
    //                 $time = $this->getWorkedTimeInSeconds($clientResultCheck->start_time);
    //             }
    //         } else {
    //             $timeFind = $data->clientItem->last();
    //             $clientResultCheck = $timeFind?->clientResult
    //                 ?->first();
    //             Log::info('timeFind', [$timeFind]);

    //             if ($clientResultCheck?->is_check_doctor == 'start') {
    //                 $time = $this->getWorkedTimeInSeconds($clientResultCheck->start_time);
    //             }
    //         }

    //         return [
    //             'time' => $time,
    //             // 'department' => $department,
    //             'data' => new ClientResource($data)
    //         ];
    //     }
    //     if (isset($request->start_date)) {

    //         $parsedDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
    //         if ($parsedDate->format('Y-m-d') ===  $request->start_date) {
    //             $startDate = $parsedDate;
    //         }
    //     }
    //     if (isset($request->end_date)) {
    //         $parsedDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
    //         if ($parsedDate->format('Y-m-d') ===  $request->end_date) {
    //             $endDate = $parsedDate;
    //         }
    //     }
    //     if (isset($request->client_id)) {
    //         return $this->modelClass::where('user_id', auth()->id())
    //             ->with([
    //                 'clientValue.service' => function ($q) {
    //                     $q->with([
    //                         'department',
    //                         'servicetype'
    //                     ]);
    //                 },
    //             ])
    //             ->find($request->client_id)
    //         ;
    //     }
    //     $startDateFormat = $startDate;
    //     $endDateFormat = $endDate;

    //     if (isset($request->full_name)) {
    //         $data =  $this->modelClass::where(function ($q) use ($request) {
    //             $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
    //                 ->orwhere('user_id', auth()->id());
    //         })
    //             ->whereNull('parent_id')

    //             ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
    //                 // if ($user->role == User::USER_ROLE_DOCTOR) {
    //                 //     $q
    //                 //         ->whereHas('clientResult', function ($q) use ($user) {
    //                 //             $q
    //                 //                 ->where('department_id', $user->department_id)
    //                 //                 ->whereNull('is_check_doctor')
    //                 //             ;
    //                 //         })
    //                 //         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                 //             $q
    //                 //                 ->with('owner')
    //                 //                 ->whereIn('service_id',   $serviceId);
    //                 //         })


    //                 //     ;
    //                 // }
    //                 if ($user->role == User::USER_ROLE_DOCTOR) {
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })

    //                         ;
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['doctor_id' => $user->id])
    //                                     ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
    //                                 ;
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })


    //                         ;
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
    //                         $q
    //                             // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
    //                             // ->orWhereHas('clientResult', function ($q) use ($user) {
    //                             //     $q
    //                             //         ->where('doctor_id', '!=', $user->id)
    //                             //         ->where('department_id', '=', $user->department_id);;
    //                             // })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             });
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                         $q
    //                             // ->whereDoesntHave('clientResultCheck')
    //                             ->whereHas('clientResult', function ($q) use ($user) {
    //                                 $q
    //                                     ->where('department_id', $user->department_id)
    //                                     ->whereNull('is_check_doctor')
    //                                     //   ->orwhere('department_id', '=', $user->department_id)
    //                                 ;
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })


    //                         ;
    //                     }
    //                 }
    //                 $q
    //                     ->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$request->full_name}%");
    //             })
    //             ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
    //                 // if ($user->role == User::USER_ROLE_DOCTOR) {
    //                 //     $q
    //                 //         ->whereHas('clientResult', function ($q) use ($user) {
    //                 //             $q
    //                 //                 ->where('department_id', $user->department_id)
    //                 //                 ->whereNull('is_check_doctor')
    //                 //             ;
    //                 //         })
    //                 //         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                 //             $q
    //                 //                 ->with('owner')
    //                 //                 ->whereIn('service_id',   $serviceId);
    //                 //         })


    //                 //     ;
    //                 // }
    //                 if ($user->role == User::USER_ROLE_DOCTOR) {
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })

    //                         ;
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['doctor_id' => $user->id])
    //                                     ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
    //                                 ;
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })


    //                         ;
    //                     } else {
    //                         if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                             $q
    //                                 ->whereHas('clientResult', function ($q) use ($user) {
    //                                     $q
    //                                         ->where('department_id', $user->department_id)
    //                                         ->whereNull('is_check_doctor')
    //                                     ;
    //                                 })
    //                                 ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                     $q
    //                                         ->with('owner')
    //                                         ->whereIn('service_id',   $serviceId);
    //                                 })


    //                             ;
    //                         }
    //                     }
    //                 }
    //                 $q

    //                     ->with([
    //                         'referringDoctor',
    //                         'clientTime.department',
    //                         'clientResult' => function ($q) use ($request) {
    //                             if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

    //                                 $q
    //                                     ->where('department_id', auth()->user()->department_id)
    //                                     ->whereNull('is_check_doctor');
    //                             } else {
    //                                 $q
    //                                     ->where('doctor_id', '=', auth()->id())
    //                                     ->where('department_id', '=', auth()->user()->department_id);
    //                             }
    //                         },
    //                         'clientValue' => function ($q) use ($user, $serviceId) {
    //                             if ($user->role == User::USER_ROLE_RECEPTION) {
    //                                 $q->with(['service' =>  function ($q) {
    //                                     $q->with('servicetype', 'department');
    //                                 }, 'owner']);
    //                             } else {

    //                                 $q->whereIn('service_id',   $serviceId)
    //                                     ->with(['service' =>  function ($q) {
    //                                         $q->with('servicetype', 'department');
    //                                     }, 'owner'])

    //                                 ;
    //                             }
    //                         },
    //                         'clientPayment.user'
    //                     ])
    //                     ->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$request->full_name}%")
    //                     ->orderBy('id', 'asc');
    //             }])
    //             ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
    //                 $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
    //             }])
    //             ->orderBy('last_client_item_created_at', 'desc')
    //             ->paginate($per_page);
    //         return [
    //             'data' => ClientResource::collection($data),
    //             'total' => $data->total(),
    //             'per_page' => $data->perPage(),
    //             'current_page' => $data->currentPage(),
    //             'last_page' => $data->lastPage(),
    //             'start_date' => $startDate->format('Y-m-d'),
    //             'end_date' => $endDate->format('Y-m-d'),
    //             'use_status' => $use_status
    //         ];
    //     }
    //     if (isset($request->phone)) {
    //         $data =  $this->modelClass::where(function ($q) use ($request) {
    //             $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
    //                 ->orwhere('user_id', auth()->id());
    //         })
    //             ->whereNull('parent_id')
    //             ->where(DB::raw("phone"), 'LIKE', "%{$request->phone}%")
    //             ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
    //                 if ($user->role == User::USER_ROLE_DOCTOR) {
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })

    //                         ;
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['doctor_id' => $user->id])
    //                                     ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
    //                                 ;
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })


    //                         ;
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
    //                         $q
    //                             // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
    //                             // ->orWhereHas('clientResult', function ($q) use ($user) {
    //                             //     $q
    //                             //         ->where('doctor_id', '!=', $user->id)
    //                             //         ->where('department_id', '=', $user->department_id);;
    //                             // })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             });
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                         $q
    //                             // ->whereDoesntHave('clientResultCheck')
    //                             ->whereHas('clientResult', function ($q) use ($user) {
    //                                 $q
    //                                     ->where('department_id', $user->department_id)
    //                                     ->whereNull('is_check_doctor')
    //                                     //   ->orwhere('department_id', '=', $user->department_id)
    //                                 ;
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })


    //                         ;
    //                     }
    //                 }
    //             })
    //             ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
    //                 if ($user->role == User::USER_ROLE_DOCTOR) {
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })

    //                         ;
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['doctor_id' => $user->id])
    //                                     ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
    //                                 ;
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })


    //                         ;
    //                     } else {
    //                         if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                             $q
    //                                 ->whereHas('clientResult', function ($q) use ($user) {
    //                                     $q
    //                                         ->where('department_id', $user->department_id)
    //                                         ->whereNull('is_check_doctor')
    //                                     ;
    //                                 })
    //                                 ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                     $q
    //                                         ->with('owner')
    //                                         ->whereIn('service_id',   $serviceId);
    //                                 })


    //                             ;
    //                         }
    //                     }
    //                 }
    //                 $q

    //                     ->with([
    //                         'referringDoctor',
    //                         'clientTime.department',
    //                         'clientResult' => function ($q) use ($request) {
    //                             if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

    //                                 $q
    //                                     ->where('department_id', auth()->user()->department_id)
    //                                     ->whereNull('is_check_doctor');
    //                                 // ->where('doctor_id', '!=', auth()->id())
    //                                 // ->where('department_id', '=', auth()->user()->department_id);
    //                             } else {
    //                                 $q
    //                                     ->where('doctor_id', '=', auth()->id())
    //                                     ->where('department_id', '=', auth()->user()->department_id);
    //                             }
    //                         },
    //                         'clientValue' => function ($q) use ($user, $serviceId) {
    //                             if ($user->role == User::USER_ROLE_RECEPTION) {
    //                                 $q->with(['service' =>  function ($q) {
    //                                     $q->with('servicetype', 'department');
    //                                 }, 'owner']);
    //                             } else {

    //                                 $q->whereIn('service_id',   $serviceId)
    //                                     ->with(['service' =>  function ($q) {
    //                                         $q->with('servicetype', 'department');
    //                                     }, 'owner'])

    //                                 ;
    //                             }
    //                         },
    //                         'clientPayment.user'
    //                     ])

    //                     ->orderBy('id', 'asc');
    //             }])
    //             ->has('clientItem')
    //             ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
    //                 $q->select(DB::raw('MAX(created_at)'));
    //             }])
    //             ->orderBy('last_client_item_created_at', 'desc')
    //             ->paginate($per_page);
    //         return [
    //             'data' => ClientResource::collection($data),
    //             'total' => $data->total(),
    //             'per_page' => $data->perPage(),
    //             'current_page' => $data->currentPage(),
    //             'last_page' => $data->lastPage(),
    //             'start_date' => $startDate->format('Y-m-d'),
    //             'start_dates' => $startDate->format('Y-m-d'),
    //             'end_date' => $endDate->format('Y-m-d'),
    //             'use_status' => $use_status
    //         ];
    //     }

    //     if (isset($request->person_id)) {
    //         $data =  $this->modelClass::where(function ($q) use ($request) {
    //             $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
    //                 ->orwhere('user_id', auth()->id());
    //         })
    //             ->whereNull('parent_id')
    //             ->where(DB::raw("person_id"), 'LIKE', "%{$request->person_id}%")
    //             ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
    //                 if ($user->role == User::USER_ROLE_DOCTOR) {
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })

    //                         ;
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['doctor_id' => $user->id])
    //                                     ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
    //                                 ;
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })


    //                         ;
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
    //                         $q
    //                             // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
    //                             // ->orWhereHas('clientResult', function ($q) use ($user) {
    //                             //     $q
    //                             //         ->where('doctor_id', '!=', $user->id)
    //                             //         ->where('department_id', '=', $user->department_id);;
    //                             // })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             });
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                         $q
    //                             // ->whereDoesntHave('clientResultCheck')
    //                             ->whereHas('clientResult', function ($q) use ($user) {
    //                                 $q
    //                                     ->where('department_id', $user->department_id)
    //                                     ->whereNull('is_check_doctor')
    //                                     //   ->orwhere('department_id', '=', $user->department_id)
    //                                 ;
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })


    //                         ;
    //                     }
    //                 }
    //             })
    //             ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
    //                 if ($user->role == User::USER_ROLE_DOCTOR) {
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })

    //                         ;
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['doctor_id' => $user->id])
    //                                     ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
    //                                 ;
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })


    //                         ;
    //                     } else {
    //                         if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                             $q
    //                                 ->whereHas('clientResult', function ($q) use ($user) {
    //                                     $q
    //                                         ->where('department_id', $user->department_id)
    //                                         ->whereNull('is_check_doctor')
    //                                     ;
    //                                 })
    //                                 ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                     $q
    //                                         ->with('owner')
    //                                         ->whereIn('service_id',   $serviceId);
    //                                 })


    //                             ;
    //                         }
    //                     }
    //                 }
    //                 $q

    //                     ->with([
    //                         'referringDoctor',
    //                         'clientTime.department',
    //                         'clientResult' => function ($q) use ($request) {
    //                             if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                                 $q
    //                                     ->where('department_id', auth()->user()->department_id)
    //                                     ->whereNull('is_check_doctor');
    //                             } else {
    //                                 $q
    //                                     ->where('doctor_id', '=', auth()->id())
    //                                     ->where('department_id', '=', auth()->user()->department_id);
    //                             }
    //                         },
    //                         'clientValue' => function ($q) use ($user, $serviceId) {
    //                             if ($user->role == User::USER_ROLE_RECEPTION) {
    //                                 $q->with(['service' =>  function ($q) {
    //                                     $q->with('servicetype', 'department');
    //                                 }, 'owner']);
    //                             } else {

    //                                 $q->whereIn('service_id',   $serviceId)
    //                                     ->with(['service' =>  function ($q) {
    //                                         $q->with('servicetype', 'department');
    //                                     }, 'owner'])

    //                                 ;
    //                                 // ->where('is_pay', 1)
    //                                 // ->with('service',  function ($q) {
    //                                 //     $q->with('servicetype', 'department');
    //                                 // });
    //                             }
    //                         },
    //                         'clientPayment.user'
    //                     ])

    //                     ->orderBy('id', 'asc');
    //             }])
    //             ->has('clientItem')
    //             ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
    //                 $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
    //             }])
    //             ->orderBy('last_client_item_created_at', 'desc')
    //             ->paginate($per_page);
    //         return [
    //             'data' => ClientResource::collection($data),
    //             'total' => $data->total(),
    //             'per_page' => $data->perPage(),
    //             'current_page' => $data->currentPage(),
    //             'last_page' => $data->lastPage(),
    //             'start_date' => $startDate->format('Y-m-d'),
    //             'start_dates' => $startDate->format('Y-m-d'),
    //             'end_date' => $endDate->format('Y-m-d'),
    //             'use_status' => $use_status
    //         ];
    //     }
    //     if (isset($request->data_birth)) {
    //         $data =  $this->modelClass::where(function ($q) use ($request) {
    //             $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
    //                 ->orwhere('user_id', auth()->id());
    //         })
    //             ->whereNull('parent_id')
    //             ->where('data_birth', $request->data_birth)
    //             ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
    //                 if ($user->role == User::USER_ROLE_DOCTOR) {
    //                     $q
    //                         // ->whereDoesntHave('clientResultCheck')
    //                         ->whereHas('clientResult', function ($q) use ($user) {
    //                             $q
    //                                 ->where('department_id', $user->department_id)
    //                                 ->whereNull('is_check_doctor')
    //                                 //   ->orwhere('department_id', '=', $user->department_id)
    //                             ;
    //                         })
    //                         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                             $q
    //                                 ->with('owner')
    //                                 ->whereIn('service_id',   $serviceId);
    //                         })


    //                     ;
    //                 }
    //             })
    //             ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
    //                 if ($user->role == User::USER_ROLE_DOCTOR) {
    //                     $q
    //                         // ->whereDoesntHave('clientResultCheck')
    //                         ->whereHas('clientResult', function ($q) use ($user) {
    //                             $q
    //                                 ->where('department_id', $user->department_id)
    //                                 ->whereNull('is_check_doctor')
    //                                 //   ->orwhere('department_id', '=', $user->department_id)
    //                             ;
    //                         })
    //                         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                             $q
    //                                 ->with('owner')
    //                                 ->whereIn('service_id',   $serviceId);
    //                         })


    //                     ;
    //                 }
    //                 $q

    //                     // ->orderBy('created_at', 'asc')

    //                     ->with([
    //                         'referringDoctor',
    //                         'clientTime.department',
    //                         'clientResult' => function ($q) use ($request) {
    //                             // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                             //     $q
    //                             //         ->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => auth()->id()]);
    //                             // } else    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                             //     $q
    //                             //         ->where('doctor_id', '!=', auth()->id())
    //                             //         ->where('department_id', '=', auth()->user()->department_id);;
    //                             // }else{

    //                             // }
    //                             if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

    //                                 $q
    //                                     ->where('department_id', auth()->user()->department_id)
    //                                     ->whereNull('is_check_doctor');
    //                                 // ->where('doctor_id', '!=', auth()->id())
    //                                 // ->where('department_id', '=', auth()->user()->department_id);
    //                             } else {
    //                                 $q
    //                                     ->where('doctor_id', '=', auth()->id())
    //                                     ->where('department_id', '=', auth()->user()->department_id);
    //                             }
    //                         },
    //                         'clientValue' => function ($q) use ($user, $serviceId) {
    //                             if ($user->role == User::USER_ROLE_RECEPTION) {
    //                                 $q
    //                                     ->with(['service' =>  function ($q) {
    //                                         $q->with('servicetype', 'department');
    //                                     }, 'owner']);
    //                             } else {

    //                                 $q->whereIn('service_id',   $serviceId)
    //                                     ->with(['service' =>  function ($q) {
    //                                         $q->with('servicetype', 'department');
    //                                     }, 'owner'])

    //                                 ;
    //                                 // ->where('is_pay', 1)
    //                                 // ->with('service',  function ($q) {
    //                                 //     $q->with('servicetype', 'department');
    //                                 // });
    //                             }
    //                         },
    //                         'clientPayment.user'
    //                     ])

    //                     ->orderBy('id', 'asc');
    //             }])
    //             ->has('clientItem')
    //             ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
    //                 $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
    //             }])
    //             ->orderBy('last_client_item_created_at', 'desc')
    //             ->paginate($per_page);
    //         return [
    //             'data' => ClientResource::collection($data),
    //             'total' => $data->total(),
    //             'per_page' => $data->perPage(),
    //             'current_page' => $data->currentPage(),
    //             'last_page' => $data->lastPage(),
    //             'start_date' => $startDate->format('Y-m-d'),
    //             'start_dates' => $startDate->format('Y-m-d'),
    //             'end_date' => $endDate->format('Y-m-d'),
    //             'use_status' => $use_status
    //         ];
    //     }


    //     $data =  $this->modelClass::where(function ($q) use ($user, $use_status) {
    //         if ($user->role == User::USER_ROLE_RECEPTION) {
    //             if (isset($use_status) && $use_status != '' && strlen($use_status) > 0) {
    //                 if (($use_status == 'ambulatory')) {
    //                     $q->whereNotIn('use_status',  ['treatment', 'at_home'])
    //                         ->orWhereNull('use_status')
    //                         ->orWhere('use_status', '-');
    //                 } else {
    //                     $q->where('use_status', $use_status);
    //                 }
    //             }
    //             $q
    //                 ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
    //         }
    //     })
    //         ->whereNull('parent_id')
    //         ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
    //             if (isset($request->use_status)) {
    //                 $q->where('use_status', $request->use_status);
    //             }
    //             if ($user->role == User::USER_ROLE_DOCTOR) {
    //                 if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                     $q
    //                         ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                             $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
    //                         })
    //                         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                             $q
    //                                 ->with('owner')
    //                                 ->whereIn('service_id',   $serviceId);
    //                         })

    //                     ;
    //                 } else
    //                 if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //                     $q
    //                         ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                             $q->where(['doctor_id' => $user->id])
    //                                 ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
    //                             ;
    //                         })
    //                         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                             $q
    //                                 ->with('owner')
    //                                 ->whereIn('service_id',   $serviceId);
    //                         })


    //                     ;
    //                 } else
    //                 if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
    //                     $q
    //                         // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
    //                         // ->orWhereHas('clientResult', function ($q) use ($user) {
    //                         //     $q
    //                         //         ->where('doctor_id', '!=', $user->id)
    //                         //         ->where('department_id', '=', $user->department_id);;
    //                         // })
    //                         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                             $q
    //                                 ->with('owner')
    //                                 ->whereIn('service_id',   $serviceId);
    //                         });
    //                 } else
    //                 if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                     $q
    //                         // ->whereDoesntHave('clientResultCheck')
    //                         ->whereHas('clientResult', function ($q) use ($user) {
    //                             $q
    //                                 ->where('department_id', $user->department_id)
    //                                 ->whereNull('is_check_doctor')
    //                                 //   ->orwhere('department_id', '=', $user->department_id)
    //                             ;
    //                         })
    //                         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                             $q
    //                                 ->with('owner')
    //                                 ->whereIn('service_id',   $serviceId);
    //                         })


    //                     ;
    //                 }
    //             }
    //             if (isset($request->department_id)  && $request->department_id > 0) {
    //                 $q
    //                     ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                         $q
    //                             ->where('department_id', $request->department_id);
    //                     });
    //             }
    //             if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
    //                 $q
    //                     ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
    //             } else {
    //                 $q
    //                     ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
    //             }
    //             // $q->limit(1);
    //         })
    //         ->with([

    //             'clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
    //                 if (isset($request->use_status)) {
    //                     $q->where('use_status', $request->use_status);
    //                 }

    //                 if ($user->role == User::USER_ROLE_DOCTOR) {
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })

    //                         ;
    //                     } else
    //                     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
    //                         $q
    //                             ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
    //                                 $q->where(['doctor_id' => $user->id])
    //                                     ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
    //                                 ;
    //                             })
    //                             ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                 $q
    //                                     ->with('owner')
    //                                     ->whereIn('service_id',   $serviceId);
    //                             })


    //                         ;
    //                     } else {
    //                         if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                             $q
    //                                 ->whereHas('clientResult', function ($q) use ($user) {
    //                                     $q
    //                                         ->where('department_id', $user->department_id)
    //                                         ->whereNull('is_check_doctor')
    //                                     ;
    //                                 })
    //                                 ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
    //                                     $q
    //                                         ->with('owner')
    //                                         ->whereIn('service_id',   $serviceId);
    //                                 })


    //                             ;
    //                         }
    //                     }
    //                 }
    //                 if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
    //                     $q
    //                         ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
    //                 } else {
    //                     $q
    //                         ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
    //                 }
    //                 // if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
    //                 //     $q
    //                 //         ->whereDate('created_at', $endDateFormat->format('Y-m-d'))
    //                 //         ->with([
    //                 //             'referringDoctor',
    //                 //             'clientTime.department',
    //                 //             'clientResult' => function ($q) use ($request) {

    //                 //                 if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

    //                 //                     $q
    //                 //                         ->where('department_id', auth()->user()->department_id)
    //                 //                         ->whereNull('is_check_doctor');
    //                 //                 } else {
    //                 //                     $q
    //                 //                         ->where('doctor_id', '=', auth()->id())
    //                 //                         ->where('department_id', '=', auth()->user()->department_id);
    //                 //                 }
    //                 //             },
    //                 //             'clientValue' => function ($q) use ($user, $serviceId) {
    //                 //                 if ($user->role == User::USER_ROLE_RECEPTION) {
    //                 //                     $q->with(['service' =>  function ($q) {
    //                 //                         $q->with('servicetype', 'department');
    //                 //                     }, 'owner']);
    //                 //                 } else {

    //                 //                     $q->whereIn('service_id',   $serviceId)
    //                 //                         ->with(['service' =>  function ($q) {
    //                 //                             $q->with('servicetype', 'department');
    //                 //                         }, 'owner'])

    //                 //                     ;
    //                 //                 }
    //                 //             },
    //                 //             'clientPayment.user'
    //                 //         ])

    //                 //     ;
    //                 // } else {
    //                 //     $q
    //                 //         ->whereBetween('created_at', [$startDateFormat, $endDateFormat])
    //                 //         ->with([
    //                 //             'referringDoctor',
    //                 //             'clientTime.department',
    //                 //             'clientResult' => function ($q) use ($request) {
    //                 //                 $q
    //                 //                     ->where('doctor_id', '=', auth()->id())
    //                 //                     ->where('department_id', '=', auth()->user()->department_id);
    //                 //             },
    //                 //             'clientValue' => function ($q) use ($user, $serviceId) {
    //                 //                 if ($user->role == User::USER_ROLE_RECEPTION) {
    //                 //                     $q->with(['service' => function ($q) {
    //                 //                         $q->with('servicetype', 'department');
    //                 //                     }, 'owner']);
    //                 //                 } else {
    //                 //                     $q->whereIn('service_id',   $serviceId)

    //                 //                         ->with('service',  function ($q) {
    //                 //                             $q->with('servicetype', 'department');
    //                 //                         });
    //                 //                 }
    //                 //             },
    //                 //             'clientPayment.user',


    //                 //         ])

    //                 //     ;
    //                 // }
    //                 $q
    //                     // ->whereDate('created_at', $endDateFormat->format('Y-m-d'))
    //                     ->with([
    //                         'referringDoctor',
    //                         'clientTime.department',
    //                         'clientResult' => function ($q) use ($request) {
    //                             if (auth()->user()->role == User::USER_ROLE_DOCTOR) {
    //                                 if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
    //                                     $q
    //                                         ->where('department_id', auth()->user()->department_id)
    //                                         ->whereNull('is_check_doctor');
    //                                 } else {
    //                                     $q
    //                                         ->where('doctor_id', '=', auth()->id())
    //                                         ->where('department_id', '=', auth()->user()->department_id);
    //                                 }
    //                             }
    //                         },
    //                         'clientValue' => function ($q) use ($user, $serviceId) {
    //                             if ($user->role == User::USER_ROLE_RECEPTION) {
    //                                 if (isset($request->department_id)  && $request->department_id > 0) {
    //                                     $q->where('department_id', $request->department_id);
    //                                 }
    //                                 $q
    //                                     ->with('department')
    //                                     ->with(['service' =>  function ($q) {
    //                                         $q->with('servicetype', 'department');
    //                                     }, 'owner']);
    //                             } else {

    //                                 $q
    //                                     ->with('department')
    //                                     ->whereIn('service_id',   $serviceId)
    //                                     ->with(['service' =>  function ($q) {
    //                                         $q->with('servicetype', 'department');
    //                                     }, 'owner'])

    //                                 ;
    //                             }
    //                         },
    //                         'clientPayment.user'
    //                     ])
    //                     ->orderBy('id', 'desc')
    //                     // ->limit(1)
    //                 ;
    //                 // $q
    //                 //     ->orderBy('id', 'asc');

    //                 // if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
    //                 // $q->limit(1);
    //                 // }
    //             }
    //         ])
    //         // ->addSelect([
    //         //     'first_client_item_letter' => 1
    //         // ])
    //         // ->has('clientItem')
    //         //     ->orderByRaw('
    //         //     (SELECT department.letter 
    //         //     FROM client_values 
    //         //     JOIN departments AS department 
    //         //     ON department.id = client_values.department_id 
    //         //     WHERE client_values.client_id = clients.id
    //         //     LIMIT 1) ASC
    //         // ')
    //         // ->where(function ($q) use ($request) {
    //         //     if (isset($request->department_id)  && $request->department_id > 0) {
    //         //     } else {
    //         //         $q->withCount(['clientItem as last_client_item_queue_letter' => function ($q) {
    //         //             $q->select('queue_letter')
    //         //                 ->orderBy('created_at', 'desc') // Eng so'nggi yozuvni olish uchun tartiblash
    //         //                 ->limit(1);
    //         //         }])
    //         //             ->orderBy('last_client_item_queue_letter', 'asc');
    //         //     }
    //         // })

    //         ->withCount(['clientItem as last_client_item_queue_letter' => function ($q) {
    //             $q->select('queue_letter')
    //                 ->orderBy('created_at', 'desc') // Eng so'nggi yozuvni olish uchun tartiblash
    //                 ->limit(1);
    //         }])
    //         // ->addSelect([
    //         //     'last_client_item_queue_letter' => DB::table('client_items')
    //         //         ->select('queue_letter')
    //         //         ->whereColumn('client_items.model_id', 'model_names.id') // Aloqa o'rnatish
    //         //         ->orderBy('created_at', 'desc')
    //         //         ->limit(1)
    //         // ])
    //         ->orderBy(DB::raw("SUBSTRING_INDEX(last_client_item_queue_letter, '-', 1)"), 'desc') // Alifbo bo‘yicha tartiblash
    //         ->orderBy(DB::raw("CAST(SUBSTRING_INDEX(last_client_item_queue_letter, '-', -1) AS UNSIGNED)"), 'asc') // Raqam bo‘yicha tartiblash
    //         // ->orderBy('last_client_item_queue_letter', 'asc')

    //         // ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
    //         //     $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
    //         // }])

    //         // ->orderBy('last_client_item_created_at', 'desc')
    //         // ->get()
    //         // ->sortBy(function ($client) {
    //         //     // clientItems ichida tartiblangan ma'lumotni olish
    //         //     // $filteredItems = $client->clientItems
    //         //     //     ->filter(function ($item) {
    //         //     //         return $item->clientValue?->is_active === 1 
    //         //     //             && $item->clientValue->department?->is_queue_number === 1;
    //         //     //     })
    //         //     //     ->sortBy('created_at') // Yaratilgan vaqti bo'yicha tartiblash
    //         //     //     ->first(); // Birinchi mos yozuvni olish

    //         //     return $client?->clientItem
    //         //     ->filter(function ($item) {
    //         //         return $item->clientValue?->is_active === 1 
    //         //             && $item->clientValue->department?->is_queue_number === 1;
    //         //     })
    //         //     ->department?->letter;
    //         // })
    //         ->paginate($per_page);
    //     return [
    //         'data' => ClientResource::collection($data),
    //         'total' => $data->total(),
    //         'per_page' => $data->perPage(),
    //         'current_page' => $data->currentPage(),
    //         'last_page' => $data->lastPage(),
    //         'start_date' => $startDate->format('Y-m-d'),
    //         'end_date' => $endDate->format('Y-m-d'),
    //         'use_status' => $use_status
    //     ];
    // }

    public function oldfilter($request)
    {
        $startDate = now();
        $endDate = now();
        $user =  auth()->user();
        $serviceId = [];
        // al show 
        $use_status = '';
        if (isset($request->status)) {
            $use_status = $request->status;
        }
        if ($user->role == User::USER_ROLE_DOCTOR) {
            $serviceId = Services::where('department_id', $user->department_id)->pluck('id');
        }
        if (isset($request->show_person_id) && $request->show_person_id) {
            return new ClientResource($this->modelClass::where([
                // 'user_id' => auth()->id(),
                'person_id' => $request->show_person_id,
            ])->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                // ->whereNull('parent_id')
                // ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId) {
                //     if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                //         if ($user->role == User::USER_ROLE_RECEPTION) {

                //             $q
                //                 ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                //         } else {
                //             $q
                //                 ->whereHas('clientValue', function ($q) use ($user, $serviceId) {
                //                     $q->whereIn('service_id',   $serviceId);
                //                 })
                //                 ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                //         }
                //     } else {
                //         if ($user->role == User::USER_ROLE_RECEPTION) {

                //             $q
                //                 ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                //         } else {
                //             $q
                //                 ->whereHas('clientValue', function ($q) use ($user, $serviceId) {
                //                     $q->whereIn('service_id',   $serviceId);
                //                 })
                //                 ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                //         }
                //     }
                // })
                ->with(['clientItem' => function ($q) {
                    $q

                        ->with(['clientTime', 'clientValue' => function ($q) {
                            $q->with(['service' =>  function ($q) {
                                $q->with('servicetype', 'department');
                            }, 'owner']);
                        }, 'clientPayment' => function ($q) {
                            $q->with(['user', 'clientTimeArchive']);
                        }])
                        ->orderBy('created_at', 'desc')
                    ;
                }])
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->first());
        }
        if (isset($request->doctor_show_person_id) && $request->doctor_show_person_id) {
            $time = 0;
            if (isset($request->department_id)) {
                if ($request->department_id == 0) {
                    $serviceId = Services::where('user_id', auth()->user()->owner_id)->pluck('id');
                } else if ($request->department_id > 0) {
                    //    oji borni tasdiqlangalri
                    $check = ClientResult::where(['is_check_doctor' => Client::STATUS_FINISH, 'department_id' => $request->department_id])
                        ->whereIn('client_id', $this->modelClass::where('person_id', $request->doctor_show_person_id)
                            ->pluck('id'))
                        ->first();
                    if ($check) {
                        $serviceId = Services::where('department_id', $request->department_id)->pluck('id');
                    } else {
                        return  [
                            'message' => 'Bu bolimda malut mavjud emas',
                        ];
                    }
                }
            }

            //     if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
            //         $q->with(['clientResult' => function ($q) use ($serviceId, $request) {
            //             if (isset($request->department_id) && $request->department_id > 0) {
            //                 $q
            //                     ->with('doctor')
            //                     ->where(['is_check_doctor' => Client::STATUS_FINISH, 'department_id' => $request->department_id]);
            //             } else {

            //                 $q
            //                     ->with('doctor')
            //                     ->where([
            //                         'is_check_doctor' => Client::STATUS_FINISH,
            //                         'department_id' => auth()->user()->department_id,
            //                         'doctor_id' => auth()->id()
            //                     ]);
            //             }
            //         }]);
            //     } else 
            // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
            //         $q->with('clientResult', function ($q) use ($serviceId, $request) {
            //             $q
            //                 ->with('doctor')
            //                 ->where(['doctor_id' => auth()->id()])
            //                 ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
            //             ;
            //         });

            //         // $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START]);
            //     } else
            // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
            //         $q
            //             ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
            //             ->with('clientResult', function ($q) {
            //                 $q->where('doctor_id', '!=', auth()->id());
            //             });
            //     } else {
            //         $q
            //             ->where(['user_id' => auth()->id(), 'is_pay' => 1]);
            //         // ozi royhatga olgan va pul tolamangan
            //     }
            $data = $this->modelClass::where([
                // 'user_id' => auth()->id(),
                'person_id' => $request->doctor_show_person_id,
            ])

                ->with([
                    'clientItem' => function ($q) use ($serviceId, $request) {
                        $q
                            ->whereHas('clientResult', function ($q) use ($serviceId, $request) {
                                if (isset($request->department_id) && $request->department_id > 0) {
                                    $q
                                        ->with('doctor')
                                        ->where('department_id', $request->department_id);
                                } else {

                                    $q
                                        ->with('doctor')
                                        ->where('department_id', auth()->user()->department_id);
                                }
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH]);
                                } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                                    $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START]);
                                } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                    $q->whereNull('is_check_doctor');
                                }
                            })
                            ->with(['clientValue' => function ($q) use ($serviceId) {
                                $q
                                    // ->where('is_pay', 1)
                                    ->whereIn('service_id',   $serviceId)
                                    ->with('service',  function ($q) {
                                        $q->with('servicetype', 'department.departmentTemplateItem.template');
                                    });
                            }, 'clientPayment.user', 'templateResult' => function ($q) {
                                $q->with('doctorTemplate');
                            }, 'doctor', 'clientResult' => function ($q) use ($request) {
                                if (isset($request->department_id) && $request->department_id > 0) {
                                    $q
                                        ->with('doctor')
                                        ->where('department_id', $request->department_id);
                                } else {

                                    $q
                                        ->with('doctor')
                                        ->where('department_id', auth()->user()->department_id);
                                }
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH]);
                                } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                                    $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START]);
                                } else if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                    $q->whereNull('is_check_doctor');
                                }
                            }])

                        ;
                    }
                ])
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->first();
            if (isset($request->target_id) && $request->target_id > 0 && is_int($request->target_id)) {
                $timeFind = $this->modelClass::with(['clientResult' => function ($q) {
                    $q->where(['department_id' => auth()->user()->department_id, 'doctor_id' => auth()->id()]);
                }])->find($request->target_id);
                $clientResultCheck = $timeFind?->clientResult
                    ?->first();
                Log::info('test', [$clientResultCheck]);
                Log::info('clientResultCheck', [$clientResultCheck]);
                if ($clientResultCheck?->is_check_doctor == 'start') {
                    $time = $this->getWorkedTimeInSeconds($clientResultCheck->start_time);
                }
            } else {
                $timeFind = $data->clientItem->last();
                $clientResultCheck = $timeFind?->clientResult
                    ?->first();
                Log::info('timeFind', [$timeFind]);

                if ($clientResultCheck?->is_check_doctor == 'start') {
                    $time = $this->getWorkedTimeInSeconds($clientResultCheck->start_time);
                }
            }

            return [
                'time' => $time,
                // 'department' => $department,
                'data' => new ClientResource($data)
            ];
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
        if (isset($request->client_id)) {
            return $this->modelClass::where('user_id', auth()->id())
                ->with([
                    'clientValue.service' => function ($q) {
                        $q->with([
                            'department',
                            'servicetype'
                        ]);
                    },
                ])
                ->find($request->client_id)
            ;
        }
        $startDateFormat = $startDate;
        $endDateFormat = $endDate;

        if (isset($request->full_name)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')

                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                    $q
                        ->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                    $q

                        // ->orderBy('created_at', 'asc')

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                //     $q
                                //         ->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => auth()->id()]);
                                // } else    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                //     $q
                                //         ->where('doctor_id', '!=', auth()->id())
                                //         ->where('department_id', '=', auth()->user()->department_id);;
                                // }else{

                                // }
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                    // ->where('doctor_id', '!=', auth()->id())
                                    // ->where('department_id', '=', auth()->user()->department_id);
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                    // ->where('is_pay', 1)
                                    // ->with('service',  function ($q) {
                                    //     $q->with('servicetype', 'department');
                                    // });
                                }
                            },
                            'clientPayment.user'
                        ])
                        ->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%")
                        ->orderBy('id', 'asc');
                }])
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->get();
            return [
                'data' => ClientResource::collection($data),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }
        if (isset($request->phone)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where(DB::raw("phone"), 'LIKE', "%{$request->phone}%")
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                    $q

                        // ->orderBy('created_at', 'asc')

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                //     $q
                                //         ->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => auth()->id()]);
                                // } else    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                //     $q
                                //         ->where('doctor_id', '!=', auth()->id())
                                //         ->where('department_id', '=', auth()->user()->department_id);;
                                // }else{

                                // }
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                    // ->where('doctor_id', '!=', auth()->id())
                                    // ->where('department_id', '=', auth()->user()->department_id);
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                    // ->where('is_pay', 1)
                                    // ->with('service',  function ($q) {
                                    //     $q->with('servicetype', 'department');
                                    // });
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->get();
            return [
                'data' => ClientResource::collection($data),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }

        if (isset($request->person_id)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where(DB::raw("person_id"), 'LIKE', "%{$request->person_id}%")
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                    $q

                        // ->orderBy('created_at', 'asc')

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                //     $q
                                //         ->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => auth()->id()]);
                                // } else    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                //     $q
                                //         ->where('doctor_id', '!=', auth()->id())
                                //         ->where('department_id', '=', auth()->user()->department_id);;
                                // }else{

                                // }
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                    // ->where('doctor_id', '!=', auth()->id())
                                    // ->where('department_id', '=', auth()->user()->department_id);
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                    // ->where('is_pay', 1)
                                    // ->with('service',  function ($q) {
                                    //     $q->with('servicetype', 'department');
                                    // });
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->get();
            return [
                'data' => ClientResource::collection($data),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }
        if (isset($request->data_birth)) {
            $data =  $this->modelClass::where(function ($q) use ($request) {
                $q->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    ->orwhere('user_id', auth()->id());
            })
                ->whereNull('parent_id')
                ->where('data_birth', $request->data_birth)
                ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                })
                ->with(['clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $request, $user, $serviceId) {
                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                    $q

                        // ->orderBy('created_at', 'asc')

                        ->with([
                            'referringDoctor',
                            'clientTime.department',
                            'clientResult' => function ($q) use ($request) {
                                // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                //     $q
                                //         ->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => auth()->id()]);
                                // } else    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                //     $q
                                //         ->where('doctor_id', '!=', auth()->id())
                                //         ->where('department_id', '=', auth()->user()->department_id);;
                                // }else{

                                // }
                                if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                    $q
                                        ->where('department_id', auth()->user()->department_id)
                                        ->whereNull('is_check_doctor');
                                    // ->where('doctor_id', '!=', auth()->id())
                                    // ->where('department_id', '=', auth()->user()->department_id);
                                } else {
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                }
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                if ($user->role == User::USER_ROLE_RECEPTION) {
                                    $q->with(['service' =>  function ($q) {
                                        $q->with('servicetype', 'department');
                                    }, 'owner']);
                                } else {

                                    $q->whereIn('service_id',   $serviceId)
                                        ->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner'])

                                    ;
                                    // ->where('is_pay', 1)
                                    // ->with('service',  function ($q) {
                                    //     $q->with('servicetype', 'department');
                                    // });
                                }
                            },
                            'clientPayment.user'
                        ])

                        ->orderBy('id', 'asc');
                }])
                ->has('clientItem')
                ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                    $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
                }])
                ->orderBy('last_client_item_created_at', 'desc')
                ->get();
            return [
                'data' => ClientResource::collection($data),
                'start_date' => $startDate->format('Y-m-d'),
                'start_dates' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'use_status' => $use_status
            ];
        }


        $data =  $this->modelClass::where(function ($q) use ($user, $use_status) {
            if ($user->role == User::USER_ROLE_RECEPTION) {
                if (isset($use_status) && $use_status != '' && strlen($use_status) > 0) {
                    if (($use_status == 'ambulatory')) {
                        $q->whereNotIn('use_status',  ['treatment', 'at_home'])
                            ->orWhereNull('use_status')
                            ->orWhere('use_status', '-');
                    } else {
                        $q->where('use_status', $use_status);
                    }
                }
                $q
                    ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
                // ->where('user_id', auth()->id());
            }
            if ($user->role == User::USER_ROLE_CASH_REGISTER) {
                $q->where('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
            }



            // if ($user->role == User::USER_ROLE_DOCTOR) {
            //     $q->where('user_id', auth()->id());
            // }
        })
            ->whereNull('parent_id')
            // ->where(['use_status'=> $use_status])
            ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                if (isset($request->use_status)) {
                    $q->where('use_status', $request->use_status);
                }
                if ($user->role == User::USER_ROLE_DOCTOR) {
                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                        $q
                            ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })

                        ;
                    } else
                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                        $q
                            ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                $q->where(['doctor_id' => $user->id])
                                    ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    } else
                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_MIX) {
                        $q
                            // ->whereDoesntHave('clientResult') // условие, если `clientResult` не существует
                            // ->orWhereHas('clientResult', function ($q) use ($user) {
                            //     $q
                            //         ->where('doctor_id', '!=', $user->id)
                            //         ->where('department_id', '=', $user->department_id);;
                            // })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            });
                    } else
                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                        $q
                            // ->whereDoesntHave('clientResultCheck')
                            ->whereHas('clientResult', function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->whereNull('is_check_doctor')
                                    //   ->orwhere('department_id', '=', $user->department_id)
                                ;
                            })
                            ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                $q
                                    ->with('owner')
                                    ->whereIn('service_id',   $serviceId);
                            })


                        ;
                    }
                }
                if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                    $q
                        ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                    // if ($user->role == User::USER_ROLE_RECEPTION) {
                    // } else {

                    //     $q
                    //         ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                    //     // else

                    //     // // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                    //     // //     $q
                    //     // //         ->whereNull('is_check_doctor')
                    //     // //         ->whereHas('clientValue', function ($q) use ($user, $serviceId) {
                    //     // //             $q
                    //     // //                 ->whereIn('service_id',   $serviceId);
                    //     // //             // ->where(function ($query) {
                    //     // //             //     $query->where('user_id', auth()->id())
                    //     // //             //         ->orWhere('is_pay', 1);
                    //     // //             // });
                    //     // //         })
                    //     // //         ->whereDate('created_at', $endDateFormat->format('Y-m-d'));;
                    //     // // } 
                    //     // // else 
                    //     // {
                    //     //     $q

                    //     //         ->whereNull('is_check_doctor')
                    //     //         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {

                    //     //             //   doctor bolimda atkaz bolgani korinmaydi
                    //     //             if (isset($request->is_payment) && $request->is_payment == 1) {
                    //     //                 $q
                    //     //                     ->where('department_id', $user->department_id)
                    //     //                     ->where('is_active', 1);
                    //     //             }
                    //     //             $q

                    //     //                 ->whereIn('service_id',   $serviceId);
                    //     //             // ->where(function ($query) {
                    //     //             //     $query->where('user_id', auth()->id())
                    //     //             //         ->orWhere('is_pay', 1);
                    //     //             // });;
                    //     //         })
                    //     //         ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                    //     // }
                    // }
                } else {
                    $q
                        ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    // if ($user->role == User::USER_ROLE_RECEPTION) {
                    //     $q
                    //         ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    // } else {
                    //     $q->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    //     // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                    //     //     $q->where('is_check_doctor', Client::STATUS_FINISH)
                    //     //         ->whereHas('clientValue', function ($q) use ($user, $serviceId) {
                    //     //             $q->whereIn('service_id',   $serviceId);
                    //     //             // ->where(function ($query) {
                    //     //             //     $query->where('user_id', auth()->id())
                    //     //             //         ->orWhere('is_pay', 1);
                    //     //             // });
                    //     //         })
                    //     //         ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    //     // } else
                    //     // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                    //     //     $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                    //     //         ->whereHas('clientValue', function ($q) use ($user, $serviceId) {
                    //     //             $q->whereIn('service_id',   $serviceId);
                    //     //             // ->where(function ($query) {
                    //     //             //     $query->where('user_id', auth()->id())
                    //     //             //         ->orWhere('is_pay', 1);
                    //     //             // });
                    //     //         })
                    //     //         ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);;
                    //     // } else
                    //     // //  if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                    //     // //     $q
                    //     // //         ->WhereNull('is_check_doctor')
                    //     // //         ->whereHas('clientValue', function ($q) use ($user, $serviceId) {
                    //     // //             $q->whereIn('service_id',   $serviceId);
                    //     // //             // ->where(function ($query) {
                    //     // //             //     $query->where('user_id', auth()->id())
                    //     // //             //         ->orWhere('is_pay', 1);
                    //     // //             // });
                    //     // //         })
                    //     // //         ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    //     // // } else
                    //     // {
                    //     //     $q
                    //     //         ->whereNull('is_check_doctor')
                    //     //         ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                    //     //             if (isset($request->is_payment) && $request->is_payment == 1) {
                    //     //                 $q
                    //     //                     ->where('department_id', $user->department_id)
                    //     //                     ->where('is_active', 1);
                    //     //             }
                    //     //             $q->whereIn('service_id',   $serviceId);
                    //     //             // ->where(function ($query) {
                    //     //             //     $query->where('user_id', auth()->id())
                    //     //             //         ->orWhere('is_pay', 1);
                    //     //             // });
                    //     //         })
                    //     //         ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    //     // }
                    // }
                }
            })
            ->with([

                'clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                    if (isset($request->use_status)) {
                        $q->where('use_status', $request->use_status);
                    }

                    if ($user->role == User::USER_ROLE_DOCTOR) {
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => $user->id]);
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })

                            ;
                        } else
                        if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_ROOM) {
                            $q
                                ->whereHas('clientResult', function ($q) use ($user, $serviceId, $request) {
                                    $q->where(['doctor_id' => $user->id])
                                        ->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                                    ;
                                })
                                ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                    $q
                                        ->with('owner')
                                        ->whereIn('service_id',   $serviceId);
                                })


                            ;
                        } else {
                            if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                $q
                                    // ->whereDoesntHave('clientResultCheck')
                                    // ->whereDoesntHave('clientResult')
                                    ->whereHas('clientResult', function ($q) use ($user) {
                                        $q
                                            ->where('department_id', $user->department_id)
                                            ->whereNull('is_check_doctor')
                                            //   ->orwhere('department_id', '=', $user->department_id)
                                        ;
                                    })
                                    ->whereHas('clientValue', function ($q) use ($user, $serviceId, $request) {
                                        $q
                                            ->with('owner')
                                            ->whereIn('service_id',   $serviceId);
                                    })


                                ;
                            }
                        }
                    }
                    if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                        $q
                            ->whereDate('created_at', $endDateFormat->format('Y-m-d'))
                            // ->orderBy('created_at', 'asc')

                            ->with([
                                'referringDoctor',
                                'clientTime.department',
                                'clientResult' => function ($q) use ($request) {

                                    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {

                                        $q
                                            ->where('department_id', auth()->user()->department_id)
                                            ->whereNull('is_check_doctor');
                                    } else {
                                        $q
                                            ->where('doctor_id', '=', auth()->id())
                                            ->where('department_id', '=', auth()->user()->department_id);
                                    }
                                },
                                'clientValue' => function ($q) use ($user, $serviceId) {
                                    if ($user->role == User::USER_ROLE_RECEPTION) {
                                        $q->with(['service' =>  function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner']);
                                    } else {

                                        $q->whereIn('service_id',   $serviceId)
                                            ->with(['service' =>  function ($q) {
                                                $q->with('servicetype', 'department');
                                            }, 'owner'])

                                        ;
                                        // ->where('is_pay', 1)
                                        // ->with('service',  function ($q) {
                                        //     $q->with('servicetype', 'department');
                                        // });
                                    }
                                },
                                'clientPayment.user'
                            ])

                        ;
                    } else {
                        $q
                            ->whereBetween('created_at', [$startDateFormat, $endDateFormat])
                            // ->orderBy('created_at', 'desc')
                            ->with([
                                'referringDoctor',
                                'clientTime.department',
                                'clientResult' => function ($q) use ($request) {
                                    // if (isset($request->is_finish) && $request->is_finish == Client::STATUS_FINISH) {
                                    //     $q
                                    //         ->where(['is_check_doctor' => Client::STATUS_FINISH, 'doctor_id' => auth()->id()]);
                                    // } else    if (isset($request->is_finish) && $request->is_finish == Client::STATUS_IN_WAIT) {
                                    //     $q
                                    //         ->where('doctor_id', '!=', auth()->id())
                                    //         ->where('department_id', '=', auth()->user()->department_id);;
                                    // }
                                    $q
                                        ->where('doctor_id', '=', auth()->id())
                                        ->where('department_id', '=', auth()->user()->department_id);
                                },
                                'clientValue' => function ($q) use ($user, $serviceId) {
                                    if ($user->role == User::USER_ROLE_RECEPTION  || $user->role == User::USER_ROLE_CASH_REGISTER) {
                                        $q->with(['service' => function ($q) {
                                            $q->with('servicetype', 'department');
                                        }, 'owner']);
                                    } else {
                                        $q->whereIn('service_id',   $serviceId)
                                            // ->where(function ($query) {
                                            //     $query->where('user_id', auth()->id())
                                            //         ->orWhere('is_pay', 1);
                                            // })
                                            ->with('service',  function ($q) {
                                                $q->with('servicetype', 'department');
                                            });
                                    }
                                },
                                'clientPayment.user',


                            ])

                        ;
                    }
                    $q
                        ->orderBy('id', 'asc');
                }
            ])
            //       ->where(function ($q) use ($user, $use_status) {
            //         if ($user->role == User::USER_ROLE_RECEPTION) {
            //               if (isset($request->use_status)) {
            //             Log::info('adsakdjakd',[$use_status]);
            //             $q->where('use_status', $request->use_status);
            //         } else {
            //             $q
            //             ->whereNotIn('use_status',  ['treatment', 'at_home'])
            //             ->orWhereNull('use_status')
            //             ->orWhere('use_status','-');
            //         }
            //     }
            // })
            ->withCount(['clientItem as last_client_item_created_at' => function ($q) {
                $q->select(DB::raw('MAX(created_at)')); // Select the latest created_at of clientItem
            }])

            ->orderBy('last_client_item_created_at', 'desc')
            ->get();
        return [
            'data' => ClientResource::collection($data),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'use_status' => $use_status
        ];
    }
    public function autocomplate($request)
    {
        if ((isset($request->data_birth) && $request->data_birth != '') && isset($request->phone) && $request->phone != '') {
            return  $this->modelClass::where([
                'phone' => $request->phone,
                'data_birth' => $request->data_birth,
                'user_id' => auth()->id()
            ])->whereNull('parent_id')

                ->get(['id', 'first_name', 'last_name', 'phone', 'data_birth', 'person_id', 'sex', 'citizenship']);
        }
    }

    public function register($request)
    {

        $startDate = now();
        $endDate = now();
        $request = $request;
        $id = auth()->id();
        $new = false;
        $oldReferringDoctor = 0;
        if (isset($request->person_id) && isset($request->person_edit) && ($request->person_edit == '1' && $request->person_id > 0)) {
            $res = $this->modelClass::where(['person_id' => $request->person_id])
                ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));;
            $res->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'person_id' => $request->person_id,
                'data_birth' => $request->data_birth,
                'citizenship' => $request->citizenship,
                'sex' => $request->sex,
            ]);
            $result  =   $res->whereNull('parent_id')->first();
            return [
                'data' => new ClientResource($this->modelClass::where('user_id', auth()->id())
                    ->whereNull('parent_id')
                    ->with([
                        'clientItem' => function ($q) use ($result) {
                            $q
                                // ->where('id', $result->id)
                                // ->with(['clientValue.service' => function ($q) {
                                //     $q->with(['servicetype', 'department']);
                                // }, 'clientPayment.user']);
                                ->with(['clientResult', 'clientValue' => function ($q) {
                                    // .service
                                    // owner
                                    $q
                                        ->with('clientUseProduct.productReceptionItem')
                                        ->with(['service' => function ($q) {

                                            $q

                                                ->with(['servicetype', 'department'])
                                                ->with('serviceProduct.product');
                                        }, 'owner']);
                                }, 'clientPayment.user', 'clientTime.department']);
                        },

                    ])
                    ->find($result->id))
            ];
        }
        // tolov
        if ($request->status == 'payed' && $request->id > 0) {

            Log::info($request->all());
            return $this->distributePayment($request); /// test uchun sinab korish //chegirmani qosh keyin

            // $reqDdata = json_decode($request->client_value);

            // $result = $this->modelClass::find($request->id);
            // // $result = $this->update($request->id, $request);
            // $payData = [];
            // //  clientValue::where([
            // //     'client_id'=> $result->id,
            // //     'pay_price'=>0,

            // // ])
            // // ->with(['service.servicetype','service.department'])
            // // ->get();
            // $paytotalPrice = 0;
            // if (count($reqDdata) > 0) {
            //     foreach ($reqDdata as $value) {
            //         $paytotalPrice  =   $paytotalPrice  + ($value->pay_price == 0 ?   $value->price * ($value->qty ?? 0) : 0);
            //         $findReqPay =   ClientValue::with(['service.servicetype', 'service.department'])->find($value->id);
            //         if ($value->is_active && +$findReqPay->pay_price == 0) {
            //             $payData[] = $findReqPay;
            //         }
            //         Log::info('Applying user filter for user ID: ' . $findReqPay);
            //         $findReqPay->update([
            //             'is_active' => $value->is_active,
            //             'pay_price' => +$value->pay_price,
            //             // 'discount' => +$value->discount,
            //             'pay_price' => +$value->is_active ? $value->price * ($value->qty ?? 0) : 0,
            //             'price' => $value->price,
            //             'qty' => $value->qty
            //         ]);
            //     }
            //     $value = ClientValue::where([
            //         'client_id' => $result->id,
            //         'is_active' => 1,
            //     ])->get();
            //     ClinetPaymet::create([
            //         'client_value_id_data' => collect($payData)->pluck('id')->toJson(),
            //         'client_id' => $result->id,
            //         'discount' => ($request->discount_price ?? 0),
            //         'pay_total_price' => $request->pay_total_price,
            //         'total_price' => $paytotalPrice == 0 ? $result->debt_price : $paytotalPrice,
            //         'cash_price' => $request->pay_type == 'cash' ? $request->pay_total_price : 0,
            //         'card_price' => $request->pay_type == 'card' ? $request->pay_total_price : 0,
            //         'transfer_price' => $request->pay_type == 'transfer' ? $request->pay_total_price : 0,
            //         'debt_price' => $request->debt_price ?? 0,
            //         // 'pay_total_price' => $result->pay_total_price - $request->pay_total_price,
            //         'pay_type' => $request->pay_type ?? '-',
            //         'debt_comment' => $request->debt_comment ?? '-',
            //         'discount_comment' => $request->discount_comment ?? '-',
            //     ]);
            //     // if ($request->pay_total_price > 0) {

            //     // }
            //     // $this->totalPriceSplit($result->id, $request->pay_total_price);
            //     $ClinetPaymet = ClinetPaymet::where(['client_id' => $result->id])->get();
            //     $result->update(
            //         [
            //             'is_pay' => 1,
            //             'payment_deadline' => $request->payment_deadline,
            //             'discount_price' => $result->is_pay ? $result->discount_price  : ($request->discount_price ?? 0),
            //             'pay_total_price' => $ClinetPaymet->sum('pay_total_price'),
            //             'debt_price' => $request->debt_price,
            //             'service_count' => $value->count(),
            //             'total_price' => $value->sum(function ($item) {
            //                 return $item->qty * $item->price; // qty * price hisoblash
            //             }),
            //             'probirka_count' => $value->where('is_probirka', 1)->count(),
            //         ]
            //     );

            //     return [
            //         'check_print_data' => [
            //             'first_name' => $result->first_name,
            //             'last_name' => $result->last_name,
            //             'parent_name' => $result->parent_name,
            //             'data_birth' => $result->data_birth,
            //             'phone' => $result->phone,
            //             'sex' => $result->sex,
            //             'person_id' => $result->person_id,
            //             'client_value' => $payData,
            //             'created_at' => $result->created_at,
            //         ],
            //         'data' => new ClientResource($this->modelClass::where('user_id', auth()->id())
            //             ->whereNull('parent_id')
            //             ->with([
            //                 'clientItem' => function ($q) use ($result) {
            //                     $q
            //                         ->with(['clientValue.service' => function ($q) {
            //                             $q->with(['servicetype', 'department']);
            //                         }, 'clientPayment.user']);
            //                 },

            //             ])
            //             ->find($result->parent_id))
            //     ];
            // }
        }


        $find = false;
        if (isset($request->edit_parent_id) && $request->edit_parent_id > 0) {
            $find = $this->modelClass::where([
                "id" => $request->edit_parent_id,
                // 'user_id' => $id,
            ])
                ->whereNull('parent_id')
                ->first();
        }

        if (isset($request->autocomplate_id) && $request->autocomplate_id > 0) {
            $find = $this->modelClass::where([
                "id" => $request->autocomplate_id,
                // 'user_id' => $id,
            ])
                // ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                ->whereNull('parent_id')
                ->first();
        }
        // else {
        //     $find = $this->modelClass::where([
        //         "data_birth" => $request->data_birth,
        //         "phone" => $request->phone,
        //         'user_id' => $id,
        //     ])
        //         ->whereNull('parent_id')
        //         ->first();
        // }

        if (isset($find->id)) {
            $request['parent_id'] = $find->id;
            //  $find->update([
            //         'created_at' => now(),
            //     ]);
            $referring_doctor_id = 0;
            $request['user_id'] = $find->user_id;
            if (isset($request->id) && ($request->id > 0)) {
                $request['person_id'] = $find->person_id;
                $result = $this->modelClass::find($request->id);
                // $this->referringDoctorChange($find, $result, $request);
                if (isset($request->referring_doctor_id) && $request->referring_doctor_id > 0) {
                    // edit bolyapdi

                    if (($find->use_status == 'at_home' || $find->use_status == 'treatment')) {
                        // $find->referring_doctor_id != $request->referring_doctor_id
                        $grapachive = GraphArchive::where([
                            'person_id' => $find->person_id,
                        ])
                            ->where('status',  'live')
                            // ->whereHas('graphArchiveItem', function ($query) {
                            //     $query->where('client_id', '>', '0'); // 'client_id' 0 dan katta bo'lganlarni filtrlaydi
                            // })
                            ->with(['graphArchiveItem' => function ($query) {
                                $query->where('client_id', '>', '0'); // Munosabatdagi ma'lumotni ham o'zgartiradi
                            }])
                            ->first();
                        Log::info('shart1 referring_doctor_id 777', [$grapachive]);
                        if ($grapachive->referring_doctor_id != $request->referring_doctor_id) {
                            $this->referringDoctorChange($grapachive, $find, $result, $request);
                        }
                        $oldReferringDoctor = $this->modelClass::find($request->id)->referring_doctor_id ?? 0;
                        $result =  $this->update($request->id, $request);
                    } else {
                        $oldReferringDoctor = $this->modelClass::find($request->id)->referring_doctor_id ?? 0;
                        $result =  $this->update($request->id, $request);
                    }
                } else {
                    $oldReferringDoctor = $this->modelClass::find($request->id)->referring_doctor_id ?? 0;
                    $result =  $this->update($request->id, $request);
                }

                // $result =  $this->referringDoctorChange($find, $result, $request);
            } else {
                $request['person_id'] = $find->person_id;
                if ($find->use_status == 'treatment' || $find->use_status == 'at_home') {
                    $grapachive = GraphArchive::where([
                        'person_id' => $find->person_id,

                    ])
                        ->where('status',  'live')
                        ->first();
                    $request['referring_doctor_id'] = $grapachive->referring_doctor_id;
                    Log::info('shart2 referring_doctor_id', [$grapachive]);
                }
                Log::info('shart referring_doctor_id', [$find->use_status]);
                Log::info('shart referring_doctor_id', [$find->use_status]);
                $request['use_status'] = $find->use_status;
                $result = $this->store($request);
                $new = true;
                // if (isset($request->referring_doctor_id) && is_int($request->referring_doctor_id) && $request->referring_doctor_id > 0) {
                //     if (($find->use_status == 'at_home' || $find->use_status == 'treatment') && $find->referring_doctor_id != $request->referring_doctor_id) {
                //        $this->referringDoctorChange($find, $result, $request);
                //         $result =  $this->update($request->id, $request);
                //         $find->update([
                //             'referring_doctor_id' => $request->referring_doctor_id
                //         ]);
                //     }else{
                //         $result =  $this->update($request->id, $request);
                //         $find->update([
                //             'referring_doctor_id' => $request->referring_doctor_id
                //         ]);
                //     }
                // }
            }
        } else {
            $request['person_id'] = generateId();
            $request['user_id'] = auth()->id();
            $find = $this->store($request);
            $request['parent_id'] = $find->id;
            if ($find->use_status == 'treatment' || $find->use_status == 'at_home') {
                $grapachive = GraphArchive::where([
                    'person_id' => $find->person_id,
                ])
                    ->where('status',  'live')
                    ->first();
                $request['referring_doctor_id'] = $grapachive->referring_doctor_id;
            }
            $request['use_status'] = $find->use_status;
            $new = true;
            $result = $this->store($request);
            // $result =  $this->referringDoctorAdd($find, $result->id, $referring_doctor_id);
        }
        Log::info('find', [$find]);


        if (isset($request->client_value)) {
            $reqDdata = json_decode($request->client_value);
            if (count($reqDdata) > 0) {
                foreach ($reqDdata as $value) {
                    if (isset($value->id)) {
                        $ClientValueFInd = ClientValue::find($value->id);
                        if ($ClientValueFInd) {
                            // $ClientValueFInd->update([
                            //     'price' => $value->price ?? $ClientValueFInd->price,
                            //     'is_probirka' => isset($ClientValueFInd->is_probirka)  ? 1 : 0,
                            //     'service_id' => $value->service_id ?? $ClientValueFInd->service_id,
                            //     'department_id' => $value->department_id ?? $ClientValueFInd->department_id,
                            //     'client_id' => $result->id,
                            //     'total_price' => $ClientValueFInd->total_price,
                            //     'pay_price' => $ClientValueFInd->pay_price  ?? 0,
                            //     'qty' => $ClientValueFInd->qty,
                            //     'is_active' => $ClientValueFInd->is_active,
                            // ]);
                        } else {
                            $cv =  ClientValue::create([
                                'price' => $value->price ?? 0,
                                'user_id' => auth()->id(),
                                'is_probirka' => isset($value->is_probirka)  ? 1 : 0,
                                'service_id' => $value->service_id,
                                'total_price' => ($value->qty  ?? 1) * ($value->price ?? 0),
                                'department_id' => $value->department_id,
                                'client_id' => $result->id,
                                'qty' => isset($value->qty) ? $value->qty : 1,
                                'discount' => isset($value->discount) ? $value->discount : 0,
                                'is_active' => 1,
                                'is_at_home' => $request->is_atHome == 1 ? 0 : 1
                                // 'created_at' => now(),
                                // 'is_active' => isset($value->is_active) ? $value->is_active : 1,
                            ]);
                            $createdAt = Carbon::parse($value->created_at)->toDateTimeString();

                            // Ma'lumotlar bazasini yangilash
                            DB::table('client_values')
                                ->where('id', $cv->id) // Yangilanishi kerak bo'lgan qatorni aniqlash
                                ->update(['created_at' => $createdAt]);
                        }
                    } else {
                        ClientValue::create([
                            'user_id' => auth()->id(),
                            'price' => $value->price ?? 0,
                            'is_probirka' => isset($value->is_probirka)  ? 1 : 0,
                            'service_id' => $value->service_id,
                            'total_price' => ($value->qty  ?? 1) * ($value->price ?? 0),
                            'client_id' => $result->id,
                            'qty' => isset($value->qty) ? $value->qty : 1,
                            'discount' => isset($value->discount) ? $value->discount : 0,
                            // 'is_active' => isset($value->is_active) ? $value->is_active : 1,
                            'is_at_home' => $request->is_atHome == '1'  ? 0 : 1,
                            'is_active' => 1
                        ]);
                    }
                }
            }
        }
        if (
            ClientValue::where('client_id', $result->id)
            ->whereHas('department', function ($q) {
                $q->where('is_probirka', 1);
            })
            ->where('is_active', 1)->exists() && $result->probirka_id == null
        ) {
            $result->update([
                'probirka_id' => generateProbirkaId()
            ]);
        }

        if (isset($request->client_time)) {
            $reqDdata = json_decode($request->client_time);
            if (count($reqDdata) > 0) {
                foreach ($reqDdata as $value) {
                    $findTime = ClientTime::where([
                        'department_id' => $value->department_id,
                        'client_id' => $result->id
                    ])->first();
                    if ($findTime) {
                        $findTime->update([
                            'agreement_time' => $value->agreement_time ?? '-',
                        ]);
                    } else {
                        ClientTime::create([
                            'department_id' => $value->department_id,
                            'client_id' => $result->id,
                            'agreement_time' => $value->agreement_time ?? '-',
                        ]);
                    }
                }
            }
        }


        $value = ClientValue::where('client_id', $result->id)
            // ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
            ->get();
        $result->update(
            [
                'service_count' => $value->count(),
                'total_price' => $value->sum(function ($item) {
                    return $item->qty * $item->price; // qty * price hisoblash
                }),
                'probirka_count' => $value->where('is_probirka', 1)->count(),
            ]
        );


        if (isset($request->graph_item_id) && $request->graph_item_id > 0) {
            $GraphItemValue = GraphItem::find($request->graph_item_id);
            $graphachiveitem = GraphArchiveItem::where('graph_item_id', $request->graph_item_id)->first();
            if ($graphachiveitem) {
                $graphachive = GraphArchive::find($graphachiveitem->graph_archive_id);
                $graphachiveitem->update([
                    'client_id' => $result->id
                ]);
                // kelgan kunlar soni
                $graphachive->update([
                    'came_graph_archive_item_count' => GraphArchiveItem::where(['graph_archive_id' => $graphachiveitem->graph_archive_id])->where('client_id', '>', '0')->count()
                ]);
            }
            if ($GraphItemValue) {
                $graph = GraphItem::where('graph_id', $GraphItemValue->graph_id)->get();
                if ($graph->count() == 1) {
                    $graphachive = GraphArchive::where('graph_id', $GraphItemValue->graph_id)->first();
                    if ($graphachive) {
                        if ($graphachive->status == 'finish') {
                            Graph::find($GraphItemValue->graph_id)->delete();
                        }
                    }
                }
                $GraphItemValue->delete();
            }
            $created_at = $this->combineDateTime($request->agreement_date, $request->agreement_time);
            $result->update([
                'created_at' => Carbon::createFromFormat('Y-m-d H:i:s',  $created_at) // Yoki boshqa sana vaqti
            ]);
            DB::table('clients')
                ->where('id', $result->id)
                ->update([
                    'created_at' => Carbon::createFromFormat('Y-m-d H:i:s',  $created_at) // Yoki boshqa sana vaqti
                ]);
        }
        $generateClietResult = ClientValue::where('client_id', $result->id)
            ///   ->orWhereNull('is_at_home')
            ->where('is_at_home', 0)

            // ->whereNotIn('department_id', ClientResult::where('client_id', $result->id)->pluck('department_id'))
            ->pluck('department_id');
        Log::info('  $generateClietResult', [$generateClietResult]);
        if ($generateClietResult->count() > 0) {
            foreach ($generateClietResult as $value) {
                if (!ClientResult::where([
                    'client_id' => $result->id,
                    'department_id' => $value,
                ])
                    ->whereDate('created_at', now()->format('Y-m-d'))
                    ->exists()) {
                    ClientResult::create([
                        'client_id' => $result->id,
                        'department_id' => $value
                    ]);
                }
            }
        }
        $queue_number_data = [];
        if (isset($request->queue_number_data)) {
            $queue_number_data = json_decode($request->queue_number_data ?? '[]');
            foreach ($queue_number_data as $value) {
                ClientValue::where([
                    'client_id' => $result->id,
                    'department_id' => $value->department_id,

                ])
                    ->where('is_at_home', 0)
                    ///   ->orWhereNull('is_at_home')
                    ->whereDate('created_at', now()->format('Y-m-d'))
                    ->update([
                        'queue_number' =>  $value->queue_number
                    ]);
            }
        }
        // uyga olyaotkanda
        // if ($request->is_atHome == '1') {
        $clintValue = ClientValue::where('client_id', $result->id)
            ->where('queue_number', 0) //nega bu
            ->where('is_at_home', 0)
            ///   ->orWhereNull('is_at_home')
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->whereNotIn('department_id', collect($queue_number_data)->pluck('department_id'))
            ->select('department_id')
            ->distinct()
            ->get();
        Log::info('clintValue: ' . $clintValue);
        foreach ($clintValue as $value) {
            // ochirni aniqlab olamiz 
            $max =  ClientValue::where([
                'department_id' => $value->department_id,
            ])
                ->whereDate('created_at', now()->format('Y-m-d')) /// bu birinchi kelganda  yaxshi ishlayabndi 
                ->where('is_at_home', 0)

                ///   ->orWhereNull('is_at_home')

                // ->where('client_id', '!=', $result->id) /// buni hzoir ochirdim kuni 
                ->max('queue_number');

            $findMax = ClientValue::where([
                'department_id' => $value->department_id,
                'client_id' => $result->id, /// klient boyicha filter qilamiz
            ])
                ->where('queue_number', '>', 0)
                ->whereDate('created_at', now()->format('Y-m-d'))
                // yangi qoshilgan
                ->where('is_at_home', 0)
                ///   ->orWhereNull('is_at_home')
                ->first();
            ClientValue::where([
                'client_id' => $result->id,
                'department_id' => $value->department_id,
            ])
                ->where('is_at_home', 0)
                ///   ->orWhereNull('is_at_home')
                ->whereDate('created_at', now()->format('Y-m-d')) ///stationarda ozgarish boldi
                ->update([
                    'queue_number' =>  $findMax ?  $findMax->queue_number : ($max ?? 0) + 1
                ]);
        }
        // }

        if (isset($request->is_statsionar) && $request->is_statsionar == 1) {
            $room = Room::find($request->statsionar_room_id);
            $room->update([
                'is_empty' => 1
            ]);
            // if (is_null($result->statsionar_room_price)) {
            //     $result->update([
            //         'statsionar_room_price' => $room->price
            //     ]);
            // }
        }
        $this->clientDepartmentCount($result->id);
        if (isset($request->graph_achive_id) && $request->graph_achive_id > 0) {
            if (auth()->user()->is_cash_reg) {
                $request2 = $request;

                $request2['id'] = $result->id;
                $request2['client_value'] = json_encode([
                    // ...$old_client_value,
                    ...ClientValue::where('client_id', $result->id)
                        ->whereNotIn('id', collect(json_decode($request->old_client_value ?? '[]'))->pluck('id')->filter())
                        ->get()->map(function ($item) {
                            return [
                                ...$item->toArray(),
                                'total_price' => (float)($item->total_price ?? 0),
                                'price' => (float)($item->price ?? 0),
                                'pay_price' => (float)($item->pay_price ?? 0),
                                'discount' => (float)($item->discount ?? 0),
                            ];
                        })

                ]);
                $pay = $this->distributePayment($request2, $oldReferringDoctor);
                return [
                    'graph_achive' => GraphArchive::with(['person',    'graphArchiveItem' => function ($q) {
                        $q->with(['client', 'graphItem.department']);
                    }, 'treatment'])->find($request->graph_achive_id),
                    'data' => $pay['data'],
                    'check_print_data' => $pay['check_print_data']
                ];
            }
            return [
                'graph_achive' => GraphArchive::with(['person',    'graphArchiveItem' => function ($q) {
                    $q->with(['client', 'graphItem.department']);
                }, 'treatment'])->find($request->graph_achive_id),
                'data' => $this->modelClass::with(['clientTime.department', 'referringDoctor', 'clientValue' => function ($q) {
                    // .service
                    // owner
                    $q->with(['service' => function ($q) {

                        $q->with(['servicetype', 'department']);
                    }, 'owner']);
                }, 'clientPayment.user'])->find($result->id)
            ];
        }




        if (auth()->user()->is_cash_reg) {
            $request2 = $request;

            $request2['id'] = $result->id;
            $request2['client_value'] = json_encode([
                // ...$old_client_value,
                ...ClientValue::where('client_id', $result->id)
                    ->whereNotIn('id', collect(json_decode($request->old_client_value ?? '[]'))->pluck('id')->filter())
                    ->get()->map(function ($item) {
                        return [
                            ...$item->toArray(),
                            'total_price' => (float)($item->total_price ?? 0),
                            'price' => (float)($item->price ?? 0),
                            'pay_price' => (float)($item->pay_price ?? 0),
                            'discount' => (float)($item->discount ?? 0),
                        ];
                    })

            ]);
            return $this->distributePayment($request2, $oldReferringDoctor);
        }

        return [
            'cash_pay_id' => $result->id,
            'data' => new ClientResource($this->modelClass::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                // where('user_id', auth()->id())
                ->whereNull('parent_id')

                ->with([
                    'clientItem' => function ($q) use ($result) {
                        $q
                            // ->where('id', $result->id)
                            // ->with(['clientValue.service' => function ($q) {
                            //     $q->with(['servicetype', 'department']);
                            // }, 'clientPayment.user']);
                            ->with(['clientTime.department', 'referringDoctor', 'clientResult', 'clientValue' => function ($q) {
                                // .service
                                // owner
                                $q
                                    ->with('clientUseProduct.productReceptionItem')
                                    ->with(['service' => function ($q) {

                                        $q
                                            // ->with('clientUseProduct.productReceptionItem')
                                            ->with(['servicetype', 'department'])
                                            ->with('serviceProduct.product')

                                        ;
                                    }, 'owner']);
                            }, 'clientPayment.user', 'clientTime.department']);
                    },

                ])
                ->find($find->id))
        ];
    }
    function combineDateTime($agreement_date, $agreement_time = null)
    {
        // Agar agreement_time kiritilmasa, joriy soat va minutni olish
        if (!$agreement_time || $agreement_time == '-') {
            $time = Carbon::now()->format('H:i'); // Joriy soat va minut
        } else {
            $time = $agreement_time;
        }

        // Sana va vaqtni birlashtirib created_at formatiga keltirish
        $combinedDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $agreement_date . ' ' . $time . ':00');

        // Natijani return qilish
        return $combinedDateTime->toDateTimeString();
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);
        if (isset($request->Client_value)) {
            Client::where('parent_id', $result->id)->delete();
            $reqDdata = json_decode($request->Client_value);
            if (count($reqDdata) > 0) {
                $insertData = array_map(function ($value) use ($result) {
                    return [
                        'user_id' => auth()->id(),
                        'parent_id' => $result->id,
                        'room_number' => $value->room_number,
                        'room_type' => $value->room_type,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $reqDdata);
                Client::insert($insertData);
            }
        }
        return $this->modelClass::with('ClientValue.owner')
            ->where('user_id', auth()->id())
            ->find($result->id);
    }


    // dokotor natija kirtish
    function getWorkedTimeInSeconds($startTime)
    {
        // Boshlanish vaqtini Carbon formatiga o'girish
        $start = Carbon::createFromFormat('H:i:s', $startTime);
        // Hozirgi vaqtni olish
        $now = Carbon::now();

        // Farqni sekundlarda olish
        $workedSeconds = $now->diffInSeconds($start);

        return $workedSeconds;
    }
    function minutesToSeconds($minutes)
    {
        return $minutes * 60;
    }
    public function doctorResult($id, $request)
    {
        // ClientResult
        $result = $this->modelClass::find($id);
        $user = auth()->user();
        $clientResult = ClientResult::where(['client_id' => $id, 'doctor_id' => $user->id])->first();
        $departament = Departments::find($user->department_id);
        if (isset($request->is_check_doctor)) {
            if (((isset($request->room_id) && $request->room_id > 0) && $request->is_check_doctor == 'start') && (!isset($clientResult->room_id)  || !$clientResult)) {
                // DoctorBalance::where(['department_id' => $user->department_id, 'client_id' => $id])->update([
                //     'doctor_id' => auth()->id()
                // ]);
                $checkRoom =  Departments::where(['id' => $request->room_id])->first();
                if ($checkRoom->empty) {
                    return ['error' => 'Bunday room mavjud emas'];
                } else {
                    $clientResult = ClientResult::where(['client_id' => $id, 'department_id' => $user->department_id])
                        ->whereNull('doctor_id')
                        ->first();
                    if ($clientResult) {
                        $clientResult->update([
                            'client_id' => $result->id,
                            'doctor_id' => auth()->id(),
                            'room_id' => $request->room_id,
                            'start_time' => now()->format('H:i:s'),
                            'department_id' => $user->department_id,
                        ]);
                    } else {
                        $clientResult =   ClientResult::create([
                            'client_id' => $result->id,
                            'doctor_id' => auth()->id(),
                            'room_id' => $request->room_id,
                            'start_time' => now()->format('H:i:s'),
                            'department_id' => $user->department_id,
                        ]);
                    }
                    // $result->update([
                    //     'room_id' => $request->room_id,
                    // ]);
                    $checkRoom->update([
                        'empty' => 1,
                        'client_id' => $result->id
                    ]);
                }
            }
            if ($request->is_check_doctor == 'start' && ($clientResult->is_check_doctor == 'pause' || $clientResult->is_check_doctor != 'finish')) {

                $clientResult->update([
                    'is_check_doctor' => 'start',
                    'start_time' => now()->format('H:i:s'),
                    'use_duration' => $clientResult->use_duration + $this->getWorkedTimeInSeconds($clientResult->start_time),
                    'duration' => +$clientResult->duration > 0 ? $clientResult->duration : $this->minutesToSeconds($departament->duration),
                ]);
                if ($request->is_check_doctor == 'start') {
                    $data = [
                        'status' => 'edit_queue',
                        'result' =>  [
                            new MonitorResource(Departments::find($user->department_id))
                        ]
                    ];
                    // $this->soketSend($data);
                }
            } elseif ($request->is_check_doctor == 'pause' &&  ($clientResult->is_check_doctor == 'start' || $clientResult->is_check_doctor != 'finish')) {
                $clientResult->update([
                    'is_check_doctor' => 'pause',
                    'use_duration' => $clientResult->use_duration + $this->getWorkedTimeInSeconds($clientResult->start_time),
                    'duration' => +$clientResult->duration > 0 ? $clientResult->duration : $this->minutesToSeconds($departament->duration),

                ]);
            } elseif ($request->is_check_doctor == 'finish'  && $clientResult->is_check_doctor != 'finish' && ($clientResult->is_check_doctor == 'pause' || $clientResult->is_check_doctor == 'start')) {
                // if ($request->is_check_doctor == 'finish' && $request->is_check_doctor != 'finish') {
                //     $data = [
                //         'status' => 'edit_queue',
                //         'result' =>  new MonitorResource(Departments::with(['departmentValue'])->find($user->department_id))
                //     ];
                //     $this->soketSend($data);
                // }


                $clientResult->update([
                    'duration' => +$clientResult->duration > 0 ? $clientResult->duration : $this->minutesToSeconds($departament->duration),
                    'is_check_doctor' => 'finish',
                    'use_duration' => $this->getWorkedTimeInSeconds($clientResult->start_time) + $clientResult->use_duration,
                ]);
                $checkRoom =  Departments::where(['id' => $clientResult->room_id, 'client_id' => $result->id, 'empty' => 1])->first();
                $checkRoom->update([
                    'empty' => 0,
                    'client_id' => 0
                ]);
            }
        }
        if ($clientResult) {
            if (isset($request->template_result)) {
                $reqDdata = json_decode($request->template_result);
                // ResultTemplate::where(['client_id' => $id, 'client_result_id' => $clientResult->id,'status' => 'ckreditor'])
                // ->whereNotIn('doctor_template_id', collect($reqDdata)->pluck('doctor_template_id'))
                // ->delete();
                $ckreditorId = collect($reqDdata)
                    ->where('status', 'ckreditor')
                    ->filter(function ($value) {
                        return (isset($value->id) && is_int($value->id));
                    })
                    ->pluck('id')->unique();
                ResultTemplate::where(['client_id' => $id, 'client_result_id' => $clientResult->id, 'status' => 'ckreditor'])
                    ->whereNotIn('doctor_template_id', $ckreditorId)
                    ->delete();
                foreach ($reqDdata as $value) {
                    if ($value?->status == 'ckreditor') {
                        // $find = ResultTemplate::where([
                        //     'client_id' => $id,
                        //     'client_result_id' => $clientResult->id,
                        //     'doctor_template_id' => $value->doctor_template_id ?? 0,
                        //     'status' => $value->status,
                        // ])->first();
                        $find = ResultTemplate::find($value->id ?? 0);
                    } else {
                        $find = ResultTemplate::where([
                            'client_id' => $id,
                            'client_result_id' => $clientResult->id,
                            'status' => $value->status,
                            'template_item_id' => $value->template_item_id ?? 0,
                            'template_id' => $value->template_id ?? 0,
                        ])->first();
                    }
                    if ($find) {
                        $find->update([
                            'value' => $value->value,
                            'description' => $value->description ?? '-',
                        ]);
                    } else {

                        ResultTemplate::create([
                            'client_result_id' => $clientResult->id,
                            'client_id' => $id,
                            'status' => $value->status,
                            'template_item_id' => $value->template_item_id ?? 0,
                            'template_id' => $value->template_id ?? 0,
                            'value' => $value->value ?? '-',
                            'description' => $value->description ?? '-',
                            'doctor_template_id' => $value->doctor_template_id ?? 0,
                        ]);
                    }
                }
            }
            $photoKeys = collect(request()->keys())->filter(function ($key) {
                return strpos($key, 'photo_') === 0;
            })->all();
            foreach ($photoKeys as $value) {
                $parts = explode('_', $value);
                $template_id = $parts[1];
                if (request()->hasFile($value)) {
                    $file = request()->file($value);
                    // Faylni saqlash yoki qayta ishlash
                    $ext = $file->getClientOriginalExtension();
                    $fileName = time() . '.' . $ext;
                    $savePath = 'clients/' . $id;
                    // $files = glob($savePath . '/*'); // Katalog ichidagi barcha fayllarni oladi

                    // foreach ($files as $file) {
                    //     if (is_file($file)) {
                    //         unlink($file); // Faylni o'chirish
                    //     }
                    // }
                    $file->move($savePath . '/', $fileName);
                    $fullPath =  $savePath . "/" . $fileName;
                }
                $find = ResultTemplate::where([
                    'client_id' => $id,
                    'status' => 'photo',
                    'template_id' => $template_id ?? 0,

                ])->first();
                if ($find) {
                    $find->update([
                        'value' => $fullPath,
                        'client_result_id' => $clientResult->id
                    ]);
                } else {
                    ResultTemplate::create([
                        'client_id' => $id,
                        'status' => 'photo',
                        'template_id' => $template_id ?? 0,
                        'value' => $fullPath,
                        'client_result_id' => $clientResult->id
                    ]);
                }
            }

            // $result->update([
            //     $value => request($value),
            // ]);
            // return $photoKeys;
            $result->update([
                'doctor_id' => auth()->id(),
            ]);
        }
        $this->clientDepartmentCount($id);
        return [
            'data' => $this->modelClass::with([
                'clientValue' => function ($q) use ($user) {
                    $q
                        ->where('department_id', $user->department_id)
                        ->with(['service' =>  function ($q) {
                            $q->with('servicetype', 'department');
                        }, 'owner']);
                },
                'clientResult' => function ($q) use ($user) {
                    if (isset($user->is_check_doctor)) {
                        $q->where(['is_check_doctor' => $user->is_check_doctor]);
                    }

                    $q

                        ->where(['department_id' => $user->department_id,]);
                },
                'clientPayment.user',
                'templateResult' => function ($q) {
                    $q->whereHas('clientResult', function ($q) {
                        $q
                            ->where('department_id', auth()->user()->department_id);
                    });
                    $q->with('doctorTemplate');
                },
                'doctor'
            ])->find($id),
            'time' => 0
        ];
    }


    public function doctorRoom($request)
    {
        $user = auth()->user();
        // $departament = Departments::with(['client' => function ($q) use ($user) {
        //     $q->with(['clientValue' => function ($q) use ($user) {
        //         $q->where('department_id', $user->department_id);
        //     },]);
        // }])->find($user->department_id);
        if (isset($request->status) && $request->status == 'empty') {
            // $room =   Departments::where(['parent_id' => $user->department_id])
            //     ->orWhere(['id' => $user->department_id])
            //     ->where(['empty' => 0])
            //     // ->orwhereNull('empty')
            //     ->get();
            // $data = [...$room];
            // if (!$departament->empty) {
            //     $data = [$departament, ...$data];
            // }
            return  Departments::with('departmentValue')->find($user->department_id);
        }
        return  RoomResource::collection([
            // $departament,
            ...Departments::where('parent_id', $user->department_id)->with(['client' => function ($q) use ($user) {
                $q->with([
                    'clientResult' => function ($q) use ($user) {
                        $q->where('department_id', $user->department_id);
                    },
                    'clientValue' => function ($q) use ($user) {
                        $q->where('department_id', $user->department_id);
                    }
                ]);
            }])
                ->orWhere('id', $user->department_id)
                ->get()
        ]);
    }

    // xona sonini xioblash
    function calculateDaysFromStartDate($result)
    {
        // Start sanasini Carbon obyektiga aylantirish
        $startDate = $result->admission_date;
        $start = Carbon::parse($startDate);
        if ($result->is_finish_statsionar) {
            $end =  Carbon::parse($result->finish_statsionar);
        }
        $end =  Carbon::now();
        // Hozirgi sanani olish

        $today = Carbon::now();

        // Kunlar farqini hisoblash
        $daysDifference = $start->diffInDays($end);

        return $daysDifference;
    }
    function daysBetweenDates($date1, $date2)
    {
        $start = Carbon::parse($date1);
        $end = Carbon::parse($date2);
        return $start->diffInDays($end);
    }
    // tolovlarni taqsimlash
    public function distributePayment($request, $oldReferringDoctor = false)
    {


        $result = $this->modelClass::find($request->id);
        $reqDdata = json_decode($request->client_value ?? "[]");
        $old_client_value = [];
        if (isset($request->old_client_value)) {
            $reqDdata = [...$reqDdata, ...collect(json_decode($request->old_client_value))->toArray()];
            Log::info('old_client_value', [json_decode($request->old_client_value ?? '[]')]);
        }
        $dailyRepot = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'start'])
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->first();
        // shu yerni tekshir 

        if (!$dailyRepot) {
            $batch_number  = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'finish'])->whereDate('created_at', now()->format('Y-m-d'))->max('batch_number');
            $dailyRepot =  DailyRepot::create([
                'user_id' => auth()->id(),
                'status' => 'start',
                'batch_number' => $batch_number + 1
            ]);
        }

        $payTotal = (float)($request->pay_total_price > 0 ? $request->pay_total_price : 0);
        $backpayTotal = abs($request->pay_total_price < 0 ? $request->pay_total_price : 0);
        $payPrice = 0;
        $toladi = 0;
        $qaytarildi = 0;
        $chegirma = 0;
        $discount = 0;
        $roomqty = 0;
        $backRoomqty = 0;
        if (isset($request->is_statsionar) && $request->is_statsionar == 1) {
            $roomqty =  $this->calculateDaysFromStartDate($result);
            if (isset($request->day_qty) && $request->day_qty > 0) {
                $roomqty = $request->day_qty;
            }
            $result->update([
                'statsionar_room_qty' => $roomqty
            ]);
        }
        $totalExtraBackPrice = 0;
        $backPrice = 0; ///qaytarilganda yozib oladi
        $payId = []; // tolanganlarni yozib oladi
        $backId = []; // tolanganlarni yozib oladi
        if (count($reqDdata) > 0) {
            Log::info('count($reqDdata)', [collect($reqDdata)->sortBy('is_active')]);
            foreach ([...collect($reqDdata)->sortBy('is_active')] as $value) {
                $findReqPay =   ClientValue::find($value->id); /// qidiradi
                // pulni qabul qilish
                $active = +$value->is_active > 0 ? true : false;

                if ((($findReqPay->is_active &&  $active && !$findReqPay->is_pay) || ((!$findReqPay->is_active &&  $active && !$findReqPay->is_pay)))  && ($findReqPay->price == 0)) {
                    $findReqPay->update([
                        'is_active' =>  $active,
                        'pay_price' =>  0,
                        'is_pay' =>  $active,
                    ]);
                    $payId[] = $findReqPay->id;
                } else
                if ((($findReqPay->is_active &&  $active) || (!$findReqPay->is_active &&  $active) || $totalExtraBackPrice > 0) && ($findReqPay->total_price != $findReqPay->pay_price)) {
                    Log::info('$totalExtraBackPrice', [$totalExtraBackPrice]);
                    // if ($totalExtraBackPrice > 0) {
                    //     $calcPay = $findReqPay->total_price;
                    // } else {
                    // }

                    $calcPay = +$findReqPay->total_price - $this->discountCalc($value);
                    if ($totalExtraBackPrice > 0) {
                        $paySum =      $totalExtraBackPrice - ($calcPay - +$findReqPay->pay_price);
                    } else {
                        $paySum =      $payTotal - (+$calcPay - +$findReqPay->pay_price);
                    }
                    $sum = 0;
                    if ($paySum >= 0) {
                        $sum = $calcPay;
                        if ($totalExtraBackPrice > 0) {
                            $totalExtraBackPrice = $totalExtraBackPrice - ($calcPay - +$findReqPay->pay_price);
                        } else {
                            $payTotal =  $payTotal - ($calcPay - +$findReqPay->pay_price);
                        }
                    } else {
                        if ($totalExtraBackPrice > 0) {
                            $sum = +$findReqPay->pay_price + +$totalExtraBackPrice;
                            $totalExtraBackPrice = 0;
                        } else {
                            $sum = +$findReqPay->pay_price + $payTotal;
                            $payTotal = 0;
                        }
                    }
                    $toladi = +$findReqPay->pay_price > 0 ? abs((($toladi + $sum) - +$findReqPay->pay_price)) : abs($toladi + $sum);
                    $payId[] = $findReqPay->id;
                    $payPrice  =  $payPrice + $sum;
                    $findReqPay->update([
                        'is_active' =>  $active,
                        'pay_price' => $sum,
                        'is_pay' => 1,
                        'discount' => $value->discount,

                    ]);
                } else
                    // qaytarish razvrad
                    if ($findReqPay->is_active && !$active   && +$findReqPay->pay_price > 0) {
                        $qaytarildi = $qaytarildi + $findReqPay->pay_price;
                        if (+$findReqPay->pay_price - $backpayTotal > 0) {
                            $totalExtraBackPrice =  $totalExtraBackPrice + +$findReqPay->pay_price - $backpayTotal;
                        }
                        $findReqPay->update([
                            'is_active' =>  $active,
                            'pay_price' => 0,
                            'is_pay' => 0,
                            // 'discount' => 0,

                        ]);
                        $backId[] = $findReqPay->id;
                    } else      if ($findReqPay->is_active && ! $active   && $findReqPay->pay_price == 0) {
                        $findReqPay->update([
                            'is_active' =>  $active,
                            'pay_price' =>  0,
                            'is_pay' =>  $active,
                            'discount' => $value->discount,

                        ]);

                        $backId[] = $findReqPay->id;
                    }
            }

            // Log::info('payId', $payId);
            // Log::info('backprice', [$backPrice]);
            // Log::info('backId', $backId);
            $backRole = false;
            $clientTimeAchive  = [];
            $resultClinetPaymet = [];

            $payOrBackPrice =  $qaytarildi > 0 ?  $toladi + $totalExtraBackPrice - $qaytarildi : ($toladi + $totalExtraBackPrice);
            $tolandiPul = $toladi + $totalExtraBackPrice;
            $use_balanse = $request->use_balanse ?? 0;


            if ((count($payId) > 0 || count($backId) > 0) && $payOrBackPrice != 0) {
                if ($tolandiPul > $use_balanse) {
                    $tolandiPul = $tolandiPul - $use_balanse;
                } else {
                    $tolandiPul = 0;
                }
                Log::info('count($payId)', [count($payId), count($backId)]);
                if (count($payId) == 0) {
                    $payId = $backId;
                    $backRole = true;
                }
                $depId = ClientValue::whereIn('id', $payId)->pluck('department_id')->unique();
                if ($depId->count() > 0) {
                    // $this->soketSend([
                    //     'status' => 'edit_queue',
                    //     'result' =>  MonitorResource::collection(Departments::whereIn('id', $depId)->get())
                    // ]);
                }

                $resultClinetPaymet = ClinetPaymet::create([
                    'client_id' => $result->id,
                    'balance' => $use_balanse,
                    'user_id' => auth()->id(),
                    'client_value_id_data' => json_encode($payId),
                    'discount' => ClientValue::whereIn('id', $payId)
                        ->select(DB::raw("
                                SUM(
                                    CASE 
                                        WHEN discount <= 100 
                                        THEN (total_price / 100) * discount
                                        ELSE discount 
                                    END
                                ) as total
                            "))
                        ->value('total'),
                    // 'pay_total_price' =>   ($qaytarildi > 0 ?  $toladi + $totalExtraBackPrice - $qaytarildi :  $tolandiPul),
                    'pay_total_price' =>  $payOrBackPrice,
                    'back_total_price' => $qaytarildi > 0 ?  $toladi + $totalExtraBackPrice - $qaytarildi : 0,
                    'debt_price' =>  ClientValue::where(['client_id' => $result->id, 'is_active' => 1])->select(DB::raw("
                    SUM(
                        CASE 
                            WHEN discount <= 100 
                            THEN (total_price - (total_price / 100 ) * discount) - pay_price
                            ELSE (total_price - discount) - pay_price
                        END
                    ) as total
                "))
                        ->value('total') - ($qaytarildi > 0 ?  $toladi + $totalExtraBackPrice : 0),
                    'cash_price' => $request->pay_type == 'cash' ? ($qaytarildi > 0 ?  $toladi + $totalExtraBackPrice - $qaytarildi :  $tolandiPul) : 0,
                    'card_price' => $request->pay_type == 'card' ? ($qaytarildi > 0 ?  $toladi + $totalExtraBackPrice - $qaytarildi :  $tolandiPul) : 0,
                    'transfer_price' => $request->pay_type == 'transfer' ? ($qaytarildi > 0 ?  $toladi + $totalExtraBackPrice - $qaytarildi :  $tolandiPul) : 0,
                    // 'total_price' =>  $backRole ? (ClientValue::whereIn('id', $payId)->sum('total_price') + ClientValue::whereNotIn('id', $payId)->where('is_pay', 1)->sum('total_price')) : ClientValue::whereIn('id', $payId)->sum('total_price'),
                    'total_price' =>  $backRole ? 0 : ClientValue::whereIn('id', $payId)->sum('total_price'),
                    'debt_comment' => $request->debt_comment ?? '-',
                    'payment_deadline' => $request->payment_deadline ?? '-',
                    'discount_comment' => $request->discount_comment ?? '-',
                ]);

                $clientTimeAchive = ClientTime::where(['client_id' => $result->id])->whereIn('department_id',  ClientValue::whereIn('id', $payId)->pluck('department_id'))->get();
                $clientTimeArchiveData = [];
                $d_id = [];
                foreach ($clientTimeAchive as $value) {
                    $clientTimeArchiveData[] = ClientTimeArchive::create([
                        'client_id' => $value->client_id,
                        'clinet_paymet_id' => $resultClinetPaymet->id,
                        'agreement_time' => $value->agreement_time,
                        'department_id' => $value->department_id,
                    ]);
                    $d_id[] = $value->department_id;
                }

                // $clintvalue = ClientValue::where(['client_id' => $result->id, 'is_active' => 1])->pluck('department_id');
                // $clientTimeAchive = ClientTime::where(['client_id' => $result->id])->whereIn('department_id',  ClientValue::whereIn('id', $payId)->pluck('department_id'))->get();


                ClientTime::where(['client_id' => $result->id])->whereIn('department_id', $d_id)->update([
                    'is_active' => ClientValue::whereIn('department_id', $d_id)->where('client_id', $result->id)->where('is_active', 1)->exists() ? 1 : 0
                ]);
            }

            $value = ClientValue::where([
                'client_id' => $result->id,
                'is_active' => 1,
            ])->get();
            $debtWithBalanse = $result->total_price - $result->discount - $result->pay_total_price;
            Log::info('$result', [$result]);
            $ClinetPaymet = ClinetPaymet::where(['client_id' => $result->id])->get();
            $queue_letter = ClientValue::where(['client_id' => $result->id, 'is_active' => 1])
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->whereHas('department', function ($query) use ($request) {
                    $query->where('is_queue_number', 1);
                })->with('department')
                ->orderBy('id', 'asc') // Kamyosh tartibda
                ->first();
            log::info('$queue_letter', [$queue_letter]);

            $queue_letter_result = null;
            if ($queue_letter) {
                $queue_letter_time = $queue_letter->queue_number;
                if ($queue_letter->department->is_reg_time) {
                    $time = ClientTime::where(['client_id' => $result->id, 'department_id' => $queue_letter->department->id])->first();
                    $queue_letter_time =    $time->agreement_time;
                }
                $queue_letter_result = $queue_letter->department->letter . " - " .  $queue_letter_time;
            }
            $result->update(
                [

                    'queue_letter' => $queue_letter_result,
                    'is_pay' => 1,
                    'payment_deadline' => $request->payment_deadline,
                    'discount' => ClientValue::where(['client_id' => $result->id, 'is_active' => 1])
                        ->select(DB::raw("
                        SUM(
                            CASE 
                                WHEN discount <= 100 
                                THEN (total_price / 100) * discount
                                ELSE discount 
                            END
                        ) as total
                    "))
                        ->value('total'),
                    // 'discount_price' => $discountPrice,
                    'pay_total_price' => ClientValue::where(['client_id' => $result->id, 'is_active' => 1])->sum('pay_price'),
                    // 'debt_price' => ClientValue::where('client_id', $result->id)->sum('price') - $ClinetPaymet->sum('pay_total_price') - $ClinetPaymet->sum('back_total_price'),
                    'service_count' => $value->count(),
                    'total_price' =>  ClientValue::where(['client_id' => $result->id, 'is_active' => 1])->sum('total_price'),
                    'back_total_price' => ClientValue::where(['client_id' => $result->id, 'is_active' => 0])->sum('total_price'),
                    'probirka_count' => $value->where('is_probirka', 1)->count(),
                ]
            );
            $this->referringDoctorBalanceAdd($result->id, $request, $oldReferringDoctor);
            $this->doctorBalanceAdd($result, $request);
            $this->clientDepartmentCount($result->id);
            $this->clientProductCount($result->id);
            $priceRom = 0;
            $clinetowner = Client::find($result->parent_id);
            // $dailyRepot = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'start'])
            //     ->whereDate('created_at', now()->format('Y-m-d'))
            //     ->first();
            // // shu yerni tekshir 

            // if (!$dailyRepot) {
            //     $batch_number  = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'finish'])->whereDate('created_at', now()->format('Y-m-d'))->max('batch_number') ?? 0;
            //     DailyRepot::create([
            //         'user_id' => auth()->id(),
            //         'status' => 'start',
            //         'batch_number' => $batch_number + 1
            //     ]);
            // }
            $dailyRepot = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'start'])
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->first();
            if (isset($request->is_statsionar) && $request->is_statsionar == 1) {
                // $priceRom = $request->pay_total_price - ($toladi + $totalExtraBackPrice);  //// kelgan summani - tolagnag summandan ayiramiz va ajratib olamiz
                // if ($priceRom > 0) {
                //     $totalRomPrice = $result->statsionar_room_qty * $result->statsionar_room_price; /// jami qanchalik kelgan summa
                //     $roomdiscount = $result->statsionar_room_discount ?? 0;
                //     if ($result->statsionar_room_discount <= 100) {
                //         $roomdiscount =    $totalRomPrice / 100 * $result->statsionar_room_discount;
                //     }
                //     $paysum =  $totalRomPrice - $roomdiscount - $result->statsionar_room_price_pay;
                //     if ($paysum > 0) { /// qarzi bolsa
                //         if ($paysum > $priceRom) { /// qarzi katta bolsa
                //             $result->update([
                //                 'statsionar_room_price_pay' => $result->statsionar_room_price_pay + $priceRom,
                //             ]);
                //             // $roompay =      ClinetPaymet::create([
                //             //     'client_id' => $result->id,
                //             //     'user_id' => auth()->id(),
                //             //     'total_price' => $totalRomPrice,
                //             //     'cash_price' => $request->pay_type == 'cash' ? $priceRom : 0,
                //             //     'card_price' => $request->pay_type == 'card' ? $priceRom : 0,
                //             //     'transfer_price'    => $request->pay_type == 'transfer' ? $priceRom : 0,
                //             //     'discount' => $request->statsionar_room_discount ?? 0,
                //             //     'debt_comment' => $request->debt_comment ?? '-',
                //             //     'payment_deadline' => $request->payment_deadline ?? '-',
                //             //     'discount_comment' => $request->discount_comment ?? '-',
                //             //     'is_room' => 1
                //             // ]);
                //             $priceRom = 0;
                //         } else { ///qarzi kichik bolsa 
                //             $result->update([
                //                 'statsionar_room_price_pay' => $result->statsionar_room_price_pay + $paysum,
                //             ]);
                //             $priceRom = $priceRom - $paysum;
                //             // ClinetPaymet::create([
                //             //     'client_id' => $result->id,
                //             //     'user_id' => auth()->id(),
                //             //     // 'total_price' => $totalRomPrice,
                //             //     // 'cash_price' => $request->pay_type == 'cash' ? $paysum : 0,
                //             //     // 'card_price' => $request->pay_type == 'card' ? $paysum : 0,
                //             //     // 'transfer_price'    => $request->pay_type == 'transfer' ? $paysum : 0,
                //             //     // 'discount' => $request->statsionar_room_discount ?? 0,
                //             //     // 'debt_comment' => $request->debt_comment ?? '-',
                //             //     // 'payment_deadline' => $request->payment_deadline ?? '-',
                //             //     // 'discount_comment' => $request->discount_comment ?? '-',
                //             //     // 'is_room' => 1
                //             // ]);
                //         }
                //     }
                // }
                // Log::info('statsionar',[$request->pay_total_price,$resultClinetPaymet->pay_total_price]);
                $pay_total_price_  =  $request->pay_total_price - ($resultClinetPaymet->pay_total_price ?? 0);
                $priceRom = $pay_total_price_;  //// kelgan summani - tolagnag summandan ayiramiz va ajratib olamiz
                if ($priceRom > 0) {
                    if ($request->balanse > $clinetowner->balance) {
                        if ($request->balanse < $pay_total_price_) {
                            $priceRom = $pay_total_price_ - $request->balanse;
                        } else {
                            $priceRom = $request->balanse - $pay_total_price_;
                        }
                    }
                    if ($result->is_finish_statsionar) {
                        $romQty = $this->daysBetweenDates($result->addmission_date,  $result->finish_statsionar_date);
                    } else {
                        if ($result->day_qty > 0) {
                            $romQty = $this->daysBetweenDates($result->addmission_date, $this->addDaysToDate($result->addmission_date, $result->day_qty - 1));
                        } else {
                            $romQty = $this->daysBetweenDates($result->addmission_date, now()->format('Y-m-d'));
                        }
                    }
                    $totalRomPrice = $romQty * $result->statsionar_room_price; /// jami qanchalik kelgan summa
                    if ($result->statsionar_room_price_pay + $priceRom <= $totalRomPrice) {
                        $result->update([
                            'statsionar_room_price_pay' => $result->statsionar_room_price_pay + $priceRom,
                        ]);

                        $resultClinetPaymet2 =   ClinetPaymet::create([
                            'client_id' => $result->id,
                            'balance' => 0,
                            'user_id' => auth()->id(),
                            'client_value_id_data' => json_encode([]),
                            'discount' => 0,
                            'total_price' => $totalRomPrice,
                            'pay_total_price' =>   $priceRom,
                            'cash_price' => $request->pay_type == 'cash' ? $priceRom : 0,
                            'card_price' => $request->pay_type == 'card' ? $priceRom : 0,
                            'transfer_price'    => $request->pay_type == 'transfer' ? $priceRom  : 0,
                            'debt_comment' => $request->debt_comment ?? '-',
                            'payment_deadline' => $request->payment_deadline ?? '-',
                            'discount_comment' => $request->discount_comment ?? '-',
                            'is_room' => 1
                        ]);
                        $dailyRepot->update([
                            'client_count' => ClinetPaymet::whereDate('created_at', now()->format('Y-m-d'))->where('user_id', auth()->id())->pluck('client_id')->unique()->count(),
                            // ozgartirdm 
                            'total_price' => $dailyRepot->total_price + $resultClinetPaymet2->pay_total_price,
                            'cash_price' => $dailyRepot->cash_price + $resultClinetPaymet2->cash_price,
                            'card_price' => $dailyRepot->card_price + $resultClinetPaymet2->card_price,
                            'transfer_price' => $dailyRepot->transfer_price + $resultClinetPaymet2->transfer_price,
                        ]);
                        DailyRepotClient::create([
                            'client_id' => $result->id,
                            'daily_repot_id' => $dailyRepot->id
                        ]);
                    }
                }
                if (isset($request->balanse)) {

                    if ($request->balanse > $clinetowner->balance) {
                        ClientBalance::create([
                            'daily_repot_id' => $dailyRepot->id,
                            'client_id' => $result->id,
                            'price' => $request->balanse - $clinetowner->balance,
                            'status' => 'pay',
                            'pay_type' => $request->pay_type ?? '-',
                            'person_id' => $result->person_id
                        ]);
                        $resultClinetPaymet1 =  ClinetPaymet::create([
                            'client_id' => $result->id,
                            'balance' => $request->balanse - $clinetowner->balance,
                            'user_id' => auth()->id(),
                            'client_value_id_data' => json_encode([]),
                            'discount' => 0,
                            'total_price' => $request->balanse - $clinetowner->balance,
                            'cash_price' => $request->pay_type == 'cash' ? $request->balanse - $clinetowner->balance : 0,
                            'card_price' => $request->pay_type == 'card' ? $request->balanse - $clinetowner->balance : 0,
                            'transfer_price'    => $request->pay_type == 'transfer' ? $request->balanse - $clinetowner->balance  : 0,
                            'debt_comment' => $request->debt_comment ?? '-',
                            'payment_deadline' => $request->payment_deadline ?? '-',
                            'discount_comment' => $request->discount_comment ?? '-',
                            'is_room' => 1
                        ]);

                        $dailyRepot->update([
                            'client_count' => ClinetPaymet::whereDate('created_at', now()->format('Y-m-d'))->where('user_id', auth()->id())->pluck('client_id')->unique()->count(),
                            // ozgartirdm 
                            'total_price' => $dailyRepot->total_price + $resultClinetPaymet1->pay_total_price,
                            'cash_price' => $dailyRepot->cash_price + $resultClinetPaymet1->cash_price,
                            'card_price' => $dailyRepot->card_price + $resultClinetPaymet1->card_price,
                            'transfer_price' => $dailyRepot->transfer_price + $resultClinetPaymet1->transfer_price,
                        ]);
                        DailyRepotClient::create([
                            'client_id' => $result->id,
                            'daily_repot_id' => $dailyRepot->id
                        ]);
                        // if (isset($resultClinetPaymet2->id)) {
                        //     $dailyRepot = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'start'])
                        //         ->whereDate('created_at', now()->format('Y-m-d'))
                        //         ->first();
                        //     // shu yerni tekshir 

                        //     if (!$dailyRepot) {
                        //         $batch_number  = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'finish'])->whereDate('created_at', now()->format('Y-m-d'))->max('batch_number');
                        //         $dailyRepot =  DailyRepot::create([
                        //             'user_id' => auth()->id(),
                        //             'status' => 'start',
                        //             'batch_number' => $batch_number + 1
                        //         ]);
                        //     }

                        //     $dailyRepot->update([
                        //         'client_count' => ClinetPaymet::whereDate('created_at', now()->format('Y-m-d'))->where('user_id', auth()->id())->pluck('client_id')->unique()->count(),
                        //         'total_price' => $dailyRepot->total_price + $resultClinetPaymet2->pay_total_price  - $resultClinetPaymet2->balance,
                        //         'cash_price' => $dailyRepot->cash_price + $resultClinetPaymet2->cash_price,
                        //         'card_price' => $dailyRepot->card_price + $resultClinetPaymet2->card_price,
                        //         'transfer_price' => $dailyRepot->transfer_price + $resultClinetPaymet2->transfer_price,
                        //     ]);
                        //     DailyRepotClient::create([
                        //         'client_id' => $result->id,
                        //         'daily_repot_id' => $dailyRepot->id
                        //     ]);
                        //     $batch_number  = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'finish'])->whereDate('created_at', $dailyRepot->created_at->format('Y-m-d'))->max('batch_number');
                        //     // $dailyRepot->update(['batch_number' => $batch_number + 1]);
                        // }
                    }
                    if ($request->balanse < $clinetowner->balance) {
                        ClientBalance::create([
                            'daily_repot_id' => $dailyRepot->id,
                            'client_id' => $result->id,
                            'price' =>  $clinetowner->balance - $request->balanse,
                            'status' => 'use',
                            'pay_type' => $request->pay_type ?? '-',
                            'person_id' => $result->person_id
                        ]);
                    }
                    $clinetowner->update(['balance' => $request->balanse]);
                }
                // if($backRoomqty > 0){
                //     $totalRomPrice = $result->statsionar_room_qty * $result->statsionar_room_price;
                //     $result->update([
                //         'statsionar_room_price_pay' => $result->statsionar_room_price_pay - $backRoomqty *  $result->statsionar_room_price,
                //     ]);
                // }
            } else {
                // if ($request->pay_total_price - ($toladi + $totalExtraBackPrice) > 0) {
                //     // //////statsianra uchun qoshildi
                //     $totalBalance = $request->pay_total_price - ($toladi + $totalExtraBackPrice);
                //     if (isset($request->is_statsionar) && $request->is_statsionar == 1) {
                //         if ($priceRom > 0) {
                //             $totalBalance  =  $priceRom;
                //         }
                //     }
                //     // //////statsianra uchun qoshildi
                //     if ($totalBalance > 0) {
                //         ClientBalance::create([
                //             'client_id' => $result->id,
                //             'price' => $totalBalance,
                //             'pay_type' => $request->pay_type ?? '-',
                //             'status' => 'pay',
                //             'person_id' => $result->person_id
                //         ]);


                //         $clinetowner->update(['balance' => ($clinetowner->balance ?? 0) + ($totalBalance)]);
                //     }
                // }
                // // if ($request->pay_total_price > 0) {
                // if (isset($request->balanse) && $request->balanse >= 0) {
                //     $clinetowner->update(['balance' => $request->balanse]);
                // }
                // if (isset($request->use_balanse) && $request->use_balanse > 0) {
                //     ClientBalance::create([
                //         'client_id' => $result->id,
                //         'pay_type' => $request->pay_type ?? '-',
                //         'price' => $request->use_balanse,
                //         'status' => 'use',
                //         'person_id' => $result->person_id
                //     ]);
                // }
                // if (isset($request->pay_balanse) && $request->pay_balanse > 0) {
                //     ClientBalance::create([
                //         'client_id' => $result->id,
                //         'pay_type' => $request->pay_type ?? '-',
                //         'price' => $request->pay_balanse,
                //         'status' => 'use',
                //         'person_id' => $result->person_id
                //     ]);
                // }
                if (isset($request->balanse)) {
                    if ($request->balanse > $clinetowner->balance) {
                        ClientBalance::create([
                            'daily_repot_id' => $dailyRepot->id,
                            'client_id' => $result->id,
                            'price' => $request->balanse - $clinetowner->balance,
                            'status' => 'pay',
                            'pay_type' => $request->pay_type ?? '-',
                            'person_id' => $result->person_id
                        ]);
                        // $resultClinetPaymet =   
                        $resultClinetPaymet11 =  ClinetPaymet::create([
                            'client_id' => $result->id,
                            'balance' => $request->balanse - $clinetowner->balance,
                            'user_id' => auth()->id(),
                            'client_value_id_data' => json_encode([]),
                            'discount' => 0,
                            'total_price' => $request->balanse - $clinetowner->balance,
                            'cash_price' => $request->pay_type == 'cash' ? $request->balanse - $clinetowner->balance : 0,
                            'card_price' => $request->pay_type == 'card' ? $request->balanse - $clinetowner->balance : 0,
                            'transfer_price'    => $request->pay_type == 'transfer' ? $request->balanse - $clinetowner->balance  : 0,
                            'debt_comment' => $request->debt_comment ?? '-',
                            'payment_deadline' => $request->payment_deadline ?? '-',
                            'discount_comment' => $request->discount_comment ?? '-',
                            'is_room' => 1
                        ]);

                        $dailyRepot->update([
                            'client_count' => ClinetPaymet::whereDate('created_at', now()->format('Y-m-d'))->where('user_id', auth()->id())->pluck('client_id')->unique()->count(),
                            // ozgartirdm 
                            'total_price' => $dailyRepot->total_price + $resultClinetPaymet11->pay_total_price,
                            'cash_price' => $dailyRepot->cash_price + $resultClinetPaymet11->cash_price,
                            'card_price' => $dailyRepot->card_price + $resultClinetPaymet11->card_price,
                            'transfer_price' => $dailyRepot->transfer_price + $resultClinetPaymet11->transfer_price,
                        ]);
                        DailyRepotClient::create([
                            'client_id' => $result->id,
                            'daily_repot_id' => $dailyRepot->id
                        ]);
                    }
                    if ($request->balanse < $clinetowner->balance) {
                        ClientBalance::create([
                            'client_id' => $result->id,
                            'daily_repot_id' => $dailyRepot->id,
                            'price' =>  $clinetowner->balance - $request->balanse,
                            'status' => 'use',
                            'pay_type' => $request->pay_type ?? '-',
                            'person_id' => $result->person_id
                        ]);
                    }
                    $clinetowner->update(['balance' => $request->balanse]);
                }
            }


            // Log::info('$debtWithBalanse', [$debtWithBalanse]);
            // if ($debtWithBalanse == 0) {
            //     ClientBalance::create([
            //         'client_id' => $result->id,
            //         'price' => $request->pay_total_price,
            //         'status' => 'pay',
            //         'person_id' => $result->person_id
            //     ]);
            //     $clinetowner->update(['balance' => ($clinetowner->balance ?? 0) + $request->pay_total_price]);
            // } else {
            //     if ($debtWithBalanse > $request->pay_total_price) { //// qarz katta bolsa tolanadigan summdan 
            //         if ($clinetowner->balance > $request->pay_total_price) { /// balas katta bolsa tolandaiga summdan 
            //             $clinetowner->update(['balance' => ($clinetowner->balance ?? 0) - $request->pay_total_price]);
            //             ClientBalance::create([
            //                 'client_id' => $result->id,
            //                 'price' => $request->pay_total_price,
            //                 'status' => 'use',
            //             ]);
            //         } else {
            //         }
            //     } else {
            //         // $debtWithBalanse < $request->pay_total_price)
            //         if ($clinetowner->balance > $debtWithBalanse) {
            //             $clinetowner->update(['balance' => ($clinetowner->balance ?? 0) - $request->pay_total_price]);
            //             ClientBalance::create([
            //                 'client_id' => $result->id,
            //                 'price' => $request->pay_total_price,
            //                 'status' => 'use',
            //             ]);
            //         } else {
            //             $clinetowner->update(['balance' => ($clinetowner->balance ?? 0) + $request->pay_total_price - $debtWithBalanse]);
            //             ClientBalance::create([
            //                 'client_id' => $result->id,
            //                 'price' => $request->pay_total_price,
            //                 'status' => 'use',
            //             ]);
            //             ClientBalance::create([
            //                 'client_id' => $result->id,
            //                 'price' => $request->pay_total_price - $debtWithBalanse,
            //                 'status' => 'pay',
            //             ]);
            //         }
            //     }


            //     // -----///---////

            // }


            // if ($clinetowner->balance >= $request->pay_total_price) {
            //     $clinetowner->update(['balance' => ($clinetowner->balance ?? 0) - $request->pay_total_price]);
            //     ClientBalance::create([
            //         'client_id' => $result->id,
            //         'price' => $request->pay_total_price,
            //         'status' => 'use',
            //         'person_id' => $result->person_id
            //     ]);
            // } else {
            //     ClientBalance::create([
            //         'client_id' => $result->id,
            //         'price' => $clinetowner->balance,
            //         'status' => 'use',
            //         'person_id' => $result->person_id
            //     ]);
            //     $clinetowner->update(['balance' => 0]);
            // }
            // }
            // if(isset())
            if (isset($resultClinetPaymet->id) && $request->pay_type != 'balance') {

                $dailyRepot->update([
                    'client_count' => ClinetPaymet::whereDate('created_at', now()->format('Y-m-d'))->where('user_id', auth()->id())->pluck('client_id')->unique()->count(),
                    // ozgartirdm 
                    'total_price' => $dailyRepot->pay_total_price + $resultClinetPaymet->pay_total_price,
                    'cash_price' => $dailyRepot->cash_price + $resultClinetPaymet->cash_price,
                    'card_price' => $dailyRepot->card_price + $resultClinetPaymet->card_price,
                    'transfer_price' => $dailyRepot->transfer_price + $resultClinetPaymet->transfer_price,
                ]);
                DailyRepotClient::create([
                    'client_id' => $result->id,
                    'daily_repot_id' => $dailyRepot->id
                ]);
                $batch_number  = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'finish'])->whereDate('created_at', $dailyRepot->created_at->format('Y-m-d'))->max('batch_number');
                // $dailyRepot->update(['batch_number' => $batch_number + 1]);
            }

            // DailyRepot

            return [
                'check_print_data' => [
                    'first_name' => $result->first_name,
                    'last_name' => $result->last_name,
                    'parent_name' => $result->parent_name,
                    'data_birth' => $result->data_birth,
                    'phone' => $result->phone,
                    'sex' => $result->sex,
                    'person_id' => $result->person_id,
                    'probirka_id' => $result->probirka_id ?? 0,
                    'client_time' => $clientTimeAchive,
                    'client_value' => ClientValue::whereIn('id', $payId)
                        ->whereHas('service', function ($q) use ($result) {
                            $q->whereHas('department', function ($q) use ($result) {
                                $q->where('is_chek_print', 1);
                            });
                        })
                        ->with(['service' => function ($q) {
                            $q->with(['servicetype', 'department']);
                        }])->get(),
                    'client_payment' => $resultClinetPaymet,
                    'created_at' => $result->created_at,
                    'total_price' => $result->total_price,
                    'pay_total_price' => $result->pay_total_price,
                    'discount' => $result->discount,
                ],
                'data' => isset($request->is_statsionar) && $request->is_statsionar == 1 ? $this->modelClass::with(['balance', 'clientValue.service.department', 'clientResult', 'statsionarRoom:id,type,number,room_index,price', 'statsionarDoctor:id,name,full_name', 'referringDoctor', 'user:id,name,full_name'])->find($result->id) : new ClientResource($this->modelClass
                    ::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                    // ::where('user_id', auth()->id())
                    ->whereNull('parent_id')
                    ->with([
                        'clientItem' => function ($q) use ($result, $request) {

                            $q
                                //

                                ->with(['clientResult', 'clientValue' => function ($q) {
                                    $q
                                        ->with('clientUseProduct.productReceptionItem')
                                        ->with(['service' => function ($q) {
                                            $q->with(['servicetype', 'department'])
                                                ->with('serviceProduct.product')
                                            ;
                                        }]);
                                }, 'clientPayment.user', 'clientTime.department']);

                            if (isset($request->send_status) && $request->send_status == 'debt_pay') {
                                $q->where('id', $result->id);
                            }
                        },

                    ])
                    ->find($result->parent_id))
            ];
        } else {
            $clinetowner = Client::find($result->parent_id);

            if (isset($request->is_statsionar) && $request->is_statsionar == 1) {
                $priceRom = $request->pay_total_price;  //// kelgan summani - tolagnag summandan ayiramiz va ajratib olamiz
                if ($priceRom > 0) {
                    if ($request->balanse > $clinetowner->balance) {
                        if ($request->balanse < $request->pay_total_price) {
                            $priceRom = $request->pay_total_price - $request->balanse;
                        } else {
                            $priceRom = $request->balanse - $request->pay_total_price;
                        }
                    }
                    if ($result->is_finish_statsionar) {
                        $romQty = $this->daysBetweenDates($result->addmission_date,  $result->finish_statsionar_date);
                    } else {
                        if ($result->day_qty > 0) {
                            $romQty = $this->daysBetweenDates($result->addmission_date, $this->addDaysToDate($result->addmission_date, $result->day_qty - 1));
                        } else {
                            $romQty = $this->daysBetweenDates($result->addmission_date, now()->format('Y-m-d'));
                        }
                    }
                    $totalRomPrice = $romQty * $result->statsionar_room_price; /// jami qanchalik kelgan summa
                    if ($result->statsionar_room_price_pay + $priceRom <= $totalRomPrice) {
                        $result->update([
                            'statsionar_room_price_pay' => $result->statsionar_room_price_pay + $priceRom,
                        ]);
                        $resultClinetPaymet3  = ClinetPaymet::create([
                            'client_id' => $result->id,
                            'balance' => 0,
                            'user_id' => auth()->id(),
                            'client_value_id_data' => json_encode([]),
                            'discount' => 0,
                            'total_price' => $totalRomPrice,
                            'pay_total_price' =>   $priceRom,
                            'cash_price' => $request->pay_type == 'cash' ? $priceRom : 0,
                            'card_price' => $request->pay_type == 'card' ? $priceRom : 0,
                            'transfer_price'    => $request->pay_type == 'transfer' ? $priceRom  : 0,
                            'debt_comment' => $request->debt_comment ?? '-',
                            'payment_deadline' => $request->payment_deadline ?? '-',
                            'discount_comment' => $request->discount_comment ?? '-',
                            'is_room' => 1
                        ]);
                        $dailyRepot->update([
                            'client_count' => ClinetPaymet::whereDate('created_at', now()->format('Y-m-d'))->where('user_id', auth()->id())->pluck('client_id')->unique()->count(),
                            // ozgartirdm 
                            'total_price' => $dailyRepot->total_price + $resultClinetPaymet3->pay_total_price,
                            'cash_price' => $dailyRepot->cash_price + $resultClinetPaymet3->cash_price,
                            'card_price' => $dailyRepot->card_price + $resultClinetPaymet3->card_price,
                            'transfer_price' => $dailyRepot->transfer_price + $resultClinetPaymet3->transfer_price,
                        ]);
                        DailyRepotClient::create([
                            'client_id' => $result->id,
                            'daily_repot_id' => $dailyRepot->id
                        ]);
                    }

                    // $totalRomPrice = $result->statsionar_room_qty * $result->statsionar_room_price; /// jami qanchalik kelgan summa
                    // $roomdiscount = $result->statsionar_room_discount ?? 0;
                    // if ($result->statsionar_room_discount <= 100) {
                    //     $roomdiscount =    $totalRomPrice / 100 * $result->statsionar_room_discount;
                    // }
                    // $paysum =  $totalRomPrice - $roomdiscount - $result->statsionar_room_price_pay;
                    // if ($paysum > 0) { /// qarzi bolsa
                    //     if ($paysum > $priceRom) { /// qarzi katta bolsa
                    //         $result->update([
                    //             'statsionar_room_price_pay' => $result->statsionar_room_price_pay + $priceRom,
                    //         ]);
                    //         Log::info('bag', [$result->id, auth()->id()]);

                    //         ClinetPaymet::create([
                    //             'client_id' => $result->id,
                    //             'balance' => 0,
                    //             'user_id' => auth()->id(),
                    //             'client_value_id_data' => json_encode([]),
                    //             'discount' => 0,
                    //             'total_price' => $totalRomPrice,
                    //             'cash_price' => $request->pay_type == 'cash' ? $priceRom : 0,
                    //             'card_price' => $request->pay_type == 'card' ? $priceRom  : 0,
                    //             'transfer_price'    => $request->pay_type == 'transfer' ? $priceRom  : 0,
                    //             'debt_comment' => $request->debt_comment ?? '-',
                    //             'payment_deadline' => $request->payment_deadline ?? '-',
                    //             'discount_comment' => $request->discount_comment ?? '-',
                    //             'is_room' => 1
                    //         ]);

                    //         // ClinetPaymet::create([
                    //         //     // 'client_id' => (int)$result->id,
                    //         //     // 'user_id' => auth()->id(),
                    //         //     // 'total_price' => $totalRomPrice,
                    //         //     // 'cash_price' => $request->pay_type == 'cash' ? $priceRom : 0,
                    //         //     // 'card_price' => $request->pay_type == 'card' ? $priceRom : 0,
                    //         //     // 'transfer_price'    => $request->pay_type == 'transfer' ? $priceRom : 0,
                    //         //     // 'discount' => $request->statsionar_room_discount ?? 0,
                    //         //     // 'debt_comment' => $request->debt_comment ?? '-',
                    //         //     // 'payment_deadline' => $request->payment_deadline ?? '-',
                    //         //     // 'discount_comment' => $request->discount_comment ?? '-',
                    //         //     'is_room' => 1
                    //         // ]);
                    //         $priceRom = 0;
                    //     } else { ///qarzi kichik bolsa 
                    //         $result->update([
                    //             'statsionar_room_price_pay' => $result->statsionar_room_price_pay + $paysum,
                    //         ]);
                    //         $priceRom = $priceRom - $paysum;
                    //         ClinetPaymet::create([
                    //             'client_id' => $result->id,
                    //             'balance' => 0,
                    //             'user_id' => auth()->id(),
                    //             'client_value_id_data' => json_encode([]),
                    //             'discount' => 0,
                    //             'total_price' => $totalRomPrice,
                    //             'cash_price' => $request->pay_type == 'cash' ? $paysum : 0,
                    //             'card_price' => $request->pay_type == 'card' ? $paysum  : 0,
                    //             'transfer_price'    => $request->pay_type == 'transfer' ? $paysum  : 0,
                    //             'debt_comment' => $request->debt_comment ?? '-',
                    //             'payment_deadline' => $request->payment_deadline ?? '-',
                    //             'discount_comment' => $request->discount_comment ?? '-',
                    //             'is_room' => 1
                    //         ]);
                    //         // ClinetPaymet::create([
                    //         //     'client_id' => $result->id,
                    //         //     'user_id' => auth()->id(),
                    //         //     // 'total_price' => $totalRomPrice,
                    //         //     // 'cash_price' => $request->pay_type == 'cash' ? $paysum : 0,
                    //         //     // 'card_price' => $request->pay_type == 'card' ? $paysum : 0,
                    //         //     // 'transfer_price'    => $request->pay_type == 'transfer' ? $paysum : 0,
                    //         //     // 'discount' => $request->statsionar_room_discount ?? 0,
                    //         //     // 'debt_comment' => $request->debt_comment ?? '-',
                    //         //     // 'payment_deadline' => $request->payment_deadline ?? '-',
                    //         //     // 'discount_comment' => $request->discount_comment ?? '-',
                    //         //     // 'is_room' => 1
                    //         // ]);
                    //     }
                    // }
                }
                // if()
                if (isset($request->balanse)) {
                    if ($request->balanse > $clinetowner->balance) {
                        ClientBalance::create([
                            'daily_repot_id' => $dailyRepot->id,
                            'client_id' => $result->id,
                            'price' => $request->balanse - $clinetowner->balance,
                            'status' => 'pay',
                            'pay_type' => $request->pay_type ?? '-',
                            'person_id' => $result->person_id
                        ]);
                        $resultClinetPaymet33 =     ClinetPaymet::create([
                            'client_id' => $result->id,
                            'balance' => $request->balanse - $clinetowner->balance,
                            'user_id' => auth()->id(),
                            'client_value_id_data' => json_encode([]),
                            'discount' => 0,
                            'total_price' => $request->balanse - $clinetowner->balance,
                            'cash_price' => $request->pay_type == 'cash' ? $request->balanse - $clinetowner->balance : 0,
                            'card_price' => $request->pay_type == 'card' ? $request->balanse - $clinetowner->balance : 0,
                            'transfer_price'    => $request->pay_type == 'transfer' ? $request->balanse - $clinetowner->balance  : 0,
                            'debt_comment' => $request->debt_comment ?? '-',
                            'payment_deadline' => $request->payment_deadline ?? '-',
                            'discount_comment' => $request->discount_comment ?? '-',
                            'is_room' => 1
                        ]);
                        $dailyRepot->update([
                            'client_count' => ClinetPaymet::whereDate('created_at', now()->format('Y-m-d'))->where('user_id', auth()->id())->pluck('client_id')->unique()->count(),
                            // ozgartirdm 
                            'total_price' => $dailyRepot->total_price + $resultClinetPaymet33->pay_total_price,
                            'cash_price' => $dailyRepot->cash_price + $resultClinetPaymet33->cash_price,
                            'card_price' => $dailyRepot->card_price + $resultClinetPaymet33->card_price,
                            'transfer_price' => $dailyRepot->transfer_price + $resultClinetPaymet33->transfer_price,
                        ]);
                        DailyRepotClient::create([
                            'client_id' => $result->id,
                            'daily_repot_id' => $dailyRepot->id
                        ]);
                    }
                    if ($request->balanse < $clinetowner->balance) {
                        ClientBalance::create([
                            'daily_repot_id' => $dailyRepot->id,
                            'client_id' => $result->id,
                            'price' =>  $clinetowner->balance - $request->balanse,
                            'status' => 'use',
                            'pay_type' => $request->pay_type ?? '-',
                            'person_id' => $result->person_id
                        ]);
                    }
                    $clinetowner->update(['balance' => $request->balanse]);
                }

                // if($backRoomqty > 0){
                //     $totalRomPrice = $result->statsionar_room_qty * $result->statsionar_room_price;
                //     $result->update([
                //         'statsionar_room_price_pay' => $result->statsionar_room_price_pay - $backRoomqty *  $result->statsionar_room_price,
                //     ]);
                // }
            } else {
                // if (isset($request->balanse)) {
                //     if ($request->balanse > $clinetowner->balance) {
                //         ClientBalance::create([
                //             'daily_repot_id' => $dailyRepot->id,
                //             'client_id' => $result->id,
                //             'price' => $request->balanse - $clinetowner->balance,
                //             'status' => 'pay',
                //             'pay_type' => $request->pay_type ?? '-',
                //             'person_id' => $result->person_id
                //         ]);
                //     }
                //     if ($request->balanse < $clinetowner->balance) {
                //         ClientBalance::create([
                //             'client_id' => $result->id,
                //             'daily_repot_id' => $dailyRepot->id,
                //             'price' =>  $clinetowner->balance - $request->balanse,
                //             'status' => 'use',
                //             'pay_type' => $request->pay_type ?? '-',
                //             'person_id' => $result->person_id
                //         ]);
                //     }
                //     $clinetowner->update(['balance' => $request->balanse]);
                // }
            }
        }
        return [
            'check_print_data' => [
                'first_name' => $result->first_name,
                'last_name' => $result->last_name,
                'parent_name' => $result->parent_name,
                'data_birth' => $result->data_birth,
                'phone' => $result->phone,
                'sex' => $result->sex,
                'person_id' => $result->person_id,
                'probirka_id' => $result->probirka_id ?? 0,
                'client_time' => [],
                'client_value' => [],
                'client_payment' => [],
                'created_at' => $result->created_at,
                'total_price' => $result->total_price,
                'pay_total_price' => $result->pay_total_price,
                'discount' => $result->discount,
            ],
            'data' => isset($request->is_statsionar) && $request->is_statsionar == 1 ? $this->modelClass::with(['balance', 'clientValue.service.department', 'clientResult', 'statsionarRoom:id,type,number,room_index,price', 'statsionarDoctor:id,name,full_name', 'referringDoctor', 'user:id,name,full_name'])->find($result->id) : new ClientResource($this->modelClass
                ::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
                // ::where('user_id', auth()->id())
                ->whereNull('parent_id')
                ->with([
                    'clientItem' => function ($q) use ($result, $request) {

                        $q
                            //

                            ->with(['clientResult', 'clientValue' => function ($q) {
                                $q
                                    ->with('clientUseProduct.productReceptionItem')
                                    ->with(['service' => function ($q) {
                                        $q->with(['servicetype', 'department'])
                                            ->with('serviceProduct.product')
                                        ;
                                    }]);
                            }, 'clientPayment.user', 'clientTime.department']);

                        if (isset($request->send_status) && $request->send_status == 'debt_pay') {
                            $q->where('id', $result->id);
                        }
                    },

                ])
                ->find($result->parent_id))
        ];
    }

    function addDaysToDate($date, $days)
    {
        return Carbon::parse($date)->addDays($days)->format('Y-m-d');
    }

    public function discountCalc($data)
    {
        if (+$data->discount <= 100) {
            return ((+$data->price / 100) * +$data->discount) * $data->qty;
        }
        return ($data->discount);
    }
    public function referringDoctorBalanceAdd($clietId, $request, $oldReferringDoctor = 0)
    {
        $client = Client::find($clietId);
        $setting = DirectorSetting::where('user_id', auth()->user()->owner_id)->first();
        $referring_doctor = ReferringDoctor::find($client->referring_doctor_id);
        Log::info('$referring_doctor', [$referring_doctor]);
        $is_statsionar = 0;
        if (isset($request->is_statsionar) && $request->is_statsionar == 1) {
            $is_statsionar = 1;
        }
        if ($referring_doctor) {

            if ($oldReferringDoctor > 0 &&  $referring_doctor->id != $oldReferringDoctor) {
                $res = ReferringDoctorBalance::where([
                    'client_id' => $client->id,
                    'referring_doctor_id' => $oldReferringDoctor,
                ])->first();
                if ($res) {
                    $res->delete();
                    ReferringDoctorChangeArchive::create([
                        'client_id' => $client->id,
                        'from_referring_doctor_id'  => $oldReferringDoctor,
                        'to_referring_doctor_id' => $referring_doctor->id,
                    ]);
                }
            }
            $clietValue = ClientValue::where(['client_id' => $clietId, 'is_active' => 1, 'is_pay' => 1])
                // ->where(DB::raw('total_price - (CASE WHEN discount <= 100 THEN (total_price * discount / 100) ELSE discount END)'), '=', DB::raw('pay_price'))
                ->with('service')
                ->get();
            $contribution_history = [];
            $total_kounteragent_contribution_price = 0;
            $total_kounteragent_doctor_contribution_price = 0;
            $total_doctor_contribution_price = 0;
            $findRef = ReferringDoctorBalance::where([
                'client_id' => $client->id,
                'referring_doctor_id' => $referring_doctor->id,
            ])
                ->where(function ($query) use ($is_statsionar) {
                    if ($is_statsionar == 1) {
                        $query->where('is_statsionar', 1);
                    } else {
                        $query->where('is_statsionar', 0)
                            ->orWhereNull('is_statsionar');
                    }
                })
                ->first();
            foreach ($clietValue as $item) {
                // kounter agent
                $kounteragent_contribution_price = $item->service->kounteragent_contribution_price ?? 0; //  kounter agent
                $qty = $item->qty; ///soni
                $totalPrice_k_c = $item->total_price;

                if ($setting->is_contribution_kounteragent) {
                    $totalPrice_k_c = ($item->discount <= 100)
                        ? $item->total_price  -  ($item->total_price / 100) * $item->discount
                        : $item->total_price - ($item->discount);
                }

                // Hisoblash logikasi
                $resTotal_d_c =  ($kounteragent_contribution_price <= 100)
                    ? ($totalPrice_k_c * $kounteragent_contribution_price / 100)
                    : ($kounteragent_contribution_price * $qty);
                $total_kounteragent_contribution_price = $total_kounteragent_contribution_price + $resTotal_d_c;

                // kounter docktor
                $kounteragent_doctor_contribution_price = 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                if ($item->service->kounteragent_doctor_contribution_price > 0) {
                    $kounteragent_doctor_contribution_price =  $item->service->kounteragent_doctor_contribution_price ?? 0;
                } else {
                    $kounteragent_doctor_contribution_price = ReferringDoctorServiceContribution::where([
                        'ref_doc_id' => $client->referring_doctor_id,
                        'service_id' => $item->service->id
                    ])->first()->contribution_price ?? 0;
                }

                $KountertotalPrice = $item->total_price;

                if ($setting->is_contribution_kt_doc) {
                    $KountertotalPrice = ($item->discount <= 100)
                        ? $KountertotalPrice  -  ($KountertotalPrice / 100) * $item->discount
                        : $KountertotalPrice - ($item->discount);
                }

                $KounterResTotal = ($kounteragent_doctor_contribution_price <= 100)
                    ? ($KountertotalPrice * $kounteragent_doctor_contribution_price / 100)
                    : ($kounteragent_doctor_contribution_price * $qty);
                $total_kounteragent_doctor_contribution_price = $total_kounteragent_doctor_contribution_price +    $KounterResTotal;
                $contribution_history[] = [
                    'service_id' => $item->service->id,
                    'department_id' => $item->service->department_id,
                    'price' => $item->price,
                    'qty' => $qty,
                    'client_value' => $item->id,
                    'kounteragent_contribution_price' => $kounteragent_contribution_price,
                    'kounteragent_doctor_contribution_price' => $kounteragent_doctor_contribution_price,
                    'total_kounteragent_contribution_price' => $resTotal_d_c,
                    'total_kounteragent_doctor_contribution_price' => $KounterResTotal,
                ];
            }
            if ($findRef) {



                // $total_doctor_contribution_price =  $clietValue->sum(function ($clientValue) use ($contribution_history) {
                //     $doctorContributionPrice = $clientValue->service->doctor_contribution_price ?? 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                //     $qty = $clientValue->qty;
                //     $totalPrice = $clientValue->total_price;

                //     // Hisoblash logikasi
                //     $resTotal =  ($doctorContributionPrice <= 100)
                //         ? ($totalPrice * $doctorContributionPrice / 100)
                //         : ($doctorContributionPrice * $qty);
                //     $contribution_history[] = [
                //         'service_id' => $clientValue->service->id,
                //         'department_id' => $clientValue->service->department_id,
                //         'doctor_contribution_price' => $resTotal,
                //         'total_price' =>   $totalPrice,
                //         'item_doctor_contribution_price' => $doctorContributionPrice,
                //     ];
                //     return $resTotal;
                // });
                // $total_kounteragent_contribution_price = $clietValue->sum(function ($clientValue) use ($setting, $contribution_history) {
                //     $doctorContributionPrice = $clientValue->service->kounteragent_contribution_price ?? 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                //     $qty = $clientValue->qty;
                //     $totalPrice = $clientValue->total_price;

                //     if ($setting->is_contribution_kounteragent) {
                //         $totalPrice = ($clientValue->discount <= 100)
                //             ? $clientValue->total_price  -  ($clientValue->total_price / 100) * $clientValue->discount
                //             : $clientValue->total_price - ($clientValue->discount);
                //     }

                //     // Hisoblash logikasi
                //     $resTotal =  ($doctorContributionPrice <= 100)
                //         ? ($totalPrice * $doctorContributionPrice / 100)
                //         : ($doctorContributionPrice * $qty);
                //     $contribution_history[] = [
                //         'service_id' => $clientValue->service->id,
                //         'department_id' => $clientValue->service->department_id,
                //         'kounteragent_contribution_price' => $resTotal,
                //         'total_price' =>   $totalPrice,
                //         'item_kounteragent_contribution_price' => $doctorContributionPrice,
                //     ];
                //     return $resTotal;
                // });
                // $total_kounteragent_doctor_contribution_price = $clietValue->sum(function ($clientValue) use ($client, $setting, $contribution_history) {
                //     $doctorContributionPrice = 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                //     if ($clientValue->service->kounteragent_doctor_contribution_price > 0) {
                //         $doctorContributionPrice =  $clientValue->service->kounteragent_doctor_contribution_price ?? 0;
                //     } else {
                //         $doctorContributionPrice = ReferringDoctorServiceContribution::where([
                //             'ref_doc_id' => $client->referring_doctor_id,
                //             'service_id' => $clientValue->service->id
                //         ])->first()->contribution_price ?? 0;
                //     }
                //     Log::info('   $doctorContributionPrice', [$doctorContributionPrice]);
                //     $qty = $clientValue->qty;
                //     $totalPrice = $clientValue->total_price;

                //     if ($setting->is_contribution_kt_doc) {
                //         $totalPrice = ($clientValue->discount <= 100)
                //             ? $totalPrice  -  ($totalPrice / 100) * $clientValue->discount
                //             : $totalPrice - ($clientValue->discount);
                //     }

                //     $resTotal = ($doctorContributionPrice <= 100)
                //         ? ($totalPrice * $doctorContributionPrice / 100)
                //         : ($doctorContributionPrice * $qty);
                //     $contribution_history[] = [
                //         'service_id' => $clientValue->service->id,
                //         'department_id' => $clientValue->service->department_id,
                //         'kounteragent_doctor_contribution_price' => $resTotal,
                //         'total_price' =>   $totalPrice,
                //         'item_kounteragent_doctor_contribution_price' => $doctorContributionPrice,
                //     ];
                //     // Hisoblash logikasi
                //     return $resTotal;
                // });
                Log::info('$contribution_history', [$contribution_history]);
                $findRef->update([
                    'is_statsionar' => $is_statsionar,
                    'total_price' =>   $clietValue
                        ->sum('total_price'),
                    'service_count' =>   $clietValue->sum('qty'),
                    'total_doctor_contribution_price' => $total_doctor_contribution_price,
                    'total_kounteragent_contribution_price' => $total_kounteragent_contribution_price,
                    'total_kounteragent_doctor_contribution_price' =>  $total_kounteragent_doctor_contribution_price,
                    'contribution_history' => json_encode($contribution_history),
                ]);
            } else {
                // $total_doctor_contribution_price =  $clietValue->sum(function ($clientValue) use ($contribution_history) {
                //     $doctorContributionPrice = $clientValue->service->doctor_contribution_price ?? 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                //     $qty = $clientValue->qty;
                //     $totalPrice = $clientValue->total_price;

                //     // Hisoblash logikasi
                //     $resTotal =  ($doctorContributionPrice <= 100)
                //         ? ($totalPrice * $doctorContributionPrice / 100)
                //         : ($doctorContributionPrice * $qty);
                //     $contribution_history[] = [
                //         'service_id' => $clientValue->service->id,
                //         'department_id' => $clientValue->service->department_id,
                //         'doctor_contribution_price' => $resTotal,
                //         'total_price' =>   $totalPrice,
                //         'item_doctor_contribution_price' => $doctorContributionPrice,
                //     ];
                //     return $resTotal;
                // });
                // $total_kounteragent_contribution_price = $clietValue->sum(function ($clientValue) use ($setting, $contribution_history) {
                //     $doctorContributionPrice = $clientValue->service->kounteragent_contribution_price ?? 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                //     $qty = $clientValue->qty;
                //     $totalPrice = $clientValue->total_price;

                //     if ($setting->is_contribution_kounteragent) {
                //         $totalPrice = ($clientValue->discount <= 100)
                //             ? $clientValue->total_price  -  ($clientValue->total_price / 100) * $clientValue->discount
                //             : $clientValue->total_price - ($clientValue->discount);
                //     }

                //     // Hisoblash logikasi
                //     $resTotal =  ($doctorContributionPrice <= 100)
                //         ? ($totalPrice * $doctorContributionPrice / 100)
                //         : ($doctorContributionPrice * $qty);
                //     $contribution_history[] = [
                //         'service_id' => $clientValue->service->id,
                //         'department_id' => $clientValue->service->department_id,
                //         'kounteragent_contribution_price' => $resTotal,
                //         'total_price' =>   $totalPrice,
                //         'item_kounteragent_contribution_price' => $doctorContributionPrice,
                //     ];
                //     return $resTotal;
                // });
                // $total_kounteragent_doctor_contribution_price = $clietValue->sum(function ($clientValue) use ($client, $setting, $contribution_history) {
                //     $doctorContributionPrice = 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                //     if ($clientValue->service->kounteragent_doctor_contribution_price > 0) {
                //         $doctorContributionPrice =  $clientValue->service->kounteragent_doctor_contribution_price ?? 0;
                //     } else {
                //         $doctorContributionPrice = ReferringDoctorServiceContribution::where([
                //             'ref_doc_id' => $client->referring_doctor_id,
                //             'service_id' => $clientValue->service->id
                //         ])->first()->contribution_price ?? 0;
                //     }
                //     Log::info('   $doctorContributionPrice', [$doctorContributionPrice]);
                //     $qty = $clientValue->qty;
                //     $totalPrice = $clientValue->total_price;

                //     if ($setting->is_contribution_kt_doc) {
                //         $totalPrice = ($clientValue->discount <= 100)
                //             ? $totalPrice  -  ($totalPrice / 100) * $clientValue->discount
                //             : $totalPrice - ($clientValue->discount);
                //     }

                //     $resTotal = ($doctorContributionPrice <= 100)
                //         ? ($totalPrice * $doctorContributionPrice / 100)
                //         : ($doctorContributionPrice * $qty);
                //     $contribution_history[] = [
                //         'service_id' => $clientValue->service->id,
                //         'department_id' => $clientValue->service->department_id,
                //         'kounteragent_doctor_contribution_price' => $resTotal,
                //         'total_price' =>   $totalPrice,
                //         'item_kounteragent_doctor_contribution_price' => $doctorContributionPrice,
                //     ];
                //     // Hisoblash logikasi
                //     return $resTotal;
                // });
                $kk =   ReferringDoctorBalance::create([
                    'client_id' => $client->id,

                    'is_statsionar' => $is_statsionar,
                    'service_count' =>   $clietValue->sum('qty'),
                    'referring_doctor_id' => $client->referring_doctor_id,
                    'total_price' =>   $clietValue->sum('total_price'),
                    'total_doctor_contribution_price' => $total_doctor_contribution_price,
                    'total_kounteragent_contribution_price' => $total_kounteragent_contribution_price,
                    'total_kounteragent_doctor_contribution_price' =>  $total_kounteragent_doctor_contribution_price,
                    'contribution_history' => json_encode($contribution_history),
                    // 'total_doctor_contribution_price' =>  $clietValue->sum(function ($clientValue) use ($setting) {
                    //     $doctorContributionPrice = $clientValue->service->doctor_contribution_price ?? 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                    //     $qty = $clientValue->qty;

                    //     $totalPrice = $clientValue->total_price;

                    //     // Hisoblash logikasi
                    //     return ($doctorContributionPrice <= 100)
                    //         ? ($totalPrice * $doctorContributionPrice / 100)
                    //         : ($doctorContributionPrice * $qty);
                    // }),
                    // 'total_kounteragent_contribution_price' =>  $clietValue->sum(function ($clientValue) use ($setting) {
                    //     $doctorContributionPrice = $clientValue->service->kounteragent_contribution_price ?? 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                    //     $qty = $clientValue->qty;
                    //     $totalPrice = $clientValue->total_price;
                    //     if ($setting->is_contribution_kounteragent) {
                    //         $totalPrice = ($clientValue->discount <= 100)
                    //             ? $clientValue->total_price  -  ($clientValue->total_price / 100) * $clientValue->discount
                    //             : $clientValue->total_price - ($clientValue->discount);
                    //     }
                    //     // Hisoblash logikasi
                    //     return ($doctorContributionPrice <= 100)
                    //         ? ($totalPrice * $doctorContributionPrice / 100)
                    //         : ($doctorContributionPrice * $qty);
                    // }),
                    // 'total_kounteragent_doctor_contribution_price' =>  $clietValue->sum(function ($clientValue) use ($client, $setting) {
                    //     $doctorContributionPrice = 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                    //     if ($clientValue->service->kounteragent_doctor_contribution_price > 0) {
                    //         $doctorContributionPrice =  $clientValue->service->kounteragent_doctor_contribution_price ?? 0;
                    //     } else {
                    //         $doctorContributionPrice = ReferringDoctorServiceContribution::where([
                    //             'ref_doc_id' => $client->referring_doctor_id,
                    //             'service_id' => $clientValue->service->id
                    //         ])->first()->contribution_price ?? 0;
                    //     }
                    //     $qty = $clientValue->qty;
                    //     $totalPrice = $clientValue->total_price;
                    //     if ($setting->is_contribution_kt_doc) {
                    //         $totalPrice = ($clientValue->discount <= 100)
                    //             ? $totalPrice  -  ($totalPrice / 100) * $clientValue->discount
                    //             : $totalPrice - ($clientValue->discount);
                    //     }
                    //     // Hisoblash logikasi
                    //     return ($doctorContributionPrice <= 100)
                    //         ? ($totalPrice * $doctorContributionPrice / 100)
                    //         : ($doctorContributionPrice * $qty);
                    // }),
                    'date' => $client->created_at,
                ]);
                Log::info('$kk------->', [$kk]);
            }
            $totalCliet =  ReferringDoctorBalance::where([
                'referring_doctor_id' => $client->referring_doctor_id
            ])
                // ->where('total_price', '>', 0)
            ;
            $referring_doctor->update([
                'client_count' => $totalCliet->count(),
                'total_price' => $totalCliet->sum('total_price'),
                'doctor_contribution_price' => $totalCliet->sum('total_doctor_contribution_price'),
                'kounteragent_contribution_price' => $totalCliet->sum('total_kounteragent_contribution_price'),
                'kounteragent_doctor_contribution_price' => $totalCliet->sum('total_kounteragent_doctor_contribution_price'),
            ]);
        }
    }
    // doctor uchun
    public function doctorBalanceAdd($client, $request)
    {
        $is_statsionar = 0;
        if (isset($request->is_statsionar) && $request->is_statsionar == 1) {
            $is_statsionar = 1;
        }
        $setting = DirectorSetting::where('user_id', auth()->user()->owner_id)->first();
        $dailyRepot = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'start'])
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->first();
        if (!$dailyRepot) {
            $batch_number  = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'finish'])->whereDate('created_at', now()->format('Y-m-d'))->max('batch_number') ?? 0;
            DailyRepot::create([
                'user_id' => auth()->id(),
                'status' => 'start',
                'batch_number' => $batch_number + 1
            ]);
        }
        $dailyRepot = DailyRepot::where(['user_id' => auth()->id(), 'status' => 'start'])
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->first();
        $clietValue = ClientValue::where(['client_id' => $client->id, 'is_active' => 1, 'is_pay' => 1])
            ->selectRaw('
           department_id,
            GROUP_CONCAT(DISTINCT service_id) as service_data,
            GROUP_CONCAT(
            DISTINCT CONCAT("[", service_id, ",", total_price, ",", id, ",", qty, ",", discount, ",",
    price, "]")
            ) AS data

        ')
            ->groupBy('department_id')
            ->get();
        DoctorBalance::whereNotIn('department_id', $clietValue->pluck('department_id'))
            ->where('client_id', $client->id)
            ->where(function ($query) use ($is_statsionar) {
                if ($is_statsionar == 1) {
                    $query->where('is_statsionar', 1);
                } else {
                    $query->where('is_statsionar', 0)
                        ->orWhereNull('is_statsionar');
                }
            })
            ->delete();
        Log::info('clietValue_doctor_balance', [$clietValue]);
        if ($clietValue->count() > 0) {
            foreach ($clietValue as $value) {
                $service_data = json_decode("[" . $value->service_data . "]");
                $all_data = collect(json_decode("[" . $value->data . "]"));
                Log::info('$all_data', [$all_data]);
                $service = Services::whereIn('id', $service_data)->get();
                $totalPrice = 0;
                $serviceCount = 0;
                $contribution_price = 0;
                $contribution_data = [];
                foreach ($service as $key => $item) {
                    $foundItem = ($all_data)->filter(function ($q) use ($item) {
                        return $q[0] === $item->id;
                    });
                    if ($setting->is_contribution_doc) {
                        // $totalPrice = ($clientValue->discount <= 100)
                        //     ? $totalPrice  -  ($totalPrice / 100) * $clientValue->discount
                        //     : $totalPrice - ($clientValue->discount);
                        $foundItemTotalPrice = $foundItem
                            ->sum(function ($item) {
                                $totalP = $item[1];
                                $qtyP = $item[3];
                                $discountP = $item[4];
                                return ($discountP <= 100)
                                    ? $totalP  - (($totalP / 100) * $discountP)
                                    : $totalP - ($discountP);
                            });
                        //     return ($item[4] <= 100)
                        //         ? $item[1]  - (($item[1] / 100) * $item[4])
                        //         : $item[1] - ($item[4] * $item[3]);
                        // });
                    } else {

                        $foundItemTotalPrice = $foundItem
                            ->sum(function ($item) {
                                return $item[1];
                            });
                    }


                    $totalPrice = $totalPrice + $foundItemTotalPrice;
                    $serviceTargteCount =  $foundItem
                        ->sum(function ($item) {
                            return $item[3];
                        });
                    $serviceCount = $serviceCount + $serviceTargteCount;
                    Log::info('foundItem', [$foundItem]);
                    $contribution_price += $item->doctor_contribution_price <= 100 ? ($foundItemTotalPrice / 100) * $item->doctor_contribution_price : $item->doctor_contribution_price * $serviceTargteCount;
                    $contribution_data[] = [

                        'service_id' => $item->id,
                        'service_count' => $serviceTargteCount,
                        'price' => $item->is_change_price ? $all_data->first()[5] :  $item->price,
                        'doctor_contribution_price' => $item->doctor_contribution_price,
                        'total_price' => $foundItemTotalPrice,
                        'total_doctor_contribution_price' => $item->doctor_contribution_price <= 100 ? ($foundItemTotalPrice / 100) * $item->doctor_contribution_price : $item->doctor_contribution_price * $serviceTargteCount
                    ];
                }
                $doctor  = User::where('role', User::USER_ROLE_DOCTOR)
                    ->where('owner_id', auth()->user()->owner_id)
                    ->where('department_id', $value->department_id)
                    ->first();
                $findRef = DoctorBalance::where(['client_id' => $client->id, 'department_id' => $value->department_id])
                    ->where(function ($query) use ($is_statsionar) {
                        if ($is_statsionar == 1) {
                            $query->where('is_statsionar', 1);
                        } else {
                            $query->where('is_statsionar', 0)
                                ->orWhereNull('is_statsionar');
                        }
                    })
                    ->first();
                if ($findRef) {
                    $findRef->update([

                        'total_price' =>   $totalPrice,
                        'doctor_id' =>   $doctor->id ?? 0,
                        'service_count' =>   $serviceCount,
                        'total_doctor_contribution_price' =>  $contribution_price,
                        'date' => $client->created_at,
                        'is_statsionar' => $is_statsionar,
                        'contribution_data' => json_encode($contribution_data),
                    ]);
                } else {
                    DoctorBalance::create([
                        'daily_repot_id' => $dailyRepot->id,
                        'is_statsionar' => $is_statsionar,
                        'client_id' => $client->id,
                        'department_id' => $value->department_id,
                        'total_price' =>   $totalPrice,
                        'doctor_id' =>   $doctor->id ?? 0,
                        'service_count' =>   $serviceCount,
                        'contribution_data' => json_encode($contribution_data),
                        'total_doctor_contribution_price' =>  $contribution_price,
                        'date' => $client->created_at,
                    ]);
                }
            }
        }
    }

    public function clientDepartmentCount($id)
    {
        $client = Client::find($id);
        $clientValue = ClientValue::where(['client_id' => $id, 'is_active' => 1])
            ->pluck('department_id')
            ->unique()->count();
        $clientResult = ClientResult::where(['client_id' => $id, 'is_check_doctor' => 'finish'])
            ->pluck('department_id')
            ->unique()->count();
        $client->update([
            'finish_department_count' => $clientResult,
            'department_count' => $clientValue,
        ]);
    }

    // qarz va chegirmalar
    public function clientAllData($request)
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
        $per_page = $request->per_page ?? 50;
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
        $userId = [];
        if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
            if ($branch_id == 'all') {
                // $q->whereIn('owner_id', $branchAllId);
                $userId = User::whereIn('owner_id', $branchAllId)->pluck('id');
            } else
            if ($branch_id > 0) {
                // $q->where('owner_id', $branch_id);
                $userId = User::whereIn('owner_id', [$branch_id])->pluck('id');
            } else {
                $userId = User::where('owner_id', auth()->user()->id)->pluck('id');
            }
        } else {
            $userId = User::where('owner_id', auth()->user()->owner_id)->pluck('id');
        }
        $client = Client::whereNotNull('parent_id')
            ->whereIn('user_id', $userId)
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
            ->where(function ($q) use ($request, $startDate, $endDate) {

                if (isset($request->status) && $request->status == 'debt') {
                    if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                        $q
                            ->whereDate('created_at', $endDate->format('Y-m-d'));
                    } else {
                        $q
                            ->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);
                    }
                    $q->whereRaw('total_price - pay_total_price - discount > 0');
                }
                if (isset($request->status) && $request->status == 'discount') {
                    if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                        $q
                            ->whereDate('created_at', $endDate->format('Y-m-d'));
                    } else {
                        $q
                            ->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);;
                    }
                    $q->where('discount', '>', 0);
                }
            })
            ->with(['clientPayment', 'clientValue', 'balance:id,balance', 'user.owner'])
            ->paginate($per_page);
        return [
            'data' => $client->items(),
            'total' => $client->total(),
            'per_page' => $client->perPage(),
            'current_page' => $client->currentPage(),
            'last_page' => $client->lastPage(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }

    // ximmatlardagi maxsulotni ishlatish
    public function clientProductCount($id)
    {
        $client = Client::find($id);
        $clientValue = ClientValue::where([
            'client_id' => $id,
            //  'is_active' => 1
        ])

            ->with(['service.serviceProduct.productReceptionItem' => function ($q) {

                $q
                    ->with(['productReception' => function ($q) {
                        $q

                            ->whereIn(
                                'user_id',
                                User::where('owner_id', auth()->user()->owner_id)
                                    ->pluck('id')
                            )
                            ->orWhere('user_id', auth()->user()->id)
                        ;
                    }])
                    ->whereRaw('qty - IFNULL(use_qty, 0) > 0')
                    // ->orderBy('created_at', 'desc')
                    ->orderBy('expiration_date', 'asc')

                ;
            }])->get();
        Log::info('v------------>',   [$clientValue]);
        foreach ($clientValue as $item) {
            $serivceQty = $item->qty; ///servis ishlatishi kerak soni
            $serviceProducts = $item->service->serviceProduct;
            foreach ($serviceProducts as $productItem) {
                $productReceptionItems = $productItem->productReceptionItem
                    ->filter(function ($item) {
                        return isset($item->productReception->id);
                    });
                if ($productReceptionItems->count() > 0) {
                    $productItemQty = $productItem->qty; ///ishlatishi kerak bolgan maxsulot soni
                    $useAll = ClientUseProduct::where([
                        // 'product_reception_item_id',
                        'product_id' => $productItem->product_id,
                        'client_value_id' => $item->id,
                        'service_id' => $item->service->id,
                        'client_id' => $item->client_id,
                    ])
                        ->get();
                    // tolov qilsa 
                    if ($item->is_pay && $item->is_active) {
                        $useAllQty = $useAll->sum('qty') ?? 0;
                        $useQty = $serivceQty * $productItemQty; ///ishlatishi kerak bolgan qiymat
                        if ($useAllQty <= $useQty) {
                            $deficit_quantity = $useQty - $useAllQty;
                            foreach (
                                $productReceptionItems->filter(function ($q) {
                                    return Carbon::parse($q->expiration_date)->format('Y-m-d') >= Carbon::now()->format('Y-m-d'); //// bugundan eskiragni olmaydi
                                }) as $key => $productReceptionItem
                            ) {
                                if ($deficit_quantity >= $productReceptionItem->qty - $productReceptionItem->use_qty) {
                                    $qty = $productReceptionItem->qty - $productReceptionItem->use_qty;
                                } else {
                                    $qty = $deficit_quantity;
                                }
                                // if ($qty == 0) {
                                //     break;
                                // }
                                if ($qty > 0) {
                                    ClientUseProduct::create([
                                        'product_id' => $productItem->product_id,
                                        'product_category_id' => $productReceptionItem->product_category_id,
                                        'client_id' => $client->id,
                                        'client_value_id' => $item->id,
                                        'service_id' => $item->service->id,
                                        'product_reception_item_id' => $productReceptionItem->id,
                                        'qty' =>   $qty,
                                    ]);
                                    $productReceptionItemdd =  ProductReceptionItem::find($productReceptionItem->id);

                                    $productReceptionItemdd->update([
                                        'use_qty' => $productReceptionItemdd->use_qty + $qty
                                    ]);
                                }

                                $deficit_quantity   = $deficit_quantity - $qty;
                            }
                        }
                    } else {
                        // atkaz bolsa 
                        if ($useAll->count() > 0) {
                            foreach ($useAll as  $useAllItem) {
                                $useProductReceptionItem =  ProductReceptionItem::find($useAllItem->product_reception_item_id);
                                $useProductReceptionItem->update([
                                    'use_qty' => $useProductReceptionItem->use_qty - $useAllItem->qty
                                ]);
                                ClientUseProduct::find($useAllItem->id)->delete();
                            }
                        }
                    }
                }
            }
        }
    }


    // referdoctor chanage
    public function referringDoctorChange($grapachive, $find, $result, $request)
    {



        if ($grapachive) {
            $client_id  = [];
            if ($grapachive->client_id > 0) {
                $client_id = Client::where('id', '>=', $grapachive->client_id)->pluck('id');
            }
            if (count($client_id) == 0) {
                ReferringDoctorChangeArchive::create([
                    'client_id' => $request->id,
                    'from_referring_doctor_id'  => $result->referring_doctor_id,
                    'to_referring_doctor_id' => $request->referring_doctor_id,
                ]);
                $grapachive->update([
                    'referring_doctor_id' => $request->referring_doctor_id
                ]);
            } else {


                Client::whereIn('id', $client_id)->update([
                    'referring_doctor_id' => $request->referring_doctor_id
                ]);
                //    hisoblangan va tolnagan mablaglar alamsadhi
                ReferringDoctorBalance::whereIn('client_id', $client_id)->where('referring_doctor_id', $result->referring_doctor_id)->update([
                    'referring_doctor_id' => $request->referring_doctor_id
                ]);
                // tolanag mablag istoriyasi
                ReferringDoctorPay::where('referring_doctor_id', $result->referring_doctor_id)
                    ->update([
                        'referring_doctor_id' => $request->referring_doctor_id
                    ]);
                $grapachive->update([
                    'referring_doctor_id' => $request->referring_doctor_id
                ]);

                foreach ($client_id as $item) {
                    ReferringDoctorChangeArchive::create([
                        'client_id' => $item,
                        'from_referring_doctor_id'  => $result->referring_doctor_id,
                        'to_referring_doctor_id' => $request->referring_doctor_id,
                    ]);
                }
            }
            // if(count($client_id) > 0){
            //     $clientStart = Client::find($client_id[0]-1);
            //     if($clientStart ){
            //         ReferringDoctorChangeArchive::create([
            //             'client_id' => $clientStart->id,
            //             'from_referring_doctor_id'  => $result->referring_doctor_id,
            //             'to_referring_doctor_id' => $request->referring_doctor_id,
            //         ]);
            //         $clientStart->update([
            //             'referring_doctor_id' => $request->referring_doctor_id
            //         ]);

            //     }
            // }

        } else {
            ReferringDoctorChangeArchive::create([
                'client_id' => $request->id,
                'from_referring_doctor_id'  => $result->referring_doctor_id,
                'to_referring_doctor_id' => $request->referring_doctor_id,
            ]);
        }
    }

    public function referringDoctorAdd($find, $id, $referring_doctor_id)
    {
        $result = Client::find($id);
        if (is_int($referring_doctor_id) && $referring_doctor_id > 0) {
            if ($find->use_status != 'at_home' || $find->use_status != 'treatment') {
                $find->update([
                    'referring_doctor_id' => $referring_doctor_id
                ]);
                $result->update([
                    'referring_doctor_id' => $referring_doctor_id
                ]);
            }
        } else {
            if ($find->use_status == 'at_home' || $find->use_status == 'treatment') {
                $result->update([
                    'referring_doctor_id' => $find->referring_doctor_id
                ]);
            } else {
                $find->update([
                    'referring_doctor_id' => 0
                ]);
                $result->update([
                    'referring_doctor_id' => 0
                ]);
            }
        }

        return $result;
    }


    // all-client --->kunterdoktor uchun
    public function counterpartyAllClient($request)
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
        $per_page = $request->per_page ?? 10;
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

        $data = Client::whereNotNull('parent_id')
            ->where(function ($q) use ($branch_id, $branchAllId) {
                if (auth()->user()->role == User::USER_ROLE_DIRECTOR) {
                    // // // $q
                    // // //     ->where('referring_doctor_id', ReferringDoctor::whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))->pluck('id'));
                    // // User::where(function ($q) {
                    // //     $q->where('owner_id', auth()->user()->id);
                    // // })->pluck('id');
                    // $worker =  User::where([
                    //     'owner_id' => auth()->id()
                    //     // , 'role' => User::USER_ROLE_COUNTERPARTY
                    // ])->pluck('id');
                    // // $referringDoctor = ReferringDoctor::whereIn('user_id', $worker)->pluck('id');

                    // // $q
                    // //     ->whereIn(
                    // //         'referring_doctor_id',
                    // //         $referringDoctor
                    // //     );
                    // $q->whereIn(
                    //     'user_id',
                    //     User::where('owner_id', auth()->user()->id)->pluck('id')
                    // );
                    if ($branch_id == 'all') {
                        $q->whereIn('user_id', User::whereIn('owner_id',  $branchAllId)->pluck('id'));
                    } else
                    if ($branch_id > 0) {
                        $q->whereIn('user_id', User::where('owner_id', $branch_id)->pluck('id'));
                    } else {
                        $q->whereIn(
                            'user_id',
                            User::where('owner_id', auth()->user()->id)->pluck('id')
                        );
                    }
                } else {
                    $q->whereIn('referring_doctor_id', ReferringDoctor::where('user_id', auth()->user()->id)->pluck('id'));
                }
            })


            ->with(['referringDoctor:id,last_name,first_name', 'clientValue.service', 'user.owner'])
            ->where(function ($query) use ($startDate, $endDate, $request) {
                if (isset($request->all) && $request->all == 1) {
                    if (isset($request->full_name)) {
                        $query->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                    }
                    if (isset($request->phone)) {
                        $query->where('phone', 'like', '%' . $request->phone . '%');
                    }
                    if (isset($request->ref_full_name)) {
                        $query->whereHas('referringDoctor', function ($query) use ($request) {
                            $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$request->ref_full_name}%");
                        });
                    }
                } else {
                    if ($startDate->format('Y-m-d') == $endDate->format('Y-m-d')) {
                        $query->whereDate('created_at', $startDate);
                    } else {
                        $query->whereBetween('created_at', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')]);;
                    }
                }
            })

            ->paginate($per_page);
        return [
            'data' => $data->items(),
            'last_page' => $data->lastPage(),
            'current_page' => $data->currentPage(),
            'per_page' => $data->perPage(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }

    // statsianar
    public function statsianar($request)
    {
        $startDate = now();
        $endDate = now();
        $user = auth()->user();
        $per_page = $request->per_page ?? 50;
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
        $data = Client::whereNotNull('parent_id')
            ->where(function ($q) use ($user) {
                if ($user->role == User::USER_ROLE_DIRECTOR) {
                    $q->whereIn('user_id', User::where('owner_id', $user->id)->pluck('id'));
                } else {
                    $q->whereIn('user_id', [$user->id]);
                }
            })
            ->where('is_statsionar', 1)
            ->when(isset($request->phone), function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->phone . '%');
            })
            ->when(isset($request->full_name), function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->full_name . '%');
            })
            ->when(isset($request->doctor_id), function ($q) use ($request) {
                $q->where('statsionar_doctor_id', $request->doctor_id);
            })

            ->where(function ($q) use ($startDate, $endDate) {
                if ($date = $startDate->format('Y-m-d') === $startDate->format('Y-m-d')) {
                    // $q->whereDate('created_at', $startDate->format('Y-m-d'));
                } else {
                    // $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);;
                }
            })->where(function ($q) use ($request, $startDate) {
                if ($request->status == 'today') {
                    $q->whereDate('created_at', $startDate->format('Y-m-d'));
                }
                if ($request->status == 'process') {
                    $q->where('is_finish_statsionar', false)
                        ->orWhereNull('is_finish_statsionar');
                } else
                if ($request->status == 'finish') {
                    $q->where('is_finish_statsionar', 1);
                } else {

                    $q->whereDate('created_at', $startDate->format('Y-m-d'))
                        ->where('is_finish_statsionar', 0)
                        ->orwherenull('is_finish_statsionar')

                    ;
                }
            })
            ->with(['balance', 'clientValue.service.department', 'clientResult', 'statsionarRoom:id,type,number,room_index,price', 'statsionarDoctor:id,name,full_name', 'referringDoctor', 'user:id,name,full_name'])
            ->paginate($per_page);
        return [
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'per_page' => $data->perPage(),
            'last_page' => $data->lastPage(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }


    // doc uchun stationar

    public function doctorStatsianar($request)
    {
        $startDate = now();
        $endDate = now();
        $user = auth()->user();
        $per_page = $request->per_page ?? 50;
        $currentdate = now()->format('Y-m-d');
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
        }
        // if (isset($request->start_date)) {
        //     $parsedDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
        //     if ($parsedDate->format('Y-m-d') ===  $request->start_date) {
        //         $startDate = $parsedDate;
        //     }
        // }
        // if (isset($request->end_date)) {
        //     $parsedDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
        //     if ($parsedDate->format('Y-m-d') ===  $request->end_date) {
        //         $endDate = $parsedDate;
        //     }
        // }
        $data = Client::whereNotNull('parent_id')
            ->where(function ($q) use ($user) {
                $q->where('statsionar_doctor_id', auth()->id());
                // if ($user->role == User::USER_ROLE_DIRECTOR) {
                //     $q->whereIn('user_id', User::where('owner_id', $user->id)->pluck('id'));
                // } else {
                //     $q->whereIn('user_id', [$user->id]);
                // }
            })
            ->where('is_statsionar', 1)
            ->when(isset($request->phone), function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->phone . '%');
            })
            ->when(isset($request->person_id), function ($q) use ($request) {
                $q->where('person_id', 'like', '%' . $request->person_id . '%');
            })
            ->when(isset($request->full_name), function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->full_name . '%');
            })
            ->when(isset($request->doctor_id), function ($q) use ($request) {
                $q->where('statsionar_doctor_id', $request->doctor_id);
            })

            ->where(function ($q) use ($startDate, $endDate) {
                // if ($date = $startDate->format('Y-m-d') === $startDate->format('Y-m-d')) {
                //     // $q->whereDate('created_at', $startDate->format('Y-m-d'));
                // } else {
                //     // $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);;
                // }
            })->where(function ($q) use ($request, $startDate) {
                // if ($request->status == 'today') {
                //     $q->whereDate('created_at', $startDate->format('Y-m-d'));
                // }
                if ($request->status == 'process') {
                    $q->where('is_finish_statsionar', false)
                        ->orWhereNull('is_finish_statsionar');
                } else
                if ($request->status == 'finish') {
                    $q->where('is_finish_statsionar', 1);
                } else {

                    $q
                        // ->whereDate('created_at', $startDate->format('Y-m-d'))
                        ->where('is_finish_statsionar', 0)
                        ->orwherenull('is_finish_statsionar')

                    ;
                }
            })
            ->with(['balance', 'clientValue.service.department', 'clientResult', 'statsionarRoom:id,type,number,room_index,price', 'statsionarDoctor:id,name,full_name', 'referringDoctor', 'user:id,name,full_name'])
            ->paginate($per_page);
        return [
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'per_page' => $data->perPage(),
            'last_page' => $data->lastPage(),
            // 'start_date' => $startDate->format('Y-m-d'),
            // 'end_date' => $endDate->format('Y-m-d'),
        ];
    }
    // doktorni hamma mijolari

    public function doctorClientAll($request)
    {
        $startDate = now();
        $endDate = now();
        $user = auth()->user();
        $per_page = $request->per_page ?? 50;
        $currentdate = now()->format('Y-m-d');
        if (isset($request->month) && $request->month > 0) {
            $currentMonthIndex = $request->month;
            $is_all = false;
        }
        // if (isset($request->start_date)) {
        //     $parsedDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
        //     if ($parsedDate->format('Y-m-d') ===  $request->start_date) {
        //         $startDate = $parsedDate;
        //     }
        // }
        // if (isset($request->end_date)) {
        //     $parsedDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
        //     if ($parsedDate->format('Y-m-d') ===  $request->end_date) {
        //         $endDate = $parsedDate;
        //     }
        // }
        $data = Client::whereNotNull('parent_id')
            ->whereHas('clientResult', function ($q) {
                $q->where('doctor_id', auth()->id());
            })
            ->orwhere('statsionar_doctor_id', auth()->id())
            // ->where('is_statsionar', 1)
            ->when(isset($request->phone), function ($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->phone . '%');
            })
            ->when(isset($request->person_id), function ($q) use ($request) {
                $q->where('person_id', 'like', '%' . $request->person_id . '%');
            })
            ->when(isset($request->sex), function ($q) use ($request) {
                $q->where('sex',   $request->sex);
            })

            ->when(isset($request->full_name), function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->full_name . '%');
            })
            ->when(isset($request->doctor_id), function ($q) use ($request) {
                $q->where('statsionar_doctor_id', $request->doctor_id);
            })
            ->where(function ($q) use ($request) {
                if (isset($request->start_age) || isset($request->start_age)) {
                    if(isset($request->start_age) || isset($request->start_age)){
                        if (($request->start_age) == $request->end_age) {
                            $q->where(DB::raw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE())"), $request->start_age);
                        } else {
                            $q->whereRaw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE()) BETWEEN ? AND ?", [$request->start_age, $request->end_age]);
                        }
                    }else{

                        $q->orwhere(DB::raw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE())"), $request->end_age)
                        ->where(DB::raw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE())"), $request->start_age)
                        ;
                    }
                   
                }
            })

            // ->where(function ($q) use ($startDate, $endDate) {
            //     // if ($date = $startDate->format('Y-m-d') === $startDate->format('Y-m-d')) {
            //     //     // $q->whereDate('created_at', $startDate->format('Y-m-d'));
            //     // } else {
            //     //     // $q->whereBetween('date', [$startDate->format('Y-m-d'),   $endDate->copy()->addDay()->format('Y-m-d')  ]);;
            //     // }
            // })->where(function ($q) use ($request, $startDate) {
            //     // if ($request->status == 'today') {
            //     //     $q->whereDate('created_at', $startDate->format('Y-m-d'));
            //     // }
            //     if ($request->status == 'process') {
            //         $q->where('is_finish_statsionar', false)
            //             ->orWhereNull('is_finish_statsionar');
            //     } else
            //     if ($request->status == 'finish') {
            //         $q->where('is_finish_statsionar', 1);
            //     } else {

            //         $q
            //             // ->whereDate('created_at', $startDate->format('Y-m-d'))
            //             ->where('is_finish_statsionar', 0)
            //             ->orwherenull('is_finish_statsionar')

            //         ;
            //     }
            // })
            // ->with(['balance', 'clientValue.service.department', 'clientResult', 'statsionarRoom:id,type,number,room_index,price', 'statsionarDoctor:id,name,full_name', 'referringDoctor', 'user:id,name,full_name'])
            ->with('clientResult')
            ->paginate($per_page);
        return [
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'per_page' => $data->perPage(),
            'last_page' => $data->lastPage(),
            // 'start_date' => $startDate->format('Y-m-d'),
            // 'end_date' => $endDate->format('Y-m-d'),
        ];
    }

    // stationar finish
    public function statsionarFinish($id, $request)
    {

        $client = Client::find($id);
        if ($request->is_delete) {
            if ($client->is_finish_statsionar) {
                Room::find($client->statsionar_room_id)->update([
                    'is_empty' => 0
                ]);
            }
            $count =  Client::where('parent_id', $client->parent_id)
                ->count();
            if ($count == 1) {
                Client::where('id', $client->parent_id)->delete();
            }
            $client->delete();
            return [];
        }
        $client->update([
            'is_finish_statsionar' => 1,
            'finish_statsionar_date' => now()->format('Y-m-d'),
        ]);
        Room::find($client->statsionar_room_id)->update([
            'is_empty' => 0
        ]);
        return [
            'data' => $client,
        ];
    }
    public function delete($id)
    {
        $result = $this->modelClass::find($id);
        Client::where('parent_id', $result->id)->delete();
        $result->delete();
        return $result;
    }

    public function dierktorDelete($id)
    {
        $result = $this->modelClass::find($id);
        if ($result->is_finish_statsionar) {
            Room::find($result->statsionar_room_id)->update([
                'is_empty' => 0
            ]);
        }
        $all =  Client::where('parent_id', $result->parent_id);
        if ($all->count() == 1) {
            $this->modelClass::destroy($result->parent_id);
            // grafik
            Graph::where('person_id', $result->person_id)
                ->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
                ->delete();

            // grafik
            GraphArchive::where('person_id', $result->person_id)
                ->whereIn('user_id', User::where('owner_id', auth()->user()->id)->pluck('id'))
                ->delete();
            // // kassaga tolagna pulini hobilash
            // // ayrish
            // $all->delete();
        } else {
            // grafikacive
            GraphArchiveItem::where('client_id', $result->id)
                ->delete();
        }
        $dailyRepotItem = DailyRepotClient::where('client_id', $result->id)
            ->distinct('daily_repot_id')
            ->pluck('daily_repot_id');
        if ($dailyRepotItem->count() > 0) {
            foreach ($dailyRepotItem as $value) {

                if (DailyRepotClient::where('daily_repot_id', $value)->count() == 1) {
                    DailyRepot::find($value)->delete();
                } else {
                    $daily = DailyRepot::find($value);
                    $ClinetPaymet = ClinetPaymet::whereDate('created_at', $daily->created_at->format('Y-m-d'))
                        ->where('client_id', $result->id)
                        ->get();
                    Log::info($daily->created_at->format('Y-m-d'), [
                        // 'id' => $value->id,
                        'pay_total_price' => $ClinetPaymet->sum('pay_total_price'),
                        'discount' => $ClinetPaymet->sum('discount'),
                        'cash_price' => $ClinetPaymet->sum('cash_price'),
                        'card_price' => $ClinetPaymet->sum('card_price'),
                        'transfer_price' => $ClinetPaymet->sum('transfer_price'),
                    ]);
                    $daily->update([
                        'total_price' => $daily->total_price - $ClinetPaymet->sum('pay_total_price'),
                        'cash_price' => $daily->cash_price - $ClinetPaymet->sum('cash_price'),
                        'card_price' => $daily->card_price - $ClinetPaymet->sum('card_price'),
                        'transfer_price'  => $daily->transfer_price - $ClinetPaymet->sum('transfer_price'),
                    ]);
                }
            }
        }
        // koutur docotr ulushi pulini hobilash
        // $findrefpay = ReferringDoctorPay::where('referring_doctor_id', $result->referring_doctor_id)->get();
        // $findrefbalans = ReferringDoctorBalance::where('client_id', $result->id)->first()->counterparty_kounteragent_contribution_price_pay ?? 0;
        // if ($findrefpay->count() > 0) {
        //     foreach ($findrefpay as $value) {
        //         $kounteragent_doctor_contribution_price = $value->kounteragent_doctor_contribution_price;
        //         if ($findrefbalans >= $value->kounteragent_doctor_contribution_price || $value->kounteragent_doctor_contribution_price == 0) {
        //             $findrefbalans = $findrefbalans - $value->kounteragent_doctor_contribution_price;
        //             ReferringDoctorPay::find($value->id)->delete();
        //         } else {
        //             ReferringDoctorPay::find($value->id)->update([
        //                 'kounteragent_doctor_contribution_price' => $kounteragent_doctor_contribution_price - $findrefbalans
        //             ]);
        //         }
        //     }
        // }
        // kuter agent puli
        $findrefbalansagent = ReferringDoctorBalance::where('client_id', $result->id)->first()->counterparty_kounteragent_contribution_price_pay ?? 0;

        if ($all->count() == 1) {
            $all->delete();
        }
        $result->delete();


        return $id;
    }
    // sertifikat write
    public function certificate($request)
    {
        $find = ClientCertificate::find($request->id);
        $request = $request;


        if ($find) {
            if ($find->doctor_id == auth()->id() || is_null($find->doctor_id)) {
                $request['doctor_id'] = auth()->id();
                $find->update($request->all());
            }
        } else {
            $request['doctor_id'] = auth()->id();
            if (isset($request->serial_number_2)) {
                $request['serial_number_2'] = $request->serial_number_2;
            } else {
                $request['serial_number_2'] = '';
            }
            if (isset($request->serial_number_1)) {
                $request['serial_number_1'] = $request->serial_number_1;
            } else {
                $request['serial_number_1'] = '';
            }

            if (isset($request->date_1)) {
                $request['date_1'] = $request->date_1;
            } else {
                $request['date_1'] = '';
            }
            if (isset($request->date_2)) {
                $request['date_2'] = $request->date_2;
            } else {
                $request['date_2'] = '';
            }
            $find = ClientCertificate::create($request->all());
        }
        return $find;
    }
    public function certificateDownload($request)
    {
        if (isset($request->client_id)) {
            $find = $this->modelClass::find($request->client_id);
            if (!$find) {
                return [
                    'error' => true,
                    'message' => 'topilmadi',
                    'data' => null
                ];
            }
            $clientValue = ClientValue::where([
                'client_id' => $request->client_id,
                // 'department_id' => $request->department_id,
            ])->with('service')->first();
            if (!$clientValue) {
                return [
                    'error' => true,
                    'message' => 'topilmadi',
                    'data' => null
                ];
            }
            $sertif  = ClientCertificate::where([
                'client_id' => $request->client_id,
                // 'department_id' => $request->department_id
            ])
                ->with('department')
                ->get();
            $clinic_name = User::find(User::find($find->user_id)->owner_id)->name ?? '';
            $clintResult = ClientResult::where(['client_id' => $request->client_id])
                ->whereHas('department', function ($q) {
                    $q->where(['probirka' => 1]);
                })
                ->first();
            $clintValueBload =  $clintResult && $clintResult->is_check_doctor == 'finish' ?
                ClientValue::where(['client_id' => $find->id, 'is_active' => 1])
                ->whereHas('department', function ($q) {
                    $q->where(['probirka' => 1]);
                })
                ->with([
                    'laboratoryTemplateResult' => function ($q) use ($clintResult) {
                        $q->where('is_print', 1);
                    },
                    'service' => function ($q) use ($clintResult) {
                        $q->with(['laboratoryTemplate' => function ($q) use ($clintResult) {
                            $q->whereIn('id', function ($query) {
                                $query->select('laboratory_template_id')
                                    ->from('laboratory_template_results')
                                    ->whereNotNull('laboratory_template_id');
                            });
                        }, 'servicetype']);
                    }
                ])

                ->get() : [];
            $owner = User::find(User::find($find->user_id)->owner_id);


            $certificate = Departments::where(['user_id' => $owner->id, 'is_certificate' => 1])->get();
            $labaratory = Departments::where(['user_id' => $owner->id, 'probirka' => 1])->get();
            $doctor = Departments::where(['user_id' => $owner->id])
                ->where('is_certificate', 0)
                ->orWhereNull('is_certificate')
                ->where('probirka', 0)

                ->orWhereNull('probirka')
                // ->where('probirka', 0)
                //        ->where('is_certificate', '!=', 1)
                // ->where([
                //     ['is_certificate', '!=', 1],
                //     ['probirka', '!=', 1]
                // ])

                ->get();
            $clientItemCertificate = Client::whereNotNull('parent_id')->where('person_id', $find->person_id)
                ->whereHas('clientValue', function ($q) use ($certificate) {
                    $q->whereIn('department_id', $certificate->pluck('id'));
                })
                ->with(['clientValue' => function ($q) use ($certificate) {
                    $q->whereIn('department_id', $certificate->pluck('id'));
                }, 'clientCertificateAll.department'])
                ->get();
            $clientItemLaboratory = Client::whereNotNull('parent_id')->where('person_id', $find->person_id)
                ->whereHas('clientValue', function ($q) use ($labaratory) {
                    $q->whereIn('department_id', $labaratory->pluck('id'));
                })
                ->with([
                    'clientResult' => function ($q) use ($labaratory) {
                        $q->whereIn('department_id', $labaratory->pluck('id'));
                    },
                    'clientValue' => function ($q) use ($labaratory) {
                        $q->whereIn('department_id', $labaratory->pluck('id'))
                            ->with([
                                'laboratoryTemplateResult' => function ($q) {
                                    $q->where('is_print', 1);
                                },
                                'service' => function ($q) {
                                    $q->with(['laboratoryTemplate' => function ($q) {
                                        $q->whereIn('id', function ($query) {
                                            $query->select('laboratory_template_id')
                                                ->from('laboratory_template_results')
                                                ->whereNotNull('laboratory_template_id');
                                        });
                                    }, 'servicetype']);
                                }
                            ])
                        ;
                    },
                    'laboratoryTemplateResultFiles'
                ])
                ->get();
            $clientItemDoctor = Client::whereNotNull('parent_id')->where('person_id', $find->person_id)

                ->whereHas('clientValue', function ($q) use ($doctor) {
                    $q->whereIn('department_id', $doctor->pluck('id'));
                })
                ->whereHas('clientResult', function ($q) use ($doctor) {
                    $q
                        ->where('is_check_doctor', 'finish')
                        ->whereIn('department_id', $doctor->pluck('id'));
                })
                ->with([
                    'clientResult' => function ($q) use ($doctor) {
                        $q
                            ->with(['departmentFirst.departmentTemplateItem.template.templateItem.templateCategory', 'doctor:id,name,full_name'])
                            ->where('is_check_doctor', 'finish')
                            ->whereIn('department_id', $doctor->pluck('id'));
                    },
                    'templateResult',

                ])
                // 'clientResult' => function ($q) use ($labaratory) {
                //     $q->whereIn('department_id', $labaratory->pluck('id'));
                // },
                ->get();

            $clientItemCertificateFind = $clientItemCertificate->where('id', $request->client_id)->first();
            if (!$clientItemCertificateFind) {
                $clientItemCertificateFind = $clientItemCertificate->last();
            }
            $clientItemLaboratoryFind = $clientItemLaboratory->where('id', $request->client_id)->first();
            if (!$clientItemLaboratoryFind) {
                $clientItemLaboratoryFind = $clientItemLaboratoryFind->last();
            }

            $clientItemDoctorFind = $clientItemDoctor->where('id', $request->client_id)->first();
            if (!$clientItemDoctorFind) {
                $clientItemDoctorFind = $clientItemDoctor->last();
            }

            return [
                'error' => false,
                'message' => 'success',
                'data' => [
                    'certificate_service' => [
                        'count' => $clientItemCertificate->count(),
                        'target' => $clientItemCertificate->count() > 0 ? [
                            'client_certificate' => $clientItemCertificateFind->clientCertificateAll,
                            'client_value' => $clientItemCertificateFind->clientValue,
                            'date' => $clientItemCertificateFind->created_at->format('Y-m-d')
                        ] : null,
                        'date' => $clientItemCertificate->pluck('created_at', 'id')->toArray()
                    ],
                    'labaratory_service' => [
                        'count' => $clientItemLaboratory->count(),
                        'target' => $clientItemLaboratory->count() > 0 ? [
                            'files' => $clientItemLaboratoryFind->laboratoryTemplateResultFiles,
                            'client_value' => $clientItemLaboratoryFind->clientValue,
                            'is_result' => $clientItemLaboratoryFind->clientResult,
                            'date' => $clientItemLaboratoryFind->created_at->format('Y-m-d')
                        ]  : null,
                        'date' => $clientItemLaboratory->pluck('created_at', 'id')->toArray()
                    ],
                    'doctor_service' => [
                        'count' => $clientItemDoctor->count(),
                        'target' => $clientItemDoctor->count() > 0 ? [
                            'client_result' => $clientItemDoctorFind->clientResult,
                            'template_result' => $clientItemDoctorFind->templateResult,
                            'date' => $clientItemDoctorFind->created_at->format('Y-m-d')
                        ]  : null,
                        'date' => $clientItemDoctor->pluck('created_at', 'id')->toArray()
                        //$clientItemLaboratory->where('id', $request->client_id)->first()
                    ],
                    'client' => [
                        'person_id' => $find->person_id,
                        'first_name' => $find->first_name,
                        'sex' => $find->sex,
                        'data_birth' => $find->data_birth,
                        'sex' => $find->sex,
                        'clinic_name' => $clinic_name,
                        'pass_number' => $find->pass_number,
                        'sex' => $find->sex,
                        'service_name' => $clientValue->service->name,
                        'date' => $find->created_at->format('Y-m-d'),
                        'client_id' => $find->id,
                        'department_id' => $request->department_id,
                        'date' => $find->created_at->format('Y-m-d'),
                    ]


                ]
            ];
        }
        return [
            'error' => true,
            'message' => 'topilmadi',
            'data' => null
        ];
    }


    // bloodtest 
    // qon olish

    public function bloodtest($request)
    {

        $startDate = now();
        $endDate = now();
        $user =  auth()->user();
        $serviceId = [];
        $departmentId = $user->department_id;
        $per_page = $request->per_page ?? 50;
        $use_status = '';
        if (isset($request->status)) {
            $use_status = $request->status;
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
        $startDateFormat = $startDate;
        $endDateFormat = $endDate;
        $data =  $this->modelClass::where(function ($q) use ($user, $use_status) {
            $q
                ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
        })
            ->whereNull('parent_id')
            ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                $q->whereHas('clientValue', function ($q) use ($user) {
                    $q->where(['is_pay' => 1, 'is_active' => 1])
                        ->whereHas('department', function ($q) use ($user) {
                            $q->where(['id' => $user->department_id, 'probirka' => 1]);;
                        });
                });
                if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                    $q
                        ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                } else {
                    $q
                        ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                }
            })
            ->with([

                'clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                    if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                        $q
                            ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                    } else {
                        $q
                            ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    }

                    $q
                        ->with([
                            'clientResult' => function ($q) use ($user) {
                                $q->where('department_id', $user->department_id);
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                $q->where('is_pay', 1)
                                    ->whereHas('department', function ($q) use ($user) {
                                        $q->where(['id' => $user->department_id, 'probirka' => 1]);;
                                    });
                            },
                        ])
                        ->orderBy('id', 'desc')

                    ;
                }
            ])
            ->paginate($per_page);
        return [
            'data' => BloodTestClint::collection($data),
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'use_status' => $use_status
        ];
    }
    // qabul qilish qon analizga
    // bloodtestAccept
    public function bloodtestAccept($id, $request)
    {
        $find = ClientResult::find($id);
        $find->update([
            'is_check_doctor' => 'start'
        ]);
        $user =  auth()->user();
        return $this->modelClass::with([
            'clientResult' => function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            },
            'clientValue' => function ($q) use ($user) {
                $q->where('is_pay', 1)
                    ->whereHas('department', function ($q) use ($user) {
                        $q->where(['id' => $user->department_id, 'probirka' => 1]);;
                    });
            },
        ])->find($find->client_id);
    }
    // labaratiya mijozlar
    public function laboratoryClient($request)
    {

        $startDate = now();
        $endDate = now();
        $user =  auth()->user();
        $serviceId = [];
        $departmentId = $user->department_id;
        $per_page = $request->per_page ?? 50;
        $use_status = '';
        if (isset($request->status)) {
            $use_status = $request->status;
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
        $startDateFormat = $startDate;
        $endDateFormat = $endDate;
        $data =  $this->modelClass::where(function ($q) use ($user, $use_status) {
            $q
                ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
        })
            ->whereNull('parent_id')
            ->whereHas('clientItem', function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {
                $q

                    ->whereHas('clientResult', function ($q) use ($user) {
                        $q->where('department_id', $user->department_id)
                            ->whereIn('is_check_doctor', ['start', 'finish'])
                        ;
                    })
                    ->whereHas('clientValue', function ($q) use ($user) {
                        $q->where(['is_pay' => 1, 'is_active' => 1])
                            ->whereHas('department', function ($q) use ($user) {
                                $q->where(['id' => $user->department_id, 'probirka' => 1]);;
                            });
                    });
                if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                    $q
                        ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                } else {
                    $q
                        ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                }
            })
            ->with([

                'clientItem' => function ($q) use ($startDateFormat, $endDateFormat, $user, $serviceId, $request) {

                    if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                        $q
                            ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                    } else {
                        $q
                            ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                    }

                    $q
                        ->with([
                            'clientResult' => function ($q) use ($user) {
                                $q->where('department_id', $user->department_id)
                                    ->whereIn('is_check_doctor', ['start', 'finish'])
                                ;
                            },
                            'clientValue' => function ($q) use ($user, $serviceId) {
                                $q->where('is_pay', 1)
                                    ->whereHas('department', function ($q) use ($user) {
                                        $q->where(['id' => $user->department_id, 'probirka' => 1]);;
                                    })
                                    ->with([
                                        'laboratoryTemplateResult',
                                        'service' => function ($q) use ($user) {
                                            $q
                                                ->where('department_id', $user->department_id)
                                                ->with('laboratoryTemplate');
                                        }
                                    ]);;
                            },
                        ])
                        ->orderBy('id', 'desc')

                    ;
                }
            ])
            ->paginate($per_page);
        return [
            'data' => LaboratoryClientResource::collection($data),
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'use_status' => $use_status
        ];
    }

    public function laboratoryClientShow($id)
    {
        $find = $this->modelClass::find($id);
        $user = auth()->user();
        $clintResult = ClientResult::where(['client_id' => $id, 'department_id' => $user->department_id])->first();
        $clintValue = ClientValue::where('client_id', $find->id)
            ->whereHas('department', function ($q) use ($user) {
                $q->where(['id' => $user->department_id, 'probirka' => 1]);
            })
            ->with([
                'laboratoryTemplateResult',
                'service' => function ($q) use ($clintResult) {
                    $q->with(['laboratoryTemplate' => function ($q) use ($clintResult) {
                        if ($clintResult->is_check_doctor == 'finish') {
                            $q->whereIn('id', function ($query) {
                                $query->select('laboratory_template_id')
                                    ->from('laboratory_template_results')
                                    ->whereNotNull('laboratory_template_id');
                            });
                        }
                    }, 'servicetype']);
                }
            ])

            ->get();
        $service = Services::whereIn('id', $clintValue->pluck('service_id')
            ->where('user_id', $user->owner_id)
            ->unique()->toArray())
            ->with([
                'laboratoryTemplate',
                'servicetype'
            ])
            ->get();

        return [
            'client' => $find,
            'client_value' => $clintValue,
            'client_result' => $clintResult,
            'files' => LaboratoryTemplateResultFiles::where(['client_id' => $id, 'user_id' => $user->id])->get(),
        ];
    }
    public function laboratoryClientSave($id, $request)
    {
        $request = $request;
        $user = auth()->user();
        $data = json_decode($request?->laboratory_template_result);
        if (count($data) > 0) {
            // LaboratoryTemplateResult::whereNotIn('id', collect($data)->filter(function ($item) {
            //     return isset($item->id) ?  is_int($item->id)  : false;
            // })->pluck('id'))->delete();
            foreach ($data as $item) {
                $find = LaboratoryTemplateResult::where([
                    'service_id' => $item->service_id,
                    'laboratory_template_id' => $item->laboratory_template_id,
                    'client_value_id' => $item->client_value_id,
                    'client_id' => $id,
                ])->first();
                if ($find) {
                    $find->update([
                        'client_id' => $id,
                        'name' => $item->name ?? $find->name,
                        'result' => $item->result ?? $find->result,
                        'normal' => $item->normal ?? $find->normal,
                        'extra_column_1' => $item->extra_column_1 ?? $find->extra_column_1,
                        'extra_column_2' => $item->extra_column_2 ?? $find->extra_column_2,
                        'is_print' => $item->is_print ?? $find->is_print,
                        'service_id' => $item->service_id ?? $find->service_id,
                        'laboratory_template_id' => $item->laboratory_template_id ?? $find->laboratory_template_id,
                        'client_value_id' => $item->client_value_id ?? $find->client_value_id,
                        'color' => $item->color ?? $find->color,
                    ]);
                } else {
                    LaboratoryTemplateResult::create([
                        'service_id' => $item->service_id,
                        'laboratory_template_id' => $item->laboratory_template_id,
                        'client_value_id' => $item->client_value_id,
                        'client_id' => $id,
                        'is_print' => $item->is_print,
                        'user_id' => auth()->id(),
                        'name' => $item->name ?? null,
                        'result' => $item->result ?? null,
                        'normal' => $item->normal ?? null,
                        'extra_column_1' => $item->extra_column_1 ?? null,
                        'extra_column_2' => $item->extra_column_2 ?? null,
                        'color' => $item->color ?? 'black',
                    ]);
                }
            }
        }
        ClientResult::where(['client_id' => $id, 'department_id' => $user->department_id])->update(['is_check_doctor' => 'finish', 'doctor_id' => $user->id]);
        $user = auth()->user();
        return   ClientValue::where('client_id', $id)
            ->whereHas('department', function ($q) use ($user) {
                $q->where(['id' => $user->department_id, 'probirka' => 1]);
            })
            ->with([
                'laboratoryTemplateResult',
                'service' => function ($q) {
                    $q->with(['laboratoryTemplate' => function ($q) {
                        $q->whereIn('id', function ($query) {
                            $query->select('laboratory_template_id')
                                ->from('laboratory_template_results')
                                ->whereNotNull('laboratory_template_id');
                        });
                    }, 'servicetype']);
                }
            ])

            ->get();
    }

    public function laboratoryTemplateResultFilesUpdate($id, $request)
    {
        $this->modelClass = LaboratoryTemplateResultFiles::class;
        return $this->update($id, $request);
    }
    public function laboratoryTemplateResultFiles($id, $request)
    {
        // $uploadedImages = [];
        // if ($request->hasFile('images')) {
        //     foreach ($request->file('images') as $image) {
        //         $uploadPath = uploadFile($image, 'laboratory_template_result_file', null, uniqid());
        //         $uploadedImages[] = LaboratoryTemplateResultFiles::create([
        //             'user_id' => auth()->id(),
        //             'client_id' => $id,
        //             'file' => $uploadPath,
        //             'type' => $request->type,
        //         ]);
        //     }
        // }
        $request->merge(['client_id' => $id, 'user_id' => auth()->id()]);
        $this->modelClass = LaboratoryTemplateResultFiles::class;
        return [$this->store($request)];
    }



    public function laboratoryTable($request)
    {

        $startDate = now();
        $endDate = now();
        $user =  auth()->user();
        $serviceId = [];
        $departmentId = $user->department_id;
        $per_page = $request->per_page ?? 50;
        $service_type =     Servicetypes::where('department_id', $user->department_id)->get();
        if (isset($request->service_type_id) && $request->service_type_id > 0) {
            $service_type_find = Servicetypes::find($request->service_type_id);
        } else {
            $service_type_find = $service_type->first();
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
        $startDateFormat = $startDate;
        $endDateFormat = $endDate;
        $data =  $this->modelClass::whereNotNull('parent_id')

            ->where(function ($q) use ($startDateFormat, $endDateFormat) {
                $q
                    ->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'));
                if ($startDateFormat->format('Y-m-d') == $endDateFormat->format('Y-m-d')) {
                    $q
                        ->whereDate('created_at', $endDateFormat->format('Y-m-d'));
                } else {
                    $q
                        ->whereBetween('created_at', [$startDateFormat, $endDateFormat]);
                }
            })
            ->whereHas('clientResult', function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                    ->whereIn('is_check_doctor', ['start', 'finish'])
                ;
            })
            ->whereHas('clientValue', function ($q) use ($user, $service_type_find) {
                $q
                    ->whereHas('service', function ($q) use ($user, $service_type_find) {
                        $q->where('servicetype_id', $service_type_find->id ?? 0);
                    })
                    ->where(['is_pay' => 1, 'is_active' => 1])
                    ->whereHas('department', function ($q) use ($user) {
                        $q->where(['id' => $user->department_id, 'probirka' => 1]);;
                    });
            })
            ->with([
                'clientResult' => function ($q) use ($user) {
                    $q->where('department_id', $user->department_id)
                        ->whereIn('is_check_doctor', ['start', 'finish'])
                    ;
                },
                'clientValue' => function ($q) use ($user, $service_type_find) {
                    // 'service', function ($q) use ($user, $service_type_find) {
                    //         $q->where('servicetype_id', $service_type_find->id ?? 0);
                    //     }
                    $q->where('is_pay', 1)
                        ->whereHas('department', function ($q) use ($user) {
                            $q->where(['id' => $user->department_id, 'probirka' => 1]);;
                        })
                        ->whereHas('service', function ($q) use ($service_type_find) {
                            $q->where('servicetype_id', $service_type_find->id ?? 0);
                        })

                        ->with([
                            'laboratoryTemplateResult',
                            'service' => function ($q) use ($user, $service_type_find) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->with('laboratoryTemplate')
                                    ->where('servicetype_id', $service_type_find->id ?? 0)
                                ;
                            }
                        ]);;
                },
            ])
            ->paginate($per_page);
        return [
            'data' => ($data->items()),
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'service_type' => $service_type,
            'service_type_find' => [
                'label' => $service_type_find->type,
                'value' => $service_type_find->id
            ],
            'service' => Services::where('servicetype_id', $service_type_find->id)->get(['id', 'short_name']),
        ];
    }



    // table save

    public function laboratoryTableSave($request)
    {
        $request = $request;
        $user = auth()->user();
        $data = json_decode($request?->table_data);
        $clientId = [];
        if (count($data) > 0) {
            foreach ($data as $item) {
                $clientValue = ClientValue::find($item->client_value_id);
                $clientValue->update([
                    'result' => $item->result
                ]);
                if (!in_array($clientValue->client_id, $clientId)) {
                    $clientId[] = $clientValue->client_id;
                }

                $labaratoryTemplate = LaboratoryTemplate::where('service_id', $clientValue->service_id)->get();
                if (count($labaratoryTemplate) > 0) {
                    $firstHeader = $labaratoryTemplate->first();
                    foreach ($labaratoryTemplate as $key => $Ltitem) {
                        $labaratoryTemplateResult = LaboratoryTemplateResult::where(['client_value_id' => $clientValue->id, 'laboratory_template_id' => $Ltitem->id])->first();
                        $result_write =  $firstHeader->is_result_name ?? 'result';
                        Log::info('labaratoryTemplateResult', [$labaratoryTemplateResult]);
                        if ($labaratoryTemplateResult) {
                            if ($key == 0) {
                                $labaratoryTemplateResult->update([
                                    ...$Ltitem->toArray()
                                ]);
                            } else {

                                Log::info('ssss', [$firstHeader->$result_write]);
                                $labaratoryTemplateResult->update([
                                    ...$labaratoryTemplateResult->toArray(),
                                    $result_write => $item->result,
                                    'is_print' => 1
                                ]);
                            }
                        } else {
                            if ($key == 0) {
                                LaboratoryTemplateResult::create([
                                    ...$Ltitem->toArray(),
                                    'client_value_id' => $clientValue->id,
                                    'client_id' => $clientValue->client_id,
                                    'laboratory_template_id' => $Ltitem->id,
                                ]);
                            } else {
                                LaboratoryTemplateResult::create([
                                    ...$Ltitem->toArray(),
                                    $result_write => $item->result,
                                    'is_print' => 1,
                                    'laboratory_template_id' => $Ltitem->id,
                                    'client_value_id' => $clientValue->id,
                                    'client_id' => $clientValue->client_id
                                ]);
                            }
                        }
                    }
                }
            }
        }


        return    $this->modelClass::whereIn('id', $clientId)
            ->whereHas('clientValue', function ($q) use ($data, $request) {
                $q

                    ->whereHas('service', function ($q) use ($request) {
                        $q->where('servicetype_id', $request->service_type_id ?? 0);
                    });
            })
            ->with([
                'clientResult' => function ($q) use ($user) {
                    $q->where('department_id', $user->department_id)
                        ->whereIn('is_check_doctor', ['start', 'finish'])
                    ;
                },
                'clientValue' => function ($q) use ($user, $request, $data) {
                    // 'service', function ($q) use ($user, $service_type_find) {
                    //         $q->where('servicetype_id', $service_type_find->id ?? 0);
                    //     }
                    $q
                        ->where('is_pay', 1)
                        ->whereHas('department', function ($q) use ($user) {
                            $q->where(['id' => $user->department_id, 'probirka' => 1]);;
                        })
                        ->whereHas('service', function ($q) use ($request) {
                            $q->where('servicetype_id', $request->service_type_id ?? 0);
                        })

                        ->with([
                            'service' => function ($q) use ($user, $request) {
                                $q

                                    ->where('servicetype_id', $request->service_type_id ?? 0);
                            }
                        ]);;
                },
            ])->get();
    }




    // sms
    public function smsSend($id, $request)
    {
        $this->modelClass::find($id)->update([
            'is_sms' => 1
        ]);
        $user = auth()->user();
        return new LaboratoryClientItemResource($this->modelClass::whereHas('clientResult', function ($q) use ($user) {
            $q->where('department_id', $user->department_id)
                ->whereIn('is_check_doctor', ['start', 'finish'])
            ;
        })
            ->whereHas('clientValue', function ($q) use ($user) {
                $q->where(['is_pay' => 1, 'is_active' => 1])
                    ->whereHas('department', function ($q) use ($user) {
                        $q->where(['id' => $user->department_id, 'probirka' => 1]);;
                    });
            })->with([
                'clientResult' => function ($q) use ($user) {
                    $q->where('department_id', $user->department_id)
                        ->whereIn('is_check_doctor', ['start', 'finish'])
                    ;
                },
                'clientValue' => function ($q) use ($user) {
                    $q->where('is_pay', 1)
                        ->whereHas('department', function ($q) use ($user) {
                            $q->where(['id' => $user->department_id, 'probirka' => 1]);;
                        })
                        ->with([
                            'laboratoryTemplateResult',
                            'service' => function ($q) use ($user) {
                                $q
                                    ->where('department_id', $user->department_id)
                                    ->with('laboratoryTemplate');
                            }
                        ]);;
                },
            ])
            ->find($id));
    }


    public function alertSoket($id, $request)
    {
        $data = [
            'status' => 'alert',
            'result' => auth()->user()->department_id,
            'number' => $request->number,
            'room' => $request->room,
        ];
        // $this->soketSend($data);
        return [
            'success' => $request->number ?? 0
        ];
    }
    public function soketSend($data, $channel = 'queue.user')
    {
        try {
            // $domain = "http://localhost:3333";
            $domain = "https://socket-u-med.tochka24.uz";
            $response = Http::post($domain . '/broadcast', [
                'channel' =>  $channel,
                'data' => $data
            ]);
            // Check if the request was successful
            if ($response->successful()) {
                $data = $response->json();
            }
        } catch (\Exception $e) {
            Log::error("Socket broadcast error", ['message' => $e->getMessage()]);
            throw $e; // Rethrow to trigger retries
        }
    }
}
