<?php

namespace App\Services\api;

use App\Models\Branch;
use App\Models\Departments;
use App\Models\DirectorSetting;
use App\Models\History;
use App\Models\InAndOutPayment;
use App\Models\PayOffice;
use App\Models\User;
use App\Models\UserTemplateItem;
use App\Services\Api\Contracts\AuthServiceInterface;
use App\Traits\Crud;
use App\Traits\HistoryTraid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService implements AuthServiceInterface
{
    public $modelClass = User::class;
    use Crud, HistoryTraid;
    public function register($request, $role)
    {
        $user = new User();
        $user->name = $request->input("name");
        $user->email = $request->input("email");
        $user->password = Hash::make($request->input("password"));
        $user->save();
        return response()->json([
            "status" => 200,
        ]);
    }
    public function login($request, $role = '')
    {

        $where = [
            'role' => $request->role,
        ];
        if ($role == User::USER_ROLE_SUPPER_ADMIN) {
            $where = [
                'login' => $request->login,
            ];
        }
        $user = User::Where($where)
            ?->get();
        // $captcha = Captcha::Where("code", $request->input('captcha'))->first() ?? false;
        $captcha = true;
        if (count($user) == 0) {
            return ['xabar' => "role yoki parol noto'g'ri !"];
        } else {

            $passwords = passwordCheck($user, $request->password);
            // $passwords = Hash::check($request->password, $user->password) ?? false;
            // Log::info($user);
            if ($passwords) {
                if (!$captcha) {
                    return
                        ['xabar' => "Kaptcha noto'gri !"];
                } else {
                    // $captcha->delete();
                    $token = $passwords->createToken($request->password . $request->role . $request->login . '_Token')->plainTextToken;
                    // $token = $user->createToken($user->email . '_Token')->plainTextToken;
                    $floors = [];
                    $logo = '';
                    if ($passwords->role == User::USER_ROLE_QUEUE) {
                        $logo = User::find($passwords->owner_id)->logo_photo;
                        $floors = Departments::where('user_id', $passwords->owner_id)
                            ->whereNull('parent_id')
                            ->with(['floorRoom' => function ($q) {
                                $q->with(['user'])
                                    ->withCount('departmentValue')
                                ;
                            }])
                            ->whereNotNull('floor')
                            ->orderBy('floor')
                            ->get(['id', 'floor']) // Faqat 'id' va 'floor' maydonlarini olish
                            ->unique('floor') // 'floor' bo‘yicha takroriy qiymatlarni olib tashlash
                            ->values();
                    }

                    return [
                        'xabar' => 'ok',
                        'status' => 200,
                        'token' => $token,
                        "user" => [
                            ...$passwords->toArray(),
                            'floors' => $floors,
                            'logo' => $logo
                        ],
                    ];
                }
            } else {
                return  ['xabar' => "parol noto'g'ri kiritlgan!"];
            }
        }
    }
    public function logout($request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => 200,
            'massage' => 'Profilgan chiqildi',
        ]);
    }

    function getUniqueYearsWithCurrentYear($dates)
    {
        // Hozirgi yilni olish
        $currentYear = Carbon::now()->year;
        if (empty($dates)) return [$currentYear];
        // Yillar kolleksiyasi yaratish
        $uniqueYears = collect($dates)
            ->map(function ($date) {
                // Carbon obyektiga aylantirish va yilni olish
                return Carbon::parse($date['created_at'])->year;
            })
            ->unique() // Takrorlanuvchi yillarni olib tashlash
            ->toArray(); // Arrayga aylantirish

        // Agar hozirgi yil yo‘q bo‘lsa, uni qo‘shamiz
        if (!in_array($currentYear, $uniqueYears)) {
            $uniqueYears[] = $currentYear;
        }

        // Har bir yilga 1 qo‘shamiz
        $incrementedYears = array_map(fn($year) => $year + 1, $uniqueYears);

        // Yangi array qaytaramiz
        return $incrementedYears;
    }
    public function profile($request)
    {
        $user = auth()->user();
        $logo = '';
        if ($user->role == User::USER_ROLE_QUEUE) {
            $logo = User::find($user->owner_id)->logo_photo;
            $departments = Departments::where('user_id', $user->owner_id)
                ->whereNull('parent_id')
                ->with(['floorRoom' => function ($q) {
                    $q->with(['user'])
                        ->withCount('departmentValue')
                    ;
                }])
                ->whereNotNull('floor')
                ->orderBy('floor')
                ->get(['id', 'floor']) // Faqat 'id' va 'floor' maydonlarini olish
                ->unique('floor') // 'floor' bo‘yicha takroriy qiymatlarni olib tashlash
                ->values();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'floors' => $departments,
                'logo' => $logo
            ];
        }
        $today = Carbon::now()->format('d.m.Y');
        $template = '';
        $logo_photo = '';
        $department = [];
        $owner = [];
        $phones = [];
        $mainKounterId = 0;
        $directorSetting = [];
        $is_pharmacy = false;
        $branch = [];
        $is_main_branch = true;
        if ($user->role == User::USER_ROLE_DOCTOR) {
            $template = UserTemplateItem::where('user_id', $user->id)
                ->whereHas('template', function ($q) {
                    $q->whereHas('templateItem');
                })
                ->with('template.templateItem.templateCategory')
                ->get();
        }
        if ($user->role == User::USER_ROLE_DOCTOR) {
            $owner = User::where('id', $user->owner_id)->first(['id', 'name', 'logo_photo']);
            $logo_photo = $owner->logo_photo ?? '';
            $department = Departments::with('departmentTemplateItem.template.templateItem.templateCategory')->find($user->department_id);
        }
        if ($user->role != User::USER_ROLE_DOCTOR) {
            $owner = User::find($user->owner_id);
            $logo_photo = $owner->logo_photo ?? '';
        }
        if ($user->role != User::USER_ROLE_LABORATORY) {
            $directorSetting  = DirectorSetting::where('user_id', $user->owner_id)->first() ?? [];
        }
        if ($user->role == User::USER_ROLE_DIRECTOR) {
            $directorSetting  = DirectorSetting::where('user_id', $user->id)->first() ?? [];
            $logo_photo = $user->logo_photo;
            // $branchFind = Branch::where('main_branch_id', $user->id)->with('branchItems.targetBranch')->first();
            // $branch[] = [
            //     'value' => $user->id,
            //     'label' => $user->name,
            //     'created_at' => $user->created_at
            // ];
            // if ($branchFind) {
            //     $is_main_branch = false;
            //     if ($branchFind->branchItems->count() > 0) {
            //         foreach ($branchFind->branchItems as $item) {
            //             $branch[] = [
            //                 'value' => $item->targetBranch->id,
            //                 'label' => $item->targetBranch->name,
            //                 'created_at' => $item->targetBranch->created_at
            //             ];
            //         }
            //     }
            // }
        }

        if ($user->role == User::USER_ROLE_RECEPTION) {
            $directorSetting  = DirectorSetting::where('user_id', $user->owner_id)->first() ?? [];
            $first =  User::where(['is_main' => 1, 'role' => User::USER_ROLE_COUNTERPARTY, 'owner_id' => $user->owner_id])
                // ->('owner_id', $user->owner_id)
                ->first();
            if ($first) {
                $mainKounterId = User::where('is_main', 1)->first()->id;
            } else {
                $first =  User::where(['role' => User::USER_ROLE_COUNTERPARTY, 'owner_id' => $user->owner_id])->first();
                $mainKounterId = $first->id ?? 0;
            }
            //    ? $mainKounterId = User::where('is_main', 1)->first()->id : $mainKounterId = 0;
        }

        return [
            'id' => $user->id,
            'role' => $user->role,
            'name' => $user->name,
            'logo_photo' => $logo_photo,
            'full_name' => $user->full_name,
            'now' => $today,
            'is_main_id' => $mainKounterId,
            'is_shikoyat' => $user->is_shikoyat,
            'is_gijja' => $user->is_gijja,
            'is_template' => $user->is_template,
            'is_marketing' => $user->is_marketing,
            'is_diagnoz' => $user->is_diagnoz,
            'is_editor' => $user->is_editor,
            'is_cash_reg' => $user->is_cash_reg,
            'is_excel_repot' => $user->is_excel_repot,
            'is_certificates' => $user->is_certificates,
            'can_accept' => $user->can_accept,
            'is_payment' => $user->is_payment,
            'graph_format_date' => Carbon::now()->format('Y-m-d'),
            'time' => Carbon::now()->format('H:i'),
            'template' => $template,
            'department' => $department,
            'owner' => $owner,
            'setting' => $directorSetting,
            'branch' => $branch,
            'is_main_branch' => $is_main_branch,
            'years' => $this->getUniqueYearsWithCurrentYear($branch),
            // boshqa atributlar...
        ];
    }
    public function profileUpdate($request)
    {
        return $this->update(auth()->id(), $request);
    }
    public function passwordChange($request)
    {
        $user = $this->modelClass::find(auth()->id());
        $oldpas = $request->new_password === $request->con_new_password ? $request->old_password : '';
        $passwords = Hash::check($oldpas, $user->password) ?? false;
        if (!$passwords) {
            return [
                "status" => false,
                'message' => "Parol noto'g'ri kiritilgan ",
            ];
        } else {
            $user->password = Hash::make($request->input("new_password"));
            $user->save();
            return [ 
                "status" => 200,
                "message" => "parol o'zgartirldi",
            ];
        }
    }
}
