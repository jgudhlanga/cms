<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Offer Letter - {{ $studentProgram->id }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
        }
    </style>
</head>
<body class="p-6">
@include('partials.poly-header')
<main class="py-4 px-2">
    <div class="flex text-[10px] items-baseline">
        <div class="flex w-full">All correspondence to be addressed to the Principal marked for the attention of
            <div class="ml-2">
                .............................................................................................................................................................
            </div>
        </div>
    </div>
    <p class="text-[12px] font-bold my-2">{{\Carbon\Carbon::now()->format('d F Y')}}</p>
    <div class="flex flex-col uppercase text-[12px]">
        <div>James Jimmy Gudhlanga</div>
        <div>44-088821F44</div>
        <div>25ICT071HP</div>
    </div>
    <div class="uppercase font-bold text-[12px] my-2">August intake 2025: Full time</div>
    <p class="text-[12px]">
        I am pleased to inform you that your application for enrolment at {Harare Polytechnic} in the
        Department of {Mechanical} ({Nc Skills Proficiency - Machine shop Engineering}) for year {2025} was
        successful. To accept the offer you are therefore requested to pay a non refundable fee of <strong> {USD375} or
            the equivalent in {ZiG} at the prevailing bank rate. First year students at each level are to pay an
            additional {USD20} processing fee. </strong>Part time students are required to pay a part-time levy fee of
        {USD20} per term.
    </p>
    <div class="flex flex-col mt-2 text-[12px]">
        <div>Fees payment must be done through:</div>
        <ul class="list-disc ml-5">
            <li>All ZB Bank Branches</li>
            <li>Online transfers where full details of the student should be indicated which includes:</li>
            <ul class="list-disc ml-5">
                <li>Full name of Student</li>
                <li>Course and Level</li>
                <li>Student I.D. number</li>
            </ul>
        </ul>
    </div>
    <div class="flex justify-between mt-2 text-[12px] uppercase italic font-bold">
        <div class="flex flex-col ">
            <div>ZB Bank (Rotten Row Branch)</div>
            <div>Harare Polytechnic (Nostro)</div>
            <div class="lowercase">Account: <span class="ml-4">414400796412405</span></div>
            <div class="lowercase">Amount: <span class="ml-4">USD375</span></div>
        </div>
        <div class="flex flex-col">
            <div>ZB Bank (Rotten Row Branch)</div>
            <div>Harare Polytechnic (ZiG)</div>
            <div class="lowercase">Account: <span class="ml-4">414400796412082</span></div>
        </div>
    </div>
</main>
</body>
</html>
