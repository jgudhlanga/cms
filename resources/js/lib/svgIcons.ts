const svgIcons: Record<string, string> = {
    users: `<svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                />
            </svg>`,
    checkDone: `<svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>`,
    chartGrowth: ` <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>`,
    time: `<svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>`,
    error: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6 text-red-600" role="img" aria-labelledby="err3Title" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <title id="err3Title">Error</title>
  <path d="M10.29 3.86L1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.42 0z"/>
  <line x1="12" y1="9" x2="12" y2="13"/>
  <line x1="12" y1="17" x2="12.01" y2="17"/>
</svg>`,
    male: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6 text-primary"  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <title>Male Symbol</title>
  <circle cx="9" cy="15" r="6"/>
  <line x1="15" y1="9" x2="21" y2="3"/>
  <line x1="16" y1="3" x2="21" y2="3" />
  <line x1="21" y1="8" x2="21" y2="3" />
</svg>`,
    female: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6 text-pink-600" role="img" aria-labelledby="female1Title" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <title id="female1Title">Female symbol</title>
  <circle cx="12" cy="8" r="5"/>
  <line x1="12" y1="13" x2="12" y2="21"/>
  <line x1="9" y1="18" x2="15" y2="18"/>
</svg>`,
    paymentSuccess: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6 text-green-600" role="img" aria-labelledby="pay3Title" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <title id="pay3Title">Payment Success</title>
  <rect x="2" y="5" width="20" height="14" rx="2" ry="2"/>
  <line x1="2" y1="10" x2="22" y2="10"/>
  <path d="M15 14l2 2 4-4"/>
</svg>`,
    paymentFailed: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6 text-red-600" role="img" aria-labelledby="fail3Title" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <title id="fail3Title">Payment Failure</title>
  <rect x="2" y="5" width="20" height="14" rx="2" ry="2"/>
  <line x1="2" y1="10" x2="22" y2="10"/>
  <line x1="15" y1="13" x2="19" y2="17"/>
  <line x1="19" y1="13" x2="15" y2="17"/>
</svg>`,
};

export { svgIcons };
