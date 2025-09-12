<header class="flex flex-col">
    <div class="flex items-center justify-between">
        <img src="{{ url('assets/images/tesc.jpg') }}" alt="Left Logo" class="h-16 w-auto">
        <div class="flex flex-col text-center">
            <h4 class="text-[10px] font-bold uppercase">{{ $documentTemplate->header_line_1 }}</h4>
            <h3 class="text-md font-extrabold uppercase my-1">{{ $documentTemplate->header_line_2 }}</h3>
            <p class="text-xs">{{ $documentTemplate->header_address_line_1 }}</p>
            <p class="text-[10px]">{{ $documentTemplate->header_address_line_2 }}</p>
            <p class="text-[10px] space-x-2">
                <span class="font-bold">Telephone:</span><span>{{ $documentTemplate->header_telephone }}</span>
            </p>
            <p class="text-[10px] space-x-2">
                <span class="font-bold">Email:</span><span>{{ $documentTemplate->header_email }}</span>
            </p>
            <p class="text-[10px] space-x-2">
                <span class="font-bold">Website:</span><span>{{ $documentTemplate->header_website }}</span>
            </p>
        </div>
        <img src="{{ url('assets/images/logo.jpeg') }}" alt="Right Logo" class="h-16 w-auto">
    </div>
    <div class="flex w-full my-3 h-[1px] bg-primary"></div>
</header>
