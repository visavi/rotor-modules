@extends('layout')

@section('title', __('blog::blogs.title_edit_blog') . ' ' . $category->name)

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.blogs.index') }}">{{ __('blog::blogs.blogs') }}</a></li>

            @foreach ($category->getParents() as $parent)
                <li class="breadcrumb-item"><a href="{{ route('admin.blogs.blog', ['id' => $parent->id]) }}">{{ $parent->name }}</a></li>
            @endforeach

            <li class="breadcrumb-item active">{{ __('blog::blogs.title_edit_blog') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        <form action="{{ route('admin.blogs.edit', ['id' => $category->id]) }}" method="post">
            @csrf
            <div class="mb-3{{ hasError('parent') }}">
                <label for="parent" class="form-label">{{ __('blog::blogs.parent_blog') }}</label>

                <?php $inputParent = (int) old('parent', $category->parent_id); ?>

                <select class="form-select" id="parent" name="parent">
                    <option value="0">---</option>

                    @foreach ($categories as $data)
                        <option value="{{ $data->id }}"{{ ($inputParent === $data->id && ! $data->closed) ? ' selected' : '' }}{{ $data->closed || $data->id === $category->id ? ' disabled' : '' }}>
                            {{ str_repeat('–', $data->depth) }} {{ $data->name }}
                        </option>
                    @endforeach

                </select>
                <div class="invalid-feedback">{{ textError('parent') }}</div>
            </div>

            <div class="mb-3{{ hasError('name') }}">
                <label for="name" class="form-label">{{ __('blog::blogs.name') }}:</label>
                <input class="form-control" name="name" id="name" maxlength="{{ setting('blog_category_max') }}" value="{{ old('name', $category->name) }}" required>
                <div class="invalid-feedback">{{ textError('name') }}</div>
            </div>

            <div class="form-check">
                <input type="hidden" value="0" name="closed">
                <input type="checkbox" class="form-check-input" value="1" name="closed" id="closed"{{ old('closed', $category->closed) ? ' checked' : '' }}>
                <label class="form-check-label" for="closed">{{ __('main.close') }}</label>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <button class="btn btn-primary">{{ __('main.change') }}</button>

                {{-- Форма удаления идёт ниже, вложить её сюда нельзя: <form> в <form> невалидна.
                     Кнопка связана с ней атрибутом form и потому стоит в одной строке с «Изменить» --}}
                <button class="btn btn-danger" form="blog-delete-{{ $category->id }}">
                    <i class="fa fa-times"></i> {{ __('main.delete') }}
                </button>
            </div>
        </form>

        @include('blog::admin/articles/_delete', ['blog' => $category])
    </div>
@stop
