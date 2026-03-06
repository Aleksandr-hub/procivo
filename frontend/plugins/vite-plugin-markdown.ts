import type { Plugin } from 'vite'
import MarkdownIt from 'markdown-it'
import anchor from 'markdown-it-anchor'

/**
 * Custom Vite plugin to transform .md files into JS modules.
 * Exports: { attributes (frontmatter), html (rendered), body (raw markdown) }
 */
export function markdown(): Plugin {
  const md = new MarkdownIt({ html: true, linkify: true, typographer: true })
  md.use(anchor, { permalink: false })

  return {
    name: 'vite-plugin-markdown',
    enforce: 'pre',

    transform(code: string, id: string) {
      if (!id.endsWith('.md')) return null

      const { attributes, body } = parseFrontmatter(code)
      const html = md.render(body)

      return {
        code: `export default ${JSON.stringify({ attributes, body, html })};`,
        map: null,
      }
    },
  }
}

function parseFrontmatter(raw: string): { attributes: Record<string, unknown>; body: string } {
  const match = raw.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n([\s\S]*)$/)
  if (!match) return { attributes: {}, body: raw }

  const frontmatter = match[1]
  const body = match[2]
  const attributes: Record<string, unknown> = {}

  let currentKey = ''
  let inArray = false
  let arrayValues: string[] = []

  for (const line of frontmatter.split('\n')) {
    const trimmed = line.trim()
    if (!trimmed || trimmed.startsWith('#')) continue

    if (inArray) {
      if (trimmed.startsWith('- ')) {
        arrayValues.push(parseValue(trimmed.slice(2).trim()))
        continue
      } else {
        attributes[currentKey] = arrayValues
        inArray = false
        arrayValues = []
      }
    }

    const kvMatch = trimmed.match(/^(\w[\w-]*)\s*:\s*(.*)$/)
    if (kvMatch) {
      const key = kvMatch[1]
      const value = kvMatch[2].trim()

      if (value === '') {
        // Start of array or empty value
        currentKey = key
        inArray = true
        arrayValues = []
      } else if (value.startsWith('[') && value.endsWith(']')) {
        // Inline array: [item1, item2]
        const inner = value.slice(1, -1)
        attributes[key] = inner
          .split(',')
          .map((s) => parseValue(s.trim()))
          .filter((s) => s !== '')
      } else {
        attributes[key] = parseValue(value)
      }
    }
  }

  if (inArray) {
    attributes[currentKey] = arrayValues
  }

  return { attributes, body }
}

function parseValue(raw: string): string {
  // Strip surrounding quotes
  if ((raw.startsWith('"') && raw.endsWith('"')) || (raw.startsWith("'") && raw.endsWith("'"))) {
    return raw.slice(1, -1)
  }
  return raw
}
