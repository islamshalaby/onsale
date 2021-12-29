@extends('admin.app')

@section('title' , __('messages.category_edit'))

@section('content')
    <div class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">

                        <h4>{{ __('messages.category_options_edit') }}</h4>
                    </div>
                </div>
                <form action="{{route('cat_options.update_new')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <input required type="hidden" name="id" value="{{$data->id}}">
                    <div class="card-body">
                        <div class="form-group mb-4">
                            <label for="">{{ __('messages.current_image') }}</label><br>
                            <img
                                src="https://res.cloudinary.com/carsads2021/image/upload/w_100,q_100/v1581928924/{{ $data->image }}"/>
                        </div>
                        <div class="custom-file-container" data-upload-id="myFirstImage">
                            <label>{{ __('messages.upload') }} ({{ __('messages.single_image') }}) <a
                                    href="javascript:void(0)" class="custom-file-container__image-clear"
                                    title="Clear Image">x</a></label>
                            <label class="custom-file-container__custom-file">
                                <input type="file" name="image"
                                       class="custom-file-container__custom-file__custom-file-input"
                                       accept="image/*">
                                <input type="hidden" name="MAX_FILE_SIZE" value="10485760"/>
                                <span class="custom-file-container__custom-file__custom-file-control"></span>
                            </label>
                            <div class="custom-file-container__image-preview"></div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="title_ar">{{ __('messages.name_ar') }}</label>
                            <input required type="text" name="title_ar" value="{{$data->title_ar}}" class="form-control"
                                   id="title_ar"
                                   placeholder="{{ __('messages.name_ar') }}">
                        </div>
                        <div class="form-group mb-4">
                            <label for="title_ar">{{ __('messages.name_en') }}</label>
                            <input required type="text" name="title_en" value="{{$data->title_en}}" class="form-control"
                                   id="title_en"
                                   placeholder="{{ __('messages.name_en') }}">
                        </div>
                        <div class="form-group">
                            <label for="sel1">{{ __('messages.type') }}</label>
                            <select name="is_required" required class="form-control">
                                <option value="y" @if($data->is_required == 'y')selected @endif >{{ __('messages.mandatory') }}</option>
                                <option value="n" @if($data->is_required == 'n')selected @endif >{{ __('messages.choice') }}</option>
                            </select>
                        </div>
                    </div>
                    <input type="submit" value="{{ __('messages.edit') }}" class="btn btn-primary">
                </form>
            </div>
        </div>
    </div>
@endsection
