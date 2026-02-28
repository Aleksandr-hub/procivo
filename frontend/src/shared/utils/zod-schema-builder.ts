import { z } from 'zod'
import type { FormFieldDefinition } from '@/modules/workflow/types/process-definition.types'

/**
 * Build a Zod object schema from an array of FormFieldDefinition.
 * Maps each field type to an appropriate Zod validator,
 * applying `.min(1)` for required string-based fields.
 */
export function buildZodSchema(fields: FormFieldDefinition[]): z.ZodObject<Record<string, z.ZodType>> {
  const shape: Record<string, z.ZodType> = {}

  for (const field of fields) {
    shape[field.name] = fieldToZod(field)
  }

  return z.object(shape)
}

function fieldToZod(field: FormFieldDefinition): z.ZodType {
  switch (field.type) {
    case 'text':
    case 'textarea':
      return field.required ? z.string().min(1) : z.optional(z.string())

    case 'number':
      return field.required ? z.number() : z.optional(z.number())

    case 'date':
      // Dates arrive as ISO strings from DatePicker
      return field.required ? z.string().min(1) : z.optional(z.string())

    case 'select':
    case 'employee':
      return field.required ? z.string().min(1) : z.optional(z.string())

    case 'checkbox':
      // Checkbox always has a boolean value
      return z.boolean()

    default:
      return field.required ? z.string().min(1) : z.optional(z.string())
  }
}

/**
 * Flatten a ZodError into a simple `Record<string, string>`,
 * returning the first error message per field.
 * Uses Zod 4 static `z.flattenError()`.
 */
export function flattenZodErrors(error: z.ZodError): Record<string, string> {
  const flat = z.flattenError(error)
  const result: Record<string, string> = {}

  for (const [key, messages] of Object.entries(flat.fieldErrors)) {
    const msgs = messages as string[] | undefined
    if (msgs && msgs.length > 0) {
      result[key] = msgs[0]!
    }
  }

  return result
}
