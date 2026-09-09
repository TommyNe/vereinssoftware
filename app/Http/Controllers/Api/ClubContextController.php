<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\SwitchClubRequest;
use Illuminate\Http\JsonResponse;

class ClubContextController extends Controller
{
    public function switch(
        SwitchClubRequest $request
    ): JsonResponse {
        $user = $request->user();

        $club = $user
            ->clubs()
            ->whereKey(
                $request->string('club_id')->toString()
            )
            ->firstOrFail();

        session([
            'current_club_id' => $club->id,
        ]);

        setPermissionsTeamId($club->id);

        $user->unsetRelation('roles')
            ->unsetRelation('permissions');

        return response()->json([
            'data' => [
                'club_id' => $club->id,
                'name' => $club->name,
            ]
        ]);
    }
}
