<?php

namespace App\Services;

use App\Models\ReferenceRoster;
use App\Models\User;
use App\Services\GraduationCalculator;
use Illuminate\Support\Facades\Log;

class ReferenceRosterSyncService
{
    public function syncExistingUsers(): array
    {
        $users = User::query()
            ->whereNotNull('graduation_year')
            ->whereNotNull('last_name')
            ->whereNotNull('first_name')
            ->get();

        $processed = 0;
        $approved = 0;
        $grantedYearAdmin = 0;

        foreach ($users as $user) {
            $targetRoster = $this->findMatchingRoster($user);
            if (!$targetRoster) {
                continue;
            }

            $updateData = [];
            $shouldApprove = false;
            $shouldGrantYearAdmin = false;

            if ($user->approval_status !== 'approved') {
                $updateData['approval_status'] = 'approved';
                $updateData['approved_at'] = now();
                $updateData['approval_note'] = '参照名簿照合により自動承認';
                $shouldApprove = true;
            }

            if ($this->shouldGrantYearAdmin($targetRoster) && $user->role !== 'year_admin') {
                $updateData['role'] = 'year_admin';
                $shouldGrantYearAdmin = true;
            }

            if (!empty($updateData)) {
                $user->update($updateData);
                $processed++;

                if ($shouldApprove) {
                    $approved++;
                }
                if ($shouldGrantYearAdmin) {
                    $grantedYearAdmin++;
                }
            }

            $targetRoster->update(['is_registered' => true]);
        }

        Log::info('参照名簿同期完了', [
            'processed' => $processed,
            'approved' => $approved,
            'granted_year_admin' => $grantedYearAdmin,
        ]);

        return [
            'processed' => $processed,
            'approved' => $approved,
            'granted_year_admin' => $grantedYearAdmin,
        ];
    }

    private function findMatchingRoster(User $user): ?ReferenceRoster
    {
        $searchName = $this->normalizeText($user->last_name . $user->first_name);
        $searchKana = $this->normalizeKana($user->last_name_kana . $user->first_name_kana);
        $searchGender = $this->normalizeGenderValue($user->gender);
        $targetRosterGenders = $this->getRosterGenderCandidates($searchGender);
        $graduationTerms = collect([
            '高校' . GraduationCalculator::yearToTerm($user->graduation_year) . '回期',
            '高校' . ($user->graduation_year - 1967) . '回期',
        ])->unique();

        return ReferenceRoster::query()
            ->whereIn('graduation_term', $graduationTerms)
            ->get()
            ->first(function ($roster) use ($searchName, $searchKana, $targetRosterGenders) {
                $rosterName = $this->normalizeText($roster->name ?? '');
                if ($rosterName !== $searchName) {
                    return false;
                }

                if (!in_array((string) ($roster->gender ?? ''), $targetRosterGenders, true)) {
                    return false;
                }

                if ($searchKana === '') {
                    return true;
                }

                $rosterKana = $this->normalizeKana($roster->kana ?? '');
                if ($rosterKana === '') {
                    return true;
                }

                return $rosterKana === $searchKana;
            });
    }

    private function shouldGrantYearAdmin(ReferenceRoster $roster): bool
    {
        return str_contains((string) ($roster->role_1 ?? ''), '常任理事');
    }

    private function normalizeText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return preg_replace('/[\s　]+/u', '', $value) ?? '';
    }

    private function normalizeKana(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = $this->normalizeText($value);
        $normalized = mb_convert_kana($normalized, 'KV', 'UTF-8');
        $normalized = mb_convert_kana($normalized, 'C', 'UTF-8');

        return $normalized;
    }

    private function normalizeGenderValue(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return match (mb_strtolower(trim($value), 'UTF-8')) {
            'male', 'm', '男性', '男' => 'male',
            'female', 'f', '女性', '女' => 'female',
            default => 'other',
        };
    }

    private function getRosterGenderCandidates(string $gender): array
    {
        return match ($gender) {
            'male' => ['男', '男性', 'm', 'M', 'male', 'Male', 'MALE'],
            'female' => ['女', '女性', 'f', 'F', 'female', 'Female', 'FEMALE'],
            default => ['その他', '不明', 'other', 'Other', 'OTHER', 'o', 'O'],
        };
    }
}
