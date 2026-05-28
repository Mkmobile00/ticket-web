@extends('admin.layouts.admin')

@section('title', 'File Manager')
@section('page-title', 'File Manager')
@section('breadcrumb', 'Media')

@push('styles')
<style>
    .lfm-tabs { display:flex; gap:8px; margin-bottom:16px; }
    .lfm-tabs .btn { min-width:140px; }
    .lfm-frame-wrap { background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.05); }
    .lfm-frame { width:100%; height: calc(100vh - 230px); min-height:560px; border:0; display:block; }
    .lfm-hint { font-size:.85rem; color:#6c757d; margin-bottom:14px; }
</style>
@endpush

@section('content')
    <div class="lfm-hint">
        <i class="bi bi-info-circle"></i>
        Browse, upload, crop and organise photos and files. Uploads go to the shared media library and are reusable across the site.
    </div>

    <div class="lfm-tabs">
        <button type="button" class="btn btn-danger" data-lfm-type="image">
            <i class="bi bi-images"></i> Photos
        </button>
        <button type="button" class="btn btn-outline-secondary" data-lfm-type="file">
            <i class="bi bi-file-earmark"></i> Files
        </button>
        <a class="btn btn-outline-secondary ms-auto" href="{{ url('filemanager?type=image') }}" target="_blank">
            <i class="bi bi-box-arrow-up-right"></i> Open in new tab
        </a>
    </div>

    <div class="lfm-frame-wrap">
        <iframe id="lfm-frame" class="lfm-frame" src="{{ url('filemanager?type=image') }}" allow="clipboard-read; clipboard-write"></iframe>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-lfm-type]').forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.lfmType;
            document.getElementById('lfm-frame').src = "{{ url('filemanager') }}?type=" + type;
            document.querySelectorAll('[data-lfm-type]').forEach(b => {
                b.classList.toggle('btn-danger', b === btn);
                b.classList.toggle('btn-outline-secondary', b !== btn);
            });
        });
    });
</script>
@endpush
