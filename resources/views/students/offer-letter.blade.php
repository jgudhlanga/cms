<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Offer Letter</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
        }
    </style>
</head>
<body class="p-10">
@include('partials.poly-header')
<main class="py-4 px-2 mt-3">
    {{--{!!  str_replace(['{title}', '{initials}', '{surname}', '{member_number}'], [$member->title->name_en, $member->initials, $member->last_name, $member->member_number], $template->letter_body) !!}--}}
    {!!  $documentTemplate->body !!}
</main>
</body>
</html>
