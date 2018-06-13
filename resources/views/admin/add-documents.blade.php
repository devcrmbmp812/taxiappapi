@extends('layouts.admin')

          @if(isset($name))
            @section('title', 'Edit Document Type | ')
          @else
            @section('title', 'Add Document Type | ')
          @endif

@section('content')

@include('notification.notify')
        <div class="panel mb25 box box-info">
          <div class="panel-heading border">
          @if(isset($name))
          {{ tr('edit_document') }}
          @else
            {{ tr('create_document') }}
          @endif
          </div>
          <div class="panel-body">
            <div class="row no-margin">
              <div class="col-lg-12">
                <form class="form-horizontal bordered-group" action="{{route('admin.add_document_process')}}" method="POST" role="form">
                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('document_name') }}</label>
                    <div class="col-sm-10">
                      <input type="text" name="document_name" value="{{ isset($document->name) ? $document->name : '' }}" required class="form-control">
                    </div>
                  </div>
                   <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('document_name') }}</label>
                    <div class="col-sm-10">
                      <input type="text" name="document_demo1" value="{{ isset($document->demo1) ? $document->demo1 : '' }}" required class="form-control">
                    </div>
                  </div>
                   <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('document_name') }}</label>
                    <div class="col-sm-10">
                      <input type="text" name="document_demo2" value="{{ isset($document->demo2) ? $document->demo2 : '' }}" required class="form-control">
                    </div>
                  </div>
                  <input type="hidden" name="id" value="@if(isset($document)) {{$document->id}} @endif" />

                <div class="form-group">
                  <label></label>
                  <div class="pull-right">
                    <button class="btn btn-success mr10">{{ tr('submit') }}</button>
                  </div>
                </div>

                </form>
              </div>
            </div>
          </div>
        </div>
@endsection
