<template>
    <div class="space-y-6">
    
        <!-- Pay Period Selection -->
        <div v-if="!showGenerated" class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
    
            </div>
            <Card>
                <template #content>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm mb-1">Pay Period</label>
                            <Select v-model="selectedPeriod" :options="periods" optionLabel="label"
                                placeholder="Select period" class="w-full" />
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Pay Date</label>
                            <DatePicker v-model="payDate" class="w-full" />
                        </div>
    
                        <div class="flex gap-2">
                            <Button label="Generate Payroll" class="ml-auto" severity="success" @click="generatePayroll" />
                        </div>
                    </div>
    
    
                    <DataTable :value="employees" selectionMode="multiple" v-model:selection="selectedEmployees"
                        showGridlines>
                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                        <Column field="name" header="Employee"></Column>
                        <Column field="role_name" header="Position"></Column>
                        <Column field="department" header="Department"></Column>
                        <Column field="branch" header="Branch"></Column>
                        <Column field="basicSalary" header="Basic Salary">
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    
    
        <!-- Preview Section -->
        <div v-else>
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <div class="flex gap-2">
                    <Button label="Back" severity="contrast" @click="showGenerated = false" />
                </div>
            </div>
            <Card>
                <Button label="Generate Payroll" severity="success" @click="generatePayroll" />
                <template #title> Preview Payroll</template>
                <template #content>
                    <DataTable :value="selectedEmployees" stripedRows>
                        <Column field="name" header="Employee"></Column>
                        <Column field="basicSalary" header="Basic">
    
                        </Column>
                        <Column header="Overtime">
                            <template #body>
                                <InputNumber :min="0" class="w-24" />
                            </template>
                        </Column>
                        <Column header="Allowances">
                            <template #body>
                                <InputNumber :min="0" class="w-24" />
                            </template>
                        </Column>
                        <Column header="Deductions">
                            <template #body>
                                <InputNumber :min="0" class="w-24" />
                            </template>
                        </Column>
                        <Column header="Net Pay">
                            {{ formatCurrency(basicSalary) }}
                        </Column>
                    </DataTable>
    
                    <Divider />
    
                    <div class="flex justify-center gap-3 mt-4">
                        <span class="font-bold">Total: {{ formatCurrency(totalNetPay) }}</span>
                        <Button label="Save as Draft" class="ml-auto" severity="secondary" />
                        <Button label="Generate Payroll" class="" severity="info" @click="generatePayroll" />
    
                    </div>
                </template>
            </Card>
        </div>
    
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Employee {
  id: number
  name: string
  role_name: string
  department: string
  basicSalary: number
  branch: string
}

// State
const showGenerated = ref<boolean>(false)
const selectedPeriod = ref(null)
const payDate = ref(new Date())
const selectedEmployees = ref([])

// Data
const periods = ref([
  { label: 'December 1-15, 2024', value: 'dec-1-15' },
  { label: 'December 16-31, 2024', value: 'dec-16-31' }
])



const employees = ref<Employee[]>([
  { id: 1, name: 'John Cruz', role_name: 'Supervisor', department: 'Operations', basicSalary: 8000, branch: 'Dasmarinas' },
  { id: 2, name: 'Maria Santos', role_name: 'Manager', department: 'Warehouse', basicSalary: 16500, branch: 'Dasmarinas' },
  { id: 3, name: 'Carlos Lim', role_name: 'Executive', department: 'Sales', basicSalary: 20000, branch: 'Dasmarinas' }
])



// Computed
const totalNetPay = computed(() => {
  return selectedEmployees.value.reduce((sum, emp) => sum + emp.basicSalary, 0)
})

// Methods
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount)
}


const generatePayroll = () => {


  console.log(employees.value[1])
  if (!selectedPeriod.value) {
    alert('Please select a pay period')
    return
  }
  if (selectedEmployees.value.length === 0) {
    alert('Please select at least one employee')
    return
  }

  showGenerated.value = true

  alert(`Payroll generated for ${selectedEmployees.value.length} employees`)
  // Navigate to payroll list 

}
</script>