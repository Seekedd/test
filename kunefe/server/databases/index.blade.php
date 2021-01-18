{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.master')

@section('title')
    @lang('server.config.database.header')
@endsection

@section('content-header')
<div class="col-sm-12 col-md-6">
    <div class="header-bilgi">
        <i class="fas fa-server"></i>
        <ul class="list list-unstyled">
            <li><h1>@lang('server.config.database.header')</h1></li>
            <li><small>@lang('server.config.database.header_sub')</small></li>
        </ul>
    </div>
</div>
<div class="col-md-6 d-none d-lg-block">
    <div class="header-liste">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('index') }}">@lang('strings.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('server.index', $server->uuidShort) }}">{{ $server->name }}</a></li>
            <li class="breadcrumb-item active">@lang('navigation.server.databases')</li>
        </ol>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header with-border">
                <h3 class="card-title">@lang('server.config.database.your_dbs')</h3>
            </div>
            @if(count($databases) > 0)
                <div class="card-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>@lang('strings.database')</th>
                                <th>@lang('strings.username')</th>
                                <th>@lang('strings.password')</th>
                                <th>@lang('server.config.database.host')</th>
                                @can('reset-db-password', $server)<td></td>@endcan
                            </tr>
                            @foreach($databases as $database)
                                <tr>
                                    <td class="middle">{{ $database->database }}</td>
                                    <td class="middle">{{ $database->username }}</td>
                                    <td class="middle">
                                        <code class="toggle-display" style="cursor:pointer" data-toggle="tooltip" data-placement="right" title="Click to see">
                                            <i class="fa fa-key"></i> &bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;
                                        </code>
                                        <code class="d-none" data-attr="set-password">
                                            {{ Crypt::decrypt($database->password) }}
                                        </code>
                                    </td>
                                    <td class="middle"><code>{{ $database->host->host }}:{{ $database->host->port }}</code></td>
                                    @if(Gate::allows('reset-db-password', $server) || Gate::allows('delete-database', $server))
                                        <td class="text-right">
                                            @can('reset-db-password', $server)
                                                <button class="btn btn-sm bg-transparent text-primary" style="margin-right:10px;" data-action="reset-password" data-id="{{ $database->id }}">
                                                    <i class="fas fa-sync-alt"></i> @lang('server.config.database.reset_password')
                                                </button>
                                            @endcan
                                            @can('delete-database', $server)
                                                <button class="btn btn-sm bg-transparent text-danger" data-action="delete-database" data-id="{{ $database->id }}">
                                                    <i class="fa fa-fw fa-trash"></i>
                                                </button>
                                            @endcan
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="card-body">
                    <div class="alert alert-info no-margin-bottom">
                        @lang('server.config.database.no_dbs')
                    </div>
                </div>
            @endif
        </div>
    </div>
    @if($allowCreation && Gate::allows('create-database', $server))
        <div class="col-md-12">
            <div class="card card-success">
                <div class="card-header with-border">
                    <h3 class="card-title">Create New Database</h3>
                </div>
                @if($overLimit)
                    <div class="card-body">
                        <div class="alert alert-danger no-margin">
                            Number of databases allowed to use: <strong>{{ count($databases) }}</strong> / <strong>{{ $server->database_limit ?? '∞' }}</strong>
                        </div>
                    </div>
                @else
                    <form action="{{ route('server.databases.new', $server->uuidShort) }}" method="POST">
                        <div class="card-body">
                            <label for="pDatabaseName" class="control-label">Database Name</label>
                            <div class="form-group input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="padding: 0.625rem 0.0rem 0.625rem 0.625rem !important;">s{{ $server->id }}_</span>
                                </div>
                                <input id="pDatabaseName" type="text" name="database" class="form-control" placeholder="database_name" />
                            </div>
                            <div class="form-group">
                                <label for="pRemote" class="control-label">Connections</label>
                                <input id="pRemote" type="text" name="remote" class="form-control" value="%" />
                            </div>
                            <p class="alert bg-info">This should reflect the IP address that connections are allowed from. Uses standard MySQL notation. If unsure leave as <code>%</code>.</p>
                                <p class="alert bg-info">You are currently using <strong>{{ count($databases) }}</strong> of <strong>{{ $server->database_limit ?? '∞' }}</strong> databases. A username and password for this database will be randomly generated after form submission.</p>
                        </div>
                        <div class="card-footer">
                            {!! csrf_field() !!}
                            <input type="submit" class="btn btn-md btn-success float-right mb-3" value="Create New Database" />
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('js/frontend/server.socket.js') !!}
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        });
        $('.toggle-display').on('click', function () {
            $(this).parent().find('code[data-attr="set-password"]').removeClass('d-none');
            $(this).hide();
        });
        @can('reset-db-password', $server)
            $('[data-action="reset-password"]').click(function (e) {
                e.preventDefault();
                var block = $(this);
                $(this).addClass('disabled').find('i').addClass('fa-spin');
                $.ajax({
                    type: 'PATCH',
                    url: Router.route('server.databases.password', { server: Pterodactyl.server.uuidShort }),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content'),
                    },
                    data: {
                        database: $(this).data('id')
                    }
                }).done(function (data) {
                    block.parent().parent().find('[data-attr="set-password"]').html(data.password);
                    Swal.fire({
                        icon: 'success',
                        title: 'Password has been reset.',
                        html: 'Database password has been successfully reset.'
                    });
                }).fail(function(jqXHR) {
                    console.error(jqXHR);
                    var error = 'An error occurred while trying to process this request.';
                    if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                        error = jqXHR.responseJSON.error;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Whoops!',
                        html: error
                    });
                }).always(function () {
                    block.removeClass('disabled').find('i').removeClass('fa-spin');
                });
            });
        @endcan
        @can('delete-database', $server)
            $('[data-action="delete-database"]').click(function (event) {
                event.preventDefault();
                var self = $(this);
                Swal.fire({
                    title: '',
                    icon: 'warning',
                    html: 'Are you sure that you want to delete this database? There is no going back, all data will immediately be removed.',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#d9534f',
                    showLoaderOnConfirm: true,
                }).then((result) => {
                    if (result.value) {
                        $.ajax({
                            method: 'DELETE',
                            url: Router.route('server.databases.delete', { server: '{{ $server->uuidShort }}', database: self.data('id') }),
                            headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
                        }).done(function () {
                            Swal.fire({
                                title: 'Successfully deleted.',
                                icon: 'success',
                            });
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        }).fail(function (jqXHR) {
                            console.error(jqXHR);
                            Swal.fire({
                                info: 'error',
                                title: 'Whoops!',
                                html: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'An error occurred while processing this request.'
                            });
                        });
                    }
                });
            });
        @endcan
    </script>
@endsection
