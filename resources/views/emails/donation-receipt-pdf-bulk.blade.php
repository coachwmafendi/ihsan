<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('emails.partials.receipt-styles')
</head>
<body>
    @foreach ($donations as $donation)
        <div class="receipt-page">
            @include('emails.partials.receipt', ['donation' => $donation])
        </div>
    @endforeach
</body>
</html>
