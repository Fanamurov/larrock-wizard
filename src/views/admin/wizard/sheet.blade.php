<div class="ibox-content uk-margin-large-bottom">
    <h3>Сопоставление полей:</h3>
    <div>
        <form action="/admin/wizard/storeConfig" method="post" class="uk-form">
            {!! csrf_field() !!}
            <table class="uk-table uk-table-condensed table-config">
                <thead>
                <tr>
                    <th style="min-width: 120px;"></th>
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
                                    <input type="text" disabled value="[system]" class="uk-width-1-1">
                                @else
                                    <input class="uk-width-1-1" name="slug[]" type="text" value="@if(isset($rows[$colomn_name])) {{ $rows[$colomn_name]['slug'] }} @endif" placeholder="-- Не назначено --">
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
                                    <input type="text" disabled value="[system]" class="uk-width-1-1">
                                @else
                                    <select class="wizard-db-colomns uk-width-1-1" name="template[]">
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
                                    <input type="text" disabled value="[system]" class="uk-width-1-1">
                                @else
                                    <select class="wizard-db-colomns uk-width-1-1" name="filters[]">
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
                                    <input type="text" disabled value="[system]" class="uk-width-1-1">
                                @else
                                    <select class="wizard-db-colomns uk-width-1-1" name="admin[]">
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
                    <td colspan="{!! count($data->first())+1 !!}"><button type="submit" class="uk-button uk-button-primary uk-button-large uk-margin-top uk-margin-bottom">Сохранить настройки импорта</button></td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>

<div class="ibox-content">
    <h3>Содержимое прайса:</h3>
    <div>
        <p>Процесс импорта:</p>
        <div class="uk-progress">
            <div class="uk-progress-bar" style="width: 15%;"><span class="imported_rows">0</span> из <span class="all_rows">{{ count($data) }}</span></div>
        </div>

        @foreach($rows as $key => $row)
            @if($key !== 'foto' && !$row['db'])
                <p class="uk-alert uk-alert-danger">Поле {{ $key }} не сопоставлено для импорта</p>
            @endif
        @endforeach

        @foreach($data as $data_key => $data_value)
            @if( !empty($data_value['naimenovanie']))
                <form action="/admin/wizard/importrow" method="post" class="import_row uk-form">
                    {!! csrf_field() !!}
                    <table class="uk-table uk-table-condensed">
                        @if($loop->first)
                            <thead>
                            @if($loop->first)
                                <tr>
                                    <th></th>
                                    @foreach($data->first() as $colomn_name => $colomn_value)
                                        @if( !empty($colomn_name))
                                            <th>@lang('larrock::excelcells.'.$loop->iteration)</th>
                                        @endif
                                    @endforeach
                                </tr>
                            @endif
                            <tr>
                                <th>1</th>
                                @foreach($data->first() as $colomn_name => $colomn_value)
                                    @if( !empty($colomn_name))
                                        <th class="@if($colomn_name !== 'foto' && !$rows[$colomn_name]['db']) uk-alert uk-alert-danger @endif">
                                            {{ $colomn_name }} <input type="hidden" name="colomns[]" value="{{ $colomn_name }}">
                                        </th>
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
                                        <td style="min-width: 20px;">{{ $data_key+2 }}</td>
                                    @endif
                                    @if($colomn_name === 'foto')
                                        <td class="@if(!empty($colomn_value) && !in_array($data_value['foto'], $images)) uk-alert-danger @endif uk-position-relative">
                                            <select name="{{ $colomn_name }}" class="cell_value" style="width: 150px" data-oldvalue="{{ $colomn_value }}"
                                                    data-coordinate="@lang('larrock::excelcells.'.$loop->iteration){{ $data_key+2 }}" data-sheet="{{ $sheet }}">
                                                <option></option>
                                                @if(in_array($data_value['foto'], $images))
                                                    <option selected value="{{ $data_value['foto'] }}">{{ $data_value['foto'] }}</option>
                                                @else
                                                    <option>[! {{ $colomn_value }} не найдено !]</option>
                                                @endif
                                            </select>
                                            <button style="position: absolute; top: 4px; right: 3px;" type="button" class="find_image uk-button" data-coordinate="@lang('excelcells.'.$loop->iteration){{ $data_key+2 }}" data-sheet="{{ $sheet }}">+</button>
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
<script type="text/javascript">
    $('button.find_image').click(function () {
        $(this).parent().find('select')
        @foreach($images as $image)
            .append($("<option></option>")
                .attr("value", "{{ $image }}")
                .text("{{ $image }}"))
        @endforeach
        ;
        $(this).remove();
    });
</script>