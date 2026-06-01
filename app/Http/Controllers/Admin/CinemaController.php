<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cinema;
use App\Models\City;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CinemaController extends AdminController
{
    protected string $modelClass = \App\Models\Cinema::class;
    protected string $resource = 'cinema';

    protected function fields(): array
    {
        $fields = parent::fields();
        foreach ($fields as &$f) {
            if ($f['name'] === 'city_id') {
                $f['type'] = 'select-or-new';
                $f['new_placeholder'] = 'Type new city name';
            }
        }
        return $fields;
    }

    protected function rules(?Model $item = null): array
    {
        $rules = parent::rules($item);
        $rules['city_id'] = 'nullable';
        $rules['city_new'] = 'nullable|string|max:120';
        return $rules;
    }

    protected function resolveCityId(Request $request): ?int
    {
        $newName = trim((string) $request->input('city_new', ''));
        if ($newName !== '') {
            $city = City::firstOrCreate(
                ['name' => $newName],
                ['slug' => Str::slug($newName)]
            );
            return $city->id;
        }
        $id = $request->input('city_id');
        return is_numeric($id) ? (int) $id : null;
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
        $data['city_id'] = $this->resolveCityId($request);
        unset($data['city_new']);
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
        $data['city_id'] = $this->resolveCityId($request);
        unset($data['city_new']);
        $item->update($data);
        return redirect()->route('admin.' . \Illuminate\Support\Str::plural($this->resource) . '.index')->with('status', 'Updated.');
    }

    public function destroy($id)
    {
        ($this->modelClass)::findOrFail($id)->delete();
        return back()->with('status', 'Deleted.');
    }
}




