<template>
  <div class="animate-pulse  p-4">
    <table class="hava-table">
      <thead class="hava-thead">
        <tr>
          <th v-for="n in 3" :key="n" class="hava-th w-24 h-4 animate-pulse"></th>
        </tr>
      </thead>
      <tbody class="hava-tbody">
          <tr v-for="r in 5" :key="r">
            <td v-for="k in 3" :key="k" class="p-3 border">
              <div class="hava-td w-24 h-4 bg-muted/40 rounded animate-pulse"></div>
            </td>
          </tr>
      </tbody>
    </table>
  </div>
</template>
