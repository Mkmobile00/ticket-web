<?php

namespace App\Http\Controllers\Admin;

use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends AdminController
{
    protected string $modelClass = \App\Models\Language::class;
    protected string $resource = 'language';

    public function index()
    {
        $items = ($this->modelClass)::query()->latest('id')->paginate(15);
        return view('admin.crud.index', [
            'items' => $items,
            'resource' => $this->resource,
            'columns' => $cols = $this->columns(),
            'fkLabels' => $this->fkLabelMap($cols),
            'title' => 'Languages',
        ]);
    }

    public function create()
    {
        return view('admin.crud.form', [
            'item' => new ($this->modelClass),
            'resource' => $this->resource,
            'fields' => $this->fields(),
            'title' => 'Create Language',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        ($this->modelClass)::create($data);
        return redirect()->route('admin.languages.index')->with('status', 'Language created.');
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
            'title' => 'Edit Language',
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = ($this->modelClass)::findOrFail($id);
        $data = $request->validate($this->rules($item));
        $item->update($data);
        return redirect()->route('admin.languages.index')->with('status', 'Language updated.');
    }

    public function destroy($id)
    {
        ($this->modelClass)::findOrFail($id)->delete();
        return back()->with('status', 'Language deleted.');
    }
}
