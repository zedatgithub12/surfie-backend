<!-- @component('mail::message')

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
@endcomponent -->

Hello {{ $user->name }},

It appears that your parental control licence has expired! 

You may need to renew your licence to continue using the parental control service.

Please check with customer support for further assistance.


Best regards,
+251992758586