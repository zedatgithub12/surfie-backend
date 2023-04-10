@component('mail::message')

Dear Sir/Madam,

Thank you for your interest in our parental application. We appreciate your commitment to ensuring the safety and well-being of your child.

We are pleased to inform you that we have received your request for a trial version of our application. download surfie parent app using button below

@component('mail::button', ['url' => 'https://itunes.apple.com/us/app/surfie-parent/id997309073?mt=8'])
For IOS
@endcomponent
@component('mail::button', ['url' => 'https://play.google.com/store/apps/details?id=com.puresight.surfie.parentapp'])
For Android
@endcomponent

We are confident that our parental application will meet your needs and help you monitor your child's activities effectively. We look forward to hearing your feedback on the trial version.

If you have any questions or concerns, please do not hesitate to contact us. We are always available to assist you.

Thank you again for choosing our parental application.


With regards!<br>
{{ config('app.name') }} Ethiopia<br>
+251992758586<br>

@endcomponent