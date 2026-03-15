<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FamilyMessageChildScope
{
    /**
     * 家庭側: 現在の保護者に紐づく child_id 一覧を返す
     *
     * @return array<int>
     */
    public static function forFamily(Request $request): array
    {
        $ctx = FamilyChildContext::resolve($request);
        $activeChildId = (int)$ctx['activeChild']->id;

        $guardian = FamilyGuardianResolver::resolve($request, $activeChildId);
        $childIds = $guardian
            ? self::childIdsForGuardian((int)$guardian->id)
            : [];

        if (empty($childIds)) {
            $childIds = $ctx['siblings']
                ->pluck('id')
                ->map(fn ($id) => (int)$id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return self::normalize($childIds, $activeChildId);
    }

    /**
     * 管理側: 対象児童と同一保護者に紐づく child_id 一覧を返す
     *
     * @return array<int>
     */
    public static function forChildId(int $childId): array
    {
        if ($childId <= 0) {
            return [];
        }

        $guardian = FamilyGuardianResolver::resolveForChild($childId);
        $childIds = $guardian
            ? self::childIdsForGuardian((int)$guardian->id)
            : [];

        return self::normalize($childIds, $childId);
    }

    /**
     * @return array<int>
     */
    private static function childIdsForGuardian(int $guardianId): array
    {
        if ($guardianId <= 0) {
            return [];
        }

        return DB::table('child_guardian')
            ->where('guardian_id', $guardianId)
            ->pluck('child_id')
            ->map(fn ($id) => (int)$id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int> $childIds
     * @return array<int>
     */
    private static function normalize(array $childIds, int $fallbackChildId): array
    {
        $ids = collect($childIds)
            ->map(fn ($id) => (int)$id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($fallbackChildId > 0 && !in_array($fallbackChildId, $ids, true)) {
            $ids[] = $fallbackChildId;
        }

        return $ids;
    }
}

