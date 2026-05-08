<template>
    <div class="animate-pulse p-4">
        <table class="hava-table">
            <thead class="hava-thead">
                <tr>
                    <th v-for="n in 3" :key="n" class="hava-th h-4 w-24 animate-pulse"></th>
                </tr>
            </thead>
            <tbody class="hava-tbody">
                <tr v-for="r in 5" :key="r">
                    <td v-for="k in 3" :key="k" class="border p-3">
                        <div class="hava-td h-4 w-24 animate-pulse rounded bg-gray-300"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
