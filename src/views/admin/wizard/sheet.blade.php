<div class="ibox-content uk-margin-large-bottom">
    <div class="uk-overflow-container">
        <p>Процесс импорта:</p>
        <div class="uk-progress">
            <div class="uk-progress-bar" style="width: 15%;"><span class="imported_rows">0</span> из <span class="all_rows">{{ count($data) }}</span></div>
        </div>

        @foreach($data as $data_key => $data_value)
            @if( !empty($data_value['naimenovanie']))
                <form action="/admin/wizard/importrow" method="post" class="import_row uk-form">
                    {!! csrf_field() !!}
                    <table class="uk-table">
                        @if($loop->first)
                            <thead>
                            <tr>
                                <th>1</th>
                                @foreach($data->first() as $colomn_name => $colomn_value)
                                    @if( !empty($colomn_name))
                                        <th>{{ $colomn_name }} <input type="hidden" name="colomns[]" value="{{ $colomn_name }}"></th>
                                    @endif
                                @endforeach
                            </tr>
                            </thead>
                        @endif
                        <tbody>
                        @php preg_match_all('/R[0-9]/', $data_value['naimenovanie'], $level) @endphp
                        <tr class="@if(str_contains($data_value['naimenovanie'], '{=R')) uk-alert uk-alert-level-@php echo array_get($level[0], 0) @endphp @endif">
                            @foreach($data_value as $colomn_name => $colomn_value)
                                @if( !empty($colomn_name))
                                    @if($loop->first)
                                        <td>{{ $data_key+2 }}</td>
                                    @endif
                                    @if($colomn_name === 'foto')
                                        <td class="@if(!empty($colomn_value) && !in_array($data_value['foto'], $images)) uk-alert-danger @endif">
                                            <select name="{{ $colomn_name }}" class="cell_value" style="width: 150px" data-oldvalue="{{ $colomn_value }}"
                                                    data-coordinate="@lang('excelcells.'.$loop->iteration){{ $data_key+2 }}" data-sheet="{{ $sheet }}">
                                                @if(empty($colomn_value))
                                                    <option></option>
                                                @else
                                                    <option>[! {{ $colomn_value }} не найдено !]</option>
                                                @endif
                                                @foreach($images as $image)
                                                    <option @if($image === $colomn_value) selected @endif value="{{ $image }}">{{ $image }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @elseif(isset($rows[$colomn_name]) && empty($rows[$colomn_name]['db']))
                                        <td><input data-oldvalue="{{ $colomn_value }}" data-coordinate="@lang('excelcells.'.$loop->iteration){{ $data_key+2 }}" data-sheet="{{ $sheet }}"
                                                   name="{{ $colomn_name }}" class="cell_value" type="text" value="{{ $colomn_value }}" data-uk-tooltip title="{{ $colomn_value }}"></td>
                                    @else
                                        <td><input data-oldvalue="{{ $colomn_value }}" data-coordinate="@lang('excelcells.'.$loop->iteration){{ $data_key+2 }}" data-sheet="{{ $sheet }}"
                                                   name="{{ $rows[$colomn_name]['db'] }}" class="cell_value"
                                                   type="text" value="{{ $colomn_value }}" data-uk-tooltip title="{{ $colomn_value }}"></td>
                                    @endif
                                @endif
                            @endforeach
                        </tr>
                        </tbody>
                    </table>
                </form>
            @endif
        @endforeach
    </div>
</div>

<div class="ibox-content">
    <div class="uk-overflow-container">
        <form action="/admin/wizard/storeConfig" method="post" class="uk-form">
            {!! csrf_field() !!}
            <table class="uk-table table-config">
                <thead>
                <tr>
                    <th></th>
                    @foreach($data->first() as $colomn_name => $colomn_value)
                        @if( !empty($colomn_name))
                            <th>{{ $colomn_name }} <input type="hidden" name="colomns[]" value="{{ $colomn_name }}"></th>
                        @endif
                    @endforeach
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Поле в БД</td>
                    @foreach($data->first() as $colomn_name => $colomn_value)
                        @if( !empty($colomn_name))
                            <td>
                                @if($colomn_name === 'foto')
                                    <input type="hidden" name="db[]" value="">
                                    <input type="text" disabled value="[system]">
                                @else
                                    <select class="wizard-db-colomns" name="db[]">
                                        <option value="">-- Не назначено --</option>
                                        <option value="create-column" data-column="{{ $colomn_name }}">Создать новое</option>
                                        @foreach($fillable as $fill_value)
                                            <option value="{{ $fill_value }}" @if(isset($rows[$colomn_name])) @if($rows[$colomn_name]['db'] === $fill_value) selected @endif @endif>{{ $fill_value }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
                <tr>
                    <td>Название поля</td>
                    @foreach($data->first() as $colomn_name => $colomn_value)
                        @if( !empty($colomn_name))
                            <td>
                                @if($colomn_name === 'foto')
                                    <input type="hidden" name="slug[]" value="">
                                    <input type="text" disabled value="[system]">
                                @else
                                    <input name="slug[]" type="text" value="@if(isset($rows[$colomn_name])) {{ $rows[$colomn_name]['slug'] }} @endif" placeholder="-- Не назначено --">
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
                <tr>
                    <td>Вывод в шаблоне</td>
                    @foreach($data->first() as $colomn_name => $colomn_value)
                        @if( !empty($colomn_name))
                            <td>
                                @if($colomn_name === 'foto')
                                    <input type="hidden" name="template[]" value="">
                                    <input type="text" disabled value="[system]">
                                @else
                                    <select class="wizard-db-colomns" name="template[]">
                                        <option value="">-- Не назначено --</option>
                                        <option @if(array_get($rows[$colomn_name], 'template') === 'category') selected @endif value="category">Раздел</option>
                                        <option @if(array_get($rows[$colomn_name], 'template') === 'item') selected @endif value="item">Товар</option>
                                        <option @if(array_get($rows[$colomn_name], 'template') === 'all') selected @endif value="all">Раздел и товар</option>
                                    </select>
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
                <tr>
                    <td>Фильтры</td>
                    @foreach($data->first() as $colomn_name => $colomn_value)
                        @if( !empty($colomn_name))
                            <td>
                                @if($colomn_name === 'foto')
                                    <input type="hidden" name="filters[]" value="">
                                    <input type="text" disabled value="[system]">
                                @else
                                    <select class="wizard-db-colomns" name="filters[]">
                                        <option value="">-- Не назначено --</option>
                                        <option @if(array_get($rows[$colomn_name], 'filters') === 'sort') selected @endif value="sort">Сортировка</option>
                                        <option @if(array_get($rows[$colomn_name], 'filters') === 'lilu') selected @endif value="lilu">Фильтры</option>
                                        <option @if(array_get($rows[$colomn_name], 'filters') === 'all') selected @endif value="all">Фильтры и сортировка</option>
                                    </select>
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
                <tr>
                    <td>В админке</td>
                    @foreach($data->first() as $colomn_name => $colomn_value)
                        @if( !empty($colomn_name))
                            <td>
                                @if($colomn_name === 'foto')
                                    <input type="hidden" name="admin[]" value="">
                                    <input type="text" disabled value="[system]">
                                @else
                                    <select class="wizard-db-colomns" name="admin[]">
                                        <option value="">-- Не назначено --</option>
                                        <option @if(array_get($rows[$colomn_name], 'admin') === 'input') selected @endif value="input">input</option>
                                        <option @if(array_get($rows[$colomn_name], 'admin') === 'textarea') selected @endif value="textarea">textarea</option>
                                        <option @if(array_get($rows[$colomn_name], 'admin') === 'checkbox') selected @endif value="checkbox">checkbox</option>
                                        <option @if(array_get($rows[$colomn_name], 'admin') === 'select') selected @endif value="select">select</option>
                                    </select>
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
                <tr>
                    <td colspan="{!! count($data->first())+1 !!}"><button type="submit" class="uk-button uk-button-primary">Сохранить настройки импорта</button></td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>