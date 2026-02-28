<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEmployeeStore } from '@/modules/organization/stores/employee.store'
import { useRoleStore } from '@/modules/organization/stores/role.store'
import { useDepartmentStore } from '@/modules/organization/stores/department.store'
import DynamicFormField from '@/modules/tasks/components/DynamicFormField.vue'
import { buildZodSchema, flattenZodErrors } from '@/shared/utils/zod-schema-builder'
import type { StatusAction } from '@/modules/tasks/types/task.types'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

const props = defineProps<{
  visible: boolean
  action: StatusAction | null
  sharedFields?: FormFieldDefinition[]
  showNextExecutor?: boolean
}>()

const emit = defineEmits<{
  hide: []
  submit: [data: { actionKey: string; formData: Record<string, unknown> }]
}>()

const { t } = useI18n()
const empStore = useEmployeeStore()
const roleStore = useRoleStore()
const deptStore = useDepartmentStore()

const formData = ref<Record<string, unknown>>({})
const errors = ref<Record<string, string>>({})
const comment = ref('')
const hasSubmitted = ref(false)

// Next executor state — 4 types
const assignmentMode = ref<'person' | 'role' | 'department' | 'position'>('person')
const selectedPersonId = ref<string | null>(null)
const selectedRoleId = ref<string | null>(null)
const selectedDepartmentId = ref<string | null>(null)

const employeeOptions = computed(() =>
  empStore.employees
    .filter((e) => e.status === 'active')
    .map((e) => ({ label: e.userFullName ?? e.userId, value: e.id })),
)

const roleOptions = computed(() =>
  roleStore.roles.map((r) => ({ label: r.name, value: r.id })),
)

const departmentOptions = computed(() => {
  const result: Array<{ label: string; value: string }> = []
  function collect(nodes: Array<{ id: string; name: string; children: unknown[] }>) {
    for (const node of nodes) {
      result.push({ label: node.name, value: node.id })
      if (node.children?.length) collect(node.children as typeof nodes)
    }
  }
  collect(deptStore.tree)
  return result
})

const assignmentHint = computed(() => {
  switch (assignmentMode.value) {
    case 'person': return t('taskDetail.userAssignmentHint')
    case 'role': return t('taskDetail.roleAssignmentHint')
    case 'department': return t('taskDetail.departmentAssignmentHint')
    case 'position': return t('taskDetail.positionAssignmentHint')
    default: return ''
  }
})

function getDefaultValue(field: FormFieldDefinition): unknown {
  switch (field.type) {
    case 'checkbox':
      return false
    case 'number':
      return null
    case 'date':
      return null
    default:
      return ''
  }
}

// Form validity check for disabled submit
const isFormValid = computed(() => {
  const fields = allFields()
  const requiredFields = fields.filter(f => f.required)
  for (const field of requiredFields) {
    const val = formData.value[field.name]
    if (val === undefined || val === null || val === '') return false
  }
  return true
})

watch(
  () => props.visible,
  (val) => {
    if (val && props.action) {
      errors.value = {}
      comment.value = ''
      hasSubmitted.value = false
      assignmentMode.value = 'person'
      selectedPersonId.value = null
      selectedRoleId.value = null
      selectedDepartmentId.value = null
      const data: Record<string, unknown> = {}
      for (const field of props.sharedFields ?? []) {
        data[field.name] = getDefaultValue(field)
      }
      for (const field of props.action.formFields) {
        data[field.name] = getDefaultValue(field)
      }
      formData.value = data
    }
  },
)

function allFields(): FormFieldDefinition[] {
  return [...(props.sharedFields ?? []), ...(props.action?.formFields ?? [])]
}

function validate(): boolean {
  const fields = allFields()
  const schema = buildZodSchema(fields)
  const result = schema.safeParse(formData.value)
  if (!result.success) {
    errors.value = flattenZodErrors(result.error)
    return false
  }
  errors.value = {}
  return true
}

function onFieldBlur() {
  if (hasSubmitted.value) {
    validate()
  }
}

function actionSeverity(actionKey: string): 'success' | 'danger' | 'secondary' {
  const key = actionKey.toLowerCase()
  if (key.includes('approve') || key.includes('accept') || key.includes('confirm')) {
    return 'success'
  }
  if (key.includes('reject') || key.includes('decline') || key.includes('cancel')) {
    return 'danger'
  }
  return 'secondary'
}

function handleSubmit() {
  if (!props.action) return

  hasSubmitted.value = true
  if (!validate()) return

  const serialized: Record<string, unknown> = {}
  for (const [key, value] of Object.entries(formData.value)) {
    serialized[key] = value instanceof Date ? value.toISOString().split('T')[0] : value
  }

  if (comment.value.trim()) {
    serialized._comment = comment.value.trim()
  }

  // Add next executor assignment data
  if (props.showNextExecutor) {
    if (assignmentMode.value === 'person' && selectedPersonId.value) {
      serialized._next_assignee_id = selectedPersonId.value
    } else if (assignmentMode.value === 'role' && selectedRoleId.value) {
      serialized._next_candidate_role_id = selectedRoleId.value
    } else if (assignmentMode.value === 'department' && selectedDepartmentId.value) {
      serialized._next_candidate_department_id = selectedDepartmentId.value
    }
  }

  emit('submit', { actionKey: props.action.key, formData: serialized })
}
</script>

<template>
  <Dialog
    :visible="visible"
    :header="action?.label ?? ''"
    modal
    :style="{ width: '672px', maxHeight: '90vh' }"
    :content-style="{ overflowY: 'auto' }"
    @update:visible="!$event && emit('hide')"
  >
    <p class="action-subtitle">{{ t('taskDetail.fillFormForAction') }}</p>

    <div class="action-form">
      <!-- Shared fields -->
      <template v-if="sharedFields && sharedFields.length > 0">
        <DynamicFormField
          v-for="field in sharedFields"
          :key="field.name"
          :field="field"
          :model-value="formData[field.name]"
          :error="errors[field.name]"
          @update:model-value="formData[field.name] = $event"
          @blur="onFieldBlur"
        />
      </template>

      <!-- Action-specific fields -->
      <template v-if="action && action.formFields.length > 0">
        <DynamicFormField
          v-for="field in action.formFields"
          :key="field.name"
          :field="field"
          :model-value="formData[field.name]"
          :error="errors[field.name]"
          @update:model-value="formData[field.name] = $event"
          @blur="onFieldBlur"
        />
      </template>

      <!-- Comment -->
      <div class="comment-section">
        <label>{{ t('taskDetail.comment') }}</label>
        <Textarea
          v-model="comment"
          :placeholder="t('taskDetail.commentPlaceholder')"
          class="w-full"
          :rows="3"
          auto-resize
        />
      </div>

      <!-- Next Executor (optional) -->
      <div v-if="showNextExecutor" class="next-executor-section">
        <label class="section-title">{{ t('taskDetail.nextExecutor') }}</label>

        <div class="assignment-mode-toggle">
          <div class="mode-option" :class="{ active: assignmentMode === 'person' }" @click="assignmentMode = 'person'">
            <RadioButton v-model="assignmentMode" value="person" />
            <div class="mode-info">
              <i class="pi pi-user" />
              <span>{{ t('taskDetail.specificPerson') }}</span>
            </div>
          </div>
          <div class="mode-option" :class="{ active: assignmentMode === 'role' }" @click="assignmentMode = 'role'">
            <RadioButton v-model="assignmentMode" value="role" />
            <div class="mode-info">
              <i class="pi pi-id-card" />
              <span>{{ t('taskDetail.byRole') }}</span>
            </div>
          </div>
          <div class="mode-option" :class="{ active: assignmentMode === 'department' }" @click="assignmentMode = 'department'">
            <RadioButton v-model="assignmentMode" value="department" />
            <div class="mode-info">
              <i class="pi pi-building" />
              <span>{{ t('taskDetail.byDepartment') }}</span>
            </div>
          </div>
          <div class="mode-option disabled" :class="{ active: assignmentMode === 'position' }" @click="assignmentMode = 'position'">
            <RadioButton v-model="assignmentMode" value="position" />
            <div class="mode-info">
              <i class="pi pi-briefcase" />
              <span>{{ t('taskDetail.byPosition') }}</span>
            </div>
          </div>
        </div>

        <!-- Info hint -->
        <Message severity="info" :closable="false" class="assignment-hint">
          {{ assignmentHint }}
        </Message>

        <div class="assignment-select">
          <Select
            v-if="assignmentMode === 'person'"
            v-model="selectedPersonId"
            :options="employeeOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('taskDetail.selectPerson')"
            filter
            class="w-full"
          />
          <Select
            v-else-if="assignmentMode === 'role'"
            v-model="selectedRoleId"
            :options="roleOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('taskDetail.selectRole')"
            filter
            class="w-full"
          />
          <Select
            v-else-if="assignmentMode === 'department'"
            v-model="selectedDepartmentId"
            :options="departmentOptions"
            optionLabel="label"
            optionValue="value"
            :placeholder="t('taskDetail.selectDepartment')"
            filter
            class="w-full"
          />
          <Select
            v-else-if="assignmentMode === 'position'"
            :model-value="null"
            :options="[]"
            :placeholder="t('taskDetail.selectPosition')"
            disabled
            class="w-full"
          />
        </div>
      </div>
    </div>

    <template #footer>
      <Button :label="t('common.cancel')" text @click="emit('hide')" />
      <Button
        :label="action?.label ?? t('common.submit')"
        :severity="action ? actionSeverity(action.key) : undefined"
        :disabled="!isFormValid"
        @click="handleSubmit"
      />
    </template>
  </Dialog>
</template>

<style scoped>
.action-subtitle {
  margin: 0 0 1rem;
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}

.action-form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.comment-section {
  margin-top: 0.5rem;
}

.comment-section label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
}

/* Next executor section */
.next-executor-section {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--p-surface-border);
}

.section-title {
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

.assignment-mode-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.mode-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--p-surface-border);
  border-radius: var(--p-border-radius);
  cursor: pointer;
  transition: border-color 0.15s;
}

.mode-option.active {
  border-color: var(--p-primary-color);
  background: color-mix(in srgb, var(--p-primary-color) 5%, transparent);
}

.mode-option.disabled {
  opacity: 0.5;
}

.mode-info {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
}

.mode-info i {
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}

.assignment-hint {
  margin-bottom: 0.75rem;
}

.assignment-hint :deep(.p-message-text) {
  font-size: 0.8rem;
}

.assignment-select {
  margin-top: 0.25rem;
}
</style>
