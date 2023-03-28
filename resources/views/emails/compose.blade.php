@component('mail::message')

Dear Customer,

{{$data['description']}},

Thanks,<br>
{{ config('app.name') }},

@component
Thanks,<br>
{{ config('app.name') }},
@endcomponent

@endcomponent