@component('mail::message')

Dear {{$data['name']}},

{{$data['message']}},


With regards!<br>
{{ config('app.name') }} Ethiopia<br>
+251992758586<br>

@endcomponent