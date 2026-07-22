<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('emails.partials.receipt-styles')
</head>
<body>
    @include('emails.partials.receipt', ['donation' => $donation])
</body>
</html>
