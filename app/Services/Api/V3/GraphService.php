<?php

namespace App\Services\Api\V3;

use App\Models\Client;
use App\Models\ClientTime;
use App\Models\ClientValue;
use App\Models\Departments;
use App\Models\Graph;
use App\Models\GraphArchive;
use App\Models\GraphArchiveItem;
use App\Models\GraphItem;
use App\Models\GraphItemValue;
use App\Models\Treatment;
use App\Models\User;
use App\Services\Api\V3\Contracts\GraphServiceInterface;
use App\Traits\Crud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class GraphService implements GraphServiceInterface
{
    public $modelClass = Graph::class;
    use Crud;
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
        for ($i = 1; $i <= 30; $i++) {
            $years[] =   [
                'value' =>  $currentDate->year + $i,
                'label' =>  $currentDate->year + $i,
            ]; // Add the year to the array

        }

        return $years; // Return the array of years
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

    public function filter($request)
    {


        $user = auth()->user();
        $owner = User::find($user->owner_id);
        // return      $user->owner_id;
        $startOfWeek2 = Carbon::now()->startOfWeek(); // Haftaning boshidan
        $endOfWeek = Carbon::now()->endOfWeek(); // Haftaning oxirigacha
        // $startOfWeek = $startOfWeek2->subDay();
        $currentMonthIndex = Carbon::now()->month;
        $currentMonthYear = Carbon::now()->year;
        if (isset($request->year) && $request->year > 0) {
            $currentMonthYear = $request->year;
        }
        $startOfWeek = $startOfWeek2;
        $weekOfYear = $startOfWeek2->weekOfYear;
        if ($user->role == User::USER_ROLE_RECEPTION) {
            if (isset($request->department_id)) {
                $department = Departments::where('user_id', $user->owner_id)->find($request->department_id);
            } else {
                $department = Departments::where('user_id', $user->owner_id)->orderBy('id', 'asc')->first();
            }
        } else {
            $department = Departments::find($user->department_id);
        }
        if (isset($request->month) && $request->month > 0) {
            $firstDayOfMonth = Carbon::create($currentMonthYear, $request->month, 1);
            $currentMonthIndex = $request->month;
            $firstDayOfWeekIndex = $firstDayOfMonth->dayOfWeekIso; //
            if ($firstDayOfWeekIndex != 1) {
                $startOfWeek = $firstDayOfMonth->copy()->startOfWeek(); // Dushanba
            } else {
                $startOfWeek = $firstDayOfMonth;
            }

            $endOfWeek = $startOfWeek->copy()->endOfWeek(); // Yakshanba
            $weekOfYear = $startOfWeek->weekOfYear;
            $startOfWeek = $startOfWeek;
        }
        if (isset($request->week) && $request->week > 0) {
            $year = 2024; // You can replace it with the desired year or make it dynamic


            $weekNumber = $request->week; // Get the week number from the request
            $totalWeeksInYear = Carbon::create($currentMonthYear)->isoWeeksInYear;

            // if ($weekNumber === 0) {
            //     $currentMonthYear -= 1; // 2023 yilga o'tish
            //     $weekNumber = Carbon::create($currentMonthYear)->isoWeeksInYear; //
            // }else
            if ($weekNumber > $totalWeeksInYear) {
                $currentMonthYear += 1; // Keyingi yil
                $weekNumber = 1;
            }


            $startOfWeek2 = Carbon::now()->setISODate($currentMonthYear, $weekNumber)->startOfWeek(); // Start of the specified week
            $endOfWeek = Carbon::now()->setISODate($currentMonthYear, $weekNumber)->endOfWeek();
            // $startOfWeek = $startOfWeek2->subDay();
            $startOfWeek = $startOfWeek2;
            $currentMonthIndex = $startOfWeek2->month;
            $weekOfYear = $endOfWeek->weekOfYear;    // End of the specified week
        }

        $weekArray = []; // Initialize an empty array
        $uzbekDays = [
            1 => 'Du', // Dushanba
            2 => 'Se', // Seshanba
            3 => 'Ch', // Chorshanba
            4 => 'Pa', // Payshanba
            5 => 'Ju', // Juma
            6 => 'Sh', // Shanba
            7 => 'Ya', // Yakshanba
            // 1 => 'Dushanba',
            // 2 => 'Seshanba',
            // 3 => 'Chorshanba',
            // 4 => 'Payshanba',
            // 5 => 'Juma',
            // 6 => 'Shanba',
            // 7 => 'Yakshanba',
        ];
        // Loop through each day from start to end of the week
        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $dayIndex = $date->dayOfWeekIso; // Get day index (1 = Monday, 7 = Sunday)
            $dayName = $date->format('l'); // Get full day name
            $formattedDate = $date->format('d.m.Y'); // Format date as d.m.Y
            $filterdate = $date->format('Y-m-d'); // Format date as d.m.Y
            // 2024-10-14
            $uzbekDayName = $uzbekDays[$dayIndex]; // Get Uzbek day name
            // Push the day details into the array
            // $weekArray[] = [$dayIndex, $dayName, $formattedDate, $filterdate, $uzbekDayName];
            $weekArray[] = [
                'filter_date' => $filterdate,
                'week_day' => $uzbekDayName,
                'date' => $filterdate,
                'day_index' => $dayIndex,

            ];
        }

        LOg::info($department);
        if ($department) {
            return [
                'weekcount' => $weekOfYear,
                'week_data' => $weekArray,
                'year' => $this->getYearsFromDate($owner->created_at->format('d.m.Y')),
                'department' => [
                    'value' => $department->id,
                    'label' => $department->name,
                    'data' => $department
                ],
                'current_year' => [
                    'value' => $currentMonthYear,
                    'label' => $currentMonthYear,
                    'data' => $currentMonthYear
                ],
                'date' => $endOfWeek->format('d.m.Y'),
                'month' => $this->getMonthByIndex($currentMonthIndex),
                'data' => $this->modelClass::
                    // where('user_id', auth()->id())
                    //     ->
                    whereHas('graphItem', function ($q) use ($startOfWeek, $endOfWeek, $department) {
                        // if (auth()->user()->role === User::USER_ROLE_DOCTOR) {
                        // $q
                        //     ->where('department_id', $department->id);
                        // }
                        $q
                            ->where('department_id', $department->id)
                            ->whereBetween('agreement_date', [$startOfWeek->subDay(), $endOfWeek])

                            ->with(['department', 'graphItemValue.service']);
                    })
                    ->with([
                        'department',
                        'graphItem' => function ($q) use ($startOfWeek, $endOfWeek, $department) {
                            // graphItem ichida agreement_date ustuni bo'yicha filtrlaymiz
                            // if (auth()->user()->role === User::USER_ROLE_DOCTOR) {
                            //     $q
                            //         ->where('department_id', $department->id);
                            // }
                            $q
                                ->where('department_id', $department->id)
                                ->whereBetween('agreement_date', [$startOfWeek->subDay(), $endOfWeek])

                                ->with(['department', 'graphItemValue.service']);
                        },
                        'graphArchive.treatment.treatmentServiceItem.service.department',
                    ])
                    ->get()
            ];
        }
        return [];
    }

    // ishkuni yoki kish kuni emasligini tekshirish
    function isWorkingDay($date, $workingDays)
    {
        $currentdate = Carbon::createFromFormat('Y-m-d', $date);

        $dayNumber = $currentdate->dayOfWeekIso;
        foreach ($workingDays as $day) {
            if ($day['value'] == $dayNumber) {
                return $day['is_working'];
            }
        }
        return  false;
    }
    // vaqtlarn ligrofkasi 
    // Function to split time into blocks
    public function splitTimeIntoBlocks($start, $end, $intervalInMinutes, $date)
    {
        $blocks = [];
        $currentTime = $start->copy();
        $now = now(); // Get the current time

        while ($currentTime < $end) {
            if ($date == now()->format('Y-m-d')) {
                if ($currentTime >= $now) {
                    $blocks[] = $currentTime->format('H:i');  // Format the time as `HH:MM`
                }
            } else {
                $blocks[] = $currentTime->format('H:i');  // Format the time as `HH:MM`
            }
            $currentTime->addMinutes($intervalInMinutes);  // Increment by the given interval
        }

        return $blocks;
    }


    public function workingDateCheck($request)
    {
        $user = auth()->user();
        $department = Departments::where('user_id', $user->owner_id)->find($request->department_id);
        $daysArray = json_decode($department->working_days ?? "[]", true);
        $checkDate = $request->date;
        if ($checkDate == now()->format('Y-m-d') && $department->work_end_time <= now()->format('H:i')) {
            return [
                'is_working' => false,
                'end_time' => true,
            ];
        }
        $is_work = $this->isWorkingDay($checkDate, $daysArray);
        if ($is_work) {
            $workStartTime = $department->work_start_time; // Assuming format '08:00'
            $workEndTime = $department->work_end_time;        // Assuming format '22:00'
            $duration = $department->duration;


            $workStart = Carbon::createFromFormat('H:i', $workStartTime);
            $workEnd = Carbon::createFromFormat('H:i', $workEndTime);


            // Call the time-splitting function to get duration-minute blocks within work hours
            $timeBlocks = $this->splitTimeIntoBlocks($workStart, $workEnd, $duration, $checkDate);
            // if (isset($request->is_reg)) {
            $graphItems  = GraphItem::where(function ($query) {
                // Ensure 'agreement_time' has a valid format (HH:MM)
                $query->where('agreement_time', 'REGEXP', '^[0-9]{2}:[0-9]{2}$');
            })
                ->when($checkDate, function ($query, $checkDate) use ($request) {
                    // Optionally filter by date if provided

                    if (isset($request->is_reg)) {
                        $query->whereDate('agreement_date', now()->format('Y-m-d'));
                    } else {
                        $query->where('agreement_date', $checkDate);
                    }
                })
                ->get();
            $clientTime = ClientTime::where(['department_id' => $request->department_id, 'is_active' => 1])
                ->whereHas('client', function ($query) use ($checkDate) {
                    $query->whereDate('created_at', $checkDate);
                })
                ->get();
            Log::info('sadsad', [$clientTime]);
            if ($clientTime->count() == 0 && $graphItems->count() == 0) {

                return [
                    'is_working' => true,
                    'data' => $timeBlocks
                ];
            } else {
                $agreementTimes = [...$clientTime->pluck('agreement_time')->toArray(), ...$graphItems->pluck('agreement_time')->toArray()];
                // Filter out agreement times from the time blocks
                $availableTimeBlocks = array_filter($timeBlocks, function ($block) use ($agreementTimes) {
                    return !in_array($block, $agreementTimes);  // Exclude times that match `agreement_time`
                });
                $res = array_values($availableTimeBlocks);
                // $res = array_values(array_diff($timeBlocks, $agreementTimes));
                return [
                    'is_working' => true,
                    'data' => $res
                ];
            }
            // }



            // $graphItems  = GraphItem::where(function ($query) {
            //     // Ensure 'agreement_time' has a valid format (HH:MM)
            //     $query->where('agreement_time', 'REGEXP', '^[0-9]{2}:[0-9]{2}$');
            // })
            //     ->when($checkDate, function ($query, $checkDate) {
            //         // Optionally filter by date if provided
            //         $query->where('agreement_date', $checkDate);
            //     })
            //     ->get();

            // if ($graphItems->count() == 0) {
            //     return [
            //         'is_working' => true,
            //         'data' => $timeBlocks
            //     ];
            // }

            // $agreementTimes = $graphItems->pluck('agreement_time')->toArray();
            // Filter out agreement times from the time blocks
            $availableTimeBlocks = array_filter($timeBlocks, function ($block) use ($agreementTimes) {
                return !in_array($block, $agreementTimes);  // Exclude times that match `agreement_time`
            });
            $res = array_values($availableTimeBlocks);
            return [
                'is_working' => true,
                'data' => $res
            ];
        }
        return [
            'is_working' => false,
            'is_working1' => $this->isWorkingDay($checkDate, $daysArray)
        ];
    }

    public function graphItemDelete($request)
    {

        if (isset($request->graph_item_achive_id) && $request->graph_item_achive_id > 0) {
            $find = GraphArchiveItem::find($request->graph_item_achive_id);
            $GraphArchiveId = $find->graph_archive_id;
            $find->delete();
            return [
                'graph_item_achive_id' => $request->graph_item_achive_id
            ];
        }

        $GraphItemValue = GraphItem::find($request->graph_item_id);
        $graph = GraphItem::where('graph_id', $GraphItemValue->graph_id)->get();
        if ($graph->count() == 1) {
            $parent =  Graph::find($GraphItemValue->graph_id);
            $graphachive = GraphArchive::where('graph_id', $parent->id)->first();
            if ($graphachive) {
                if ($graphachive->status == 'finish') {
                    $parent->delete();
                }
            } else {
                $parent->delete();
            }
        }
        $GraphItemValue->delete();
        $item = GraphArchiveItem::where('graph_item_id', $request->graph_item_id)->first();
        if ($item) {
            $reponseid = $item->graph_archive_id;
            $item->delete();
            return [
                'graph_achive' =>    GraphArchive::with(['person',    'graphArchiveItem' => function ($q) {
                    $q->with(['client.clientResult', 'graphItem.department']);
                },  'treatment'])
                    ->find($reponseid),

            ];
        }

        return [
            'id' => $request->graph_item_id
        ];
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $GraphArchive = false;
        if (isset($request->graph_archive_id) && $request->graph_archive_id > 0) {
            $GraphArchive = GraphArchive::find($request->graph_archive_id);
            $result = Graph::find($GraphArchive->graph_id);
        } else {
            $result = $this->store($request);
        }

        if (isset($request->graph_item)) {
            if (((isset($request->use_status)  && $request->use_status != 'at_home')) || !isset($request->use_status)) {
                $reqDdata = json_decode($request->graph_item);
                if (count($reqDdata) > 0) {
                    foreach ($reqDdata as $key => $value) {
                        $GraphItem = GraphItem::create([
                            'department_id' => $request->department_id,
                            'graph_id' => $result->id,
                            'is_arrived' => 0,
                            'is_active' => 1,
                            'agreement_date' => $value->agreement_date ?? '-',
                            'agreement_time' => $value->agreement_time ?? '-',
                            // 'created_at' => now(),
                            // 'updated_at' => now(),
                        ]);
                        // $child = $value?->graph_item_value;
                        // if (count($child) > 0) {
                        //     $insertData = array_map(function ($item) use ($id, $GraphItem) {
                        //         return [
                        //             'service_id' => $item->service_id,
                        //             'graph_item_id' => $GraphItem->id,
                        //             'created_at' => now(),
                        //             'updated_at' => now(),
                        //         ];
                        //     }, $value->graph_item_value);
                        //     GraphItemValue::insert($insertData);
                        // }
                    }
                }
            }
        }

        if (isset($request->use_status)) {

            if (!$GraphArchive && isset($request->re_client_id) && $request->re_client_id > 0) {
                Client::find($request->re_client_id)->update(['use_status' => $request->use_status]);
            }

            $GraphItem = GraphItem::where('graph_id', $result->id)->get();
            if (!$GraphArchive) {
                $GraphArchive = GraphArchive::create([
                    'graph_id' => $result->id,
                    'at_home_client_id' => $request->at_home_client_id,
                    'shelf_number' => $request->shelf_number ?? 0,
                    'treatment_id' => $result->treatment_id,
                    'use_status' => $request->use_status,
                    'department_id' => $request->department_id,
                    'person_id' => Client::find($request->re_client_id)->person_id,
                    'user_id' => auth()->id(),
                    'referring_doctor_id' => $request->referring_doctor_id ?? 0,
                    'client_id' => $request->target_client_id ?? 0,
                    'status' => 'live'
                ]);
                // $referring_doctor_id = Client::find($request->re_client_id)->referring_doctor_id;
            }
            $find = Client::whereNull('parent_id')->where('person_id', $request->person_id)->first();
            if (isset($request->referring_doctor_id) && $request->referring_doctor_id > 0) {
                // $graphachive = GraphArchive::where('person_id', $request->person_id)
                //     ->orderBy('id', 'desc')
                //     ->where('status', GraphArchive::STATUS_FINISH)
                //     ->first();
                // if (($graphachive && $find->referring_doctor_id != $request->referring_doctor_id) || is_null($find->referring_doctor_id)) {
                // $find->update([
                //     'referring_doctor_id' => $request->referring_doctor_id
                // ]);
                // }
            }

            // if ($find && $find->referring_doctor_id > 0) {
            //     $result->update([
            //         'referring_doctor_id' => $find->referring_doctor_id
            //     ]);
            //     $GraphArchive->update([
            //         'referring_doctor_id' => $find->referring_doctor_id
            //     ]);
            // }
            if ($request->use_status == 'at_home') {
                $reqDdata = json_decode($request->graph_item);
                foreach ($reqDdata as $item) {

                    GraphArchiveItem::create([
                        // 'graph_item_id' => $item->id,
                        'graph_archive_id' => $GraphArchive->id,
                        'client_id' => $request->client_id,
                        'agreement_date' => $item->agreement_date ?? '-',
                        'agreement_time' => $item->agreement_time ?? '-',
                        'is_assigned' => $item->is_assigned ?? '0',
                        'department_id' => $request->department_id,
                        'is_assigned' => $item->is_assigned ?? '0',
                        // 'user_id' => $id
                    ]);
                }
            } else {

                if ($GraphItem->count() > 0) {
                    GraphArchiveItem::where('graph_archive_id', $GraphArchive->id)->delete();
                    foreach ($GraphItem as $item) {

                        GraphArchiveItem::create([
                            'graph_item_id' => $item->id,
                            'graph_archive_id' => $GraphArchive->id,
                            'client_id' => $request->client_id,
                            'agreement_date' => $item->agreement_date,
                            'agreement_time' => $item->agreement_time,
                            'department_id' => $request->department_id,
                            // 'user_id' => $id
                        ]);
                    }
                }
            }
            return GraphArchive::with(['person', 'graphArchiveItem'])->find($GraphArchive->id);
        }
        return  $this->modelClass::where('user_id', auth()->id())
            ->with(['department', 'graphItem' => function ($q) {
                $q->with(['department', 'graphItemValue.service']);
            }])
            ->find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $trenToAtHome = false;
        $AtHomeTotren = false;
        $exchangeId = 0;
        if (isset($request->use_status) && $request->use_status == 'at_home' && (GraphArchive::find($request->graph_archive_id)->use_status ?? '') == 'treatment') {
            $GraphArchive = GraphArchive::find($request->graph_archive_id);
            if ($GraphArchive->use_status == 'treatment') {
                $exchangeId = $GraphArchive->id;
                $graph = Graph::find($GraphArchive->graph_id);
                $reqDdata2 = collect(json_decode($request->graph_item))->filter(function ($item) {
                    return isset($item->id) ?   $item->id > 0  : false;
                });
                foreach ($reqDdata2 as $item) {
                    GraphArchiveItem::where([
                        'graph_archive_id' =>  $GraphArchive->id,
                        'graph_item_id' => $item->id
                    ])->update([
                        'is_at_home' => 1
                    ]);
                }
                Client::whereNull('parent_id')->where('person_id', $request->person_id)->first()->update(['use_status' => $request->use_status]);
                $graph->delete();
                $GraphArchive->update([
                    'status' => 'finish',
                    'comment' => "Uyga ozgartildi",
                    'shelf_number' => $request->shelf_number ?? $GraphArchive->shelf_number
                ]);
                $GraphArchive = GraphArchive::create([
                    'at_home_client_id' => $request->at_home_client_id,
                    'shelf_number' => $request->shelf_number ?? $GraphArchive->shelf_number,
                    'use_status' => $request->use_status,
                    'treatment_id' => $request->treatment_id,
                    'person_id' => $request->person_id,
                    'user_id' => auth()->id(),
                    'status' => 'live',
                    'referring_doctor_id' => $GraphArchive->referring_doctor_id ?? 0,
                    'client_id' => $request->target_client_id ?? 0,
                    'department_id' => $request->department_id,
                ]);
                $trenToAtHome = true;
            }
        } elseif (isset($request->use_status) && $request->use_status == 'treatment' && (GraphArchive::find($request->graph_archive_id)->use_status ?? '') == 'at_home') {
            $GraphArchive = GraphArchive::find($request->graph_archive_id);
            if ($GraphArchive->use_status == 'at_home') {
                $AtHomeTotren = true;
                $exchangeId = $GraphArchive->id;
                $graph = Graph::find($GraphArchive->graph_id);
                $reqDdata2 = collect(json_decode($request->graph_item))->filter(function ($item) {
                    return isset($item->id) ?   $item->id > 0  : false;
                });
                foreach ($reqDdata2 as $item) {
                    GraphArchiveItem::where([
                        'graph_archive_id' =>  $GraphArchive->id,
                        // 'graph_item_id' => $item->id
                    ])->update([
                        'is_at_home' => 1
                    ]);
                }
                Client::whereNull('parent_id')->where('person_id', $request->person_id)->first()->update(['use_status' => $request->use_status]);
                // $graph->delete();
                $GraphArchive->update([
                    'status' => 'finish',
                    'comment' => "Muolajaga ozgartildi",
                    'shelf_number' => $request->shelf_number ?? $GraphArchive->shelf_number
                ]);
                $result =   $this->store($request);
                $GraphArchive = GraphArchive::create([
                    'at_home_client_id' => $request->at_home_client_id,
                    'shelf_number' => $request->shelf_number ?? $GraphArchive->shelf_number,
                    'use_status' => $request->use_status,
                    'treatment_id' => $request->treatment_id,
                    'person_id' => $request->person_id,
                    'user_id' => auth()->id(),
                    'status' => 'live',
                    'referring_doctor_id' => $GraphArchive->referring_doctor_id ?? 0,
                    'client_id' => $request->target_client_id ?? 0,
                    'department_id' => $request->department_id,
                    'graph_id' => $result->id,
                ]);
                if (isset($request->graph_item)) {
                    $reqDdata = json_decode($request->graph_item);
                    if (count($reqDdata) > 0) {
                        foreach ($reqDdata as $key => $value) {
                            if (isset($value->id)) {
                                $GraphItem = GraphItem::find($value->id);
                                if ($GraphItem) {
                                    $GraphItem->update([
                                        'department_id' => $request->department_id ?? $GraphItem->department_id,
                                        'graph_id' => $result->id,
                                        'is_arrived' => 0,
                                        'is_active' => 1,
                                        'agreement_date' => $value->agreement_date ??  $GraphItem->agreement_date,
                                        'agreement_time' => $value->agreement_time ?? $GraphItem->agreement_time,
                                    ]);
                                } else {
                                    GraphItem::create([
                                        'department_id' => $request->department_id,
                                        'graph_id' => $result->id,
                                        'is_arrived' => 0,
                                        'is_active' => 1,
                                        'agreement_date' => $value->agreement_date,
                                        'agreement_time' => $value->agreement_time ?? '-',
                                    ]);
                                }
                            } else {
                                $GraphItem =    GraphItem::create([
                                    'department_id' => $request->department_id,
                                    'graph_id' => $result->id,
                                    'is_arrived' => 0,
                                    'is_active' => 1,
                                    'agreement_date' => $value->agreement_date,
                                    'agreement_time' => $value->agreement_time ?? '-',
                                ]);
                            }
                            // $child = $value->graph_item_value;
                            // GraphItemValue::where('graph_item_id', $GraphItem->id)->delete();
                            // if (count($child) > 0) {
                            //     $insertData = array_map(function ($item) use ($GraphItem) {
                            //         return [
                            //             'service_id' => $item->service_id,
                            //             'graph_item_id' => $GraphItem->id,
                            //             'created_at' => isset($item->created_at)  ? $item->created_at :  now(),
                            //             'updated_at' => isset($item->updated_at)  ? $item->updated_at :  now(),
                            //         ];
                            //     }, $value->graph_item_value);
                            //     GraphItemValue::insert($insertData);
                            // }
                        }
                    }
                }
                // $trenToAtHome = fal;
            }
            //  else if ($GraphArchive->use_status == 'treatment') {
            //     $result = $this->update($id, $request);
            //     if (isset($request->graph_item)) {
            //         $reqDdata = json_decode($request->graph_item);
            //         if (count($reqDdata) > 0) {
            //             foreach ($reqDdata as $key => $value) {
            //                 if (isset($value->id)) {
            //                     $GraphItem = GraphItem::find($value->id);
            //                     if ($GraphItem) {
            //                         $GraphItem->update([
            //                             'department_id' => $request->department_id ?? $GraphItem->department_id,
            //                             'graph_id' => $result->id,
            //                             'is_arrived' => 0,
            //                             'is_active' => 1,
            //                             'agreement_date' => $value->agreement_date ??  $GraphItem->agreement_date,
            //                             'agreement_time' => $value->agreement_time ?? $GraphItem->agreement_time,
            //                         ]);
            //                     } else {
            //                         GraphItem::create([
            //                             'department_id' => $request->department_id,
            //                             'graph_id' => $result->id,
            //                             'is_arrived' => 0,
            //                             'is_active' => 1,
            //                             'agreement_date' => $value->agreement_date,
            //                             'agreement_time' => $value->agreement_time ?? '-',
            //                         ]);
            //                     }
            //                 } else {
            //                     $GraphItem =    GraphItem::create([
            //                         'department_id' => $request->department_id,
            //                         'graph_id' => $result->id,
            //                         'is_arrived' => 0,
            //                         'is_active' => 1,
            //                         'agreement_date' => $value->agreement_date,
            //                         'agreement_time' => $value->agreement_time ?? '-',
            //                     ]);
            //                 }
            //                 // $child = $value->graph_item_value;
            //                 // GraphItemValue::where('graph_item_id', $GraphItem->id)->delete();
            //                 // if (count($child) > 0) {
            //                 //     $insertData = array_map(function ($item) use ($GraphItem) {
            //                 //         return [
            //                 //             'service_id' => $item->service_id,
            //                 //             'graph_item_id' => $GraphItem->id,
            //                 //             'created_at' => isset($item->created_at)  ? $item->created_at :  now(),
            //                 //             'updated_at' => isset($item->updated_at)  ? $item->updated_at :  now(),
            //                 //         ];
            //                 //     }, $value->graph_item_value);
            //                 //     GraphItemValue::insert($insertData);
            //                 // }
            //             }
            //         }
            //     }
            // };
        } else {

            if (isset($request->graph_archive_id) && +$request->graph_archive_id > 0) {
                $GraphArchive = GraphArchive::find($request->graph_archive_id);
                Log::info('edit: ', [$request->graph_archive_id]);

                if ($this->modelClass::find($GraphArchive->graph_id)) {
                    $result = $this->update($GraphArchive->graph_id, $request);
                    $GraphArchive->update([
                        'graph_id' => $result->id,
                        'treatment_id' => $result->treatment_id,
                        'department_id' => $request->department_id,
                        'shelf_number' => $request->shelf_number ?? $GraphArchive->shelf_number
                    ]);
                } else {
                    $result =   $this->store($request);
                    $GraphArchive->update(['graph_id' => $result->id, 'treatment_id' => $result->treatment_id,            'department_id' => $request->department_id,]);
                }
            } else {
                $result = $this->update($id, $request);
                Log::info('$result', [$result]);
            }

            if (isset($request->graph_item)) {
                $reqDdata = json_decode($request->graph_item);
                if (count($reqDdata) > 0) {
                    foreach ($reqDdata as $key => $value) {
                        if (isset($value->id)) {
                            $GraphItem = GraphItem::find($value->id);
                            if ($GraphItem) {
                                $GraphItem->update([
                                    'department_id' => $request->department_id ?? $GraphItem->department_id,
                                    'graph_id' => $result->id,
                                    'is_arrived' => 0,
                                    'is_active' => 1,
                                    'agreement_date' => $value->agreement_date ??  $GraphItem->agreement_date,
                                    'agreement_time' => $value->agreement_time ?? $GraphItem->agreement_time,
                                ]);
                            } else {
                                GraphItem::create([
                                    'department_id' => $request->department_id,
                                    'graph_id' => $result->id,
                                    'is_arrived' => 0,
                                    'is_active' => 1,
                                    'agreement_date' => $value->agreement_date,
                                    'agreement_time' => $value->agreement_time ?? '-',
                                ]);
                            }
                        } else {
                            $GraphItem =    GraphItem::create([
                                'department_id' => $request->department_id,
                                'graph_id' => $result->id,
                                'is_arrived' => 0,
                                'is_active' => 1,
                                'agreement_date' => $value->agreement_date,
                                'agreement_time' => $value->agreement_time ?? '-',
                            ]);
                        }
                        // $child = $value->graph_item_value;
                        // GraphItemValue::where('graph_item_id', $GraphItem->id)->delete();
                        // if (count($child) > 0) {
                        //     $insertData = array_map(function ($item) use ($GraphItem) {
                        //         return [
                        //             'service_id' => $item->service_id,
                        //             'graph_item_id' => $GraphItem->id,
                        //             'created_at' => isset($item->created_at)  ? $item->created_at :  now(),
                        //             'updated_at' => isset($item->updated_at)  ? $item->updated_at :  now(),
                        //         ];
                        //     }, $value->graph_item_value);
                        //     GraphItemValue::insert($insertData);
                        // }
                    }
                }
            }
        }

        if (isset($request->use_status) && $request->use_status) {
            if (!$GraphArchive && isset($request->client_id) && $request->client_id > 0) {
                Client::find($request->client_id)->update(['use_status' => $request->use_status]);
            }

            if (!$GraphArchive) {
                $GraphArchive = GraphArchive::create([
                    'graph_id' => $result->id,
                    'at_home_client_id' => $request->at_home_client_id,
                    'shelf_number' => $request->shelf_number ?? 0,
                    'use_status' => $request->use_status,
                    'treatment_id' => $result->treatment_id,
                    'person_id' => Client::find($request->re_client_id)->person_id,
                    'user_id' => auth()->id(),
                    'status' => 'live',
                    'referring_doctor_id' => $request->referring_doctor_id ?? 0,
                    'client_id' => $request->target_client_id ?? 0,
                    'department_id' => $request->department_id,
                ]);
            }
            if (isset($request->use_status) && $request->use_status == 'at_home') {
                $reqDdata = json_decode($request->graph_item);
                foreach ($reqDdata as $item) {
                    if ($trenToAtHome) {
                        GraphArchiveItem::create([
                            // 'graph_item_id' => $item->id,
                            'graph_archive_id' => $GraphArchive->id,
                            'client_id' => $request->client_id,
                            'agreement_date' => $item->agreement_date ?? '-',
                            'agreement_time' => $item->agreement_time ?? '-',
                            'is_assigned' => $item->is_assigned ?? '0',
                            'department_id' => $request->department_id,
                            'is_assigned' => $item->is_assigned ?? '0',
                            // 'user_id' => $id
                        ]);
                    } else {
                        $find = GraphArchiveItem::find($item->id ?? 0);
                        if ($find) {
                            $find->update([
                                'client_id' => $request->client_id,
                                'agreement_date' => $item->agreement_date ?? $find->agreement_date,
                                'agreement_time' => $item->agreement_time ??  $find->agreement_time,
                                'is_assigned' => $item->is_assigned ??  $find->is_assigned,
                                'department_id' => $request->department_id ?? $find->department_id,
                                // 'user_id' => $id
                            ]);
                        } else {
                            GraphArchiveItem::create([
                                // 'graph_item_id' => $item->id,
                                'graph_archive_id' => $GraphArchive->id,
                                'client_id' => $request->client_id,
                                'agreement_date' => $item->agreement_date ?? '-',
                                'agreement_time' => $item->agreement_time ?? '-',
                                'is_assigned' => $item->is_assigned ?? '0',
                                'department_id' => $request->department_id,
                                'is_assigned' => $item->is_assigned ?? '0',
                                // 'user_id' => $id
                            ]);
                        }
                    }
                }
                return ['graph_achive' => GraphArchive::with(['person',    'graphArchiveItem', 'treatment'])
                    ->find($GraphArchive->id), 'exchange_id' => $exchangeId];
            } else {
                $GraphItem = GraphItem::where('graph_id', $result->id)->get();
                if ($GraphItem->count() > 0) {
                    // GraphArchiveItem::where('graph_archive_id', $GraphArchive->id)->delete();
                    foreach ($GraphItem as $item) {
                        $finddata = GraphArchiveItem::where(['graph_item_id' => $item->id, 'graph_archive_id' => $GraphArchive->id,])->with(['graphItem'])->first();
                        if ($finddata) {
                            $finddata->update([
                                'agreement_date' => $item->agreement_date,
                                'agreement_time' => $item->agreement_time,
                                'department_id' => $request->department_id,
                            ]);
                        } else {
                            GraphArchiveItem::create([
                                'graph_item_id' => $item->id,
                                'graph_archive_id' => $GraphArchive->id,
                                'client_id' => $request->client_id,
                                'agreement_date' => $item->agreement_date,
                                'agreement_time' => $item->agreement_time,
                                'department_id' => $request->department_id,
                                // 'user_id' => $id
                            ]);
                        }
                    }
                }
            }
            if ($AtHomeTotren) {
                return ['exchange_id' => $exchangeId];
            }
            return GraphArchive::with(['person',    'graphArchiveItem' => function ($q) {
                $q->with(['client.clientResult', 'graphItem.department']);
            },  'treatment'])
                ->find($GraphArchive->id);
        }
        return $this->modelClass::where('user_id', auth()->id())
            ->with(['department', 'graphItem' => function ($q) {
                $q->with(['department', 'graphItemValue.service']);
            }])
            ->find($result->id);
    }
    public function delete($id)
    {
        $result = $this->modelClass::find($id);

        return $result->id;
    }


    public function graphClient($request)
    {
        $user = auth()->user();
        $startDate = now();
        $endDate = now();
        if (isset($request->start_date) && $request->start_date != 0) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
            if ($parsedDate->format('Y-m-d') ===  $request->start_date) {
                $startDate = $parsedDate;
            }
        }
        if (isset($request->end_date) && $request->end_date != 0) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
            if ($parsedDate->format('Y-m-d') ===  $request->end_date) {
                $endDate = $parsedDate;
            }
        }
        $worker = User::where('owner_id', $user->owner_id)->pluck('id');
        $queue_number =  ClientValue::where('department_id',     $user->department_id)
            ->max('queue_number');
        return [
            'data' => $this->modelClass::whereIn('user_id', $worker)
                ->whereHas('graphItem', function ($q) use ($user, $startDate, $endDate) {
                    if ($endDate->format('Y-m-d') == $startDate->format('Y-m-d')) {
                        $q
                            ->where('department_id', $user->department_id)
                            ->whereDate('agreement_date', $endDate->format('Y-m-d'));;
                    } else {
                        $q
                            ->where('department_id', $user->department_id)
                            ->whereBetween('agreement_date', [$startDate, $endDate]);
                    }
                })
                ->with(['graphItem' => function ($q) use ($user, $startDate, $endDate) {
                    if ($endDate->format('Y-m-d') == $startDate->format('Y-m-d')) {
                        $q
                            ->where('department_id', $user->department_id)
                            ->whereDate('agreement_date', $endDate->format('Y-m-d'));;
                    } else {
                        $q
                            ->where('department_id', $user->department_id)
                            ->whereBetween('agreement_date', [$startDate, $endDate]);
                    }
                }])
                ->get(),
            'queue_number' => $queue_number,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'department' => Departments::find($user->department_id)
        ];
    }

    public function treatment($request)
    {
        // return $this->modelClass::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
        //     ->where('client_id', '>', '0')->with(['graphItem', 'client'])->get();
        $status = ['live'];
        $treatment = [];
        $today = ($request->is_today);
        $index = $request->index ?? '-';
        Log::info($request->all());
        Log::info([$today == 1]);
        // $start_age = $request->start_age ?? 0;
        // $end_age = $request->end_age ?? 0;

        if (isset($request->treatment_id)) {
            $treatment = [$request->treatment_id];
        } else {
            $treatment = Treatment::where('user_id', auth()->user()->owner_id)
                ->pluck('id')->toArray();
        }
        if (isset($request->status)) {
            if ($request->status == 'all') {
                $status = ['live', 'archive', 'finish'];
            } else {

                $status = [$request->status];
            }
        }
        if (isset($request->client_status) && ($request->client_status == 'new_client' || $request->client_status == 'end_client')) {
            $status = ['live', 'archive', 'finish'];
        }
        Log::info('s', [$request->full_name ?? '-']);
        $data =  GraphArchive::whereIn('status', $status)
            ->where('use_status', 'treatment')
            ->whereIn('treatment_id',  $treatment)
            ->whereHas('person', function ($q) use ($request) {
                if (isset($request->full_name)) {
                    // ism familya 
                    $q->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                }
                if (isset($request->phone)) {
                    // telefon
                    $q->where(DB::raw("phone"), 'LIKE', "%{$request->phone}%");
                }
                if (isset($request->start_age) || isset($request->end_age)) {
                    // Yosh
                    // if(($request->start_age ?? false)  || !($request->end_age ?? false)){
                    //     $q->where(DB::raw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE())"), $request->start_age);
                    // }else if(!($request->start_age ?? false)  || ($request->end_age ?? false)){
                    //     $q->where(DB::raw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE())"), $request->end_age);
                    // }else
                    if (($request->start_age) == $request->end_age) {
                        $q->where(DB::raw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE())"), $request->start_age);
                    } else {
                        $q->whereRaw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE()) BETWEEN ? AND ?", [$request->start_age, $request->end_age]);
                    }
                }
            })
            ->whereHas('graphArchiveItem', function ($q) use ($today, $request) {

                if (isset($request->client_status)) {
                    if ($request->client_status == Client::STATUS_IN_ROOM) {
                        $q->whereNotNull('client_id');
                    } else
                    if ($request->client_status == Client::STATUS_FINISH) {
                        $q->whereHas('client', function ($q) use ($today, $request) {
                            $q->whereColumn('department_count', 'finish_department_count');
                            // $q->whereHas('clientResult', function ($q) use ($today, $request) {
                            //     $q->whereIn('is_check_doctor', [Client::STATUS_FINISH]);
                            //     // Проверяем, что department_id в clientResult совпадает с department_id в graphArchiveItem
                            //     $q->whereColumn('client_results.department_id', 'graph_archive_items.department_id');
                            // });
                            // if ($request->client_status == Client::STATUS_IN_ROOM) {
                            //     $q->where('is_check_doctor', '!=', Client::STATUS_FINISH);
                            // } else
                            // if ($request->client_status == Client::STATUS_FINISH) {
                            //     $q->whereIn('is_check_doctor', [Client::STATUS_FINISH]);
                            // }
                            // else {
                            //     $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                            //         ->orWhereNull('is_check_doctor')
                            //     ;
                            // }
                            // else{
                            //     $q->where('is_check_doctor', '!=', Client::STATUS_FINISH);
                            // }
                        });
                    } else
                  
                    if ($request->client_status == 'new_client' || $request->client_status == 'end_client') {
                        $q
                            ->whereDate('agreement_date', now()->format('Y-m-d'));
                    } else

                    if ($request->client_status == Client::STATUS_IN_WAIT) {
                        if ($today == '1') {
                            $q->whereDate('agreement_date', now()->format('Y-m-d'))
                                ->whereNull('client_id');
                        } else {
                            $q->whereDate('agreement_date', '>=', now()->format('Y-m-d'))
                                ->whereNull('client_id');
                        }
                        //    kutilyabdi

                    } else
                    if ($request->client_status == Client::STATUS_NO_SHOW) {

                        // $kelmaganlar = GraphArchive::whereHas('graphArchiveItem', function ($q) use ($request, $currentMonthIndex, $is_all) {

                        //     $q->whereNull('client_id');
                        //     if (isset($request->is_today) && $request->is_today == 1) {
                        //         $q->whereDate('agreement_date', now()->format('Y-m-d'));
                        //     }
                        //     if (!$is_all) {
                        //         $q->whereMonth('agreement_date', $currentMonthIndex);
                        //     }

                        // })
                        // ->with(['graphArchiveItem', 'person']);
                        // ->whereDate('agreement_date', '<', now()->format('Y-m-d'))
                        // // $q
                        //     // ->whereNull('client_id')
                        //     ->whereHas('graphItem', function ($q) use ($request) {
                        //         $q->whereHas('department', function ($q) {
                        //             // Using a join to reference columns directly
                        //             // $q->whereRaw("CASE 
                        //             // WHEN graph_archive_items.agreement_date <= CURRENT_DATE THEN (CASE WHEN  graph_archive_items.agreement_time IS NULL OR graph_archive_items.agreement_time = '-' THEN  (CASE WHEN departments.work_end_time <= CURRENT_TIME then 1 else 0 end) else (departments.work_end_time > graph_archive_items.agreement_time)  end) ELSE (departments.work_end_time > graph_archive_items.agreement_time) END");
                        //             // $q->whereRaw("
                        //             // CASE 
                        //             //  WHEN graph_archive_items.agreement_date <= CURRENT_DATE
                        //             //   THEN (CASE
                        //             //      WHEN  graph_archive_items.agreement_time IS NULL OR graph_archive_items.agreement_time = '-'
                        //             //         THEN  (CASE
                        //             //          WHEN departments.work_end_time <= CURRENT_TIME  then 1 else 1 end)
                        //             //          else (1)  
                        //             //     end) 
                        //             //  END");

                        //             //     $q->whereRaw("departments.work_end_time > graph_archive_items.agreement_time 
                        //             //     AND (CASE WHEN departments.work_end_time <= CURRENT_TIME THEN 1 ELSE 0 END = 1)
                        //             //   ");

                        //         });
                        //     });;
                        // // kelmaganlar

                        $q
                            ->whereNull('client_id')
                            ->whereHas('department', function ($q) {
                                $time = Carbon::now()->format('H:i');
                                $date =  Carbon::now()->format('Y-m-d');
                                $q
                                    ->whereRaw("graph_archive_items.agreement_date < '$date'")
                                    ->orWhere(function ($q) use ($date, $time) {
                                        $q->whereRaw("graph_archive_items.agreement_date = '$date'")
                                            ->whereRaw("departments.work_end_time <= '$time'");
                                    });;
                            })

                        ;
                    }
                } else {
                    if ($today == '1') {
                        $q->whereDate('agreement_date', now()->format('Y-m-d'));
                    }
                }
            })
            ->with([
                'person',
                // 'graphArchiveItem'

                // => function ($q) use ($today, $request) {
                //     if (isset($request->client_status)) {

                //         $q->whereHas('client', function ($q) use ($today, $request) {

                //             // if ($request->client_status == Client::STATUS_IN_ROOM) {
                //             //     $q->where('is_check_doctor', '!=', Client::STATUS_FINISH);
                //             // } else
                //             if ($request->client_status == Client::STATUS_FINISH) {
                //                 $q->whereIn('is_check_doctor', [Client::STATUS_FINISH]);
                //             }
                //             // else {
                //             //     $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                //             //         ->orWhereNull('is_check_doctor')
                //             //     ;
                //             // }
                //             // else{
                //             //     $q->where('is_check_doctor', '!=', Client::STATUS_FINISH);
                //             // }
                //         });
                //     }
                //     // else
                //     // if ($index >= 0) {
                //     //     $q->where('client_id', '>', '0');
                //     // }
                // },
                'graphArchiveItem' => function ($q) use ($request) {
                    $q->with(['client.clientResult', 'graphItem.department']);
                },
                //  => function ($q) use ($request) {
                // if (isset($request->client_status)) {
                //     if ($request->client_status == 'end_client') {
                //         $q
                //             ->orderBy('id', 'desc');
                //     }
                // }
                // },
                'treatment.treatmentServiceItem.service' => function ($q) {
                    $q->with(['serviceProduct.product', 'department']);
                },
            ])


            ->get();
        Log::info('index ', [$index]);
        return   [
            'data' => [
                ...$data
                    ->filter(function ($item) use ($index, $request) {
                        if (isset($request->client_status) && ($request->client_status == 'new_client' || $request->client_status == 'end_client')) {
                            if ($request->client_status == 'end_client') {
                                $first = $item->graphArchiveItem->last();
                            } else {
                                $first = $item->graphArchiveItem->first();
                            }
                            return $first && $first->agreement_date == now()->format('Y-m-d'); // Agar birinchi yozuv 
                        } else {
                            if ($index == '-' || $index == 'today') {
                                return $item;
                            }
                            if ($index >= 0) {
                                return $item->came_graph_archive_item_count == +$index;
                            }
                        }
                    })
                // ->map(function ($element, $kalit) {
                //     // Har bir element ustida bajariladigan amallar
                //     return $element;
                // })
            ],
            'came_graph_archive_item_count' => $data
                ->whereNotNull('came_graph_archive_item_count')
                ->pluck('came_graph_archive_item_count')->unique()->sort()->values()

            // ->map(function ($item) use ($index) {
            //     // `graphArchiveItem` ichidagi faqat kerakli indeksdagi elementni kesib olish
            //     if ($index == '-' || $index == 'today') {
            //         return $item;
            //     }
            //     Log::info('index ', [$item->graphArchiveItem[$index] ?? false]);
            //     if ($index >= 0 &&  $item->graphArchiveItem->count() > 0) {
            //         if (($item->graphArchiveItem[$index] ?? false)) {
            //             $item->graph_archive_item = [$item->graphArchiveItem[$index]];
            //         } else {
            //             $item->graph_archive_item = [];
            //         }
            //         unset($item->graphArchiveItem);
            //     } else {
            //         $item->graph_archive_item = [];
            //     }
            //     return $item;
            // }),
            ,
            'count' => $data->max('came_graph_archive_item_count')
        ];
    }
    public function treatmentUpdate($id, $request)
    {
        $find = GraphArchive::find($id);
        Graph::find($find->graph_id)?->delete();
        Client::where('person_id', $find->person_id)->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))->update([
            'use_status' => '-',
            'referring_doctor_id' => null
        ]);
        Log::info(Client::where('person_id', $find->person_id)->whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))->first());
        $find->update([
            'status' => $request->status,
            'comment' => $request->comment,

        ]);

        return GraphArchive::with(['person',    'graphArchiveItem' => function ($q) {
            $q->with(['client.clientResult', 'graphItem.department']);
        },  'treatment'])->find($id);
    }
    public function graphArchive($request)
    {
        return GraphArchive::with(['person', 'graphArchiveItem'])->get();
    }
    public function graphArchiveShow($request)
    {
        // graph_item_id
        // graph_id	
        if (isset($request->graph_achive_id) && $request->graph_achive_id > 0) {
            return GraphArchive::with([
                'person',
                'graphArchiveItem',
                'treatment.treatmentServiceItem.service' => function ($q) {
                    $q->with(['serviceProduct.product', 'department']);
                },
                'department',
                'atHomeClient.clientValue' => function ($q) {
                    $q->with(['service' => function ($q) {
                        $q->with(['servicetype', 'department', 'serviceProduct.product']);
                    }, 'owner']);
                }
            ])->find($request->graph_achive_id);
        }
        if (isset($request->person_id) && $request->person_id > 0) {
            $graph =   $this->modelClass::with([
                'department',
                'graphItem' => function ($q) use ($request) {
                    $q
                        ->with(['department', 'graphItemValue.service']);
                },
                'treatment.treatmentServiceItem.service' => function ($q) {
                    $q->with(['serviceProduct.product', 'department']);
                },
                'person'

            ])
                ->where('treatment_id', '>', '0')
                ->orderBy('id', 'desc')
                ->where('person_id',  $request->person_id)->first();
            $GraphArchive = GraphArchive::with(['department', 'person', 'graphArchiveItem', 'treatment.treatmentServiceItem.service' => function ($q) {
                $q->with(['serviceProduct.product', 'department']);
            }, 'person'])->where(['person_id' => $request->person_id, 'status' => 'live'])->first();
            return [
                'graph' =>$graph?   [ ...($graph ?? [])->toArray(), 'shelf_number' =>  $GraphArchive->shelf_number ?? 0]  : ['shelf_number' =>  $GraphArchive->shelf_number ?? 0],
                'graph_archive' => $GraphArchive
            ];
        }

        return  [
            ...$this->modelClass::with([
                'department',
                'graphItem' => function ($q) use ($request) {
                    $q
                        ->with(['department', 'graphItemValue.service']);
                },
                'treatment.treatmentServiceItem.service' => function ($q) {
                    $q->with(['serviceProduct.product', 'department']);
                },
                'atHomeClient.clientValue' => function ($q) {
                    $q->with(['service' => function ($q) {
                        $q->with(['servicetype', 'department', 'serviceProduct.product']);
                    }, 'owner']);
                },
                'person'
            ])
                ->find($request->graph_id)->toArray(),
            'shelf_number' => GraphArchive::where('graph_id', $request->graph_id)->first()->shelf_number ?? 0
        ];
    }

    public function atHomeTreatment($request)
    {
        // return $this->modelClass::whereIn('user_id', User::where('owner_id', auth()->user()->owner_id)->pluck('id'))
        //     ->where('client_id', '>', '0')->with(['graphItem', 'client'])->get();
        $status = ['live'];
        $treatment = [];
        $today = ($request->is_today);
        $index = $request->index ?? '-';
        Log::info($request->all());
        Log::info([$today == 1]);
        // $start_age = $request->start_age ?? 0;
        // $end_age = $request->end_age ?? 0;

        if (isset($request->treatment_id)) {
            $treatment = [$request->treatment_id];
        } else {
            $treatment = Treatment::where('user_id', auth()->user()->owner_id)
                ->pluck('id')->toArray();
        }
        if (isset($request->status)) {
            if ($request->status == 'all') {
                $status = ['live', 'archive', 'finish'];
            } else {

                $status = [$request->status];
            }
        }
        if (isset($request->client_status) && ($request->client_status == 'new_client' || $request->client_status == 'end_client')) {
            $status = ['live', 'archive', 'finish'];
        }
        $data =  GraphArchive::whereIn('status', $status)
            ->where('use_status', 'at_home')
            ->whereIn('treatment_id',  $treatment)
            ->whereHas('person', function ($q) use ($request) {
                if (isset($request->full_name)) {
                    // ism familya 
                    $q->where(DB::raw("first_name"), 'LIKE', "%{$request->full_name}%");
                }
                if (isset($request->phone)) {
                    // telefon
                    $q->where(DB::raw("phone"), 'LIKE', "%{$request->phone}%");
                }
                if (isset($request->start_age) || isset($request->end_age)) {
                    // Yosh
                    // if(($request->start_age ?? false)  || !($request->end_age ?? false)){
                    //     $q->where(DB::raw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE())"), $request->start_age);
                    // }else if(!($request->start_age ?? false)  || ($request->end_age ?? false)){
                    //     $q->where(DB::raw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE())"), $request->end_age);
                    // }else
                    if (($request->start_age) == $request->end_age) {
                        $q->where(DB::raw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE())"), $request->start_age);
                    } else {
                        $q->whereRaw("TIMESTAMPDIFF(YEAR, data_birth, CURDATE()) BETWEEN ? AND ?", [$request->start_age, $request->end_age]);
                    }
                }
            })
            ->whereHas('graphArchiveItem', function ($q) use ($today, $request) {
                if ($request->client_status == 'taken') {
                    $q
                        ->where('is_assigned', 1)
                        // ->where('is_assigned', [1])
                        // ->whereNotIn('is_assigned', [0])
                    ;
                }
                if ($request->client_status == 'not_taken') {
                    $q
                        ->whereIn('is_assigned', [0])
                        ->whereNotIn('is_assigned', [1]);
                }
                if (isset($request->client_status)) {

                    if ($request->client_status == Client::STATUS_IN_ROOM) {
                        $q->whereNotNull('client_id');
                    } else
                    if ($request->client_status == Client::STATUS_FINISH) {
                        $q->whereHas('client', function ($q) use ($today, $request) {
                            $q->whereColumn('department_count', 'finish_department_count');
                            // $q->whereHas('clientResult', function ($q) use ($today, $request) {
                            //     $q->whereIn('is_check_doctor', [Client::STATUS_FINISH]);
                            //     // Проверяем, что department_id в clientResult совпадает с department_id в graphArchiveItem
                            //     $q->whereColumn('client_results.department_id', 'graph_archive_items.department_id');
                            // });
                            // if ($request->client_status == Client::STATUS_IN_ROOM) {
                            //     $q->where('is_check_doctor', '!=', Client::STATUS_FINISH);
                            // } else
                            // if ($request->client_status == Client::STATUS_FINISH) {
                            //     $q->whereIn('is_check_doctor', [Client::STATUS_FINISH]);
                            // }
                            // else {
                            //     $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                            //         ->orWhereNull('is_check_doctor')
                            //     ;
                            // }
                            // else{
                            //     $q->where('is_check_doctor', '!=', Client::STATUS_FINISH);
                            // }
                        });
                    } else
                  
                    if (isset($request->client_status) && ($request->client_status == 'new_client' || $request->client_status == 'end_client')) {
                        $q
                            ->whereDate('agreement_date', now()->format('Y-m-d'));
                    } else

                    if ($request->client_status == Client::STATUS_IN_WAIT) {
                        if ($today == '1') {
                            $q->whereDate('agreement_date', now()->format('Y-m-d'))
                                ->whereNull('client_id');
                        } else {
                            $q->whereDate('agreement_date', '>=', now()->format('Y-m-d'))
                                ->whereNull('client_id');
                        }
                        //    kutilyabdi

                    } else
                    if ($request->client_status == Client::STATUS_NO_SHOW) {

                        // $kelmaganlar = GraphArchive::whereHas('graphArchiveItem', function ($q) use ($request, $currentMonthIndex, $is_all) {

                        //     $q->whereNull('client_id');
                        //     if (isset($request->is_today) && $request->is_today == 1) {
                        //         $q->whereDate('agreement_date', now()->format('Y-m-d'));
                        //     }
                        //     if (!$is_all) {
                        //         $q->whereMonth('agreement_date', $currentMonthIndex);
                        //     }

                        // })
                        // ->with(['graphArchiveItem', 'person']);
                        // ->whereDate('agreement_date', '<', now()->format('Y-m-d'))
                        // // $q
                        //     // ->whereNull('client_id')
                        //     ->whereHas('graphItem', function ($q) use ($request) {
                        //         $q->whereHas('department', function ($q) {
                        //             // Using a join to reference columns directly
                        //             // $q->whereRaw("CASE 
                        //             // WHEN graph_archive_items.agreement_date <= CURRENT_DATE THEN (CASE WHEN  graph_archive_items.agreement_time IS NULL OR graph_archive_items.agreement_time = '-' THEN  (CASE WHEN departments.work_end_time <= CURRENT_TIME then 1 else 0 end) else (departments.work_end_time > graph_archive_items.agreement_time)  end) ELSE (departments.work_end_time > graph_archive_items.agreement_time) END");
                        //             // $q->whereRaw("
                        //             // CASE 
                        //             //  WHEN graph_archive_items.agreement_date <= CURRENT_DATE
                        //             //   THEN (CASE
                        //             //      WHEN  graph_archive_items.agreement_time IS NULL OR graph_archive_items.agreement_time = '-'
                        //             //         THEN  (CASE
                        //             //          WHEN departments.work_end_time <= CURRENT_TIME  then 1 else 1 end)
                        //             //          else (1)  
                        //             //     end) 
                        //             //  END");

                        //             //     $q->whereRaw("departments.work_end_time > graph_archive_items.agreement_time 
                        //             //     AND (CASE WHEN departments.work_end_time <= CURRENT_TIME THEN 1 ELSE 0 END = 1)
                        //             //   ");

                        //         });
                        //     });;
                        // // kelmaganlar

                        $q
                            ->whereNull('client_id')
                            ->whereHas('department', function ($q) {
                                $time = Carbon::now()->format('H:i');
                                $date =  Carbon::now()->format('Y-m-d');
                                $q
                                    ->whereRaw("graph_archive_items.agreement_date < '$date'")
                                    ->orWhere(function ($q) use ($date, $time) {
                                        $q->whereRaw("graph_archive_items.agreement_date = '$date'")
                                            ->whereRaw("departments.work_end_time <= '$time'");
                                    });;
                            })

                        ;
                    }
                } else {
                    if ($today == '1') {
                        $q->whereDate('agreement_date', now()->format('Y-m-d'));
                    }
                }
            })
            ->where(function ($q) use ($request) {
                if ($request->client_status == 'taken') {
                    $q->whereDoesntHave('graphArchiveItem', function ($q2) {
                        $q2->where('is_assigned', '!=', 1); // Hech bir yozuv `1`dan farqli bo'lmasligi kerak
                    });
                }
            })
            ->with([
                'person',
                // 'graphArchiveItem'

                // => function ($q) use ($today, $request) {
                //     if (isset($request->client_status)) {

                //         $q->whereHas('client', function ($q) use ($today, $request) {

                //             // if ($request->client_status == Client::STATUS_IN_ROOM) {
                //             //     $q->where('is_check_doctor', '!=', Client::STATUS_FINISH);
                //             // } else
                //             if ($request->client_status == Client::STATUS_FINISH) {
                //                 $q->whereIn('is_check_doctor', [Client::STATUS_FINISH]);
                //             }
                //             // else {
                //             //     $q->whereIn('is_check_doctor', [Client::STATUS_PAUSE, Client::STATUS_START])
                //             //         ->orWhereNull('is_check_doctor')
                //             //     ;
                //             // }
                //             // else{
                //             //     $q->where('is_check_doctor', '!=', Client::STATUS_FINISH);
                //             // }
                //         });
                //     }
                //     // else
                //     // if ($index >= 0) {
                //     //     $q->where('client_id', '>', '0');
                //     // }
                // },
                'graphArchiveItem' => function ($q) use ($request) {
                    // if ($request->client_status == 'taken') {
                    //     $q->whereNotIn('is_assigned', [0]);
                    // }
                    // if ($request->client_status == 'not_taken') {
                    //     $q->whereNotIn('is_assigned', [1]);
                    // }
                    $q->with(['client.clientResult', 'graphItem.department']);
                },
                //  => function ($q) use ($request) {
                // if (isset($request->client_status)) {
                //     if ($request->client_status == 'end_client') {
                //         $q
                //             ->orderBy('id', 'desc');
                //     }
                // }
                // },
                'treatment'
            ])
            ->has('graphArchiveItem')

            ->get();
        Log::info('index ', [$index]);
        return   [
            'data' => [
                ...$data
                    ->filter(function ($item) use ($index, $request) {
                        if (isset($request->client_status) && ($request->client_status == 'new_client' || $request->client_status == 'end_client')) {
                            if ($request->client_status == 'end_client') {
                                $first = $item->graphArchiveItem->last();
                            } else {
                                $first = $item->graphArchiveItem->first();
                            }
                            return $first && $first->agreement_date == now()->format('Y-m-d'); // Agar birinchi yozuv 
                        } else {
                            if ($index == '-' || $index == 'today') {
                                return $item;
                            }
                            if ($index >= 0) {
                                return $item->came_graph_archive_item_count == +$index;
                            }
                        }
                    })
                // ->map(function ($element, $kalit) {
                //     // Har bir element ustida bajariladigan amallar
                //     return $element;
                // })
            ],
            'came_graph_archive_item_count' => $data
                ->whereNotNull('came_graph_archive_item_count')
                ->pluck('came_graph_archive_item_count')->unique()->sort()->values()

            // ->map(function ($item) use ($index) {
            //     // `graphArchiveItem` ichidagi faqat kerakli indeksdagi elementni kesib olish
            //     if ($index == '-' || $index == 'today') {
            //         return $item;
            //     }
            //     Log::info('index ', [$item->graphArchiveItem[$index] ?? false]);
            //     if ($index >= 0 &&  $item->graphArchiveItem->count() > 0) {
            //         if (($item->graphArchiveItem[$index] ?? false)) {
            //             $item->graph_archive_item = [$item->graphArchiveItem[$index]];
            //         } else {
            //             $item->graph_archive_item = [];
            //         }
            //         unset($item->graphArchiveItem);
            //     } else {
            //         $item->graph_archive_item = [];
            //     }
            //     return $item;
            // }),
            ,
            'count' => $data->max('came_graph_archive_item_count')
        ];
    }
    // javon raqami
    public function shelfNumberLimitGenerate($count)
    {
        $result = [];
        for ($i = 1; $i <= $count; $i++) {
            $result[] = $i;
        }
        return $result;
    }


    public function shelfNumberLimit($id)
    {
        $result = Departments::find($id);
        $limitData = $this->shelfNumberLimitGenerate($result->shelf_number_limit);
        $clintValue = GraphArchive::where(['department_id' => $result->id, 'use_status' => 'treatment', 'status' => 'live'])
            ->where('shelf_number', '>', 0)
            ->pluck('shelf_number')
            ->unique()
            ->filter(function ($value) {
                return is_numeric($value) && $value > 0;
            })
            ->values()
            ->toArray();
        return [
            'data' => $clintValue,
            'limit' => $limitData,
            'department' => $result
        ];
    }
}
