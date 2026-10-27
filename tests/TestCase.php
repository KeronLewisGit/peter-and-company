<?php

namespace Tests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function staff(Role $role): User
    {
        return User::factory()->role($role)->create();
    }

    /** @return array<string, string> */
    protected function contact(array $overrides = []): array
    {
        return array_merge(['name' => 'Shanice Joseph', 'phone' => '758-452-1234', 'email' => 'shanice@example.com', 'contact_pref' => 'whatsapp'], $overrides);
    }
}
