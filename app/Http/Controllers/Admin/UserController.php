<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends AdminController
{
    protected string $modelClass = \App\Models\User::class;
    protected string $resource = 'user';

    /**
     * Constrain the privilege/identity fields instead of the generic
     * `nullable|string|max:65000` default — `role` must be an allowed value so
     * the admin CRUD can't persist an arbitrary/invalid (or escalated) role.
     */
    protected function rules(?Model $item = null): array
    {
        $rules = parent::rules($item);
        $rules['name'] = 'required|string|max:120';
        $rules['email'] = ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($item?->id)];
        $rules['role'] = 'required|in:admin,customer';

        return $rules;
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
        $item->update($data);
        return redirect()->route('admin.' . \Illuminate\Support\Str::plural($this->resource) . '.index')->with('status', 'Updated.');
    }

    public function destroy($id)
    {
        ($this->modelClass)::findOrFail($id)->delete();
        return back()->with('status', 'Deleted.');
    }
}




