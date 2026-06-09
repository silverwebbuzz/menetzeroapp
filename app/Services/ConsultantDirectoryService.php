<?php

namespace App\Services;

use App\Models\Consultant;
use Illuminate\Support\Collection;

class ConsultantDirectoryService
{
    public function __construct(
        protected PlanEntitlementService $entitlements,
    ) {}

    public function approvedCount(): int
    {
        return Consultant::query()->where('status', 'approved')->where('is_active', true)->count();
    }

    public function directoryLevel(int $companyId): string
    {
        return $this->entitlements->consultantDirectoryLevel($companyId);
    }

    public function canBrowse(int $companyId): bool
    {
        return $this->directoryLevel($companyId) !== 'teaser' || $this->approvedCount() > 0;
    }

    public function canRequestIntro(int $companyId): bool
    {
        return in_array($this->directoryLevel($companyId), ['partial', 'full', 'priority'], true);
    }

    public function canSeeFullProfile(int $companyId): bool
    {
        return in_array($this->directoryLevel($companyId), ['full', 'priority'], true);
    }

    public function canSeeContact(int $companyId): bool
    {
        return in_array($this->directoryLevel($companyId), ['full', 'priority'], true);
    }

    /**
     * @return Collection<int, Consultant>
     */
    public function listedConsultantsQuery()
    {
        return Consultant::query()
            ->where('status', 'approved')
            ->where('is_active', true)
            ->withCount('introRequests')
            ->orderByDesc('is_featured')
            ->orderBy('company_name');
    }

    /**
     * @return Collection<int, Consultant>
     */
    public function listedConsultants(): Collection
    {
        return $this->listedConsultantsQuery()->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function presentForClient(Consultant $consultant, int $companyId): array
    {
        $level = $this->directoryLevel($companyId);
        $full = in_array($level, ['full', 'priority'], true);
        $partial = in_array($level, ['partial', 'full', 'priority'], true);

        return [
            'id' => $consultant->id,
            'display_name' => $partial ? $consultant->company_name : $this->blurredName($consultant),
            'company_name' => $partial ? $consultant->company_name : null,
            'bio' => $full ? $consultant->bio : ($partial ? \Illuminate\Support\Str::limit($consultant->bio ?? '', 120) : null),
            'specialties' => $partial ? $consultant->specialtyLabels() : [],
            'emirates' => $partial ? $consultant->emirateLabels() : [],
            'experience_years' => $partial ? $consultant->experience_years : null,
            'has_moccae_experience' => $partial && $consultant->has_moccae_experience,
            'is_featured' => $consultant->is_featured && in_array($level, ['full', 'priority'], true),
            'phone' => $full ? $consultant->phone : null,
            'email' => $full ? $consultant->email : null,
            'website' => $full ? $consultant->website : null,
            'linkedin' => $full ? $consultant->linkedin : null,
            'can_request_intro' => $this->canRequestIntro($companyId),
            'visibility' => $level,
        ];
    }

    protected function blurredName(Consultant $consultant): string
    {
        $name = $consultant->company_name;
        if (strlen($name) <= 3) {
            return '•••';
        }

        return substr($name, 0, 1) . str_repeat('•', max(3, strlen($name) - 2)) . substr($name, -1);
    }
}
