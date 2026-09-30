<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultitenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_people_index_only_shows_the_current_family(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $familyA = Family::factory()->create(['owner_id' => $owner->id, 'name' => 'Alpha Family']);
        $familyB = Family::factory()->create(['owner_id' => $other->id, 'name' => 'Beta Family']);

        $familyA->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);
        $familyB->members()->attach($other->id, ['role' => 'owner', 'joined_at' => now()]);

        $familyA->execute(function () use ($familyA, $owner) {
            Person::create([
                'family_id' => $familyA->id,
                'first_name' => 'Ada',
                'last_name' => 'Alpha',
                'created_by' => $owner->id,
            ]);
        });

        $familyB->execute(function () use ($familyB, $other) {
            Person::create([
                'family_id' => $familyB->id,
                'first_name' => 'Bea',
                'last_name' => 'Beta',
                'created_by' => $other->id,
            ]);
        });

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $familyA->id])
            ->get(route('people.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('People/Index')
                ->has('people.data', 1)
                ->where('people.data.0.first_name', 'Ada'));
    }

    public function test_user_cannot_open_a_person_from_another_family(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $familyA = Family::factory()->create(['owner_id' => $owner->id]);
        $familyB = Family::factory()->create(['owner_id' => $other->id]);

        $familyA->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);
        $familyB->members()->attach($other->id, ['role' => 'owner', 'joined_at' => now()]);

        $hidden = $familyB->execute(fn () => Person::create([
            'family_id' => $familyB->id,
            'first_name' => 'Hidden',
            'created_by' => $other->id,
        ]));

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $familyA->id])
            ->get(route('people.show', $hidden))
            ->assertNotFound();
    }

    public function test_switching_family_sets_the_current_tenant(): void
    {
        $user = User::factory()->create();
        $familyA = Family::factory()->create(['owner_id' => $user->id, 'name' => 'First']);
        $familyB = Family::factory()->create(['owner_id' => $user->id, 'name' => 'Second']);

        $familyA->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $familyB->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $familyA->id])
            ->post(route('families.switch', $familyB))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($familyB->id, Family::current()?->id);
    }
}
