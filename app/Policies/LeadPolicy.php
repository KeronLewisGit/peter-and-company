<?php

namespace App\Policies;

use App\Enums\LeadType;
use App\Enums\Role;
use App\Models\Lead;
use App\Models\LeadFile;
use App\Models\User;

class LeadPolicy
{
    /** Each role sees the request types it works (Role::leadTypes); admins see all. */
    public function view(User $user, Lead $lead): bool
    {
        return $user->canWork($lead->type);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->canWork($lead->type);
    }

    /**
     * Registration and licence numbers, credit terms, references and the declaration on a
     * trade account application: credit control and administrators. The trade desk sees
     * that an application exists and who sent it, nothing more.
     */
    public function viewCreditDetails(User $user, Lead $lead): bool
    {
        return $lead->type === LeadType::Account && $user->hasRole(Role::Credit, Role::Admin);
    }

    /** Approving or declining an application, and opening the trade account: credit control and administrators. */
    public function decideCredit(User $user, Lead $lead): bool
    {
        return $lead->type === LeadType::Account && $user->hasRole(Role::Credit, Role::Admin);
    }

    /**
     * Credit documents (trade licence, VAT certificate, ID, pharmacy licence, bank reference)
     * open for credit control only, not even administrators. CVs and cover letters open for
     * HR and administrators. Other uploads (a principal's brand deck) follow view().
     */
    public function openFile(User $user, Lead $lead, LeadFile $file): bool
    {
        if ($file->lead_id !== $lead->id) {
            return false;
        }
        if ($file->isCreditDocument()) {
            return $user->role === Role::Credit && $user->active;
        }
        if ($file->isHrDocument()) {
            return $user->hasRole(Role::Hr, Role::Admin) && $user->active;
        }

        return $this->view($user, $lead);
    }
}
