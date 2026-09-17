<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Citation;
use App\Models\Family;
use App\Models\LifeEvent;
use App\Models\Person;
use App\Models\Place;
use App\Models\Relationship;
use App\Models\Source;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $divya = User::query()->updateOrCreate(
            ['email' => 'divya@example.com'],
            ['name' => 'Divya Shrestha', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        $cousin = User::query()->updateOrCreate(
            ['email' => 'viewer@example.com'],
            ['name' => 'Family Viewer', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        $family = Family::query()->updateOrCreate(
            ['name' => 'Shrestha Family', 'owner_id' => $divya->id],
            ['description' => 'A private workspace for recording Shrestha relatives, stories, and photographs.']
        );

        $family->members()->syncWithoutDetaching([
            $divya->id => ['role' => 'owner', 'joined_at' => now()],
            $cousin->id => ['role' => 'viewer', 'joined_at' => now()],
        ]);

        $kathmandu = Place::firstOrCreate(['name' => 'Kathmandu'], ['country' => 'Nepal']);
        $bhaktapur = Place::firstOrCreate(['name' => 'Bhaktapur'], ['country' => 'Nepal', 'region' => 'Bagmati']);
        $london = Place::firstOrCreate(['name' => 'London'], ['country' => 'England']);

        $make = function (array $attrs) use ($family, $divya) {
            return Person::updateOrCreate(
                [
                    'family_id' => $family->id,
                    'first_name' => $attrs['first_name'],
                    'last_name' => $attrs['last_name'] ?? null,
                    'birth_date' => $attrs['birth_date'] ?? null,
                ],
                array_merge($attrs, [
                    'family_id' => $family->id,
                    'created_by' => $divya->id,
                ])
            );
        };

        $grandfather = $make([
            'first_name' => 'Hari',
            'last_name' => 'Shrestha',
            'gender' => 'male',
            'birth_date' => '1938-03-12',
            'birth_date_precision' => 'approximate',
            'birth_place_id' => $bhaktapur->id,
            'death_date' => '2012-08-04',
            'death_date_precision' => 'exact',
            'is_living' => false,
            'notes' => 'Birth year is approximate; family bible says “around 1938”.',
        ]);

        $grandmother = $make([
            'first_name' => 'Sita',
            'last_name' => 'Shrestha',
            'maiden_name' => 'Maharjan',
            'gender' => 'female',
            'birth_date' => '1942-01-01',
            'birth_date_precision' => 'year',
            'birth_place_id' => $kathmandu->id,
            'is_living' => true,
            'notes' => 'Only the birth year is known.',
        ]);

        $father = $make([
            'first_name' => 'Rajan',
            'last_name' => 'Shrestha',
            'gender' => 'male',
            'birth_date' => '1968-06-21',
            'birth_date_precision' => 'exact',
            'birth_place_id' => $kathmandu->id,
            'is_living' => true,
        ]);

        $mother = $make([
            'first_name' => 'Anjali',
            'last_name' => 'Shrestha',
            'maiden_name' => 'Pradhan',
            'gender' => 'female',
            'birth_date' => '1971-11-02',
            'birth_date_precision' => 'exact',
            'birth_place_id' => $kathmandu->id,
            'is_living' => true,
        ]);

        $uncle = $make([
            'first_name' => 'Bikash',
            'last_name' => 'Shrestha',
            'gender' => 'male',
            'birth_date' => '1970-09-15',
            'birth_date_precision' => 'exact',
            'is_living' => true,
        ]);

        $self = $make([
            'first_name' => 'Divya',
            'last_name' => 'Shrestha',
            'gender' => 'female',
            'birth_date' => '1998-04-09',
            'birth_date_precision' => 'exact',
            'birth_place_id' => $london->id,
            'is_living' => true,
            'notes' => 'Started this family workspace to keep stories in one place.',
        ]);

        $brother = $make([
            'first_name' => 'Nabin',
            'last_name' => 'Shrestha',
            'gender' => 'male',
            'birth_date' => '2001-12-01',
            'birth_date_precision' => 'month',
            'is_living' => true,
        ]);

        $cousinPerson = $make([
            'first_name' => 'Maya',
            'last_name' => 'Shrestha',
            'gender' => 'female',
            'birth_date' => '1999-07-18',
            'birth_date_precision' => 'exact',
            'is_living' => true,
        ]);

        $link = function (Person $one, Person $two, string $type, array $extra = []) use ($family, $divya) {
            [$a, $b] = Relationship::canonicalize($one->id, $two->id, $type);
            Relationship::updateOrCreate(
                [
                    'person_one_id' => $a,
                    'person_two_id' => $b,
                    'relationship_type' => $type,
                ],
                array_merge([
                    'family_id' => $family->id,
                    'is_directional' => Relationship::isDirectionalType($type),
                    'confidence_level' => 'confirmed',
                    'created_by' => $divya->id,
                ], $extra)
            );
        };

        $link($grandfather, $father, 'parent');
        $link($grandmother, $father, 'parent');
        $link($grandfather, $uncle, 'parent');
        $link($grandmother, $uncle, 'parent');
        $link($father, $self, 'parent');
        $link($mother, $self, 'parent');
        $link($father, $brother, 'parent');
        $link($mother, $brother, 'parent');
        $link($uncle, $cousinPerson, 'parent');
        $link($grandfather, $grandmother, 'spouse', [
            'start_date' => '1962-02-14',
            'start_date_precision' => 'approximate',
            'start_date_text' => 'circa 1962',
        ]);
        $link($father, $mother, 'spouse', [
            'start_date' => '1995-05-20',
            'start_date_precision' => 'exact',
        ]);
        $link($father, $uncle, 'sibling');
        $link($self, $brother, 'sibling');

        $marriage = LifeEvent::updateOrCreate(
            ['family_id' => $family->id, 'event_type' => 'marriage', 'event_date' => '1995-05-20'],
            [
                'event_date_precision' => 'exact',
                'place_id' => $kathmandu->id,
                'description' => 'Rajan and Anjali married in Kathmandu.',
                'created_by' => $divya->id,
            ]
        );
        $marriage->people()->syncWithoutDetaching([
            $father->id => ['role' => 'spouse'],
            $mother->id => ['role' => 'spouse'],
        ]);

        $migration = LifeEvent::updateOrCreate(
            ['family_id' => $family->id, 'event_type' => 'migration', 'event_date' => '1996-09-01'],
            [
                'event_date_precision' => 'month',
                'place_id' => $london->id,
                'description' => 'Moved to London for work.',
                'created_by' => $divya->id,
            ]
        );
        $migration->people()->syncWithoutDetaching([
            $father->id => ['role' => 'subject'],
            $mother->id => ['role' => 'subject'],
        ]);

        $source = Source::updateOrCreate(
            ['family_id' => $family->id, 'title' => 'Family bible kept by Sita Shrestha'],
            [
                'type' => 'book',
                'citation_text' => 'Handwritten entries in the Shrestha family bible, Bhaktapur.',
                'created_by' => $divya->id,
            ]
        );

        Citation::updateOrCreate(
            [
                'source_id' => $source->id,
                'citable_type' => 'person',
                'citable_id' => $grandfather->id,
                'field_name' => 'birth_date',
            ],
            [
                'family_id' => $family->id,
                'confidence_level' => 'probable',
                'notes' => 'Year only; day and month were added later from memory.',
                'created_by' => $divya->id,
            ]
        );

        AuditLog::record($family, $divya, 'created', $family, ['seeded' => true]);
    }
}
