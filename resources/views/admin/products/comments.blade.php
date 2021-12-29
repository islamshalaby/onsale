@extends('admin.app')
@section('title' , __('messages.comments'))
@section('content')
    <div id="tableSimple" class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>{{ __('messages.comments') }}</h4>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-area">
            <div class="table-responsive">
                <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th class="text-center">Id</th>
                            <th class="text-center">{{ __('messages.user_name') }}</th>
                            <th class="text-center">{{ __('messages.comment') }}</th>
                            @if(Auth::user()->delete_data)
                                <th class="text-center" >{{ __('messages.delete') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; ?>
                            @foreach ($data as $row)
                                <tr >
                                    <td class="text-center"><?=$i;?></td>
                                    <td class="text-center">{{$row->User->name}}</td>
                                    <td class="text-center">{{ $row->comment }}</td>
                                    @if(Auth::user()->delete_data)
                                        <td class="text-center blue-color" ><a onclick="return confirm('{{ __('messages.are_you_sure') }}');" href="{{ route('delete.comment', $row->id) }}" ><i class="far fa-trash-alt"></i></a></td>
                                    @endif
                                    <?php $i++; ?>
                                </tr>
                            @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- <div class="paginating-container pagination-solid">
            <ul class="pagination">
                <li class="prev"><a href="{{$data['products']->previousPageUrl()}}">Prev</a></li>
                @for($i = 1 ; $i <= $data['products']->lastPage(); $i++ )
                    <li class="{{ $data['products']->currentPage() == $i ? "active" : '' }}"><a href="/admin-panel/users/show?page={{$i}}">{{$i}}</a></li>
                @endfor
                <li class="next"><a href="{{$data['products']->nextPageUrl()}}">Next</a></li>
            </ul>
        </div>   --}}
    </div>
@endsection

