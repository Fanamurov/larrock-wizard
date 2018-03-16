@extends('larrock::admin.main')
@section('title') wizard admin @endsection

@section('content')
    <div class="container-head uk-margin-bottom">
        <div class="uk-grid">
            <div class="uk-width-expand">
                {!! Breadcrumbs::render('admin.'. $app->name .'.result') !!}
            </div>
            <div class="uk-width-auto">
                <a class="uk-button uk-button-primary" href="/admin/{{ $app->name }}/clear/manual">Ручная очистка каталога</a>
                <button type="button" class="start_import uk-button uk-button-success" @if( !isset($xlsx) || !isset($data)) disabled @endif>Старт импорта</button>
            </div>
        </div>
    </div>

    <div class="ibox-content">
        <h3>Загрузка прайса .xlsx и фотографий к нему</h3>
        <div uk-grid class="uk-grid">
            <div class="uk-width-1-2">
                <form class="uk-form" method="post" action="/admin/wizard/loadXLSX" enctype="multipart/form-data">
                    {!! csrf_field() !!}
                    <div class="uk-grid">
                        <div class="uk-width-1-2">
                            <input type="file" name="xlsx">
                        </div>
                        <div class="uk-width-1-2">
                            <button type="submit" class="uk-button uk-button-primary">Загрузить новый прайс</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="uk-width-1-2">
                <form class="uk-form" method="post" action="/admin/wizard/loadImages" enctype="multipart/form-data">
                    {!! csrf_field() !!}
                    <div class="uk-grid">
                        <div class="uk-width-1-2">
                            <input type="file" name="images[]" multiple>
                        </div>
                        <div class="uk-width-1-2">
                            <button type="submit" class="uk-button uk-button-primary">Загрузить фотографии</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <br/>

    @if(isset($xlsx) || isset($data))
        <h3>Файл прайса: <a target="_blank" href="{{ $xlsx->getPathname() }}">{{ $xlsx->getFilename() }}</a></h3>
        <div class="uk-margin-large-bottom" id="ibox-wizard">
            <ul class="uk-tab" data-uk-tab="{connect:'#tab-content'}">
                @if(count($data) > 1)
                    @foreach($data as $data_key => $data_value)
                        <li class="sheet{{ $data_key }} @if($loop->first) uk-active @endif">
                            <a href="#sheet{{ $data_key }}" aria-controls="sheet{{ $data_key }}">{{ $data_value->getTitle() }}</a>
                        </li>
                    @endforeach
                @else
                    <li class="sheet0 uk-active">
                        <a href="#sheet0" aria-controls="sheet0">{{ $data->getTitle() }}</a>
                    </li>
                @endif
            </ul>

            <ul class="uk-switcher tab-content-wizard" id="tab-content">
                @foreach($data as $data_key => $data_value)
                    <div role="tabpanel" class="tab-pane @if($data_key === 0) active @endif load_sheet" data-sheet="{{ $data_key }}" id="sheet{{ $data_key }}">
                        <div id="sheet_content{{ $data_key }}" class="sheet_content"><div class="uk-alert uk-alert-warning">Загружается...</div></div>
                    </div>
                @endforeach
            </ul>
        </div>
    @endif
@endsection