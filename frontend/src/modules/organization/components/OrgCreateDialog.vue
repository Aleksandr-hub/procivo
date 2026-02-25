<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useOrganizationStore } from '@/modules/organization/stores/organization.store'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  hide: []
  created: [id: string]
}>()

const toast = useToast()
const orgStore = useOrganizationStore()
const { t } = useI18n()

const name = ref('')
const slug = ref('')
const description = ref('')
const submitted = ref(false)
const saving = ref(false)
const slugManuallyEdited = ref(false)

watch(
  () => name.value,
  (val) => {
    if (!slugManuallyEdited.value) {
      slug.value = val
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .slice(0, 60)
    }
  },
)

watch(
  () => props.visible,
  (val) => {
    if (val) {
      name.value = ''
      slug.value = ''
      description.value = ''
      submitted.value = false
      slugManuallyEdited.value = false
    }
  },
)

function onSlugInput() {
  slugManuallyEdited.value = true
}

async function onSubmit() {
  submitted.value = true
  if (!name.value || !slug.value) return

  saving.value = true
  try {
    const id = await orgStore.createOrganization({
      name: name.value,
      slug: slug.value,
      description: description.value || null,
    })
    emit('created', id)
  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { error?: string } } }
    toast.add({
      severity: 'error',
      summary: t('common.error'),
      detail: axiosError.response?.data?.error || t('dialog.orgCreate.failedToCreate'),
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Dialog
    :visible="visible"
    :header="t('dialog.orgCreate.header')"
    :style="{ width: '480px' }"
    modal
    @update:visible="!$event && emit('hide')"
  >
    <form @submit.prevent="onSubmit" class="dialog-form">
      <div class="field">
        <label for="orgName">{{ t('dialog.orgCreate.name') }}</label>
        <InputText
          id="orgName"
          v-model="name"
          :placeholder="t('dialog.orgCreate.namePlaceholder')"
          :invalid="submitted && !name"
          fluid
        />
        <small v-if="submitted && !name" class="p-error">{{ t('dialog.orgCreate.nameRequired') }}</small>
      </div>

      <div class="field">
        <label for="orgSlug">{{ t('dialog.orgCreate.slug') }}</label>
        <InputText
          id="orgSlug"
          v-model="slug"
          :placeholder="t('dialog.orgCreate.slugPlaceholder')"
          :invalid="submitted && !slug"
          fluid
          @input="onSlugInput"
        />
        <small v-if="submitted && !slug" class="p-error">{{ t('dialog.orgCreate.slugRequired') }}</small>
      </div>

      <div class="field">
        <label for="orgDesc">{{ t('dialog.orgCreate.description') }}</label>
        <Textarea
          id="orgDesc"
          v-model="description"
          :placeholder="t('dialog.orgCreate.descriptionPlaceholder')"
          rows="3"
          fluid
        />
      </div>

      <div class="dialog-footer">
        <Button :label="t('common.cancel')" severity="secondary" text @click="emit('hide')" />
        <Button type="submit" :label="t('common.create')" :loading="saving" />
      </div>
    </form>
  </Dialog>
</template>

<style scoped>
.dialog-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding-top: 0.5rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field label {
  font-weight: 600;
  font-size: 0.875rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding-top: 0.5rem;
}
</style>
