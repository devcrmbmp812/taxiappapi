@extends('layouts.admin')

@section('title', tr('map'))

@section('content-header',tr('map') )

@section('breadcrumb')
    <li><a href="#"><i class="fa fa-dashboard"></i>Home</a></li>
    <li ><i class="fa fa-map"></i> {{tr('map')}} </li>
    <li class="active"><i class="fa fa-map"></i> {{tr('user_map_view')}} </li>
@endsection

@section('content')

@include('notification.notify')
<div class="panel">

    <div class="panel-body">
           <!-- <div class="row">
            <div class="col-md-10">
                <input id="pac-input" class="controls" type="text" placeholder="Enter a location">
            </div>
            <div class="col-md-2">
                <button class="btn btn-success controls" id="location-search">Search</button>
            </div>
        </div> -->
        <div class="row">
            <div class="col-xs-12">
                <div id="map"></div>
                  <h6>*Click on a Location Pointer to view Provider Details</h6>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style type="text/css">
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    #map {
        height: 100%;
        min-height: 400px;
    }

    .controls {
        /*margin-top: 10px;*/
        border: 1px solid transparent;
        border-radius: 2px 0 0 2px;
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        height: 32px;
        outline: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        margin-bottom: 10px;
    }

    #pac-input {
        background-color: #fff;
        font-family: Roboto;
        font-size: 15px;
        font-weight: 300;
        /*margin-left: 12px;*/
        padding: 0 11px 0 13px;
        text-overflow: ellipsis;
        width: 100%;
    }

    #pac-input:focus {
        border-color: #4d90fe;
    }

    #location-search {
        width: 100%;
    }

    #legend {
        font-family: Arial, sans-serif;
        background: rgba(255,255,255,0.8);
        padding: 10px;
        margin: 10px;
        border: 2px solid #f3f3f3;
    }
    #legend h3 {
        margin-top: 0;
        font-size: 16px;
        font-weight: bold;
        text-align: center;
    }
    #legend img {
        vertical-align: middle;
        margin-bottom: 5px;
    }
</style>
@endsection

@section('scripts')
<script>
    var map;
    var markers = [
        @foreach($Users as $User)
        {
            user_id: "{{ $User->id }}",
            name: "{{ $User->name }}",
            lat: {{ $User->latitude }},
            lng: {{ $User->longitude }},
        },
        @endforeach
    ];

    var mapMarkers = [];
    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            center: {lat: -7.617944, lng: 111.527741},
            zoom: 8,
            minZoom: 1
        });

        markers.forEach( function(element, index) {

            var url = "../user/details/"

            marker = new google.maps.Marker({
                position: {lat: element.lat, lng: element.lng},
                map: map,
                title: element.name,
            });

            mapMarkers.push(marker);

            google.maps.event.addListener(marker, 'click', function() {
                window.location.href = url + element.user_id;
            });

        });
    }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDoujGbr86VY2F6vhh-bzZjsebCFoRn0ik&libraries=places&callback=initMap" async defer></script>
@endsection
