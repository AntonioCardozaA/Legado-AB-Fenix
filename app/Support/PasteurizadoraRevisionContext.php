<?php

namespace App\Support;

use App\Models\Linea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Read snapshot owned by one revision operation; never stored on a model or container. */
final class PasteurizadoraRevisionContext
{
    private array $records = [];

    public function __construct(private readonly Linea $linea) {}

    public function lineaFor(mixed $id): ?Linea
    {
        return (string) $id === (string) $this->linea->getKey() ? $this->linea : null;
    }

    public function records(Builder $query): Collection
    {
        $key = hash('sha256', serialize([
            $query->getConnection()->getName(), $query->toSql(), $query->getBindings(),
            array_keys($query->getEagerLoads()),
        ]));

        return $this->records[$key] ??= $query->get();
    }
}
