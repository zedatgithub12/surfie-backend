@component('mail::message')

Dear {{$data['name']}},

{{$data['greating']}} <br>

{{$data['message']}} <br>

{{$data['closing']}}


With regards!<br>
{{ config('app.name') }} Ethiopia<br>
+251992758586<br>

@endcomponent