<script setup lang="ts">
import { Separator } from '@/components/ui/separator'
import { IconName } from '@/enums/icons';




const fields = [
  { label: 'Student ID',           value: 'B20/ICT/001234/22'  },
  { label: 'National ID',          value: '63-2198744-F-34'    },
  { label: 'Date of Birth',        value: '14 March 2002'      },
  { label: 'Gender',               value: 'Female'             },
  { label: 'Enrolled',             value: 'Feb 2022'           },
  { label: 'Expected Graduation',  value: 'Dec 2024'           },
  { label: 'Sponsorship',          value: 'ZIMDEF'             },
  { label: 'Accommodation',        value: 'Off-campus'         },
]

const data = [
  { label: 'Phone',               value: '+263 77 345 6789',       icon: IconName.phone },
  { label: 'Email',               value: 'rutendo.c@poly.ac.zw',   icon: IconName.mail  },
  { label: 'Home Address',        value: '45 Mbare Ave, Harare',   icon: IconName.house  },
  { label: 'Guardian',            value: 'Tarisai Chikwanda',      icon: IconName.user  },
  { label: 'Guardian Contact',    value: '+263 71 234 5678',       icon: IconName.phone },
  { label: 'Entry Qualification', value: '5 O-Levels incl. Maths', icon: IconName.award  },
]

</script>

<template>
    <div class="grid grid-cols-3 gap-x-6 gap-y-3">
        <LabelValue v-for="(field, idx) in fields" :key="idx" :label="field.label" :value="field.value" />
    </div>
    <Separator class="my-6" />
    <div class="grid grid-cols-3 gap-x-6 gap-y-3">
        <InfoCard v-for="(field, idx) in data" :key="idx" :label="field.label" :value="field.value" :icon="field.icon" />
    </div>
</template>
