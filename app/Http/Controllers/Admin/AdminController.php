<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class AdminController extends Controller
{
    /**
     * Map of column → fully-qualified model class for FK columns where the
     * convention `{column}` → `App\Models\{Studly}` doesn't match the actual class.
     * Override per-controller as needed.
     */
    protected array $foreignKeyMap = [];

    /**
     * Smart default: id + first 4 fillable + created_at.
     */
    protected function columns(): array
    {
        $instance = new ($this->modelClass);
        $fillable = array_slice($instance->getFillable(), 0, 4);
        return array_values(array_unique(array_filter(array_merge(['id'], $fillable, ['created_at']))));
    }

    /**
     * Smart default: build form fields from $fillable + DB column types.
     * `*_id` columns turn into <select> dropdowns populated from the related model.
     */
    protected function fields(): array
    {
        $instance = new ($this->modelClass);
        $fillable = $instance->getFillable();
        if (empty($fillable)) return [];

        $table = $instance->getTable();
        $cached = cache()->remember("admin.cols.$table", 60, function () use ($table) {
            try {
                return Schema::getColumns($table);
            } catch (\Throwable $e) {
                return [];
            }
        });
        $colMeta = collect($cached)->keyBy('name');

        $imageColumnNames = ['image', 'photo', 'thumbnail', 'banner', 'banner_image', 'poster', 'poster_image', 'logo', 'avatar', 'cover', 'cover_image', 'icon'];

        $fields = [];
        foreach ($fillable as $col) {
            if (in_array($col, ['password', 'remember_token'])) continue;

            // Image / file picker (Laravel Filemanager)
            if (in_array($col, $imageColumnNames) || str_ends_with($col, '_image') || str_ends_with($col, '_photo')) {
                $fields[] = [
                    'name' => $col,
                    'label' => ucwords(str_replace('_', ' ', $col)),
                    'type' => 'image',
                ];
                continue;
            }

            // Foreign-key dropdown
            if (str_ends_with($col, '_id') && $col !== 'id') {
                $relatedClass = $this->resolveRelatedModel($col);
                if ($relatedClass) {
                    $fields[] = [
                        'name' => $col,
                        'label' => ucwords(str_replace(['_', ' id'], [' ', ''], $col)),
                        'type' => 'select',
                        'options' => $this->relatedOptions($relatedClass),
                        'placeholder' => '— Select —',
                    ];
                    continue;
                }
            }

            // Status enum convenience: keep as text but show available values via DB enum if present
            $type = strtolower($colMeta[$col]['type_name'] ?? 'string');
            $fields[] = [
                'name' => $col,
                'label' => ucwords(str_replace('_', ' ', $col)),
                'type' => match (true) {
                    str_contains($type, 'json') => 'textarea',
                    str_contains($type, 'text') => 'textarea',
                    str_contains($type, 'tinyint'), str_contains($type, 'bool') => 'checkbox',
                    str_contains($type, 'date') && ! str_contains($type, 'datetime') => 'date',
                    str_contains($type, 'datetime'), str_contains($type, 'timestamp') => 'datetime-local',
                    $type === 'time' => 'time',
                    str_contains($type, 'int'), str_contains($type, 'decimal'), str_contains($type, 'float') => 'number',
                    default => 'text',
                },
            ];
        }
        return $fields;
    }

    /**
     * Resolve `movie_id` → `App\Models\Movie`, with $foreignKeyMap as override.
     */
    protected function resolveRelatedModel(string $col): ?string
    {
        if (isset($this->foreignKeyMap[$col]) && class_exists($this->foreignKeyMap[$col])) {
            return $this->foreignKeyMap[$col];
        }
        $base = Str::studly(Str::beforeLast($col, '_id'));
        $class = "App\\Models\\$base";
        return class_exists($class) ? $class : null;
    }

    /**
     * Build a map of [columnName => [id => displayName]] for every FK column listed in $columns.
     * Used by admin index views to render entity names instead of raw IDs.
     */
    protected function fkLabelMap(array $columns): array
    {
        $map = [];
        foreach ($columns as $col) {
            if (! str_ends_with($col, '_id') || $col === 'id') continue;
            $related = $this->resolveRelatedModel($col);
            if (! $related) continue;
            $instance = new $related;
            $candidates = ['title', 'name', 'code', 'slug', 'email', 'question'];
            $displayCol = null;
            foreach ($candidates as $c) {
                if (in_array($c, $instance->getFillable())) { $displayCol = $c; break; }
            }
            $cols = $displayCol ? ['id', $displayCol] : ['id'];
            $shortName = class_basename($related);
            $map[$col] = $related::query()->limit(1000)->get($cols)
                ->mapWithKeys(fn ($m) => [$m->id => $displayCol ? Str::limit($m->{$displayCol}, 40) : $shortName . ' #' . $m->id])
                ->all();
        }
        return $map;
    }

    /**
     * Build [id => "Display Name (#id)"] options for a related model.
     * Picks the first available display column: title, name, code, slug, email, question.
     */
    protected function relatedOptions(string $relatedClass): array
    {
        $instance = new $relatedClass;
        $candidates = ['title', 'name', 'code', 'slug', 'email', 'question'];
        $displayCol = null;
        foreach ($candidates as $c) {
            if (in_array($c, $instance->getFillable())) { $displayCol = $c; break; }
        }
        $cols = $displayCol ? ['id', $displayCol] : ['id'];
        $shortName = class_basename($relatedClass);
        return $relatedClass::query()
            ->orderBy($displayCol ?: 'id')
            ->limit(500)
            ->get($cols)
            ->mapWithKeys(fn ($m) => [$m->id => $displayCol
                ? Str::limit($m->{$displayCol}, 60) . ' (#' . $m->id . ')'
                : $shortName . ' #' . $m->id])
            ->all();
    }

    // ---- Index search & filtering (shared by all list pages) ---------------

    /** Text columns (from the schema) that the `q` search box matches with LIKE. */
    protected function searchableColumns(): array
    {
        $instance = new ($this->modelClass);
        $table = $instance->getTable();
        $cached = cache()->remember("admin.cols.$table", 60, function () use ($table) {
            try {
                return Schema::getColumns($table);
            } catch (\Throwable $e) {
                return [];
            }
        });
        $meta = collect($cached)->keyBy('name');

        $out = [];
        foreach ($instance->getFillable() as $col) {
            if (in_array($col, ['password', 'remember_token'])) continue;
            if (str_ends_with($col, '_id')) continue;
            $type = strtolower($meta[$col]['type_name'] ?? 'string');
            // Plain text-ish columns only (skip json/blob/numeric/date).
            if ($type === 'string' || str_contains($type, 'char') || str_contains($type, 'text')) {
                if (str_contains($type, 'json')) continue;
                $out[] = $col;
            }
        }
        return $out;
    }

    /** [relationMethod => displayColumn] so the search box can also match related names. */
    protected function searchRelations(): array
    {
        $instance = new ($this->modelClass);
        $rels = [];
        foreach ($instance->getFillable() as $col) {
            if (! str_ends_with($col, '_id') || $col === 'id') continue;
            $related = $this->resolveRelatedModel($col);
            if (! $related) continue;
            $method = Str::camel(Str::beforeLast($col, '_id'));
            if (! method_exists($instance, $method)) continue;
            $rInstance = new $related;
            $disp = collect(['title', 'name', 'code', 'slug', 'email'])
                ->first(fn ($c) => in_array($c, $rInstance->getFillable()));
            if ($disp) $rels[$method] = $disp;
        }
        return $rels;
    }

    /** FK columns exposed as dropdown filters on the index. */
    protected function indexFilterColumns(): array
    {
        $instance = new ($this->modelClass);
        return array_values(array_filter(
            $instance->getFillable(),
            fn ($c) => str_ends_with($c, '_id') && $c !== 'id' && $this->resolveRelatedModel($c)
        ));
    }

    /** Options for one FK filter dropdown. Override per-controller for nicer labels. */
    protected function filterOptionsFor(string $col): array
    {
        $related = $this->resolveRelatedModel($col);
        return $related ? $this->relatedOptions($related) : [];
    }

    /**
     * Apply ?q= search and ?fk= filters from the request to $query.
     * Returns metadata for the view's filter bar.
     */
    protected function applyIndexFilters($query): array
    {
        $request = request();
        $q = trim((string) $request->query('q', ''));
        $searchCols = $this->searchableColumns();
        $searchRels = $this->searchRelations();

        if ($q !== '') {
            $query->where(function ($w) use ($q, $searchCols, $searchRels) {
                foreach ($searchCols as $c) {
                    $w->orWhere($c, 'like', "%{$q}%");
                }
                foreach ($searchRels as $method => $disp) {
                    $w->orWhereHas($method, fn ($r) => $r->where($disp, 'like', "%{$q}%"));
                }
            });
        }

        $fk = [];
        foreach ($this->indexFilterColumns() as $col) {
            $val = $request->query($col);
            if ($val !== null && $val !== '') {
                $query->where($col, $val);
            }
            $fk[] = [
                'name' => $col,
                'label' => ucwords(str_replace(['_', ' id'], [' ', ''], $col)),
                'options' => $this->filterOptionsFor($col),
                'selected' => $val,
            ];
        }

        return [
            'q' => $q,
            'searchable' => ! empty($searchCols) || ! empty($searchRels),
            'fk' => $fk,
            'active' => $q !== '' || collect($fk)->contains(fn ($f) => $f['selected'] !== null && $f['selected'] !== ''),
        ];
    }

    /**
     * Smart default: scalar fields nullable; *_id fields integer + exists check.
     */
    protected function rules(?Model $item = null): array
    {
        $instance = new ($this->modelClass);
        $rules = [];
        foreach ($instance->getFillable() as $col) {
            if (in_array($col, ['password', 'remember_token'])) continue;
            if (str_ends_with($col, '_id') && $col !== 'id') {
                $related = $this->resolveRelatedModel($col);
                if ($related) {
                    $table = (new $related)->getTable();
                    $rules[$col] = "nullable|integer|exists:$table,id";
                    continue;
                }
            }
            $rules[$col] = 'nullable|string|max:65000';
        }
        return $rules;
    }
}
