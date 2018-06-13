@extends('layouts.admin')

@section('title', tr('documents'))

@section('content-header', tr('documents'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-user"></i> {{tr('documents')}}</li>
@endsection

@section('content')

	@include('notification.notify')


<div class="row">
      <div class="col-xs-12">
        <div class="box box-info">
          <div class="box-body">

            @if(count($documents) > 0)

          <table id="example1" class="table table-bordered table-striped">

            <thead>
              <tr>
                <th>{{ tr('id') }}</th>
                <th>{{ tr('doc_name') }}</th>
                <th>{{ tr('action') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($documents as $index => $document)
            <tr>
                <td>{{$index + 1}}</td>
                <td>{{$document->name}}</td>
                <td class="btn-left">
                    <div class="input-group-btn">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">Action
                          <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                          <li>
                            <a href="{{route('admin.document_edit', array('id' => $document->id))}}">{{ tr('edit') }}</a>
                          </li>
                          <li>
                            <a onclick="return confirm('{{ tr('delete_confirmation') }}')" href="{{route('admin.document_delete', array('id' => $document->id))}}">{{ tr('delete') }}</a>
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
