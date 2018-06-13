@extends('layouts.admin')

@section('title', tr('service_types'))

@section('content-header', tr('service_types'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-user"></i> {{tr('service_types')}}</li>
@endsection

@section('content')

	@include('notification.notify')


<div class="row">
      <div class="col-xs-12">
        <div class="box box-info">
          <div class="box-body">

            @if(count($services) > 0)

          <table id="example1" class="table table-bordered table-striped">

            <thead>
              <tr>
                <th>{{ tr('id') }}</th>
                <th class="min">{{ tr('service_types') }}</th>
                <th class="min">{{ tr('provider_name') }}</th>
                <th class="min">{{ tr('status') }}</th>
                <th>{{ tr('action') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($services as $index => $service)
            <tr>
                <td>{{$index + 1 }}</td>
                <td>{{$service->name}}</td>
                <td>{{$service->provider_name}}</td>
                <td>@if($service->status == 1) {{ tr('default') }} @else NA @endif</td>
                <td class="btn-left">
                    <div class="input-group-btn">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">{{ tr('action') }}
                          <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                          <li>
                            <a href="{{route('admin.edit.service', array('id' => $service->id))}}">{{ tr('edit') }}</a>
                          </li>
                          <li>
                            <a onclick="return confirm('{{ tr('delete_confirmation') }}')" href="{{route('admin.delete.service', array('id' => $service->id))}}">{{ tr('delete') }}</a>
                          </li>
                        </ul>
                      </div>
                </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <h3 class="no-result">{{tr('no_data_found')}}</h3>
      @endif
          </div>
        </div>
      </div>
  </div>


@endsection
