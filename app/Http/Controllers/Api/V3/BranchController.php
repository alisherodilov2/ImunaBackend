<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Branch\BranchResource;
use App\Models\Branch;
use App\Services\Api\V3\Contracts\BranchServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BranchController extends Controller
{

    public $modelClass = Branch::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;


/*************  ✨ Codeium Command ⭐  *************/
    /**
     * @OA\Get(
     *     path="/api/v3/branch",
     *     summary="Get all branches",
     *     tags={"Branch"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name, code",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit per page",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     ref="#/components/schemas/Branch"
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string"
     *             )
     *         )
     *     )
     * )
     */
/******  27ba7e89-9d35-4963-a911-823169b373e2  *******/
    public function index(Request $request, BranchServiceInterface $service)
    {
        $res = ($service->filter());
        return $this->success($res);
    }
    public function remainingBranches(Request $request, BranchServiceInterface $service)
    {
        $res = ($service->remainingBranches($request));
        return $this->success($res);
    }
    public function store(Request $request, BranchServiceInterface $service)
    {

        return $this->success(($service->add($request)));
    }
    public function update(Request $request, $id, BranchServiceInterface $service)
    {
        return $this->success(($service->edit($id, $request)));
    }
}
