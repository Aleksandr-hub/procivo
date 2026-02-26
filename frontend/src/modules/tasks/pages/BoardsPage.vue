<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useBoardStore } from '@/modules/tasks/stores/board.store'
import type { BoardDTO, BoardColumnDTO } from '@/modules/tasks/types/board.types'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const boardStore = useBoardStore()
const { t } = useI18n()

const orgId = computed(() => route.params.orgId as string)

const showBoardDialog = ref(false)
const editingBoard = ref<BoardDTO | null>(null)
const boardForm = ref({ name: '', description: '' })

const expandedRows = ref<Record<string, boolean>>({})

const showColumnDialog = ref(false)
const columnBoardId = ref('')
const editingColumn = ref<BoardColumnDTO | null>(null)
const columnForm = ref({ name: '', statusMapping: '', wipLimit: null as number | null, color: '' })

const statusOptions = [
  { label: t('tasks.statusDraft'), value: 'draft' },
  { label: t('tasks.statusOpen'), value: 'open' },
  { label: t('tasks.statusInProgress'), value: 'in_progress' },
  { label: t('tasks.statusReview'), value: 'review' },
  { label: t('tasks.statusDone'), value: 'done' },
  { label: t('tasks.statusBlocked'), value: 'blocked' },
  { label: t('tasks.statusCancelled'), value: 'cancelled' },
]

onMounted(() => {
  boardStore.fetchBoards(orgId.value)
})

function openCreateBoard() {
  editingBoard.value = null
  boardForm.value = { name: '', description: '' }
  showBoardDialog.value = true
}

function openEditBoard(board: BoardDTO) {
  editingBoard.value = board
  boardForm.value = { name: board.name, description: board.description || '' }
  showBoardDialog.value = true
}

async function saveBoard() {
  try {
    if (editingBoard.value) {
      await boardStore.updateBoard(orgId.value, editingBoard.value.id, boardForm.value)
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('boards.boardUpdated'), life: 3000 })
    } else {
      await boardStore.createBoard(orgId.value, boardForm.value)
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('boards.boardCreated'), life: 3000 })
    }
    showBoardDialog.value = false
  } catch {
    toast.add({ severity: 'error', summary: t('common.error'), detail: t('boards.failedToSave'), life: 5000 })
  }
}

function confirmDeleteBoard(board: BoardDTO) {
  confirm.require({
    message: t('boards.confirmDelete', { name: board.name }),
    header: t('boards.confirmDeleteTitle'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await boardStore.deleteBoard(orgId.value, board.id)
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('boards.boardDeleted'), life: 3000 })
      } catch {
        toast.add({ severity: 'error', summary: t('common.error'), detail: t('boards.failedToDelete'), life: 5000 })
      }
    },
  })
}

function openAddColumn(boardId: string) {
  columnBoardId.value = boardId
  editingColumn.value = null
  columnForm.value = { name: '', statusMapping: '', wipLimit: null, color: '' }
  showColumnDialog.value = true
}

function openEditColumn(boardId: string, col: BoardColumnDTO) {
  columnBoardId.value = boardId
  editingColumn.value = col
  columnForm.value = {
    name: col.name,
    statusMapping: col.statusMapping || '',
    wipLimit: col.wipLimit,
    color: col.color || '',
  }
  showColumnDialog.value = true
}

async function saveColumn() {
  try {
    const payload = {
      name: columnForm.value.name,
      status_mapping: columnForm.value.statusMapping || undefined,
      wip_limit: columnForm.value.wipLimit ?? undefined,
      color: columnForm.value.color || undefined,
    }

    if (editingColumn.value) {
      await boardStore.updateColumn(orgId.value, columnBoardId.value, editingColumn.value.id, {
        ...payload,
        position: editingColumn.value.position,
      })
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('boards.columnUpdated'), life: 3000 })
    } else {
      await boardStore.addColumn(orgId.value, columnBoardId.value, payload)
      toast.add({ severity: 'success', summary: t('common.success'), detail: t('boards.columnAdded'), life: 3000 })
    }
    showColumnDialog.value = false
  } catch {
    toast.add({ severity: 'error', summary: t('common.error'), detail: t('boards.failedToSave'), life: 5000 })
  }
}

function confirmDeleteColumn(boardId: string, col: BoardColumnDTO) {
  confirm.require({
    message: t('boards.confirmDeleteColumn', { name: col.name }),
    header: t('boards.confirmDeleteTitle'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await boardStore.deleteColumn(orgId.value, boardId, col.id)
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('boards.columnDeleted'), life: 3000 })
      } catch {
        toast.add({ severity: 'error', summary: t('common.error'), detail: t('boards.failedToDelete'), life: 5000 })
      }
    },
  })
}
</script>

<template>
  <div class="boards-page">
    <div class="page-header">
      <h3>{{ t('boards.title') }}</h3>
      <Button :label="t('boards.createBoard')" icon="pi pi-plus" @click="openCreateBoard" />
    </div>

    <DataTable
      :value="boardStore.boards"
      :loading="boardStore.loading"
      v-model:expandedRows="expandedRows"
      dataKey="id"
      stripedRows
    >
      <template #empty>
        <div class="empty-table">{{ t('boards.noBoardsFound') }}</div>
      </template>
      <Column expander style="width: 3rem" />
      <Column field="name" :header="t('boards.name')" sortable />
      <Column field="description" :header="t('boards.description')">
        <template #body="{ data }">
          {{ data.description || '—' }}
        </template>
      </Column>
      <Column :header="t('boards.columnsCount')" style="width: 120px">
        <template #body="{ data }">
          <Tag :value="String(data.columns.length)" severity="info" />
        </template>
      </Column>
      <Column field="createdAt" :header="t('boards.createdAt')" sortable style="width: 160px">
        <template #body="{ data }">
          {{ new Date(data.createdAt).toLocaleDateString() }}
        </template>
      </Column>
      <Column style="width: 100px">
        <template #body="{ data }">
          <div class="action-buttons">
            <Button
              icon="pi pi-th-large"
              text
              rounded
              size="small"
              v-tooltip="t('kanban.openKanban')"
              @click="router.push({ name: 'kanban', params: { orgId: orgId, boardId: data.id } })"
            />
            <Button icon="pi pi-pencil" text rounded size="small" @click="openEditBoard(data)" />
            <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="confirmDeleteBoard(data)" />
          </div>
        </template>
      </Column>
      <template #expansion="{ data: board }">
        <div class="columns-section">
          <div class="columns-header">
            <h4>{{ t('boards.columns') }}</h4>
            <Button :label="t('boards.addColumn')" icon="pi pi-plus" size="small" outlined @click="openAddColumn(board.id)" />
          </div>
          <DataTable :value="board.columns" v-if="board.columns.length > 0" size="small">
            <Column field="position" header="#" style="width: 60px" />
            <Column field="name" :header="t('boards.columnName')" />
            <Column field="statusMapping" :header="t('boards.statusMapping')" style="width: 160px">
              <template #body="{ data: col }">
                <Tag v-if="col.statusMapping" :value="col.statusMapping" severity="secondary" />
                <span v-else>—</span>
              </template>
            </Column>
            <Column field="wipLimit" :header="t('boards.wipLimit')" style="width: 120px">
              <template #body="{ data: col }">
                {{ col.wipLimit ?? '—' }}
              </template>
            </Column>
            <Column style="width: 100px">
              <template #body="{ data: col }">
                <div class="action-buttons">
                  <Button icon="pi pi-pencil" text rounded size="small" @click="openEditColumn(board.id, col)" />
                  <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="confirmDeleteColumn(board.id, col)" />
                </div>
              </template>
            </Column>
          </DataTable>
          <div v-else class="no-columns">{{ t('boards.noColumns') }}</div>
        </div>
      </template>
    </DataTable>

    <!-- Board Dialog -->
    <Dialog
      :visible="showBoardDialog"
      :header="editingBoard ? t('boards.dialogHeaderEdit') : t('boards.dialogHeaderCreate')"
      modal
      :style="{ width: '450px' }"
      @update:visible="showBoardDialog = $event"
    >
      <div class="form-field">
        <label>{{ t('boards.nameLabel') }}</label>
        <InputText v-model="boardForm.name" :placeholder="t('boards.namePlaceholder')" class="w-full" />
      </div>
      <div class="form-field">
        <label>{{ t('boards.descriptionLabel') }}</label>
        <Textarea v-model="boardForm.description" :placeholder="t('boards.descriptionPlaceholder')" rows="3" class="w-full" />
      </div>
      <template #footer>
        <Button :label="t('common.cancel')" text @click="showBoardDialog = false" />
        <Button :label="t('common.save')" icon="pi pi-check" @click="saveBoard" :disabled="!boardForm.name" />
      </template>
    </Dialog>

    <!-- Column Dialog -->
    <Dialog
      :visible="showColumnDialog"
      :header="editingColumn ? t('boards.dialogHeaderEditColumn') : t('boards.dialogHeaderAddColumn')"
      modal
      :style="{ width: '450px' }"
      @update:visible="showColumnDialog = $event"
    >
      <div class="form-field">
        <label>{{ t('boards.columnNameLabel') }}</label>
        <InputText v-model="columnForm.name" :placeholder="t('boards.columnNamePlaceholder')" class="w-full" />
      </div>
      <div class="form-field">
        <label>{{ t('boards.statusMapping') }}</label>
        <Select
          v-model="columnForm.statusMapping"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
          :placeholder="t('common.none')"
          showClear
          class="w-full"
        />
        <small class="form-help">{{ t('boards.statusMappingHelp') }}</small>
      </div>
      <div class="form-field">
        <label>{{ t('boards.wipLimit') }}</label>
        <InputNumber v-model="columnForm.wipLimit" :min="1" :placeholder="t('common.none')" showButtons class="w-full" />
        <small class="form-help">{{ t('boards.wipLimitHelp') }}</small>
      </div>
      <template #footer>
        <Button :label="t('common.cancel')" text @click="showColumnDialog = false" />
        <Button :label="t('common.save')" icon="pi pi-check" @click="saveColumn" :disabled="!columnForm.name" />
      </template>
    </Dialog>

    <ConfirmDialog />
  </div>
</template>

<style scoped>
.boards-page {
  max-width: 1200px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.page-header h3 {
  margin: 0;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
}

.columns-section {
  padding: 1rem;
}

.columns-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.columns-header h4 {
  margin: 0;
}

.no-columns {
  text-align: center;
  padding: 1.5rem;
  color: var(--p-text-muted-color);
}

.form-field {
  margin-bottom: 1rem;
}

.form-field label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-help {
  display: block;
  margin-top: 0.25rem;
  color: var(--p-text-muted-color);
}

.w-full {
  width: 100%;
}

.empty-table {
  text-align: center;
  padding: 2rem;
  color: var(--p-text-muted-color);
}
</style>
