import type { NodeType, FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

export interface TemplateNode {
  type: NodeType
  nameKey: string
  position_x: number
  position_y: number
  config?: Record<string, unknown>
}

export interface TemplateTransition {
  sourceIndex: number
  targetIndex: number
  nameKey?: string
  action_key?: string
  condition_expression?: string
  form_fields?: FormFieldDefinition[]
}

export interface ProcessTemplate {
  id: string
  nameKey: string
  descriptionKey: string
  category: 'general' | 'hr' | 'finance' | 'it'
  icon: string
  nodes: TemplateNode[]
  transitions: TemplateTransition[]
}

export const processTemplates: ProcessTemplate[] = [
  // ── General: Task with review ────────────────────────────
  // Start → Execute → Review → XOR
  //   → Done → End
  //   → Rework → back to Execute
  //   → Not relevant → End (Closed)
  {
    id: 'simple-task',
    nameKey: 'templates.simpleTaskName',
    descriptionKey: 'templates.simpleTaskDesc',
    category: 'general',
    icon: 'pi pi-file',
    nodes: [
      /* 0 */ { type: 'start', nameKey: 'Start', position_x: 80, position_y: 200 },
      /* 1 */ { type: 'task', nameKey: 'Execute', position_x: 280, position_y: 200, config: { task_title_template: 'Execute the stage', priority: 'medium', formFields: [{ name: 'result', label: 'Work result', type: 'textarea', required: true }] } },
      /* 2 */ { type: 'task', nameKey: 'Review', position_x: 530, position_y: 200, config: { task_title_template: 'Review completed work', priority: 'high', formFields: [{ name: 'decision', label: 'Decision', type: 'select', required: true, options: ['Done', 'Rework', 'Not relevant'] }, { name: 'comment', label: 'Comment', type: 'textarea', required: false }] } },
      /* 3 */ { type: 'exclusive_gateway', nameKey: 'Decision', position_x: 780, position_y: 200 },
      /* 4 */ { type: 'end', nameKey: 'Done', position_x: 1030, position_y: 100 },
      /* 5 */ { type: 'end', nameKey: 'Closed', position_x: 1030, position_y: 350 },
    ],
    transitions: [
      { sourceIndex: 0, targetIndex: 1 },
      { sourceIndex: 1, targetIndex: 2 },
      { sourceIndex: 2, targetIndex: 3 },
      { sourceIndex: 3, targetIndex: 4, nameKey: 'Done', condition_expression: "decision == 'Done'" },
      { sourceIndex: 3, targetIndex: 1, nameKey: 'Rework', condition_expression: "decision == 'Rework'" },
      { sourceIndex: 3, targetIndex: 5, nameKey: 'Not relevant', condition_expression: "decision == 'Not relevant'" },
    ],
  },

  // ── General: Document Approval ───────────────────────────
  // Start → Submit → Review → XOR
  //   → Approved → Notify → End
  //   → Needs revision → Revision → back to Review
  //   → Rejected → Notify rejection → End (Rejected)
  {
    id: 'document-approval',
    nameKey: 'templates.documentApprovalName',
    descriptionKey: 'templates.documentApprovalDesc',
    category: 'general',
    icon: 'pi pi-check-circle',
    nodes: [
      /* 0 */ { type: 'start', nameKey: 'Start', position_x: 80, position_y: 250 },
      /* 1 */ { type: 'task', nameKey: 'Submit Document', position_x: 280, position_y: 250, config: { task_title_template: 'Submit document for review', priority: 'medium', formFields: [{ name: 'document_name', label: 'Document name', type: 'text', required: true }, { name: 'description', label: 'Description', type: 'textarea', required: false }] } },
      /* 2 */ { type: 'task', nameKey: 'Review', position_x: 530, position_y: 250, config: { task_title_template: 'Review submitted document', priority: 'high', formFields: [{ name: 'decision', label: 'Decision', type: 'select', required: true, options: ['Approved', 'Needs revision', 'Rejected'] }, { name: 'feedback', label: 'Feedback', type: 'textarea', required: false }] } },
      /* 3 */ { type: 'exclusive_gateway', nameKey: 'Decision', position_x: 800, position_y: 250 },
      /* 4 */ { type: 'notification', nameKey: 'Approved', position_x: 1050, position_y: 100, config: { message_template: 'Your document has been approved', recipient_type: 'initiator' } },
      /* 5 */ { type: 'task', nameKey: 'Revision', position_x: 530, position_y: 470, config: { task_title_template: 'Revise document based on feedback', priority: 'medium' } },
      /* 6 */ { type: 'notification', nameKey: 'Rejected', position_x: 1050, position_y: 400, config: { message_template: 'Your document has been rejected', recipient_type: 'initiator' } },
      /* 7 */ { type: 'end', nameKey: 'End (Approved)', position_x: 1300, position_y: 100 },
      /* 8 */ { type: 'end', nameKey: 'End (Rejected)', position_x: 1300, position_y: 400 },
    ],
    transitions: [
      { sourceIndex: 0, targetIndex: 1 },
      { sourceIndex: 1, targetIndex: 2 },
      { sourceIndex: 2, targetIndex: 3 },
      { sourceIndex: 3, targetIndex: 4, nameKey: 'Approved', condition_expression: "decision == 'Approved'" },
      { sourceIndex: 3, targetIndex: 5, nameKey: 'Needs revision', condition_expression: "decision == 'Needs revision'" },
      { sourceIndex: 3, targetIndex: 6, nameKey: 'Rejected', condition_expression: "decision == 'Rejected'" },
      { sourceIndex: 5, targetIndex: 2 },
      { sourceIndex: 4, targetIndex: 7 },
      { sourceIndex: 6, targetIndex: 8 },
    ],
  },

  // ── HR: Employee Onboarding ──────────────────────────────
  // Start → Collect docs → Verify docs → XOR
  //   → OK → AND split: Workspace + IT + HR docs → AND merge → Welcome → End
  //   → Incomplete → Notify to fix → back to Collect
  {
    id: 'hr-onboarding',
    nameKey: 'templates.hrOnboardingName',
    descriptionKey: 'templates.hrOnboardingDesc',
    category: 'hr',
    icon: 'pi pi-users',
    nodes: [
      /* 0  */ { type: 'start', nameKey: 'Start', position_x: 80, position_y: 250 },
      /* 1  */ { type: 'task', nameKey: 'Collect Documents', position_x: 260, position_y: 250, config: { task_title_template: 'Collect employee documents (passport, diploma, etc.)', priority: 'high', formFields: [{ name: 'docs_ready', label: 'All documents provided?', type: 'checkbox', required: true }] } },
      /* 2  */ { type: 'task', nameKey: 'Verify Documents', position_x: 490, position_y: 250, config: { task_title_template: 'Verify employee documents', assignee_type: 'role', assignee_value: 'HR', priority: 'high', formFields: [{ name: 'verification', label: 'Verification result', type: 'select', required: true, options: ['OK', 'Incomplete'] }, { name: 'comment', label: 'Comment', type: 'textarea', required: false }] } },
      /* 3  */ { type: 'exclusive_gateway', nameKey: 'Docs OK?', position_x: 720, position_y: 250 },
      /* 4  */ { type: 'parallel_gateway', nameKey: 'Split', position_x: 920, position_y: 250 },
      /* 5  */ { type: 'task', nameKey: 'Workspace Setup', position_x: 1140, position_y: 100, config: { task_title_template: 'Set up workspace and equipment', priority: 'medium' } },
      /* 6  */ { type: 'task', nameKey: 'IT Access', position_x: 1140, position_y: 250, config: { task_title_template: 'Create accounts and access rights', assignee_type: 'role', assignee_value: 'IT', priority: 'high' } },
      /* 7  */ { type: 'task', nameKey: 'HR Documents', position_x: 1140, position_y: 400, config: { task_title_template: 'Prepare employment contract and docs', assignee_type: 'role', assignee_value: 'HR', priority: 'medium' } },
      /* 8  */ { type: 'parallel_gateway', nameKey: 'Merge', position_x: 1380, position_y: 250 },
      /* 9  */ { type: 'task', nameKey: 'Welcome Meeting', position_x: 1580, position_y: 250, config: { task_title_template: 'Welcome meeting with new employee', priority: 'medium' } },
      /* 10 */ { type: 'end', nameKey: 'End', position_x: 1800, position_y: 250 },
      /* 11 */ { type: 'notification', nameKey: 'Docs Incomplete', position_x: 720, position_y: 450, config: { message_template: 'Some documents are missing or incorrect. Please provide them.', recipient_type: 'initiator' } },
    ],
    transitions: [
      { sourceIndex: 0, targetIndex: 1 },
      { sourceIndex: 1, targetIndex: 2 },
      { sourceIndex: 2, targetIndex: 3 },
      { sourceIndex: 3, targetIndex: 4, nameKey: 'OK', condition_expression: "verification == 'OK'" },
      { sourceIndex: 3, targetIndex: 11, nameKey: 'Incomplete', condition_expression: "verification == 'Incomplete'" },
      { sourceIndex: 11, targetIndex: 1 },
      { sourceIndex: 4, targetIndex: 5 },
      { sourceIndex: 4, targetIndex: 6 },
      { sourceIndex: 4, targetIndex: 7 },
      { sourceIndex: 5, targetIndex: 8 },
      { sourceIndex: 6, targetIndex: 8 },
      { sourceIndex: 7, targetIndex: 8 },
      { sourceIndex: 8, targetIndex: 9 },
      { sourceIndex: 9, targetIndex: 10 },
    ],
  },

  // ── HR: Leave Request ────────────────────────────────────
  // Start → Submit → Manager Review → XOR
  //   → Approved → HR Notify → End
  //   → Clarification needed → Clarify → back to Manager Review
  //   → Rejected → Reject Notify → End (Rejected)
  {
    id: 'leave-request',
    nameKey: 'templates.leaveRequestName',
    descriptionKey: 'templates.leaveRequestDesc',
    category: 'hr',
    icon: 'pi pi-calendar',
    nodes: [
      /* 0 */ { type: 'start', nameKey: 'Start', position_x: 80, position_y: 250 },
      /* 1 */ { type: 'task', nameKey: 'Submit Request', position_x: 280, position_y: 250, config: { task_title_template: 'Submit leave request', priority: 'medium', formFields: [{ name: 'start_date', label: 'Start date', type: 'date', required: true }, { name: 'end_date', label: 'End date', type: 'date', required: true }, { name: 'leave_type', label: 'Leave type', type: 'select', required: true, options: ['Annual', 'Sick', 'Personal', 'Other'] }, { name: 'reason', label: 'Reason', type: 'textarea', required: false }] } },
      /* 2 */ { type: 'task', nameKey: 'Manager Review', position_x: 530, position_y: 250, config: { task_title_template: 'Review leave request', assignee_type: 'role', assignee_value: 'Manager', priority: 'high', formFields: [{ name: 'decision', label: 'Decision', type: 'select', required: true, options: ['Approved', 'Clarification needed', 'Rejected'] }, { name: 'comment', label: 'Comment', type: 'textarea', required: false }] } },
      /* 3 */ { type: 'exclusive_gateway', nameKey: 'Decision', position_x: 800, position_y: 250 },
      /* 4 */ { type: 'notification', nameKey: 'Approved Notice', position_x: 1050, position_y: 100, config: { message_template: 'Your leave request has been approved', recipient_type: 'initiator' } },
      /* 5 */ { type: 'task', nameKey: 'Clarify', position_x: 530, position_y: 470, config: { task_title_template: 'Clarify leave request details', priority: 'medium', formFields: [{ name: 'clarification', label: 'Clarification', type: 'textarea', required: true }] } },
      /* 6 */ { type: 'notification', nameKey: 'Rejected Notice', position_x: 1050, position_y: 400, config: { message_template: 'Your leave request has been rejected', recipient_type: 'initiator' } },
      /* 7 */ { type: 'end', nameKey: 'End (Approved)', position_x: 1300, position_y: 100 },
      /* 8 */ { type: 'end', nameKey: 'End (Rejected)', position_x: 1300, position_y: 400 },
    ],
    transitions: [
      { sourceIndex: 0, targetIndex: 1 },
      { sourceIndex: 1, targetIndex: 2 },
      { sourceIndex: 2, targetIndex: 3 },
      { sourceIndex: 3, targetIndex: 4, nameKey: 'Approved', condition_expression: "decision == 'Approved'" },
      { sourceIndex: 3, targetIndex: 5, nameKey: 'Clarification needed', condition_expression: "decision == 'Clarification needed'" },
      { sourceIndex: 3, targetIndex: 6, nameKey: 'Rejected', condition_expression: "decision == 'Rejected'" },
      { sourceIndex: 5, targetIndex: 2 },
      { sourceIndex: 4, targetIndex: 7 },
      { sourceIndex: 6, targetIndex: 8 },
    ],
  },

  // ── Finance: Invoice Processing ──────────────────────────
  // Start → Submit → Manager → XOR
  //   → Approved → Finance Check → XOR
  //     → OK → Payment → Notify → End
  //     → Correction → back to Submit
  //   → Revision → back to Submit
  //   → Rejected → Notify → End (Rejected)
  {
    id: 'invoice-processing',
    nameKey: 'templates.invoiceProcessingName',
    descriptionKey: 'templates.invoiceProcessingDesc',
    category: 'finance',
    icon: 'pi pi-wallet',
    nodes: [
      /* 0  */ { type: 'start', nameKey: 'Start', position_x: 80, position_y: 250 },
      /* 1  */ { type: 'task', nameKey: 'Submit Invoice', position_x: 280, position_y: 250, config: { task_title_template: 'Submit invoice for processing', priority: 'medium', formFields: [{ name: 'vendor', label: 'Vendor name', type: 'text', required: true }, { name: 'amount', label: 'Amount', type: 'number', required: true }, { name: 'description', label: 'Description', type: 'textarea', required: true }] } },
      /* 2  */ { type: 'task', nameKey: 'Manager Review', position_x: 530, position_y: 250, config: { task_title_template: 'Review and approve invoice', assignee_type: 'role', assignee_value: 'Manager', priority: 'high', formFields: [{ name: 'decision', label: 'Decision', type: 'select', required: true, options: ['Approved', 'Needs correction', 'Rejected'] }, { name: 'comment', label: 'Comment', type: 'textarea', required: false }] } },
      /* 3  */ { type: 'exclusive_gateway', nameKey: 'Manager Decision', position_x: 780, position_y: 250 },
      /* 4  */ { type: 'task', nameKey: 'Finance Check', position_x: 1010, position_y: 150, config: { task_title_template: 'Verify invoice details and budget', assignee_type: 'role', assignee_value: 'Finance', priority: 'high', formFields: [{ name: 'check', label: 'Finance check', type: 'select', required: true, options: ['OK', 'Correction needed'] }] } },
      /* 5  */ { type: 'exclusive_gateway', nameKey: 'Finance Decision', position_x: 1250, position_y: 150 },
      /* 6  */ { type: 'task', nameKey: 'Payment', position_x: 1480, position_y: 100, config: { task_title_template: 'Execute payment', assignee_type: 'role', assignee_value: 'Finance', priority: 'high' } },
      /* 7  */ { type: 'notification', nameKey: 'Paid Notice', position_x: 1480, position_y: 250, config: { message_template: 'Invoice has been paid', recipient_type: 'initiator' } },
      /* 8  */ { type: 'end', nameKey: 'End (Paid)', position_x: 1700, position_y: 150 },
      /* 9  */ { type: 'notification', nameKey: 'Rejected Notice', position_x: 1010, position_y: 430, config: { message_template: 'Invoice has been rejected', recipient_type: 'initiator' } },
      /* 10 */ { type: 'end', nameKey: 'End (Rejected)', position_x: 1250, position_y: 430 },
    ],
    transitions: [
      { sourceIndex: 0, targetIndex: 1 },
      { sourceIndex: 1, targetIndex: 2 },
      { sourceIndex: 2, targetIndex: 3 },
      { sourceIndex: 3, targetIndex: 4, nameKey: 'Approved', condition_expression: "decision == 'Approved'" },
      { sourceIndex: 3, targetIndex: 1, nameKey: 'Needs correction', condition_expression: "decision == 'Needs correction'" },
      { sourceIndex: 3, targetIndex: 9, nameKey: 'Rejected', condition_expression: "decision == 'Rejected'" },
      { sourceIndex: 4, targetIndex: 5 },
      { sourceIndex: 5, targetIndex: 6, nameKey: 'OK', condition_expression: "check == 'OK'" },
      { sourceIndex: 5, targetIndex: 1, nameKey: 'Correction', condition_expression: "check == 'Correction needed'" },
      { sourceIndex: 6, targetIndex: 7 },
      { sourceIndex: 7, targetIndex: 8 },
      { sourceIndex: 9, targetIndex: 10 },
    ],
  },

  // ── IT: Bug Fix ──────────────────────────────────────────
  // Start → Report → Triage → XOR
  //   → Fix → Code Review → XOR
  //     → Approved → Deploy → End
  //     → Changes needed → back to Fix
  //   → Won't fix → Notify → End (Closed)
  {
    id: 'bug-fix',
    nameKey: 'templates.bugFixName',
    descriptionKey: 'templates.bugFixDesc',
    category: 'it',
    icon: 'pi pi-wrench',
    nodes: [
      /* 0  */ { type: 'start', nameKey: 'Start', position_x: 80, position_y: 250 },
      /* 1  */ { type: 'task', nameKey: 'Report Bug', position_x: 260, position_y: 250, config: { task_title_template: 'Report and describe the bug', priority: 'medium', formFields: [{ name: 'title', label: 'Bug title', type: 'text', required: true }, { name: 'steps', label: 'Steps to reproduce', type: 'textarea', required: true }, { name: 'severity', label: 'Severity', type: 'select', required: true, options: ['Low', 'Medium', 'High', 'Critical'] }] } },
      /* 2  */ { type: 'task', nameKey: 'Triage', position_x: 490, position_y: 250, config: { task_title_template: 'Triage bug and decide action', assignee_type: 'role', assignee_value: 'Tech Lead', priority: 'high', formFields: [{ name: 'action', label: 'Action', type: 'select', required: true, options: ['Fix', 'Won\'t fix'] }, { name: 'assignee_note', label: 'Notes for developer', type: 'textarea', required: false }] } },
      /* 3  */ { type: 'exclusive_gateway', nameKey: 'Triage Decision', position_x: 720, position_y: 250 },
      /* 4  */ { type: 'task', nameKey: 'Fix', position_x: 940, position_y: 200, config: { task_title_template: 'Implement bug fix', priority: 'high', formFields: [{ name: 'fix_description', label: 'Fix description', type: 'textarea', required: true }] } },
      /* 5  */ { type: 'task', nameKey: 'Code Review', position_x: 1170, position_y: 200, config: { task_title_template: 'Review bug fix code', assignee_type: 'role', assignee_value: 'Tech Lead', priority: 'high', formFields: [{ name: 'review_result', label: 'Review result', type: 'select', required: true, options: ['Approved', 'Changes needed'] }, { name: 'review_comment', label: 'Comment', type: 'textarea', required: false }] } },
      /* 6  */ { type: 'exclusive_gateway', nameKey: 'Review Decision', position_x: 1400, position_y: 200 },
      /* 7  */ { type: 'task', nameKey: 'Deploy', position_x: 1600, position_y: 150, config: { task_title_template: 'Deploy fix to production', priority: 'high' } },
      /* 8  */ { type: 'end', nameKey: 'Fixed', position_x: 1800, position_y: 150 },
      /* 9  */ { type: 'notification', nameKey: 'Won\'t Fix Notice', position_x: 940, position_y: 420, config: { message_template: 'Bug has been marked as won\'t fix', recipient_type: 'initiator' } },
      /* 10 */ { type: 'end', nameKey: 'Closed', position_x: 1170, position_y: 420 },
    ],
    transitions: [
      { sourceIndex: 0, targetIndex: 1 },
      { sourceIndex: 1, targetIndex: 2 },
      { sourceIndex: 2, targetIndex: 3 },
      { sourceIndex: 3, targetIndex: 4, nameKey: 'Fix', condition_expression: "action == 'Fix'" },
      { sourceIndex: 3, targetIndex: 9, nameKey: 'Won\'t fix', condition_expression: "action == 'Won\\'t fix'" },
      { sourceIndex: 4, targetIndex: 5 },
      { sourceIndex: 5, targetIndex: 6 },
      { sourceIndex: 6, targetIndex: 7, nameKey: 'Approved', condition_expression: "review_result == 'Approved'" },
      { sourceIndex: 6, targetIndex: 4, nameKey: 'Changes needed', condition_expression: "review_result == 'Changes needed'" },
      { sourceIndex: 7, targetIndex: 8 },
      { sourceIndex: 9, targetIndex: 10 },
    ],
  },

  // ── General: Request Processing (user-driven, no gateways) ─
  // Start → Нова → В роботу → Виконання → На перевірці → Ініціатору → End
  // Будь-який етап може відправити на Уточнення, яке повертається до Нова
  {
    id: 'request-processing',
    nameKey: 'templates.requestProcessingName',
    descriptionKey: 'templates.requestProcessingDesc',
    category: 'general',
    icon: 'pi pi-list-check',
    nodes: [
      /* 0 */ { type: 'start', nameKey: 'Start', position_x: 80, position_y: 300 },
      /* 1 */ { type: 'task', nameKey: 'Нова', position_x: 300, position_y: 300, config: { task_title_template: 'Нова заявка', priority: 'medium' } },
      /* 2 */ { type: 'task', nameKey: 'На уточненні', position_x: 300, position_y: 550, config: { task_title_template: 'Уточнення заявки', priority: 'medium' } },
      /* 3 */ { type: 'task', nameKey: 'В роботу', position_x: 550, position_y: 300, config: { task_title_template: 'Планування роботи', priority: 'medium' } },
      /* 4 */ { type: 'task', nameKey: 'Виконання', position_x: 800, position_y: 300, config: { task_title_template: 'Виконання заявки', priority: 'high' } },
      /* 5 */ { type: 'task', nameKey: 'На перевірці', position_x: 1050, position_y: 300, config: { task_title_template: 'Перевірка виконаної роботи', priority: 'high' } },
      /* 6 */ { type: 'task', nameKey: 'Перевірка ініціатором', position_x: 1300, position_y: 300, config: { task_title_template: 'Фінальна перевірка ініціатором', priority: 'medium' } },
      /* 7 */ { type: 'end', nameKey: 'Закрито', position_x: 1550, position_y: 300 },
    ],
    transitions: [
      // Start → Нова
      { sourceIndex: 0, targetIndex: 1 },
      // Нова → На уточнення
      { sourceIndex: 1, targetIndex: 2, nameKey: 'На уточнення', action_key: 'clarify', form_fields: [{ name: 'details', label: 'Деталі уточнення', type: 'textarea', required: true }] },
      // Нова → В роботу
      { sourceIndex: 1, targetIndex: 3, nameKey: 'В роботу', action_key: 'to_work', form_fields: [{ name: 'deadline', label: 'Дедлайн', type: 'date', required: true }, { name: 'category', label: 'Категорія', type: 'select', required: true, options: ['Розробка', 'Дизайн'] }, { name: 'notes', label: 'Примітки', type: 'textarea', required: false }] },
      // На уточненні → Нова
      { sourceIndex: 2, targetIndex: 1, nameKey: 'Відповісти', action_key: 'respond', form_fields: [{ name: 'response', label: 'Відповідь', type: 'textarea', required: true }] },
      // В роботу → На уточнення
      { sourceIndex: 3, targetIndex: 2, nameKey: 'На уточнення', action_key: 'clarify', form_fields: [{ name: 'details', label: 'Деталі уточнення', type: 'textarea', required: true }] },
      // В роботу → Виконання (призначити)
      { sourceIndex: 3, targetIndex: 4, nameKey: 'Призначити виконавця', action_key: 'assign', form_fields: [{ name: 'executor', label: 'Виконавець', type: 'text', required: true }, { name: 'comment', label: 'Коментар', type: 'textarea', required: false }] },
      // В роботу → Виконання (самому)
      { sourceIndex: 3, targetIndex: 4, nameKey: 'Зробити самому', action_key: 'self_assign', form_fields: [{ name: 'comment', label: 'Коментар', type: 'textarea', required: false }] },
      // Виконання → На уточнення
      { sourceIndex: 4, targetIndex: 2, nameKey: 'На уточнення', action_key: 'clarify', form_fields: [{ name: 'details', label: 'Деталі уточнення', type: 'textarea', required: true }] },
      // Виконання → На перевірку
      { sourceIndex: 4, targetIndex: 5, nameKey: 'На перевірку', action_key: 'to_review', form_fields: [{ name: 'result', label: 'Результат роботи', type: 'textarea', required: false }, { name: 'notes', label: 'Примітки', type: 'textarea', required: false }] },
      // На перевірці → На доопрацювання
      { sourceIndex: 5, targetIndex: 4, nameKey: 'На доопрацювання', action_key: 'rework', form_fields: [{ name: 'comment', label: 'Коментар', type: 'textarea', required: true }] },
      // На перевірці → Ініціатору
      { sourceIndex: 5, targetIndex: 6, nameKey: 'Ініціатору на перевірку', action_key: 'to_initiator' },
      // Перевірка ініціатором → На доопрацювання
      { sourceIndex: 6, targetIndex: 4, nameKey: 'На доопрацювання', action_key: 'rework', form_fields: [{ name: 'comment', label: 'Коментар', type: 'textarea', required: true }] },
      // Перевірка ініціатором → Закрити
      { sourceIndex: 6, targetIndex: 7, nameKey: 'Закрити задачу', action_key: 'close', form_fields: [{ name: 'resolution', label: 'Підсумок', type: 'textarea', required: false }] },
    ],
  },
]
