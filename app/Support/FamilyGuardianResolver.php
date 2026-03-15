<?php

namespace App\Support;

use App\Models\Child;
use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FamilyGuardianResolver
{
    public static function resolve(Request $request, int $childId): ?Guardian
    {
        if ($childId <= 0) {
            return null;
        }

        $childGuardianIds = self::guardianIdsForChildren([$childId]);
        $familyGuardianIds = self::familyGuardianIds($request, $childId);

        $sessionMap = (array)$request->session()->get('family_guardian_by_child', []);
        $mappedGuardianId = (int)($sessionMap[(string)$childId] ?? 0);
        $sessionGuardianId = $mappedGuardianId > 0
            ? $mappedGuardianId
            : (int)$request->session()->get('family_guardian_id', 0);

        if ($sessionGuardianId > 0 && in_array($sessionGuardianId, $childGuardianIds, true)) {
            $selected = Guardian::query()->find($sessionGuardianId);
            if ($selected) {
                self::persist($request, $childId, (int)$selected->id);
                return $selected;
            }
        }

        $storedGuardianId = self::storedGuardianIdForChild($childId);
        if ($storedGuardianId > 0 && in_array($storedGuardianId, $childGuardianIds, true)) {
            $selected = Guardian::query()->find($storedGuardianId);
            if ($selected) {
                self::persist($request, $childId, (int)$selected->id);
                return $selected;
            }
        }

        if (!empty($childGuardianIds)) {
            $selected = self::pickGuardian($childGuardianIds);
            if ($selected) {
                self::persist($request, $childId, (int)$selected->id);
                return $selected;
            }
        }

        if ($sessionGuardianId > 0 && in_array($sessionGuardianId, $familyGuardianIds, true)) {
            $selected = Guardian::query()->find($sessionGuardianId);
            if ($selected) {
                self::persist($request, $childId, (int)$selected->id);
                return $selected;
            }
        }

        if (!empty($familyGuardianIds)) {
            $selected = self::pickGuardian($familyGuardianIds);
            if ($selected) {
                self::persist($request, $childId, (int)$selected->id);
                return $selected;
            }
        }

        return null;
    }

    public static function resolveForChild(int $childId): ?Guardian
    {
        if ($childId <= 0) {
            return null;
        }

        $childGuardianIds = self::guardianIdsForChildren([$childId]);
        if (empty($childGuardianIds)) {
            return null;
        }

        $storedGuardianId = self::storedGuardianIdForChild($childId);
        if ($storedGuardianId > 0 && in_array($storedGuardianId, $childGuardianIds, true)) {
            $selected = Guardian::query()->find($storedGuardianId);
            if ($selected) {
                return $selected;
            }
        }

        return self::pickGuardian($childGuardianIds);
    }

    public static function setForChild(Request $request, int $childId, int $guardianId): void
    {
        if ($childId <= 0 || $guardianId <= 0) {
            return;
        }
        self::persist($request, $childId, $guardianId);
        self::saveStoredGuardianIdForChild($childId, $guardianId);
    }

    /**
     * @param array<int> $guardianIds
     */
    private static function pickGuardian(array $guardianIds): ?Guardian
    {
        if (empty($guardianIds)) {
            return null;
        }

        if (Schema::hasColumn('guardians', 'avatar_path')) {
            $withAvatar = Guardian::query()
                ->whereIn('id', $guardianIds)
                ->whereNotNull('avatar_path')
                ->where('avatar_path', '<>', '')
                ->orderByDesc('updated_at')
                ->first();
            if ($withAvatar) {
                return $withAvatar;
            }
        }

        return Guardian::query()
            ->whereIn('id', $guardianIds)
            ->orderBy('id')
            ->first();
    }

    private static function persist(Request $request, int $childId, int $guardianId): void
    {
        $map = (array)$request->session()->get('family_guardian_by_child', []);
        $map[(string)$childId] = $guardianId;
        $request->session()->put('family_guardian_by_child', $map);
        $request->session()->put('family_guardian_id', $guardianId);
    }

    /**
     * @param array<int> $childIds
     * @return array<int>
     */
    private static function guardianIdsForChildren(array $childIds): array
    {
        if (empty($childIds)) {
            return [];
        }

        return DB::table('child_guardian')
            ->whereIn('child_id', $childIds)
            ->pluck('guardian_id')
            ->map(fn ($id) => (int)$id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int>
     */
    private static function familyGuardianIds(Request $request, int $childId): array
    {
        $familyLoginCode = trim((string)$request->session()->get('family_login_code', ''));
        if ($familyLoginCode === '' || !Schema::hasColumn('children', 'family_login_code')) {
            return self::guardianIdsForChildren([$childId]);
        }

        $familyChildIds = Child::query()
            ->where('status', 'enrolled')
            ->where(function ($q) use ($familyLoginCode) {
                $q->where('family_login_code', $familyLoginCode)
                    ->orWhere('child_code', $familyLoginCode);
            })
            ->pluck('id')
            ->map(fn ($id) => (int)$id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($familyChildIds)) {
            $familyChildIds = [$childId];
        }

        return self::guardianIdsForChildren($familyChildIds);
    }

    private static function storedGuardianIdForChild(int $childId): int
    {
        if ($childId <= 0 || !Schema::hasColumn('children', 'message_icon_guardian_id')) {
            return 0;
        }

        return (int)(Child::query()->whereKey($childId)->value('message_icon_guardian_id') ?? 0);
    }

    private static function saveStoredGuardianIdForChild(int $childId, int $guardianId): void
    {
        if ($childId <= 0 || $guardianId <= 0 || !Schema::hasColumn('children', 'message_icon_guardian_id')) {
            return;
        }

        Child::query()
            ->whereKey($childId)
            ->update(['message_icon_guardian_id' => $guardianId]);
    }
}
