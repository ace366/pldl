<?php

namespace App\Support;

use App\Models\Child;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FamilyChildContext
{
    /**
     * @return array{loginChild: Child, activeChild: Child, siblings: Collection<int, Child>, familyLoginCode: string}
     */
    public static function resolve(Request $request): array
    {
        $loginChildId = (int)$request->session()->get('family_child_id', 0);
        $loginChild = Child::with(['guardians:id'])->findOrFail($loginChildId);

        $hasFamilyLoginCode = Schema::hasColumn('children', 'family_login_code');
        $familyLoginCode = trim((string)$request->session()->get('family_login_code', ''));
        if ($familyLoginCode === '') {
            $familyLoginCode = $hasFamilyLoginCode
                ? (string)($loginChild->family_login_code ?: $loginChild->child_code)
                : (string)$loginChild->child_code;
            $request->session()->put('family_login_code', $familyLoginCode);
        }

        $query = Child::query()
            ->with(['school', 'baseMaster'])
            ->where('status', 'enrolled');

        if ($hasFamilyLoginCode) {
            $query->where(function ($q) use ($familyLoginCode) {
                $q->where('family_login_code', $familyLoginCode)
                    ->orWhere('child_code', $familyLoginCode);
            });
        } else {
            $guardianIds = $loginChild->guardians->pluck('id')->all();
            if (!empty($guardianIds)) {
                $query->whereHas('guardians', function ($gq) use ($guardianIds) {
                    $gq->whereIn('guardians.id', $guardianIds);
                });
            } else {
                $query->where('id', $loginChild->id);
            }
        }

        $siblings = $query
            ->orderBy('grade')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($siblings->isEmpty()) {
            $siblings = Child::query()
                ->with(['school', 'baseMaster'])
                ->whereKey($loginChild->id)
                ->get();
        }

        $requestedChildId = (int)$request->query('child_id', (int)$request->session()->get('family_active_child_id', 0));
        $activeChild = $siblings->firstWhere('id', $requestedChildId)
            ?? $siblings->firstWhere('id', $loginChild->id)
            ?? $siblings->first();

        $request->session()->put('family_active_child_id', (int)$activeChild->id);

        return [
            'loginChild' => $loginChild,
            'activeChild' => $activeChild,
            'siblings' => $siblings,
            'familyLoginCode' => $familyLoginCode,
        ];
    }
}
