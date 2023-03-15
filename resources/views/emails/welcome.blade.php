@component('mail::message')

Dear {{$data['name']}},

{{$data['greating']}},<br>

{{$data['message']}},<br>

@component('mail::button', ['url' => 'https://afromina-digitals.com'])
Visit Us
@endcomponent

{{$data['footer']}},
<br>




Kind Regards!<br>
{{ config('app.name') }} Ethiopia<br>
+251992758586<br>
surfieethiopia@gmail.com
@endcomponent
