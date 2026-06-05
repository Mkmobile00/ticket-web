<?php

namespace App\Http\Controllers\Admin;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventSpeaker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EventController extends AdminController
{
    protected string $modelClass = \App\Models\Event::class;
    protected string $resource = 'event';

    /** Admin list columns — include the city (resolved to its name via fkLabelMap). */
    protected function columns(): array
    {
        return ['id', 'title', 'city_id', 'event_date', 'status'];
    }

    protected function fields(?Model $item = null): array
    {
        $fields = parent::fields();
        foreach ($fields as &$f) {
            if ($f['name'] === 'seat_layout') {
                $f['type'] = 'seat-layout';
            }
        }
        unset($f);
        $fields[] = [
            'name' => 'categories',
            'label' => 'Categories',
            'type' => 'multiselect',
            'options' => EventCategory::orderBy('name')->pluck('name', 'id')->all(),
            'selected' => ($item && $item->exists) ? $item->categories->pluck('id')->all() : [],
        ];
        $fields[] = [
            'name' => 'speakers',
            'label' => 'Speakers',
            'type' => 'multiselect',
            'options' => EventSpeaker::orderBy('name')->pluck('name', 'id')->all(),
            'selected' => ($item && $item->exists) ? $item->speakers->pluck('id')->all() : [],
        ];
        $fields[] = [
            'name' => 'stats',
            'label' => 'Statistics (the "Our Recent Statistics" section)',
            'type' => 'kv-repeater',
            'value_placeholder' => 'Number e.g. 70+',
            'label_placeholder' => 'Label e.g. Speakers',
            'rows' => ($item && $item->exists)
                ? $item->stats->map(fn ($s) => ['value' => $s->value, 'label' => $s->label])->all()
                : [],
        ];
        return $fields;
    }

    private function syncCategories(Request $request, Model $item): void
    {
        $item->categories()->sync(array_map('intval', (array) $request->input('categories', [])));
    }

    private function syncSpeakers(Request $request, Model $item): void
    {
        $ids = array_map('intval', (array) $request->input('speakers', []));
        // Preserve display order from the checkbox order.
        $sync = [];
        foreach (array_values($ids) as $i => $id) {
            $sync[$id] = ['order' => $i];
        }
        $item->speakers()->sync($sync);
    }

    private function syncStats(Request $request, Model $item): void
    {
        $values = (array) $request->input('stats.value', []);
        $labels = (array) $request->input('stats.label', []);
        $item->stats()->delete();
        $order = 0;
        foreach ($values as $i => $value) {
            $value = trim((string) $value);
            $label = trim((string) ($labels[$i] ?? ''));
            if ($value === '' && $label === '') continue; // skip blank rows
            $item->stats()->create(['value' => $value, 'label' => $label, 'order' => $order++]);
        }
    }

    protected function rules(?\Illuminate\Database\Eloquent\Model $item = null): array
    {
        $rules = parent::rules($item);
        $rules['seat_layout'] = 'nullable|array';
        $rules['seat_layout.grid'] = 'nullable|string';
        $rules['seat_layout.rows'] = 'nullable|array';
        $rules['seat_layout.rows.*'] = 'string|max:3';
        $rules['seat_layout.seats_per_row'] = 'nullable|array';
        $rules['seat_layout.seats_per_row.*'] = 'integer|min:1|max:200';
        return $rules;
    }

    protected function normalizeSeatLayout(Request $request): array
    {
        return \App\Support\SeatLayout::normalize($request->input('seat_layout', []));
    }

    public function index()
    {
        $query = ($this->modelClass)::query()->latest('id');
        $filters = $this->applyIndexFilters($query);
        $items = $query->paginate(15)->withQueryString();
        return view('admin.crud.index', [
            'items' => $items,
            'filters' => $filters,
            'resource' => $this->resource,
            'columns' => $cols = $this->columns(),
            'fkLabels' => $this->fkLabelMap($cols),
            'title' => ucwords(str_replace('-', ' ', $this->resource)) . 's',
        ]);
    }

    public function create()
    {
        return view('admin.crud.form', [
            'item' => new ($this->modelClass),
            'resource' => $this->resource,
            'fields' => $this->fields(),
            'title' => 'Create ' . ucwords(str_replace('-', ' ', $this->resource)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['seat_layout'] = $this->normalizeSeatLayout($request);
        $event = ($this->modelClass)::create($data);
        $this->syncCategories($request, $event);
        $this->syncSpeakers($request, $event);
        $this->syncStats($request, $event);
        return redirect()->route('admin.' . \Illuminate\Support\Str::plural($this->resource) . '.index')->with('status', 'Created.');
    }

    public function show($id)
    {
        $item = ($this->modelClass)::findOrFail($id);
        return view('admin.crud.show', ['item' => $item, 'resource' => $this->resource, 'fields' => $this->fields()]);
    }

    public function edit($id)
    {
        $item = ($this->modelClass)::findOrFail($id);
        return view('admin.crud.form', [
            'item' => $item,
            'resource' => $this->resource,
            'fields' => $this->fields($item),
            'title' => 'Edit ' . ucwords(str_replace('-', ' ', $this->resource)),
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = ($this->modelClass)::findOrFail($id);
        $data = $request->validate($this->rules($item));
        $data['seat_layout'] = $this->normalizeSeatLayout($request);
        $item->update($data);
        $this->syncCategories($request, $item);
        $this->syncSpeakers($request, $item);
        $this->syncStats($request, $item);
        return redirect()->route('admin.' . \Illuminate\Support\Str::plural($this->resource) . '.index')->with('status', 'Updated.');
    }

    public function destroy($id)
    {
        ($this->modelClass)::findOrFail($id)->delete();
        return back()->with('status', 'Deleted.');
    }
}




