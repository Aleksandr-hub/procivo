declare module '*.md' {
  const content: {
    attributes: Record<string, unknown>
    body: string
    html: string
  }
  export default content
}
