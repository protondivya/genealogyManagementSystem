<?php

namespace App\Services;

use App\Models\Family;
use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Support\Collection;

class GraphService
{
    public const MAX_DEPTH = 15;

    public function familyEdges(Family $family): Collection
    {
        return $family->relationships()->get();
    }

    public function wouldCreateCycle(int $parentId, int $childId, Family $family): bool
    {
        if ($parentId === $childId) {
            return true;
        }

        $descendants = $this->walk($family, $childId, 'descendants', self::MAX_DEPTH);

        return in_array($parentId, $descendants, true);
    }

    public function walk(Family $family, int $startId, string $direction = 'descendants', int $maxDepth = self::MAX_DEPTH): array
    {
        $edges = $this->directionalEdges($family);
        $visited = [];
        $queue = [[$startId, 0]];

        while ($queue) {
            [$id, $depth] = array_shift($queue);
            if ($depth >= $maxDepth) {
                continue;
            }

            $neighbors = $direction === 'ancestors'
                ? ($edges['parents'][$id] ?? [])
                : ($edges['children'][$id] ?? []);

            foreach ($neighbors as $next) {
                if (isset($visited[$next])) {
                    continue;
                }
                $visited[$next] = true;
                $queue[] = [$next, $depth + 1];
            }
        }

        return array_keys($visited);
    }

    public function buildTree(Family $family, Person $root, int $depth = 4, bool $redactLiving = false): array
    {
        $people = $family->people()->with(['birthPlace', 'deathPlace'])->get()->keyBy('id');
        $edges = $this->directionalEdges($family);
        $spouses = $this->spouseMap($family);

        $nodes = [];
        $seen = [];
        $this->collectTree($root->id, $people, $edges, $spouses, $nodes, $seen, 0, $depth, $redactLiving);

        return [
            'root_id' => $root->id,
            'depth' => $depth,
            'nodes' => array_values($nodes),
        ];
    }

    public function shortestPath(Family $family, Person $from, Person $to, bool $redactLiving = false): array
    {
        if ($from->id === $to->id) {
            return [
                'found' => true,
                'path' => [$from->toPublicArray($redactLiving)],
                'steps' => [],
                'phrase' => 'This is the same person.',
            ];
        }

        $adjacency = $this->undirectedAdjacency($family);
        $queue = [[$from->id]];
        $visited = [$from->id => true];
        $found = null;

        while ($queue) {
            $path = array_shift($queue);
            $current = end($path);
            if (count($path) > self::MAX_DEPTH) {
                continue;
            }

            foreach ($adjacency[$current] ?? [] as $edge) {
                $next = $edge['other'];
                if (isset($visited[$next])) {
                    continue;
                }
                $visited[$next] = true;
                $newPath = [...$path, $next];
                if ($next === $to->id) {
                    $found = $newPath;
                    break 2;
                }
                $queue[] = $newPath;
            }
        }

        if (! $found) {
            return [
                'found' => false,
                'path' => [],
                'steps' => [],
                'phrase' => 'No relationship path was found between these two people.',
            ];
        }

        $people = $family->people()->with(['birthPlace', 'deathPlace'])->get()->keyBy('id');
        $pathPeople = array_map(fn ($id) => $people[$id]->toPublicArray($redactLiving), $found);
        $steps = [];
        for ($i = 0; $i < count($found) - 1; $i++) {
            $a = $found[$i];
            $b = $found[$i + 1];
            $rel = $this->edgeBetween($adjacency, $a, $b);
            $steps[] = [
                'from' => $people[$a]->toPublicArray($redactLiving),
                'to' => $people[$b]->toPublicArray($redactLiving),
                'type' => $rel['type'] ?? 'related',
                'label' => $this->stepLabel($people[$a], $people[$b], $rel),
            ];
        }

        return [
            'found' => true,
            'path' => $pathPeople,
            'steps' => $steps,
            'phrase' => $this->phraseFromSteps($steps, $from),
        ];
    }

    private function collectTree(
        int $id,
        Collection $people,
        array $edges,
        array $spouses,
        array &$nodes,
        array &$seen,
        int $generation,
        int $maxDepth,
        bool $redactLiving
    ): void {
        if (isset($seen[$id]) || $generation > $maxDepth || ! isset($people[$id])) {
            return;
        }

        $seen[$id] = true;
        $person = $people[$id];
        $childIds = $edges['children'][$id] ?? [];
        $parentIds = $edges['parents'][$id] ?? [];
        $spouseIds = $spouses[$id] ?? [];

        $nodes[$id] = [
            ...$person->toPublicArray($redactLiving),
            'generation' => $generation,
            'parent_ids' => $parentIds,
            'child_ids' => $childIds,
            'spouse_ids' => $spouseIds,
        ];

        foreach ($spouseIds as $spouseId) {
            if (! isset($seen[$spouseId]) && isset($people[$spouseId])) {
                $seen[$spouseId] = true;
                $nodes[$spouseId] = [
                    ...$people[$spouseId]->toPublicArray($redactLiving),
                    'generation' => $generation,
                    'parent_ids' => $edges['parents'][$spouseId] ?? [],
                    'child_ids' => $edges['children'][$spouseId] ?? [],
                    'spouse_ids' => $spouses[$spouseId] ?? [],
                ];
            }
        }

        foreach ($parentIds as $parentId) {
            $this->collectTree($parentId, $people, $edges, $spouses, $nodes, $seen, $generation - 1, $maxDepth, $redactLiving);
        }

        foreach ($childIds as $childId) {
            $this->collectTree($childId, $people, $edges, $spouses, $nodes, $seen, $generation + 1, $maxDepth, $redactLiving);
        }
    }

    private function directionalEdges(Family $family): array
    {
        $parents = [];
        $children = [];

        $family->relationships()
            ->whereIn('relationship_type', Relationship::DIRECTIONAL)
            ->get()
            ->each(function (Relationship $rel) use (&$parents, &$children) {
                $parent = $rel->person_one_id;
                $child = $rel->person_two_id;
                $parents[$child][] = $parent;
                $children[$parent][] = $child;
            });

        return ['parents' => $parents, 'children' => $children];
    }

    private function spouseMap(Family $family): array
    {
        $map = [];
        $family->relationships()
            ->whereIn('relationship_type', ['spouse', 'partner'])
            ->get()
            ->each(function (Relationship $rel) use (&$map) {
                $map[$rel->person_one_id][] = $rel->person_two_id;
                $map[$rel->person_two_id][] = $rel->person_one_id;
            });

        return $map;
    }

    private function undirectedAdjacency(Family $family): array
    {
        $adj = [];
        $family->relationships()->get()->each(function (Relationship $rel) use (&$adj) {
            $adj[$rel->person_one_id][] = [
                'other' => $rel->person_two_id,
                'type' => $rel->relationship_type,
                'direction' => 'forward',
            ];
            $adj[$rel->person_two_id][] = [
                'other' => $rel->person_one_id,
                'type' => $rel->relationship_type,
                'direction' => 'reverse',
            ];
        });

        return $adj;
    }

    private function edgeBetween(array $adjacency, int $a, int $b): array
    {
        foreach ($adjacency[$a] ?? [] as $edge) {
            if ($edge['other'] === $b) {
                return $edge;
            }
        }

        return ['type' => 'related', 'direction' => 'forward'];
    }

    private function stepLabel(Person $from, Person $to, array $rel): string
    {
        $type = $rel['type'] ?? 'related';
        $forward = ($rel['direction'] ?? 'forward') === 'forward';

        return match ($type) {
            'parent' => $forward ? $from->full_name.' is a parent of '.$to->full_name : $from->full_name.' is a child of '.$to->full_name,
            'adoptive_parent' => $forward ? $from->full_name.' is an adoptive parent of '.$to->full_name : $from->full_name.' was adopted by '.$to->full_name,
            'step_parent' => $forward ? $from->full_name.' is a step-parent of '.$to->full_name : $from->full_name.' is a step-child of '.$to->full_name,
            'guardian' => $forward ? $from->full_name.' is a guardian of '.$to->full_name : $from->full_name.' is a ward of '.$to->full_name,
            'spouse' => $from->full_name.' is a spouse of '.$to->full_name,
            'partner' => $from->full_name.' is a partner of '.$to->full_name,
            'sibling' => $from->full_name.' is a sibling of '.$to->full_name,
            default => $from->full_name.' is related to '.$to->full_name,
        };
    }

    private function phraseFromSteps(array $steps, Person $from): string
    {
        if (! $steps) {
            return 'These two people are the same person.';
        }

        $chain = [];
        foreach ($steps as $step) {
            $type = $step['type'];
            $chain[] = match ($type) {
                'parent' => str_contains($step['label'], 'is a parent') ? 'parent' : 'child',
                'adoptive_parent' => str_contains($step['label'], 'adoptive parent') ? 'adoptive parent' : 'adopted child',
                'step_parent' => str_contains($step['label'], 'step-parent') ? 'step-parent' : 'step-child',
                'guardian' => str_contains($step['label'], 'guardian') ? 'guardian' : 'ward',
                'spouse' => 'spouse',
                'partner' => 'partner',
                'sibling' => 'sibling',
                default => 'relative',
            };
        }

        $readable = implode("'s ", $chain);

        return $from->full_name."'s ".$readable;
    }
}
