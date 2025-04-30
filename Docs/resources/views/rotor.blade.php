@extends('layout')

@section('title', 'RotorCMS')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">RotorCMS</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="col-md-8 mx-auto text-center">
        <?php if ($release): ?>
            <a class="badge bg-warning text-black mb-4 p-2 px-4" href="/rotor/releases">
                Последняя версия <?= $release['tag_name'] ?>
            </a>
        <?php endif; ?>

        <img src="/assets/modules/docs/rotor.png" alt="RotorCMS" class="d-block mx-auto mb-3">

        <h1 class="mb-3 fw-semibold lh-1">Создайте свой первый сайт</h1>
        <p class="lead mb-4">
            Легкий и быстрый движок для сайта. Работает абсолютно на всех бесплатных хостингах
        </p>

        <div class="d-flex flex-column flex-lg-row align-items-md-stretch justify-content-md-center gap-3 mb-4">
            <div class="d-inline-block v-align-middle fs-5">
                <div class="input-group">
                    <input class="form-control form-control-lg" type="text" value="composer create-project visavi/rotor .">
                    <span class="input-group-text" onclick="return copyToClipboard(this)" data-bs-toggle="tooltip" title="{{ __('main.copy') }}"><i class="far fa-clipboard"></i></span>
                </div>
            </div>

            <a href="/docs" class="btn btn-lg btn-primary">
                <i class="fa-solid fa-book-open"></i> Документация
            </a>
        </div>
        <p class="text-muted mb-0">
            <?php if ($release): ?>
            Версия <strong><?= $release['tag_name'] ?></strong>
            <span class="px-1">&middot;</span>
            <?php endif; ?>
            <a href="/rotor/releases" class="link-secondary text-nowrap">Последние версии</a>
            <span class="px-1">&middot;</span>
            <a href="/rotor/commits" class="link-secondary text-nowrap">История изменений</a>
        </p>
    </div>
@stop
