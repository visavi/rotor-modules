@extends('layout')

@section('title', __('rating::ratings.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Rating']) }}">{{ __('admin.modules.module') }} {{ __('main.reputation') }}</a></li>
            <li class="breadcrumb-item active">{{ __('rating::ratings.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('rating::ratings.settings') }}</h1>
@stop

@section('content')
<form method="post" action="{{ route('rating.settings.update') }}">
    @csrf
    <div class="mb-3{{ hasError('sets[editratingpoint]') }}">
        <label for="editratingpoint" class="form-label">{{ __('rating::ratings.points_rating_edit') }}:</label>
        <input type="number" class="form-control" id="editratingpoint" name="sets[editratingpoint]" maxlength="4" value="{{ getInput('sets.editratingpoint', $settings['editratingpoint']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[editratingpoint]') }}</div>
    </div>

    <div class="mb-3{{ hasError('sets[ratinglist]') }}">
        <label for="ratinglist" class="form-label">{{ __('rating::ratings.ratinglist_per_page') }}:</label>
        <input type="number" class="form-control" id="ratinglist" name="sets[ratinglist]" maxlength="2" value="{{ getInput('sets.ratinglist', $settings['ratinglist']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[ratinglist]') }}</div>
    </div>

    <button class="btn btn-primary">{{ __('main.save') }}</button>
</form>
@stop
