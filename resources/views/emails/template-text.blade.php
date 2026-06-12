{!! strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], ["\n\n", "\n", "\n", "\n"], $bodyText ?? '')) !!}

---
{{ config('app.name', 'MeNetZero') }}
Support: {{ config('mail.addresses.help.address', 'help@menetzero.com') }}
