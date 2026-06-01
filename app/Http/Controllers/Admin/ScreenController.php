<?php

namespace App\Http\Controllers\Admin;

use App\Models\Screen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ScreenController extends AdminController
{
    protected string $modelClass = \App\Models\Screen::class;
    protected string $resource = 'screen';

    protected function fields(): array
    {
        $fields = parent::fields();
        foreach ($fields as &$f) {
            if ($f['name'] === 'seat_layout') {
                $f['type'] = 'seat-layout';
            }
        }
        return $fields;
    }

    protected function rules(?Model $item = null): array
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
        $data['total_seats'] = array_sum($data['seat_layout']['seats_per_row']) ?: ($data['total_seats'] ?? 0);
        ($this->modelClass)::create($data);
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
            'fields' => $this->fields(),
            'title' => 'Edit ' . ucwords(str_replace('-', ' ', $this->resource)),
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = ($this->modelClass)::findOrFail($id);
        $data = $request->validate($this->rules($item));
        $data['seat_layout'] = $this->normalizeSeatLayout($request);
        $data['total_seats'] = array_sum($data['seat_layout']['seats_per_row']) ?: ($data['total_seats'] ?? 0);
        $item->update($data);
        return redirect()->route('admin.' . \Illuminate\Support\Str::plural($this->resource) . '.index')->with('status', 'Updated.');
    }

    public function destroy($id)
    {
        ($this->modelClass)::findOrFail($id)->delete();
        return back()->with('status', 'Deleted.');
    }
}




