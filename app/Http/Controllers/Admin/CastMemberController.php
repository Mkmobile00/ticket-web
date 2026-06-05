<?php

namespace App\Http\Controllers\Admin;

use App\Models\CastMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CastMemberController extends AdminController
{
    protected string $modelClass = \App\Models\CastMember::class;
    protected string $resource = 'cast-member';

    /** List columns. */
    protected function columns(): array
    {
        return ['id', 'name', 'photo', 'created_at'];
    }

    /** Hide the auto-generated slug from the form. */
    protected function fields(): array
    {
        return array_values(array_filter(parent::fields(), fn ($f) => $f['name'] !== 'slug'));
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
            'title' => 'Cast & Crew',
        ]);
    }

    public function create()
    {
        return view('admin.crud.form', [
            'item' => new ($this->modelClass),
            'resource' => $this->resource,
            'fields' => $this->fields(),
            'title' => 'Add Cast / Crew Member',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $this->uniqueSlug($data['name'] ?? 'member');
        ($this->modelClass)::create($data);
        return redirect()->route('admin.cast-members.index')->with('status', 'Member added.');
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
            'title' => 'Edit Cast / Crew Member',
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = ($this->modelClass)::findOrFail($id);
        $data = $request->validate($this->rules($item));
        $item->update($data);
        return redirect()->route('admin.cast-members.index')->with('status', 'Member updated.');
    }

    public function destroy($id)
    {
        ($this->modelClass)::findOrFail($id)->delete();
        return back()->with('status', 'Member deleted.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'member';
        $slug = $base;
        $i = 1;
        while (CastMember::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
