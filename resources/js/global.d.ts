declare global {
  function route(
    name?: string,
    params?: Record<string, any>,
    absolute?: boolean,
    config?: any
  ): string
}
export {}