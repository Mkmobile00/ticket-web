<?php

namespace App\Http\Controllers\Admin;

use App\Models\Screen;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShowtimeController extends AdminController
{
    protected string $modelClass = \App\Models\Showtime::class;
    protected string $resource = 'showtime';

    public function index()
    {
        $items = ($this->modelClass)::query()
            ->orderBy('show_date', 'desc')
            ->orderBy('show_time', 'desc')
            ->orderBy('movie_id')
            ->paginate(15);
        return view('admin.crud.index', [
            'items' => $items,
            'resource' => $this->resource,
            'columns' => $cols = $this->columns(),
            'fkLabels' => $this->fkLabelMap($cols),
            'title' => ucwords(str_replace('-', ' ', $this->resource)) . 's',
        ]);
    }

    protected function columns(): array
    {
        return ['id', 'movie_id', 'screen_id', 'show_date', 'show_time', 'language_id', 'format_id'];
    }

    protected function fields(): array
    {
        $fields = parent::fields();
        foreach ($fields as &$field) {
            if (($field['name'] ?? null) === 'screen_id') {
                $field['options'] = $this->screenOptions();
            }
        }
        return $fields;
    }

    protected function fkLabelMap(array $columns): array
    {
        $map = parent::fkLabelMap($columns);
        if (in_array('screen_id', $columns, true)) {
            $map['screen_id'] = Screen::with('cinema')->get()
                ->mapWithKeys(fn (Screen $s) => [$s->id => Str::limit(
                    ($s->cinema?->name ?: 'Unknown Cinema') . ' — ' . $s->name,
                    50,
                )])
                ->all();
        }
        return $map;
    }

    private function screenOptions(): array
    {
        return Screen::with('cinema')
            ->get()
            ->sortBy(fn (Screen $s) => ($s->cinema?->name ?? '') . ' ' . $s->name)
            ->mapWithKeys(fn (Screen $s) => [
                $s->id => ($s->cinema?->name ?: 'Unknown Cinema') . ' — ' . $s->name . ' (#' . $s->id . ')',
            ])
            ->all();
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




