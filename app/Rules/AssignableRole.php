<?php

namespace App\Rules;

use App\Models\User;
use App\Services\RoleHierarchyService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AssignableRole implements ValidationRule
{
    public function __construct(private User $currentUser) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $roleHierarchyService = app(RoleHierarchyService::class);

        if (! $roleHierarchyService->canAssignRole($this->currentUser, $value)) {
            $currentUserRole = $this->currentUser->getRoleNames()->first();
            $assignableRoles = $roleHierarchyService->getAssignableRoles($this->currentUser);

            if (empty($assignableRoles)) {
                $fail("You cannot assign any roles as a {$currentUserRole}.");
            } else {
                $availableRoles = implode(', ', $assignableRoles);
                $fail("You can only assign these roles: {$availableRoles}. You cannot assign roles equal to or higher than your own role ({$currentUserRole}).");
            }
        }
    }
}
